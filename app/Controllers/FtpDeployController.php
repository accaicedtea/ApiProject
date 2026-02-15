<?php
namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\FtpNew;

class FtpDeployController extends Controller {
    private $generatedApiPath = __DIR__ . '/../../generated-api';
    private $db;
    private $ftp;

    public function __construct(Database $db) {
        $this->db = $db;
    }
    public function index() {
        $apiExists = file_exists($this->generatedApiPath);
        
        // Statistiche API generata
        $apiStats = null;
        if ($apiExists) {
            $apiStats = $this->analyzeGeneratedApi();
        }
        
        $savedCredentials = $this->loadCredentials();
        
        $data = [
            'title' => 'Deploy FTP - Carica API sul Server',
            'databaseType' => $this->db->getConnectionType(),
            'connectionStatus' => $this->db->isConnected(),
            'databaseName' => $this->db->getDatabaseName(),
            'apiExists' => $apiExists,
            'apiPath' => $this->generatedApiPath,
            'apiStats' => $apiStats,
            'savedCredentials' => $savedCredentials
        ];
        
        $this->view('deploy/index', $data);
    }


    public function test() {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        try {
            $host = $_POST['ftp_host'] ?? '';
            $username = $_POST['ftp_user'] ?? '';
            $password = $_POST['ftp_pass'] ?? '';
            $port = (int)($_POST['ftp_port'] ?? 21);
            $useSsl = isset($_POST['ftp_ssl']);
            
            if (empty($host) || empty($username) || empty($password)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Host, username e password sono obbligatori'
                ]);
                exit;
            }
            
            // Connessione FTP
            $this->ftp = new FtpNew($host, $port, $useSsl);
            
            $loginResult = $this->ftp->login($username, $password);
            
