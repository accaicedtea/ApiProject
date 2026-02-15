<?php
namespace App\Controllers;

use Core\Controller;

class ApiBuilderController extends Controller
{
    private $configFile = __DIR__ . '/../../config/api_config.json';
    private $outputPath = __DIR__ . '/../../generated-api';
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }
    public function index()
    {
        $config = loadApiConfig();
        $currentDbConfig = $config[$this->db->getDatabaseName()] ?? [];
        $enabledTables = $this->getEnabledTables($currentDbConfig);

        // Conta le viste personalizzate dalla sezione _views
        $viewsCount = 0;
        if (isset($currentDbConfig['_views'])) {
            $viewsCount = count($currentDbConfig['_views']);
        }

        $data = [
            'title' => 'API Builder - Generatore',
            'databaseType' => $this->db->getConnectionType(),
            'connectionStatus' => $this->db->isConnected(),
            'databaseName' => $this->db->getDatabaseName(),
            'config' => $currentDbConfig,
            'enabledCount' => count($enabledTables),
            'viewsCount' => $viewsCount,
            'outputPath' => $this->outputPath,
        ];

        $this->view('/generator/builder', $data);
    }

    public function generate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/generator/builder'));
            exit;
        }

        if (!\Core\Security::checkCsrf($_POST['csrf_token'] ?? '')) {
            die('Token CSRF non valido');
        }

        try {
            $config = loadApiConfig();
            $currentDbConfig = $config[$this->db->getDatabaseName()] ?? [];

            if (empty($currentDbConfig)) {
                throw new \Exception("Configurazione non trovata per il database: " . $this->db->getDatabaseName());
            }

            // Crea struttura cartelle
            $this->createDirectoryStructure();

            // Genera file di configurazione
            $this->generateConfigFiles();

            // Genera middleware
            $this->generateMiddleware();

            // Genera modelli ed endpoint per ogni tabella abilitata
            $this->generateTablesApi($currentDbConfig);

            // Genera auth endpoint e modello User
            $this->generateAuthApi();

            // Genera .htaccess
            $this->generateHtaccess();

            // Genera documentazione
            $this->generateReadme($config);

            header('Location: ' . url('/generator/builder?success=1'));
            exit;
        } catch (\Exception $e) {
            header('Location: ' . url('/generator/builder?error=' . urlencode($e->getMessage())));
            exit;
        }
    }

    // ===== STRUTTURA CARTELLE =====

    private function createDirectoryStructure()
    {
        $dirs = [
            $this->outputPath,
            $this->outputPath . '/config',
            $this->outputPath . '/middleware',
            $this->outputPath . '/models',
            $this->outputPath . '/endpoints',
            $this->outputPath . '/auth',
            $this->outputPath . '/logs',
        ];
        // Esempio: per cambiare la struttura
        /* 
            $dirs = [
            $this->outputPath,
            $this->outputPath . '/config',
            $this->outputPath . '/middleware',
            $this->outputPath . '/models',
            $this->outputPath . '/endpoints',
            $this->outputPath . '/auth',
            $this->outputPath - '/storage', // aggiunta 
            $this->outputPath - '/storage/cache', // aggiunta

        ];
        */

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    // ===== CONFIGURAZIONE =====

    private function generateConfigFiles()
    {
        // database.php - usa i dati dall'ENV
        $this->generateDatabaseConfig();

        // jwt.php - usa JWT_SECRET dall'ENV
        $this->generateJwtConfig();

        // helpers.php - funzioni globali unificate
        $this->generateHelpersFile();

        // cors.php - CORS headers standalone
        $this->generateCorsFile();

        // api_config.json
        copy($this->configFile, $this->outputPath . '/config/api_config.json');

        // htaccess per proteggere config
        file_put_contents($this->outputPath . '/config/.htaccess', "Deny from all\n");
    }


    private function generateDatabaseConfig()
    {
        require_once __DIR__ . '/../../config/database.php';
        $devConfig = getDatabaseConfig('development');
        $prodConfig = getDatabaseConfig('production');

        $content = <<<PHP
<?php
// Database configuration - Auto-detect environment
\$isAltervista = strpos(\$_SERVER['HTTP_HOST'] ?? '', 'altervista.org') !== false 
    || strpos(\$_SERVER['SERVER_NAME'] ?? '', 'altervista.org') !== false
    || isset(\$_SERVER['AlterVista']);

if (\$isAltervista) {
    // Production - Altervista
    define('DB_HOST', 'localhost');
    define('DB_NAME', '{$prodConfig['dbname']}');
    define('DB_USER', trim('{$prodConfig['user']}')); // Trim per rimuovere spazi
    define('DB_PASS', '{$prodConfig['pass']}');
} else {
    // Development - Local
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', '{$devConfig['dbname']}');
    define('DB_USER', '{$devConfig['user']}');
    define('DB_PASS', '{$devConfig['pass']}');
}

class Database {
    private \$host = DB_HOST;
    private \$db_name = DB_NAME;
    private \$username = DB_USER;
    private \$password = DB_PASS;
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        try {
            \$dsn = "mysql:host=" . \$this->host . ";dbname=" . \$this->db_name;
            \$this->conn = new PDO(\$dsn, \$this->username, \$this->password);
            
            \$this->conn->exec("set names utf8");
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException \$exception) {
            throw new \RuntimeException('Database connection failed: ' . \$exception->getMessage(), 500, \$exception);
        }
        return \$this->conn;
    }
}
?>
PHP;
        file_put_contents($this->outputPath . '/config/database.php', $content);
    }
    // ===== JWT CONFIG =====
    private function generateJwtConfig()
    {
        // Template JWT di riferimento (app/Builder/JWT.php)
        $templatePath = dirname(__DIR__) . '/Builder/JWT.php';
        $outDir = $this->outputPath . '/config';
        $outFile = $outDir . '/jwt.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException("Template JWT non trovato: {$templatePath}");
        }
        if (!is_dir($outDir)) {
            mkdir($outDir, 0775, true);
        }

        // Valori da .env con default
        $vars = [
            '__JWT_SECRET__' => env('JWT_SECRET'),
            '__JWT_ALGO__' => env('JWT_ALGO', 'HS256'),
            '__JWT_TTL__' => (string) env('JWT_EXPIRES_IN'), // in secondi (24h)
        ];

        // Carica template
        $tpl = file_get_contents($templatePath);

        // Se il template non ha i placeholder standard, sostituisci i pattern comuni
        if (strpos($tpl, '__JWT_SECRET__') === false || strpos($tpl, '__JWT_ALGO__') === false || strpos($tpl, '__JWT_TTL__') === false) {
            $tpl = preg_replace(
                [
                    '/(private\s+\$secret_key\s*=\s*[\'"]).*?([\'"];)/',
                    '/(private\s+\$algorithm\s*=\s*[\'"]).*?([\'"];)/',
                    '/(private\s+\$ttl\s*=\s*)\d+(\s*;)/',
                ],
                [
                    '$1__JWT_SECRET__$2',
                    '$1__JWT_ALGO__$2',
                    '$1__JWT_TTL__$2',
                ],
                $tpl
            );
        }

        // Applica le sostituzioni
        $rendered = strtr($tpl, $vars);
        
        // Aggiungi i campi personalizzati dal config
        $config = loadApiConfig();
        $currentDbConfig = $config[$this->db->getDatabaseName()] ?? [];
        $customFieldsCode = '';
        
        if (isset($currentDbConfig['_jwt_config']['custom_fields'])) {
            $customFields = $currentDbConfig['_jwt_config']['custom_fields'];
            foreach ($customFields as $field) {
                $fieldName = $field['name'];
                $fieldType = $field['type'];
                
                // Genera il codice per ogni campo in base al tipo
                switch ($fieldType) {
                    case 'int':
                        $customFieldsCode .= "            '{$fieldName}' => (int)(\$user['{$fieldName}'] ?? 0),\n";
                        break;
                    case 'bool':
                        $customFieldsCode .= "            '{$fieldName}' => (bool)(\$user['{$fieldName}'] ?? false),\n";
                        break;
                    case 'float':
                        $customFieldsCode .= "            '{$fieldName}' => (float)(\$user['{$fieldName}'] ?? 0.0),\n";
                        break;
                    default: // string
                        $customFieldsCode .= "            '{$fieldName}' => \$user['{$fieldName}'] ?? '',\n";
                        break;
                }
            }
        }
        
        // Sostituisci il placeholder dei campi personalizzati
        $rendered = str_replace(
            '            // __CUSTOM_FIELDS__ - Campi personalizzati verranno inseriti qui dal builder',
            rtrim($customFieldsCode),
            $rendered
        );

        file_put_contents($outFile, $rendered);
    }

    // ===== HELPERS CONFIG =====
    private function generateHelpersFile()
    {
        // Copia il file helpers.php dal Builder
        $templatePath = dirname(__DIR__) . '/Builder/helpers.php';
        copy($templatePath, $this->outputPath . '/config/helpers.php');
    }

    private function generateCorsFile()
    {
        $templatePath = dirname(__DIR__) . '/Builder/cors.php';
        copy($templatePath, $this->outputPath . '/cors.php');
    }

    // ===== MIDDLEWARE =====

    private function generateMiddleware()
    {
        // 1. auth.php
        $this->generateAuthMiddleware();

        // 2. security.php (basico)
        $this->generateSecurityMiddleware();

        // 3. security_helper.php
        $this->generateSecurityHelper();

        // 4. error_handler.php
        $this->generateErrorHandler();
    }

    private function generateAuthMiddleware()
    {
        $templatePath = dirname(__DIR__) . '/Builder/auth.php';
        copy($templatePath, $this->outputPath . '/middleware/auth.php');
    }

    private function generateSecurityMiddleware()
    {
        $templatePath = dirname(__DIR__) . '/Builder/security.php';
        copy($templatePath, $this->outputPath . '/middleware/security.php');
    }

    private function generateSecurityHelper()
    {
        $templatePath = dirname(__DIR__) . '/Builder/security_helper.php';
        copy($templatePath, $this->outputPath . '/middleware/security_helper.php');
    }

    private function generateErrorHandler()
    {
        $templatePath = dirname(__DIR__) . '/Builder/error_handler.php';
        copy($templatePath, $this->outputPath . '/middleware/error_handler.php');

        // Crea directory logs con .htaccess di protezione
        $logsDir = $this->outputPath . '/logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        file_put_contents($logsDir . '/.htaccess', "Deny from all\n");
        file_put_contents($logsDir . '/.gitkeep', '');
    }

    // ===== GENERAZIONE TABELLE =====
    private function generateTablesApi($currentDbConfig)
    {
        // Tabelle di sistema da escludere dalla generazione
        $systemTables = ['security_logs', 'rate_limits', 'banned_ips', 'failed_attempts'];
        
        foreach ($currentDbConfig as $tableName => $tableConfig) {
            // Salta la sezione _views e le tabelle di sistema
            if ($tableName === '_views' || in_array($tableName, $systemTables)) {
                continue;
            }

            if (isset($tableConfig['enabled']) && $tableConfig['enabled'] === true) {
                // Verifica se è una view virtuale
                $isVirtualView = (substr($tableName, 0, 6) === '_view_');
                
                if ($isVirtualView) {
                    // Per le views virtuali, estrai il nome reale della view e ottieni le colonne dalla query
                    $realViewName = substr($tableName, 6); // Rimuovi il prefisso _view_
                    
                    // Verifica che la view esista nella sezione _views
                    if (!isset($currentDbConfig['_views'][$realViewName])) {
                        error_log("View definition not found for: {$realViewName}");
                        continue;
                    }
                    
                    $viewDef = $currentDbConfig['_views'][$realViewName];
                    $query = $viewDef['query'] ?? '';
                    
                    if (empty($query)) {
                        error_log("Empty query for view: {$realViewName}");
                        continue;
                    }
                    
                    // Ottieni le colonne eseguendo la query con LIMIT 0
                    $columns = $this->getViewColumns($query);
                    if (empty($columns)) {
                        error_log("No columns found for view: {$realViewName}");
                        continue;
                    }
                    
                    $viewRequireAuth = $viewDef['require_auth'] ?? false;
                    $this->generateModel($tableName, $columns, true, $query);
                    $this->generateEndpoint($tableName, $tableConfig, $columns, true, $viewRequireAuth);
                } else {
                    // Per le tabelle normali, usa DESCRIBE
                    $columns = $this->getTableColumns($tableName);
                    $isView = (substr($tableName, 0, 2) === 'v_');
                    $this->generateModel($tableName, $columns, $isView);
                    $this->generateEndpoint($tableName, $tableConfig, $columns, $isView);
                }
            }
        }
    }

    private function generateModel($tableName, $columns, $isView = false, $viewQuery = null)
    {
        $className = $this->toCamelCase($tableName);
        $primaryKey = $this->getPrimaryKey($columns);

        // Per le viste, genera solo metodi di lettura
        if ($isView && $viewQuery) {
            // Viste personalizzate con query SQL
            // Prepara la query per l'inclusione nel codice (escape)
            // Rimuovi semicolon finale poiché verrà usata in sottoquery
            $trimmedQuery = trim($viewQuery);
            $trimmedQuery = rtrim($trimmedQuery, ';');
            $escapedQuery = str_replace("'", "\\'", $trimmedQuery);
            
            $content = <<<PHP
<?php
require_once __DIR__ . '/../config/database.php';

class {$className} {
    private \$conn;
    private \$query = '{$escapedQuery}';

    public function __construct(\$db) {
        \$this->conn = \$db;
    }

    public function getAll() {
        \$stmt = \$this->conn->prepare(\$this->query);
        \$stmt->execute();
        return \$stmt;
    }

    public function getById(\$id) {
        // Per le viste, getById filtra i risultati della query
        \$query = "SELECT * FROM (" . \$this->query . ") AS view_result WHERE {$primaryKey} = ? LIMIT 1";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->bindParam(1, \$id);
        \$stmt->execute();
        return \$stmt;
    }

    // Filtra per azienda_id dell'utente autenticato
    public function getAllByAzienda(\$azienda_id) {
        \$query = "SELECT * FROM (" . \$this->query . ") AS view_result WHERE azienda_id = ? ORDER BY {$primaryKey}";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->bindParam(1, \$azienda_id);
        \$stmt->execute();
        return \$stmt;
    }

    public function getByIdByAzienda(\$id, \$azienda_id) {
        \$query = "SELECT * FROM (" . \$this->query . ") AS view_result WHERE {$primaryKey} = ? AND azienda_id = ? LIMIT 1";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->bindParam(1, \$id);
        \$stmt->bindParam(2, \$azienda_id);
        \$stmt->execute();
        return \$stmt;
    }
}

PHP;
        } elseif ($isView) {
            $content = <<<PHP
<?php
require_once __DIR__ . '/../config/database.php';

class {$className} {
    private \$conn;
    private \$table_name = "{$tableName}";

    public function __construct(\$db) {
        \$this->conn = \$db;
    }

    public function getAll() {
        \$query = "SELECT * FROM " . \$this->table_name . " ORDER BY {$primaryKey}";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->execute();
        return \$stmt;
    }

    public function getById(\$id) {
        \$query = "SELECT * FROM " . \$this->table_name . " WHERE {$primaryKey} = ? LIMIT 0,1";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->bindParam(1, \$id);
        \$stmt->execute();
        return \$stmt;
    }
}

PHP;
        } else {
            // Per le tabelle normali, genera anche metodi di scrittura
            $fields = [];
            foreach ($columns as $column) {
                if ($column['Field'] !== $primaryKey && $column['Extra'] !== 'auto_increment') {
                    $fields[] = $column['Field'];
                }
            }

            $bindParams = '';
            $setParams = '';
            foreach ($fields as $field) {
                $bindParams .= "        \$stmt->bindParam(\":{$field}\", \$data['{$field}']);\n";
                $setParams .= "{$field}=:{$field}, ";
            }
            $setParams = rtrim($setParams, ', ');

            $content = <<<PHP
<?php
require_once __DIR__ . '/../config/database.php';

class {$className} {
    private \$conn;
    private \$table_name = "{$tableName}";

    public function __construct(\$db) {
        \$this->conn = \$db;
    }

    public function getAll() {
        \$query = "SELECT * FROM " . \$this->table_name . " ORDER BY {$primaryKey}";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->execute();
        return \$stmt;
    }

    public function getById(\$id) {
        \$query = "SELECT * FROM " . \$this->table_name . " WHERE {$primaryKey} = ? LIMIT 0,1";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->bindParam(1, \$id);
        \$stmt->execute();
        return \$stmt;
    }

    // Filtra per azienda_id dell'utente autenticato
    public function getAllByAzienda(\$azienda_id) {
        \$query = "SELECT * FROM " . \$this->table_name . " WHERE azienda_id = ? ORDER BY {$primaryKey}";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->bindParam(1, \$azienda_id);
        \$stmt->execute();
        return \$stmt;
    }

    public function getByIdByAzienda(\$id, \$azienda_id) {
        \$query = "SELECT * FROM " . \$this->table_name . " WHERE {$primaryKey} = ? AND azienda_id = ? LIMIT 0,1";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->bindParam(1, \$id);
        \$stmt->bindParam(2, \$azienda_id);
        \$stmt->execute();
        return \$stmt;
    }

    public function create(\$data) {
        \$query = "INSERT INTO " . \$this->table_name . " 
                SET {$setParams}";
        
        \$stmt = \$this->conn->prepare(\$query);
        
{$bindParams}        
        if(\$stmt->execute()) {
            return \$this->conn->lastInsertId();
        }
        return false;
    }

    public function update(\$id, \$data) {
        \$query = "UPDATE " . \$this->table_name . " 
                SET {$setParams} 
                WHERE {$primaryKey} = :{$primaryKey}";
        
        \$stmt = \$this->conn->prepare(\$query);
        
        \$stmt->bindParam(":{$primaryKey}", \$id);
{$bindParams}        
        if(\$stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete(\$id) {
        \$query = "DELETE FROM " . \$this->table_name . " WHERE {$primaryKey} = ?";
        \$stmt = \$this->conn->prepare(\$query);
        \$stmt->bindParam(1, \$id);
        
        if(\$stmt->execute()) {
            return true;
        }
        return false;
    }
}

PHP;
        }
        
        file_put_contents($this->outputPath . "/models/{$className}.php", $content);
    }

    private function generateEndpoint($tableName, $config, $columns, $isView = false, $viewRequireAuth = false)
    {
        $className = $this->toCamelCase($tableName);
        $primaryKey = $this->getPrimaryKey($columns);

        // Controllo autenticazione
        $requiresAuth = $viewRequireAuth || ($config['select'] !== 'all' || $config['insert'] !== 'all' ||
            $config['update'] !== 'all' || $config['delete'] !== 'all');

        $authRequire = $requiresAuth ? "require_once __DIR__ . '/../middleware/auth.php';" : "";

        // Validazione campi obbligatori (solo per tabelle, non per views)
        $requiredFields = [];
        if (!$isView) {
            foreach ($columns as $column) {
                if (
                    $column['Null'] === 'NO' && $column['Extra'] !== 'auto_increment' &&
                    $column['Field'] !== $primaryKey && !isset($column['Default'])
                ) {
                    $requiredFields[] = $column['Field'];
                }
            }
        }

        $requiredCheck = '';
        if (!empty($requiredFields)) {
            $checks = [];
            foreach ($requiredFields as $field) {
                $checks[] = "empty(\$data['{$field}'])";
            }
            $requiredCheck = "if(" . implode(' || ', $checks) . ") {\n        sendResponse(400, null, 'Missing required fields');\n    }\n    ";
        }

        // Controlli di autenticazione
        $selectAuth = '';
        if ($isView && $viewRequireAuth) {
            $selectAuth = "\$user = requireAuth();";
        } else {
            $selectAuth = $config['select'] !== 'all' ? "\$user = requireAuth();" : "";
        }
        $insertAuth = $config['insert'] !== 'all' ? "\$user = requireAuth();" : "";
        $updateAuth = $config['update'] !== 'all' ? "\$user = requireAuth();" : "";
        $deleteAuth = $config['delete'] !== 'all' ? "\$user = requireAuth();" : "";

        // Rate limiting dalla configurazione
        $rateLimit = $config['rate_limit'] ?? 100;
        $rateLimitWindow = $config['rate_limit_window'] ?? 60;

        // Per le views, genera solo endpoint GET (sola lettura)
        if ($isView) {
            $content = <<<PHP
<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
{$authRequire}
require_once __DIR__ . '/../middleware/security_helper.php';
require_once __DIR__ . '/../models/{$className}.php';

applySecurity('{$tableName}', {$rateLimit}, {$rateLimitWindow});

\$database = new Database();
\$db = \$database->getConnection();
\$model = new {$className}(\$db);

\$method = \$_SERVER['REQUEST_METHOD'];
\$request_uri = \$_SERVER['REQUEST_URI'];
\$path = parse_url(\$request_uri, PHP_URL_PATH);
\$path_parts = array_values(array_filter(explode('/', \$path)));
\$last_part = end(\$path_parts) ?: '';
\$prev_part = \$path_parts[count(\$path_parts) - 2] ?? '';
\$view_table = '{$tableName}';
\$view_slug = substr(\$view_table, 6); // rimuove prefisso _view_

// Supporta sia /api/_view_nomevista che /api/views/nomevista
\$id = null;
if (\$prev_part === 'views') {
    // /api/views/{view}/{id?}
    if (\$last_part !== \$view_slug && \$last_part !== \$view_table) {
        \$id = \$last_part;
    }
} else {
    // /api/_view_{view}/{id?}
    if (\$last_part !== \$view_table && \$last_part !== \$view_slug && \$last_part !== 'views') {
        \$id = \$last_part;
    }
}

switch(\$method) {
    case 'GET':
        \$user = null;
        {$selectAuth}
        // Se autenticato, filtra per azienda_id dell'utente
        if (!empty(\$user)) {
            // Utente autenticato - filtra per azienda
            if (\$id && \$id !== '{$tableName}' && \$id !== \$view_slug) {
                \$stmt = \$model->getByIdByAzienda(\$id, \$user['azienda_id']);
                \$row = \$stmt->fetch(PDO::FETCH_ASSOC);
                
                if(\$row) {
                    sendResponse(200, \$row);
                } else {
                    sendResponse(404, null, 'Not found');
                }
            } else {
                \$stmt = \$model->getAllByAzienda(\$user['azienda_id']);
                \$items = array();
                
                while (\$row = \$stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push(\$items, \$row);
                }
                
                sendResponse(200, \$items);
            }
        } else {
            // Pubblico - nessun filtro
            if (\$id && \$id !== '{$tableName}' && \$id !== \$view_slug) {
                \$stmt = \$model->getById(\$id);
                \$row = \$stmt->fetch(PDO::FETCH_ASSOC);
                
                if(\$row) {
                    sendResponse(200, \$row);
                } else {
                    sendResponse(404, null, 'Not found');
                }
            } else {
                \$stmt = \$model->getAll();
                \$items = array();
                
                while (\$row = \$stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push(\$items, \$row);
                }
                
                sendResponse(200, \$items);
            }
        }
        break;
        
    default:
        sendResponse(405, null, 'Method not allowed. Views are read-only.');
        break;
}

PHP;
        } else {
            // Endpoint completo per tabelle normali
            $content = <<<PHP
<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
{$authRequire}
require_once __DIR__ . '/../middleware/security_helper.php';
require_once __DIR__ . '/../models/{$className}.php';

applySecurity('{$tableName}', {$rateLimit}, {$rateLimitWindow});

\$database = new Database();
\$db = \$database->getConnection();
\$model = new {$className}(\$db);

\$method = \$_SERVER['REQUEST_METHOD'];
\$request_uri = \$_SERVER['REQUEST_URI'];
\$path = parse_url(\$request_uri, PHP_URL_PATH);
\$path_parts = explode('/', \$path);
\$id = end(\$path_parts);

switch(\$method) {
    case 'GET':
        \$user = null;
        {$selectAuth}
        if (!empty(\$user)) {
            // Utente autenticato - filtra per azienda se la tabella ha azienda_id
            if (\$id && \$id !== '{$tableName}') {
                \$stmt = \$model->getByIdByAzienda(\$id, \$user['azienda_id']);
                \$row = \$stmt->fetch(PDO::FETCH_ASSOC);
                
                if(\$row) {
                    sendResponse(200, \$row);
                } else {
                    sendResponse(404, null, 'Not found');
                }
            } else {
                \$stmt = \$model->getAllByAzienda(\$user['azienda_id']);
                \$items = array();
                
                while (\$row = \$stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push(\$items, \$row);
                }
                
                sendResponse(200, \$items);
            }
        } else {
            // Pubblico o nessuna auth richiesta
            if (\$id && \$id !== '{$tableName}') {
                \$stmt = \$model->getById(\$id);
                \$row = \$stmt->fetch(PDO::FETCH_ASSOC);
                
                if(\$row) {
                    sendResponse(200, \$row);
                } else {
                    sendResponse(404, null, 'Not found');
                }
            } else {
                \$stmt = \$model->getAll();
                \$items = array();
                
                while (\$row = \$stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push(\$items, \$row);
                }
                
                sendResponse(200, \$items);
            }
        }
        break;
        
    case 'POST':
        {$insertAuth}
        \$data = json_decode(file_get_contents("php://input"), true);
        
        {$requiredCheck}\$item_id = \$model->create(\$data);
        if(\$item_id) {
            sendResponse(201, ['id' => \$item_id], 'Created successfully');
        } else {
            sendResponse(500, null, 'Failed to create');
        }
        break;
        
    case 'PUT':
        {$updateAuth}
        \$data = json_decode(file_get_contents("php://input"), true);
        
        {$requiredCheck}if(\$model->update(\$id, \$data)) {
            sendResponse(200, null, 'Updated successfully');
        } else {
            sendResponse(500, null, 'Failed to update');
        }
        break;
        
    case 'DELETE':
        {$deleteAuth}
        if(\$model->delete(\$id)) {
            sendResponse(200, null, 'Deleted successfully');
        } else {
            sendResponse(500, null, 'Failed to delete');
        }
        break;
        
    default:
        sendResponse(405, null, 'Method not allowed');
        break;
}

PHP;
        }
        
        file_put_contents($this->outputPath . "/endpoints/{$tableName}.php", $content);
    }

    // ===== AUTENTICAZIONE =====

    private function generateAuthApi()
    {
        // 1. Modello User
        $this->generateUserModel();

        // 2. Endpoint auth/login
        $this->generateAuthEndpoint();

        // 3. auth/me.php
        $this->generateAuthMe();
    }

    private function generateUserModel()
    {
        $templatePath = dirname(__DIR__) . '/Builder/User.php';
        copy($templatePath, $this->outputPath . '/models/User.php');
    }

    private function generateAuthEndpoint()
    {
        $templatePath = dirname(__DIR__) . '/Builder/ep_auth.php';
        copy($templatePath, $this->outputPath . '/endpoints/auth.php');
    }

    private function generateAuthMe()
    {
        $templatePath = dirname(__DIR__) . '/Builder/me.php';
        copy($templatePath, $this->outputPath . '/auth/me.php');
    }

    // ===== HTACCESS =====

    private function generateHtaccess()
    {
        // Leggi FTP_REMOTE_PATH dall'env per impostare il RewriteBase
        $remotePath = env('FTP_REMOTE_PATH', '/');
        $rewriteBase = $remotePath !== '/' ? "RewriteBase $remotePath/\n" : "";
        
        // 1. .htaccess per Apache
        $rootHtaccess = <<<HTACCESS
RewriteEngine On
$rewriteBase
# CORS Headers per Altervista
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
    Header always set Access-Control-Max-Age "3600"
</IfModule>

# Handle preflight OPTIONS requests
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ \$1 [R=200,L]

# Route /api/auth/login to endpoints/auth.php
RewriteRule ^api/auth/login/?\$ endpoints/auth.php [QSA,L]

# Route /api/auth/me to auth/me.php
RewriteRule ^api/auth/me/?\$ auth/me.php [QSA,L]

# Route /api/views/{view} to endpoints/_view_{view}.php (read-only views)
RewriteRule ^api/views/([^/]+)/?$ endpoints/_view_$1.php [QSA,L]

# Route /api/views/{view}/{id} to endpoints/_view_{view}.php
RewriteRule ^api/views/([^/]+)/([0-9]+)$ endpoints/_view_$1.php [QSA,L]

# Route /api/{table} to endpoints/{table}.php
RewriteRule ^api/([^/]+)/?\$ endpoints/\$1.php [QSA,L]

# Route /api/{table}/{id} to endpoints/{table}.php
RewriteRule ^api/([^/]+)/([0-9]+)\$ endpoints/\$1.php [QSA,L]

# Disable directory listing
Options -Indexes

HTACCESS;
        file_put_contents($this->outputPath . '/.htaccess', $rootHtaccess);

        // 2. index.php router per PHP built-in server
        $indexRouter = <<<'PHP'
<?php
// Router per server PHP built-in

// CORS Headers - SEMPRE per primo
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Richieste preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Rimuovi query string
$path = parse_url($request_uri, PHP_URL_PATH);

// Route /api/auth/login
if (preg_match('#^/api/auth/login/?$#', $path)) {
    require __DIR__ . '/endpoints/auth.php';
    exit;
}

// Route /api/auth/me
if (preg_match('#^/api/auth/me/?$#', $path)) {
    require __DIR__ . '/auth/me.php';
    exit;
}

// Route /api/views/{view}/{id?} to endpoints/_view_{view}.php (read-only)
if (preg_match('#^/api/views/([^/]+)(?:/([0-9]+))?$#', $path, $matches)) {
    $viewName = $matches[1];
    $endpoint_file = __DIR__ . '/endpoints/_view_' . $viewName . '.php';
    if (file_exists($endpoint_file)) {
        // Normalizza REQUEST_URI per gli endpoint generati (_view_{name})
        $normalized = '/api/_view_' . $viewName;
        if (!empty($matches[2])) {
            $normalized .= '/' . $matches[2];
        }
        $_SERVER['REQUEST_URI'] = $normalized;
        require $endpoint_file;
        exit;
    }
}

// Route /api/{table}/{id} to endpoints/{table}.php
if (preg_match('#^/api/([^/]+)/([0-9]+)$#', $path, $matches)) {
    require __DIR__ . '/endpoints/' . $matches[1] . '.php';
    exit;
}

// Route /api/{table} to endpoints/{table}.php
if (preg_match('#^/api/([^/]+)/?$#', $path, $matches)) {
    $endpoint_file = __DIR__ . '/endpoints/' . $matches[1] . '.php';
    if (file_exists($endpoint_file)) {
        require $endpoint_file;
        exit;
    }
}

// 404 - Not Found
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'error' => true,
    'message' => 'Endpoint not found',
    'status' => 404,
    'path' => $path
]);

PHP;
        file_put_contents($this->outputPath . '/index.php', $indexRouter);
    }



    // ===== UTILITY =====
    private function getEnabledTables($currentDbConfig)
    {
        return array_filter($currentDbConfig, function ($table, $key) {
            return $key !== '_views'
                && isset($table['enabled'])
                && $table['enabled'] === true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function getTableColumns($tableName)
    {
        $stmt = $this->db->getConnection()->query("DESCRIBE `{$tableName}`");
        return $stmt->fetchAll();
    }
    
    private function getViewColumns($query)
    {
        try {
            // Esegui la query con LIMIT 0 per ottenere solo le colonne senza dati
            $limitedQuery = rtrim(trim($query), ';') . ' LIMIT 0';
            $stmt = $this->db->getConnection()->query($limitedQuery);
            
            // Ottieni le informazioni sulle colonne dal risultato
            $columnCount = $stmt->columnCount();
            $columns = [];
            
            for ($i = 0; $i < $columnCount; $i++) {
                $meta = $stmt->getColumnMeta($i);
                $columns[] = [
                    'Field' => $meta['name'],
                    'Type' => $meta['native_type'] ?? 'string',
                    'Null' => 'YES',
                    'Key' => ($i === 0) ? 'PRI' : '', // Prima colonna come chiave primaria per convenzione
                    'Default' => null,
                    'Extra' => ''
                ];
            }
            
            return $columns;
        } catch (\PDOException $e) {
            error_log("Error getting view columns: " . $e->getMessage());
            return [];
        }
    }

    private function getPrimaryKey($columns)
    {
        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                return $column['Field'];
            }
        }
        return 'id';
    }

    private function toCamelCase($string)
    {
        $str = str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
        return $str;
    }



    // ===== DOCUMENTAZIONE =====
    private function generateReadme($config)
    {
        $currentDbConfig = $config[$this->db->getDatabaseName()] ?? [];
        $enabledTables = $this->getEnabledTables($currentDbConfig);
        $databaseName = $this->db->getDatabaseName();
        $viewsCount = isset($currentDbConfig['_views']) ? count($currentDbConfig['_views']) : 0;
        $remotePath = env('FTP_REMOTE_PATH', '/api');
        $ftpHost = env('FTP_HOST', 'tuosito.altervista.org');
        $baseUrl = "https://{$ftpHost}{$remotePath}";
        $tablesCount = count($enabledTables);
        $generatedDate = date('d/m/Y H:i');
        $firstTable = array_key_first($enabledTables);

        // Header
        $header = <<<MARKDOWN
# API REST — {$databaseName}

Generato il {$generatedDate} — **{$tablesCount} tabelle**, **{$viewsCount} viste**


MARKDOWN;

        // Setup
        $setup = <<<MARKDOWN
---

## Setup

### Requisiti server
- PHP 7.4+, MySQL 5.7+, Apache con mod_rewrite
- Estensioni PHP: `pdo`, `pdo_mysql`, `json`

### Installazione

1. Carica `generated-api/` sul server via FTP o dal builder
2. Verifica credenziali in `config/database.php`
3. Verifica che `.htaccess` sia presente nella root
4. Testa: `curl {$baseUrl}/api/auth/login`


MARKDOWN;

        // Base URL
        $baseUrlSection = <<<MARKDOWN
---

## Base URL

```
{$baseUrl}/api/
```

Tutti gli endpoint hanno prefisso `/api/`. Esempio completo:
```
{$baseUrl}/api/allergens
{$baseUrl}/api/auth/login
{$baseUrl}/api/views/v_products_full
```


MARKDOWN;

        // Autenticazione
        $auth = <<<'MARKDOWN'
---

## Autenticazione (JWT)

### POST `/api/auth/login`

```json
// Request body
{ "email": "admin@example.com", "password": "admin123" }
```

```json
// Response 200
{
  "status": 200,
  "data": {
    "token": "eyJ0eXAiOiJKV1Q...",
    "user": { "id": 1, "email": "admin@example.com", "role": "admin" }
  },
  "message": "Login effettuato con successo"
}
```

```json
// Response 401
{ "error": true, "message": "Credenziali non valide", "status": 401 }
```

### GET `/api/auth/me`

```
Authorization: Bearer <token>
```
Restituisce i dati dell'utente autenticato. Token valido **24 ore**.


MARKDOWN;

        // Endpoint - Tabella permessi
        $endpointsHeader = <<<MARKDOWN
---

## Endpoint

Ogni tabella abilitata espone 5 operazioni CRUD.

### Riepilogo permessi

| Tabella | GET | POST | PUT | DELETE | Rate Limit |
|---------|-----|------|-----|--------|------------|

MARKDOWN;

        $permissionsTable = '';
        foreach ($enabledTables as $tableName => $tableConfig) {
            $rl = ($tableConfig['rate_limit'] ?? 100) . '/' . ($tableConfig['rate_limit_window'] ?? 60) . 's';
            $permissionsTable .= "| `{$tableName}` | " 
                . ($tableConfig['select'] ?? 'all') . " | "
                . ($tableConfig['insert'] ?? 'auth') . " | "
                . ($tableConfig['update'] ?? 'auth') . " | "
                . ($tableConfig['delete'] ?? 'admin') . " | "
                . $rl . " |\n";
        }

        $endpointsSchema = <<<MARKDOWN

**Legenda:** `all` = pubblico, `auth` = token richiesto, `admin` = ruolo admin, `owner` = proprietario

### Schema richieste

| Metodo | URL | Body | Descrizione |
|--------|-----|------|-------------|
| GET | `/api/{tabella}` | — | Lista tutti |
| GET | `/api/{tabella}/{id}` | — | Singolo per ID |
| POST | `/api/{tabella}` | JSON | Crea nuovo |
| PUT | `/api/{tabella}/{id}` | JSON | Aggiorna |
| DELETE | `/api/{tabella}/{id}` | — | Elimina |


MARKDOWN;

        // Viste
        $viewsSection = '';
        if ($viewsCount > 0) {
            $viewsSection = <<<MARKDOWN
### Viste (sola lettura)

Le viste sono accessibili sia come `/api/_view_{nome}` che come `/api/views/{nome}`.

| Vista | Descrizione | Auth |
|-------|-------------|------|

MARKDOWN;
            foreach ($currentDbConfig['_views'] as $viewName => $viewConfig) {
                $desc = $viewConfig['description'] ?? '-';
                $auth = ($viewConfig['require_auth'] ?? false) ? 'auth' : 'all';
                $viewsSection .= "| `{$viewName}` | {$desc} | {$auth} |\n";
            }
            $viewsSection .= "\n";
        }

        // Formato risposte
        $responsesFormat = <<<MARKDOWN
---

## Formato risposte

### Successo
```json
{ "status": 200, "data": [...], "timestamp": 1234567890 }
```

### Errore
```json
{ "error": true, "message": "Descrizione errore", "status": 400, "timestamp": 1234567890 }
```

### Codici HTTP

| Codice | Significato |
|--------|-------------|
| 200 | OK |
| 201 | Creato |
| 400 | Dati mancanti o non validi |
| 401 | Token mancante o scaduto |
| 403 | Accesso diretto da browser bloccato / Permesso negato |
| 404 | Risorsa non trovata |
| 405 | Metodo non consentito |
| 429 | Rate limit superato |
| 500 | Errore server |

> **Nota:** L'accesso diretto da browser (barra URL) è bloccato. Usare un client API (fetch, axios, curl) con gli header corretti.


MARKDOWN;

        // Vue.js - Proxy Vite
        $vueProxyVite = <<<MARKDOWN
---

## Integrazione Vue.js / Vite

### 1. Proxy Vite (sviluppo locale)

In `vite.config.js`:
```javascript
export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: '{$baseUrl}',
        changeOrigin: true,
        secure: true,
        rewrite: (path) => path
      }
    }
  }
})
```


MARKDOWN;

        // Vue.js - Servizio API
        $vueApiService = <<<MARKDOWN
### 2. Servizio API (`src/services/api.js`)

```javascript
// api.js — Servizio API pronto all'uso

const BASE_URL = import.meta.env.DEV
  ? '/api/'
  : '{$baseUrl}/api/'

const getHeaders = () => {
  const headers = { 'Content-Type': 'application/json' }
  const token = localStorage.getItem('token')
  if (token) headers['Authorization'] = `Bearer \${token}`
  return headers
}

const handleResponse = async (response) => {
  const text = await response.text()
  let result
  try {
    result = JSON.parse(text)
  } catch {
    throw new Error('Risposta non JSON: ' + text.substring(0, 200))
  }
  if (!response.ok) throw new Error(result.message || `Errore \${response.status}`)
  return result
}

// === AUTH ===
export const login = async (email, password) => {
  const res = await fetch(`\${BASE_URL}auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  })
  const result = await handleResponse(res)
  localStorage.setItem('token', result.data.token)
  localStorage.setItem('user', JSON.stringify(result.data.user))
  return result.data
}

