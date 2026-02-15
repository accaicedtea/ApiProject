<?php
namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Exception;

class HomeController extends Controller {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    public function index() {

        $tables = [];
        $selectedTableDetails = null;
        
        if(!$this->db->isConnected()) {
            throw new Exception("Database non connesso");
        }      

        $tables = $this->db->getDatabaseTables();
        
        // Struttura database in JSON
        generateDatabaseStructure($this->db, $tables);
        
        // Stato API per tabella
        $apiStatus = [];
        
        foreach ($tables as $table) {
            $apiStatus[$table] = $this->hasApi($table);
        }
        // Tabella selezionata o prima disponibile
        $selectedTable = $_GET['table'] ?? ($tables[0] ?? null);
        
        if ($selectedTable && in_array($selectedTable, $tables)) {
            try {
                $columns = $this->db->describeTable($selectedTable);
                $rowCount = $this->db->getTableRowCount($selectedTable);
                
                $selectedTableDetails = [
                    'name' => $selectedTable,
                    'columns' => $columns,
                    'rowCount' => $rowCount
                ];
            } catch (\PDOException $e) {
                $selectedTableDetails = [
                    'error' => $e->getMessage()
                ];
            }
        }

        $title = $this->db->isConnected() ? 'Home - Connesso' : 'Home - Disconnesso';
        
        // Dati per la vista
        $data = [
            'title' => $title,
            'databaseType' => $this->db->getConnectionType(),
            'connectionStatus' => $this->db->isConnected(),
            'databaseName' => $this->db->getDatabaseName(),
            'tables' => $tables,
            'tableDetails' => $selectedTableDetails,
            'apiStatus' => $apiStatus ?? [],
            'totalTables' => isset($tables) ? count($tables) : 0,
        ];
        
        $this->view('home/index', $data);
    }
    
    public function about() {
        $readmePath = __DIR__ . '/../../README.md';
        $readmeContent = file_exists($readmePath) ? file_get_contents($readmePath) : 'README.md non trovato.';
        
        $isConnected = $this->db !== null && $this->db->isConnected();
        
        $data = [
            'title' => 'About - SerioApi',
            'readmeContent' => $readmeContent,
            'dbConnected' => $isConnected,
            'connectionStatus' => $isConnected,
            'databaseName' => $isConnected ? $this->db->getDatabaseName() : null,
        ];
        
        $this->view('home/about', $data);
    }

    public function settings() {
        $dbConfig = getDatabaseConfig();
        
        $currentConfig = [
            'db_host' => $dbConfig['host'] ?? 'localhost',
            'db_name' => $dbConfig['dbname'] ?? '',
            'db_user' => $dbConfig['user'] ?? '',
            'db_pass' => $dbConfig['pass'] ?? '',
        ];
        
        $isConnected = $this->db !== null && $this->db->isConnected();
        
        $data = [
            'title' => 'Settings - Configurazione Database',
            'currentConfig' => $currentConfig,
            'isConnected' => $isConnected,
            'dbConnected' => $isConnected,
            'databaseName' => $isConnected ? $this->db->getDatabaseName() : null,
            'success' => $_GET['success'] ?? null,
            'error' => $_GET['error'] ?? null,
        ];
        
        $this->view('home/settings', $data);
    }
    












    /**
     * Test connessione database (AJAX)
     */
    public function test() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $host = trim($input['db_host'] ?? 'localhost');
        $name = trim($input['db_name'] ?? '');
        $user = trim($input['db_user'] ?? '');
        $pass = $input['db_pass'] ?? '';
        
        if (empty($name) || empty($user)) {
            echo json_encode(['success' => false, 'message' => 'Nome database e utente sono obbligatori']);
            exit;
        }
        
        // Forza IPv4 per compatibilità
        $connectHost = ($host === 'localhost') ? '127.0.0.1' : $host;
        
