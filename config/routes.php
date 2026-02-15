<?php

// Definizione routes dell'applicazione
$routes = [
    // Home routes
    '/' => [
        'controller' => 'HomeController',
        'action' => 'index',
        'method' => 'GET'
    ],
    // Generator routes
    '/generator' => [
        'controller' => 'GeneratorController',
        'action' => 'index',
        'method' => 'GET'
    ],
    
    '/generator/save' => [
        'controller' => 'GeneratorController',
        'action' => 'save',
        'method' => 'POST'
    ],
    
    '/generator/security' => [
        'controller' => 'GeneratorController',
        'action' => 'createSecurityTables',
        'method' => 'POST'
    ],
    
    '/generator/views' => [
        'controller' => 'GeneratorController',
        'action' => 'views',
        'method' => 'GET'
    ],
    
    '/generator/test-view' => [
        'controller' => 'GeneratorController',
        'action' => 'testView',
        'method' => 'POST'
    ],
    
    '/generator/save-view' => [
        'controller' => 'GeneratorController',
        'action' => 'saveView',
        'method' => 'POST'
    ],
    
    '/generator/toggle-view' => [
        'controller' => 'GeneratorController',
        'action' => 'toggleView',
        'method' => 'POST'
    ],
    
    '/generator/delete-view' => [
        'controller' => 'GeneratorController',
        'action' => 'deleteView',
        'method' => 'POST'
    ],
    
    '/generator/jwt' => [
        'controller' => 'GeneratorController',
        'action' => 'jwt',
        'method' => 'GET'
    ],
    
    '/generator/jwt/save' => [
        'controller' => 'GeneratorController',
        'action' => 'saveJwt',
        'method' => 'POST'
    ],
    
    // API Builder routes
    '/generator/builder' => [
        'controller' => 'ApiBuilderController',
        'action' => 'index',
        'method' => 'GET'
    ],
    
    '/builder/generate' => [
        'controller' => 'ApiBuilderController',
        'action' => 'generate',
        'method' => 'POST'
    ],
    // FTP Deploy routes
    '/deploy' => [
        'controller' => 'FtpDeployController',
        'action' => 'index',
        'method' => 'GET'
    ],
    
    '/deploy/upload' => [
        'controller' => 'FtpDeployController',
        'action' => 'upload',
        'method' => 'POST'
    ],
    
    '/deploy/test' => [
        'controller' => 'FtpDeployController',
        'action' => 'test',
        'method' => 'POST'
    ],
    
    '/deploy/move' => [
        'controller' => 'FtpDeployController',
        'action' => 'move',
        'method' => 'POST'
    ],
    
    // Settings routes
    '/settings' => [
        'controller' => 'HomeController',
        'action' => 'settings',
        'method' => 'GET'
    ],
    
    '/settings/test' => [
        'controller' => 'HomeController',
        'action' => 'test',
        'method' => 'POST'
    ],
    
    '/settings/save' => [
        'controller' => 'HomeController',
        'action' => 'save',
        'method' => 'POST'
    ],
    
    // About
    '/about' => [
        'controller' => 'HomeController',
        'action' => 'about',
        'method' => 'GET'
    ],
    
    // // API Test Suite (disabilitate online)
    // '/test' => [
    //     'controller' => 'ApiTestController',
    //     'action' => 'index',
    //     'method' => 'GET'
    // ],
    
];

return $routes;