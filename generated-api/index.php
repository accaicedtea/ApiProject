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