export const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
}

export const getUser = () => {
  const user = localStorage.getItem('user')
  return user ? JSON.parse(user) : null
}

export const isAuthenticated = () => !!localStorage.getItem('token')

// === CRUD GENERICO ===
export const apiGet = async (endpoint) => {
  const res = await fetch(`\${BASE_URL}\${endpoint}`, { headers: getHeaders() })
  return handleResponse(res)
}

export const apiPost = async (endpoint, data) => {
  const res = await fetch(`\${BASE_URL}\${endpoint}`, {
    method: 'POST', headers: getHeaders(), body: JSON.stringify(data)
  })
  return handleResponse(res)
}

export const apiPut = async (endpoint, data) => {
  const res = await fetch(`\${BASE_URL}\${endpoint}`, {
    method: 'PUT', headers: getHeaders(), body: JSON.stringify(data)
  })
  return handleResponse(res)
}

export const apiDelete = async (endpoint) => {
  const res = await fetch(`\${BASE_URL}\${endpoint}`, {
    method: 'DELETE', headers: getHeaders()
  })
  return handleResponse(res)
}
```


MARKDOWN;

        // Vue.js - Uso componenti
        $exampleEndpoint = $firstTable ?: 'endpoint';
        $exampleCall = $firstTable 
            ? "const result = await apiGet('{$firstTable}')" 
            : "const result = await apiGet('endpoint')";
        
        $vueUsage = <<<MARKDOWN
### 3. Uso nei componenti Vue

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { apiGet, login } from '@/services/api'

const items = ref([])
const loading = ref(true)
const error = ref(null)

onMounted(async () => {
  try {
    // Login (una volta, salva il token in localStorage)
    // await login('admin@example.com', 'admin123')

    // Fetch dati (il token viene incluso automaticamente)
    {$exampleCall}
    items.value = result.data
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})
</script>
```


