<?php

require_once __DIR__ . '/../core/helpers.php';

loadEnv();

// Configurazione database per environment
function getDatabaseConfig($environment = null) {
    
    if ($environment === null) {
        $environment = env('ENVIRONMENT', 'development');
    }
    
    $databases = [
        'development' => [
            'dbType' => 'MySQL',
            'host' => 'localhost',
            'dbname' => 'acca_totem',
            'user' => 'root',
            'pass' => '',
            'charset' => 'utf8mb4',
        ],
        'production' => [
            'dbType' => 'MySQL',
            'host' => 'localhost',
            'dbname' =>  'my_thisisnotmysite',
            'user' =>  ' thisisnotmysite',
            'pass' =>  '',
            'charset' => 'utf8mb4',
        ],
        // 'example' => [
        //     'dbType' => 'MySQL',
        //     'host' => 'localhost',
        //     'dbname' => 'example_db',
        //     'user' => 'example_user',
        //     'pass' => 'example_pass',
        //     'charset' => 'utf8mb4',
        // ]
    ];
    
    return $databases[$environment] ?? $databases['development'];
}

// Default: development
return getDatabaseConfig();

