<?php
// ===== HELPER FUNCTIONS =====

// Error handler centralizzato (PRIMA di tutto)
require_once __DIR__ . '/../middleware/error_handler.php';

header("Content-Type: application/json; charset=UTF-8");
// CORS è gestito da cors.php

/**
 * Blocca l'accesso diretto da browser alla API.
 */
function blockBrowserAccess() {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $hasAuth = !empty($_SERVER['HTTP_AUTHORIZATION']);
    $isOptions = $_SERVER['REQUEST_METHOD'] === 'OPTIONS';
    $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
    $hasContentType = strpos($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '', 'application/json') !== false;
    $hasXHR = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

    if ($isOptions || $hasAuth || $isPost || $hasXHR || $hasContentType) {
        return;
    }

    if (strpos($accept, 'text/html') !== false && strpos($accept, 'application/json') === false) {
        http_response_code(403);
        echo json_encode([
            'error' => true,
            'message' => 'Direct browser access not allowed. Use an API client.',
            'status' => 403,
            'timestamp' => time()
        ]);
        exit;
    }
}
blockBrowserAccess();

/**
 * Risposta JSON standardizzata
 */
function sendResponse($status, $data = null, $message = null) {
    http_response_code($status);
    
    $response = [];
    
    if ($status >= 400) {
        $response['error'] = true;
        $response['message'] = $message ?? 'An error occurred';
    } else {
        if ($data !== null) {
            $response['data'] = $data;
        }
        if ($message !== null) {
            $response['message'] = $message;
        }
    }
    
    $response['status'] = $status;
    $response['timestamp'] = time();
    
    echo json_encode($response);
    exit;
}