MARKDOWN;

        // Vue.js - Endpoint rapidi
        $vueEndpointsQuick = "### 4. Endpoint rapidi (copia-incolla)\n\n```javascript\n// === TABELLE ===\n";
        foreach ($enabledTables as $tableName => $tableConfig) {
            $selectAuth = $tableConfig['select'] ?? 'all';
            $comment = $selectAuth === 'all' ? '// pubblico' : '// richiede token';
            $vueEndpointsQuick .= "await apiGet('{$tableName}')         {$comment}\n";
        }
        $vueEndpointsQuick .= "\n// === VISTE (sola lettura) ===\n";
        if ($viewsCount > 0) {
            foreach ($currentDbConfig['_views'] as $viewName => $viewConfig) {
                $auth = ($viewConfig['require_auth'] ?? false) ? '// richiede token' : '// pubblico';
                $vueEndpointsQuick .= "await apiGet('views/{$viewName}')  {$auth}\n";
            }
        }
        $vueEndpointsQuick .= "\n// === CRUD ===\n";
        if ($firstTable) {
            $vueEndpointsQuick .= "await apiGet('{$firstTable}')          // lista\n";
            $vueEndpointsQuick .= "await apiGet('{$firstTable}/1')         // singolo\n";
            $vueEndpointsQuick .= "await apiPost('{$firstTable}', { ... }) // crea\n";
            $vueEndpointsQuick .= "await apiPut('{$firstTable}/1', { ... }) // aggiorna\n";
            $vueEndpointsQuick .= "await apiDelete('{$firstTable}/1')       // elimina\n";
        }
        $vueEndpointsQuick .= "```\n\n";

        // cURL
        $curlEndpoint = $firstTable ?: 'endpoint';
        $curlSection = <<<MARKDOWN
