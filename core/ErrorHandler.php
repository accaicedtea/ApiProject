<?php
namespace Core;

/**
 * Gestore centralizzato degli errori dell'applicazione
 */
class ErrorHandler {
    private static $logFile;
    private static $isProduction = false;
    
    /**
     * Inizializza l'error handler
     */
    public static function init($logFile = null, $isProduction = false) {
        self::$logFile = $logFile ?? __DIR__ . '/../logs/errors.log';
        self::$isProduction = $isProduction;
        
        // Crea directory logs se non esiste
        $logDir = dirname(self::$logFile);
        if (!file_exists($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        // Registra handler
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        
        // In produzione, nascondi errori PHP
        if (self::$isProduction) {
            ini_set('display_errors', '0');
            error_reporting(E_ALL);
        }
    }
    
    /**
     * Handler per errori PHP (warnings, notices, etc)
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Ignora errori soppressi con @
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorType = self::getErrorType($errno);
        
        self::log([
            'type' => 'PHP Error',
            'severity' => $errorType,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'trace' => self::getBacktrace()
        ]);
        
        // Non impedire la normale gestione degli errori PHP
        return false;
    }
    
    /**
     * Handler per eccezioni non catturate
     */
    public static function handleException($exception) {
        self::log([
            'type' => 'Uncaught Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Mostra pagina errore user-friendly
        self::showErrorPage(500, 'Internal Server Error', 
            self::$isProduction ? 'Si è verificato un errore interno.' : $exception->getMessage(),
            [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
    }
    
    /**
     * Handler per errori fatali
     */
    public static function handleFatalError() {
        $error = error_get_last();
        
        if ($error !== null &&  in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::log([
                'type' => 'Fatal Error',
                'severity' => self::getErrorType($error['type']),
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
            
            self::showErrorPage(500, 'Fatal Error', 
                self::$isProduction ? 'Si è verificato un errore critico.' : $error['message']);
        }
    }
    
    /**
     * Log un errore personalizzato
     */
    public static function log($data, $level = 'ERROR') {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        
        $logEntry = sprintf(
            "[%s] [%s] [IP: %s] [URI: %s]\n%s\n%s\n\n",
            $timestamp,
            $level,
            $ip,
            $uri,
            is_array($data) ? json_encode($data, JSON_PRETTY_PRINT) : $data,
            str_repeat('-', 80)
        );
        
        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Mostra una pagina di errore user-friendly
     */
    public static function showErrorPage($code, $title, $message = '', $extraData = []) {
        if (php_sapi_name() === 'cli') {
            echo "ERROR $code: $title - $message\n";
            return;
        }
        
        http_response_code($code);
        
        // Se è una richiesta AJAX/API, restituisci JSON
        if (self::isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'code' => $code,
                'message' => $message ?: $title,
                'timestamp' => time()
            ]);
            exit;
        }
        
        // Prepara le variabili per il template
        $debug = !self::$isProduction;
        $homeUrl = function_exists('url') ? url('/') : '/';
        $uri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        
        // Estrai extra data
        $file = $extraData['file'] ?? null;
        $line = $extraData['line'] ?? null;
        $trace = $extraData['trace'] ?? null;
        $icon = $extraData['icon'] ?? '❌';
        $debugInfo = $debug ? print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true) : null;
        
        // Determina quale template usare
        $templatePath = __DIR__ . '/errors/';
        if ($code == 404 && file_exists($templatePath . '404.php')) {
            include $templatePath . '404.php';
        } elseif ($code == 500 && file_exists($templatePath . '500.php')) {
            include $templatePath . '500.php';
        } elseif (file_exists($templatePath . 'generic.php')) {
            include $templatePath . 'generic.php';
        } else {
            // Fallback se i file non esistono
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error $code</title></head>";
            echo "<body><h1>$code - $title</h1><p>" . htmlspecialchars($message) . "</p>";
            echo "<a href='$homeUrl'>Torna alla home</a></body></html>";
        }
        exit;
    }
    
    /**
     * Controlla se è una richiesta AJAX
     */
    private static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Ottiene il nome del tipo di errore
     */
    private static function getErrorType($type) {
        $types = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        
        return $types[$type] ?? 'UNKNOWN';
    }
    
    /**
     * Ottiene backtrace leggibile
     */
    private static function getBacktrace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($trace); // Rimuovi handleError
        return array_slice($trace, 0, 5); // Prime 5 chiamate
    }
    
    /**
     * Metodo helper per errori 404
     */
    public static function notFound($message = 'Pagina non trovata') {
        self::log(['type' => '404', 'message' => $message, 'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A'], 'INFO');
        self::showErrorPage(404, 'Not Found', $message);
    }
    
    /**
     * Metodo helper per errori 400
     */
    public static function badRequest($message = 'Richiesta non valida') {
        self::log(['type' => '400', 'message' => $message], 'WARN');
        self::showErrorPage(400, 'Bad Request', $message);
    }
    
    /**
     * Metodo helper per errori 500
     */
    public static function serverError($message = 'Errore interno del server') {
        self::log(['type' => '500', 'message' => $message], 'ERROR');
        self::showErrorPage(500, 'Internal Server Error', $message);
    }
}
