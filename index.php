<?php
require_once './core/helpers.php';

loadEnv();

// Error handler
require_once './core/ErrorHandler.php';
Core\ErrorHandler::init(__DIR__ . '/logs/app-errors.log', false); // false = modalità sviluppo

require_once './core/App.php';
require_once './core/Controller.php';
require_once './core/Route.php';
require_once './core/Security.php';
require_once './core/Database.php';
require_once './core/FtpNew.php';
use Core\Security;

// Sessione sicura
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict',
]);

// CSRF token
Security::initCsrf();

// AVVIO APP
$app = new Core\App();
$app->run();
