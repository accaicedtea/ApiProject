<?php
/**
 * Gestione errori centralizzata per API REST
 */
class ApiErrorHandler {
    private static $logFile;
    private static $isProduction = false;

    /**
     * Inizializza handler e log
     */
    public static function init() {
        self::$logFile = __DIR__ . '/../logs/errors.log';
        self::$isProduction = self::detectProduction();

        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);

        if (self::$isProduction) {
            ini_set('display_errors', '0');
        }
        error_reporting(E_ALL);
    }

    /**
     * Rileva ambiente di produzione
     */
    private static function detectProduction() {
        return strpos($_SERVER['HTTP_HOST'] ?? '', 'altervista.org') !== false
            || strpos($_SERVER['SERVER_NAME'] ?? '', 'altervista.org') !== false
            || isset($_SERVER['AlterVista']);
    }

    /**
     * Gestisce errori PHP
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        self::log('PHP Error', $errstr, $errfile, $errline);

        if (in_array($errno, [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        }

        return true;
    }

    /**
     * Gestisce eccezioni non catturate
     */
    public static function handleException($e) {
        self::log(get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());

        $code = 500;
        $message = 'Internal Server Error';
        if ($e instanceof \PDOException) {
            $message = 'Database error';
        } elseif ($e instanceof \InvalidArgumentException) {
            $code = 400;
            $message = 'Bad request';
        }

        self::sendError($code, $message, $e);
    }

    /**
     * Gestisce errori fatali
     */
    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::log('Fatal Error', $error['message'], $error['file'], $error['line']);
            self::sendError(500, 'Internal Server Error');
        }
    }

    /**
     * Risposta di errore JSON
     */
    private static function sendError($code, $message, $exception = null) {
        if (headers_sent()) {
            return;
        }

        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');

        $response = [
            'error' => true,
            'message' => $message,
            'status' => $code,
            'timestamp' => time()
        ];

        // Dettagli debug in development
        if (!self::$isProduction && $exception !== null) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'detail' => $exception->getMessage(),
                'file' => basename($exception->getFile()) . ':' . $exception->getLine()
            ];
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Scrive nel log
     */
    private static function log($type, $message, $file = '', $line = 0, $trace = '') {
        $entry = sprintf(
            "[%s] [%s] [IP: %s] %s | %s:%d\n",
            date('Y-m-d H:i:s'),
            $type,
            $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            $message,
            basename($file),
            $line
        );

        if ($trace) {
            $entry .= "  Trace: " . substr($trace, 0, 500) . "\n";
        }

        $entry .= str_repeat('-', 60) . "\n";

        @file_put_contents(self::$logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}

// Auto-init
ApiErrorHandler::init();