            if (!$loginResult) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Login FTP fallito - Verifica username e password'
                ]);
                exit;
            }
            
            // Salva sessione FTP
            $_SESSION['ftp_connected'] = true;
            $_SESSION['ftp_host'] = $host;
            $_SESSION['ftp_user'] = $username;
            $_SESSION['ftp_pass'] = $password;
            $_SESSION['ftp_port'] = $port;
            $_SESSION['ftp_ssl'] = $useSsl;
            
            echo json_encode([
                'success' => true,
                'message' => 'Connessione FTP stabilita con successo',
                'directory' => 'Pronto per l\'upload'
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Errore: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }
    
    public function move() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/deploy'));
            exit;
        }
        
        if (!\Core\Security::checkCsrf($_POST['csrf_token'] ?? '')) {
            die('Token CSRF non valido');
        }
        
        $sourcePath = $_POST['source_path'] ?? '';
        $destinationPath = $_POST['destination_path'] ?? '';
        $copyMode = isset($_POST['copy_mode']);
        
        if (empty($sourcePath) || empty($destinationPath)) {
            header('Location: ' . url('/deploy?error=' . urlencode('Percorsi non validi')));
            exit;
        }
        
        if (!is_dir($sourcePath)) {
            header('Location: ' . url('/deploy?error=' . urlencode('La cartella sorgente non esiste')));
            exit;
        }
        
        if (file_exists($destinationPath)) {
            header('Location: ' . url('/deploy?error=' . urlencode('La destinazione esiste già. Scegli un altro percorso.')));
            exit;
        }
        
        try {
            if ($copyMode) {
                $this->recursiveCopy($sourcePath, $destinationPath);
                header('Location: ' . url('/deploy?success=' . urlencode('Cartella copiata con successo in: ' . $destinationPath)));
            } else {
                if (rename($sourcePath, $destinationPath)) {
                    header('Location: ' . url('/deploy?success=' . urlencode('Cartella spostata con successo in: ' . $destinationPath)));
                } else {
                    header('Location: ' . url('/deploy?error=' . urlencode('Impossibile spostare la cartella. Verifica i permessi.')));
                }
            }
        } catch (\Exception $e) {
            header('Location: ' . url('/deploy?error=' . urlencode('Errore: ' . $e->getMessage())));
        }
        
        exit;
    }
    
    private function recursiveCopy($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $srcFile = $source . '/' . $file;
            $dstFile = $destination . '/' . $file;
            
            if (is_dir($srcFile)) {
                $this->recursiveCopy($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
        closedir($dir);
    }
    
    public function upload() {
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_implicit_flush(true);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Metodo non valido']);
            exit;
        }
        
        if (!\Core\Security::checkCsrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['error' => 'Token CSRF non valido']);
            exit;
        }
        
        $host = $_POST['ftp_host'] ?? '';
        $username = $_POST['ftp_user'] ?? '';
        $password = $_POST['ftp_pass'] ?? '';
        $remotePath = $_POST['ftp_path'] ?? '/';
        $port = (int)($_POST['ftp_port'] ?? 21);
        $useSsl = isset($_POST['ftp_ssl']);
        
        if (empty($host) || empty($username) || empty($password)) {
            echo json_encode(['error' => 'Compila tutti i campi obbligatori']);
            exit;
        }
        
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        try {
            $this->sendProgress('Connessione al server FTP...', 0);
            
            $this->ftp = new FtpNew($host, $port, $useSsl);
            $loginResult = $this->ftp->login($username, $password);
            
            if (!$loginResult) {
                $this->sendProgress('Errore: Login FTP fallito', 100, true);
                exit;
            }
            
            $this->sendProgress('Login effettuato', 10);
            
            $totalFiles = $this->countFiles($this->generatedApiPath);
            $this->sendProgress("Trovati $totalFiles file da caricare", 15);
            
            $this->sendProgress('Caricamento in corso...', 20);
            
            $errorList = $this->ftp->send_recursive_directory($this->generatedApiPath, $remotePath);
            
            $this->ftp->disconnect();
            
            $errors = array_filter($errorList, function($val) {
                return !empty($val) && is_string($val);
            });
            
            if (count($errors) > 0) {
                $this->sendProgress('Completato con ' . count($errors) . ' errori', 100, true);
            } else {
                $this->sendProgress('✓ Upload completato!', 100, false);
            }
            
        } catch (\Exception $e) {
            $this->sendProgress('Errore: ' . $e->getMessage(), 100, true);
        }
        
        exit;
    } 
    
    private function sendProgress($message, $percentage, $isError = false) {
        echo "data: " . json_encode([
            'message' => $message,
            'percentage' => $percentage,
            'error' => $isError
        ]) . "\n\n";
        
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
    
    private function countFiles($dir) {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $count++;
            }
        }
        
        return $count;
    }
    
    private function loadCredentials() {
        return [
            'ftp_host' => env('FTP_HOST'),
            'ftp_port' => env('FTP_PORT'),
            'ftp_user' => env('FTP_USERNAME'),
            'ftp_pass' => env('FTP_PASSWORD'),
            'ftp_path' => env('FTP_REMOTE_PATH'),
            'ftp_ssl' => env('FTP_SSL'),
        ];
    }
    
    /**
     * Statistiche della cartella generated-api
     */
    private function analyzeGeneratedApi(): array {
        $path = $this->generatedApiPath;
        $stats = [
            'totalFiles' => 0,
            'totalSize' => 0,
            'folders' => [],
            'endpoints' => [],
            'models' => [],
            'hasAuth' => false,
            'hasCors' => false,
            'hasMiddleware' => false,
            'lastModified' => 0,
        ];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        $folderSet = [];
        
        foreach ($iterator as $item) {
            $relativePath = str_replace($path . '/', '', $item->getPathname());
            $topFolder = explode('/', $relativePath)[0];
            
            if ($item->isDir()) {
                $folderSet[$topFolder] = true;
            }
            
            if ($item->isFile()) {
                $stats['totalFiles']++;
                $stats['totalSize'] += $item->getSize();
                
                $mtime = $item->getMTime();
                if ($mtime > $stats['lastModified']) {
                    $stats['lastModified'] = $mtime;
                }
                
                if (strpos($relativePath, 'endpoints/') === 0 && $item->getExtension() === 'php') {
                    $name = pathinfo($item->getFilename(), PATHINFO_FILENAME);
                    if ($name !== 'auth') {
                        $stats['endpoints'][] = $name;
                    }
                }
                
                if (strpos($relativePath, 'models/') === 0 && $item->getExtension() === 'php') {
                    $name = pathinfo($item->getFilename(), PATHINFO_FILENAME);
                    if ($name !== 'User') {
                        $stats['models'][] = $name;
                    }
                }
                
                if (strpos($relativePath, 'endpoints/auth.php') !== false) $stats['hasAuth'] = true;
                if ($relativePath === 'cors.php') $stats['hasCors'] = true;
                if (strpos($relativePath, 'middleware/') === 0) $stats['hasMiddleware'] = true;
            }
        }
        
        $stats['folders'] = array_keys($folderSet);
        sort($stats['folders']);
        sort($stats['endpoints']);
        sort($stats['models']);
        
        return $stats;
    }
    
}