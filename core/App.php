<?php
namespace Core;

use Core\ErrorHandler;

class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];
    
    // Controller con dipendenza dal database
    private $dbRequiredControllers = [
        'GeneratorController', 
        'ApiBuilderController', 
        'FtpDeployController'
    ];
    
    public function run() {
        // Connessione database
        $db = null;
        try {
            $db = new Database();
        } catch (\Exception $e) {
            // Connessione fallita
        }
        
        $route = Route::resolve();
        $this->controller = $route['controller'];
        $this->method = $route['method'];
        $this->params = $route['params'];
        
        if ($db === null) {
            // Redirect ai settings per configurare il database
            if (in_array($this->controller, $this->dbRequiredControllers)) {
                header('Location: ' . url('/settings?error=' . urlencode('Database non connesso. Configura la connessione.')));
                exit;
            }
            
            // Pagina "nessuna connessione"
            if ($this->controller === 'HomeController') {
                $this->showNoConnection();
                return;
            }
            
        }

        $controllerFile = __DIR__ . '/../app/Controllers/' . $this->controller . '.php';
        if (file_exists($controllerFile)) {
            
            require_once $controllerFile;
            
            $controllerClass = '\App\Controllers\\' . $this->controller;
            
            try {
                $controller = new $controllerClass($db);
                
                if (method_exists($controller, $this->method)) {
                    call_user_func_array([$controller, $this->method], $this->params);
                } else {
                    ErrorHandler::notFound(
                        "Metodo '{$this->method}' non trovato nel controller '{$this->controller}'"
                    );
                }
            } catch (\Exception $e) {
                // Log e pagina errore
                ErrorHandler::log([
                    'type' => 'Controller Exception',
                    'controller' => $this->controller,
                    'method' => $this->method,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 'ERROR');
                
                if ($this->controller === 'HomeController') {
                    $this->showNoConnection();
                } else {
                    ErrorHandler::serverError('Errore durante l\'esecuzione del controller: ' . $e->getMessage());
                }
            }
        } else {
            // 404 - Controller non trovato
            ErrorHandler::notFound(
                "Controller non trovato: {$this->controller}<br>" .
                "URI: {$_SERVER['REQUEST_URI']}<br>" .
                "Metodo: {$this->method}"
            );
        }
    }
    
    /**
     * Mostra la pagina "nessuna connessione al database"
     */
    private function showNoConnection() {
        $data = [
            'title' => 'Home - Disconnesso',
            'csrf_token' => Security::csrfToken(),
            'style' => '',
            'appName' => 'SerioAPI',
            'connectionStatus' => false,
            'dbConnected' => false,
        ];
        extract($data);
        
        if (file_exists(__DIR__ . '/../app/Views/components/Header.php')) {
            require __DIR__ . '/../app/Views/components/Header.php';
        }
        if (file_exists(__DIR__ . '/../app/Views/components/Navbar.php')) {
            require __DIR__ . '/../app/Views/components/Navbar.php';
        }
        echo '<div class="page-content-wrapper">';
        require __DIR__ . '/../app/Views/home/nocon.php';
        echo '</div>';
        if (file_exists(__DIR__ . '/../app/Views/components/Footer.php')) {
            require __DIR__ . '/../app/Views/components/Footer.php';
        }
    }
}