        try {
            $dsn = "mysql:host={$connectHost};dbname={$name};charset=utf8mb4";
            $pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            
            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            $tableCount = count($tables);
            
            echo json_encode([
                'success' => true, 
                'message' => "Connessione riuscita! Database '{$name}' contiene {$tableCount} tabelle.",
                'tables' => $tables,
                'tableCount' => $tableCount
            ]);
        } catch (\PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Messaggi di errore leggibili
            if (strpos($errorMsg, 'Access denied') !== false) {
                $errorMsg = 'Accesso negato: username o password errati';
            } elseif (strpos($errorMsg, 'Unknown database') !== false) {
                $errorMsg = "Database '{$name}' non trovato";
            } elseif (strpos($errorMsg, 'Connection refused') !== false) {
                $errorMsg = "Connessione rifiutata: il server MySQL non è raggiungibile su {$host}";
            } elseif (strpos($errorMsg, 'No such file or directory') !== false) {
                $errorMsg = "Server MySQL non trovato su {$host}. Prova con 127.0.0.1 invece di localhost";
            }
            
            echo json_encode(['success' => false, 'message' => $errorMsg]);
        }
        
        exit;
    }
    
    /**
     * Salva configurazione database
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/settings'));
            exit;
        }
        
        if (!\Core\Security::checkCsrf($_POST['csrf_token'] ?? '')) {
            die('Token CSRF non valido');
        }
        
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        
        if (empty($dbName) || empty($dbUser)) {
            header('Location: ' . url('/settings?error=' . urlencode('Nome database e utente sono obbligatori')));
            exit;
        }
        
        // Template config/database.php
        $phpContent = <<<'PHPSTART'
<?php

// Carica le funzioni (per loadEnv)
require_once __DIR__ . '/../core/helpers.php';

// Carica le variabili d'ambiente
loadEnv();

// Funzione per ottenere la configurazione del database
function getDatabaseConfig($environment = null) {
    
    // Se non viene passato un environment, usa quello dall'ENV
    if ($environment === null) {
        $environment = env('ENVIRONMENT', 'development');
    }
    
    $databases = [
        'development' => [
            'dbType' => 'MySQL',
PHPSTART;

        $phpContent .= "\n            'host' => " . var_export($dbHost, true) . ",\n";
        $phpContent .= "            'dbname' => " . var_export($dbName, true) . ",\n";
        $phpContent .= "            'user' => " . var_export($dbUser, true) . ",\n";
        $phpContent .= "            'pass' => " . var_export($dbPass, true) . ",\n";

        $phpContent .= <<<'PHPEND'
            'charset' => 'utf8mb4',
        ],
        'production' => [
            'dbType' => 'MySQL',
PHPEND;

        $phpContent .= "\n            'host' => " . var_export($dbHost, true) . ",\n";
        $phpContent .= "            'dbname' => " . var_export($dbName, true) . ",\n";
        $phpContent .= "            'user' => " . var_export($dbUser, true) . ",\n";
        $phpContent .= "            'pass' => " . var_export($dbPass, true) . ",\n";

        $phpContent .= <<<'PHPEND2'
            'charset' => 'utf8mb4',
        ],
    ];
    
    return $databases[$environment] ?? $databases['development'];
}

// Compatibilità retroattiva: ritorna development di default
return getDatabaseConfig();
PHPEND2;

        $configPath = __DIR__ . '/../../config/database.php';
        
        if (file_put_contents($configPath, $phpContent) === false) {
            header('Location: ' . url('/settings?error=' . urlencode('Impossibile scrivere config/database.php. Verifica i permessi.')));
            exit;
        }
        
        header('Location: ' . url('/settings?success=' . urlencode('Configurazione database salvata! Ricarica la pagina per applicare.')));
        exit;
    }

    // Verifica se una tabella ha l'API abilitata
    private function hasApi(string $tableName): bool {
        $config = loadApiConfig();
        $databaseName = $this->db->getDatabaseName();
        
        if (!$config || !isset($config[$databaseName][$tableName])) {
            return false;
        }
        
        return isset($config[$databaseName][$tableName]['enabled']) 
            && $config[$databaseName][$tableName]['enabled'] === true;
    }
}