---

## cURL

```bash
# Login
curl -X POST {$baseUrl}/api/auth/login \\
  -H 'Content-Type: application/json' \\
  -d '{"email":"admin@example.com","password":"admin123"}'

# GET con token
curl {$baseUrl}/api/{$curlEndpoint} \\
  -H 'Authorization: Bearer <token>'
```


MARKDOWN;

        // Sicurezza
        $security = <<<MARKDOWN
---

## Sicurezza

| Protezione | Implementazione |
|------------|----------------|
| SQL Injection | PDO Prepared Statements |
| XSS | htmlspecialchars() |
| Accesso browser | Bloccato (richiede header API) |
| Rate Limiting | Per IP, configurabile per endpoint |
| JWT | Scadenza configurabile (default 24h) |
| Password | Bcrypt hash |
| Error Handling | Centralizzato, log su file, JSON-only |


MARKDOWN;

        // Struttura
        $structure = <<<'MARKDOWN'
---

## Struttura

```
├── .htaccess            # Routing Apache
├── index.php            # Router PHP built-in server
├── cors.php             # CORS headers
├── config/
│   ├── database.php     # Credenziali DB + classe Database
│   ├── helpers.php      # sendResponse() + blockBrowserAccess()
│   ├── jwt.php          # JWTHandler
│   └── api_config.json  # Permessi tabelle
├── middleware/
│   ├── auth.php         # requireAuth() / validateToken()
│   ├── security.php     # Rate limiting + security headers
│   ├── security_helper.php # applySecurity()
│   └── error_handler.php   # Gestione errori centralizzata
├── models/              # Un file per ogni tabella/vista
├── endpoints/           # CRUD per ogni tabella/vista
├── auth/me.php          # GET /api/auth/me
└── logs/                # Error log (protetto da .htaccess)
```


MARKDOWN;

        // Troubleshooting
        $troubleshooting = <<<'MARKDOWN'
---

## Troubleshooting

| Problema | Soluzione |
|----------|----------|
| 404 su Altervista | Verifica `.htaccess` e `RewriteBase` |
| CORS bloccato | Controlla `cors.php` e header Apache |
| Token invalid | Token scaduto → rifai login |
| 403 da browser | Normale: usa fetch/axios, non la barra URL |
| 500 senza dettagli | Controlla `logs/errors.log` |
| Response non JSON | Errore PHP fatale → controlla logs server |

MARKDOWN;

        // Componi il contenuto finale
        $content = $header . $setup . $baseUrlSection . $auth 
                 . $endpointsHeader . $permissionsTable . $endpointsSchema 
                 . $viewsSection . $responsesFormat 
                 . $vueProxyVite . $vueApiService . $vueUsage . $vueEndpointsQuick 
                 . $curlSection . $security . $structure . $troubleshooting;

        file_put_contents($this->outputPath . '/README.md', $content);
    }
}
