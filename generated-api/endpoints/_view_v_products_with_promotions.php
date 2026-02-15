<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/security_helper.php';
require_once __DIR__ . '/../models/_view_v_products_with_promotions.php';

applySecurity('_view_v_products_with_promotions', 100, 60);

$database = new Database();
$db = $database->getConnection();
$model = new _view_v_products_with_promotions($db);

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = array_values(array_filter(explode('/', $path)));
$last_part = end($path_parts) ?: '';
$prev_part = $path_parts[count($path_parts) - 2] ?? '';
$view_table = '_view_v_products_with_promotions';
$view_slug = substr($view_table, 6); // rimuove prefisso _view_

// Supporta sia /api/_view_nomevista che /api/views/nomevista
$id = null;
if ($prev_part === 'views') {
    // /api/views/{view}/{id?}
    if ($last_part !== $view_slug && $last_part !== $view_table) {
        $id = $last_part;
    }
} else {
    // /api/_view_{view}/{id?}
    if ($last_part !== $view_table && $last_part !== $view_slug && $last_part !== 'views') {
        $id = $last_part;
    }
}

switch($method) {
    case 'GET':
        $user = null;
        $user = requireAuth();
        // Se autenticato, filtra per azienda_id dell'utente
        if (!empty($user)) {
            // Utente autenticato - filtra per azienda
            if ($id && $id !== '_view_v_products_with_promotions' && $id !== $view_slug) {
                $stmt = $model->getByIdByAzienda($id, $user['azienda_id']);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($row) {
                    sendResponse(200, $row);
                } else {
                    sendResponse(404, null, 'Not found');
                }
            } else {
                $stmt = $model->getAllByAzienda($user['azienda_id']);
                $items = array();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push($items, $row);
                }
                
                sendResponse(200, $items);
            }
        } else {
            // Pubblico - nessun filtro
            if ($id && $id !== '_view_v_products_with_promotions' && $id !== $view_slug) {
                $stmt = $model->getById($id);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($row) {
                    sendResponse(200, $row);
                } else {
                    sendResponse(404, null, 'Not found');
                }
            } else {
                $stmt = $model->getAll();
                $items = array();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push($items, $row);
                }
                
                sendResponse(200, $items);
            }
        }
        break;
        
    default:
        sendResponse(405, null, 'Method not allowed. Views are read-only.');
        break;
}
