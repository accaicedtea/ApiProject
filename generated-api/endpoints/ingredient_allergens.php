<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/security_helper.php';
require_once __DIR__ . '/../models/Ingredient_allergens.php';

applySecurity('ingredient_allergens', 100, 60);

$database = new Database();
$db = $database->getConnection();
$model = new Ingredient_allergens($db);

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', $path);
$id = end($path_parts);

switch($method) {
    case 'GET':
        $user = null;
        $user = requireAuth();
        if (!empty($user)) {
            // Utente autenticato - filtra per azienda se la tabella ha azienda_id
            if ($id && $id !== 'ingredient_allergens') {
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
            // Pubblico o nessuna auth richiesta
            if ($id && $id !== 'ingredient_allergens') {
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
        
    case 'POST':
        $user = requireAuth();
        $data = json_decode(file_get_contents("php://input"), true);
        
        if(empty($data['ingredient_id']) || empty($data['allergen_id'])) {
        sendResponse(400, null, 'Missing required fields');
    }
    $item_id = $model->create($data);
        if($item_id) {
            sendResponse(201, ['id' => $item_id], 'Created successfully');
        } else {
            sendResponse(500, null, 'Failed to create');
        }
        break;
        
    case 'PUT':
        $user = requireAuth();
        $data = json_decode(file_get_contents("php://input"), true);
        
        if(empty($data['ingredient_id']) || empty($data['allergen_id'])) {
        sendResponse(400, null, 'Missing required fields');
    }
    if($model->update($id, $data)) {
            sendResponse(200, null, 'Updated successfully');
        } else {
            sendResponse(500, null, 'Failed to update');
        }
        break;
        
    case 'DELETE':
        $user = requireAuth();
        if($model->delete($id)) {
            sendResponse(200, null, 'Deleted successfully');
        } else {
            sendResponse(500, null, 'Failed to delete');
        }
        break;
        
    default:
        sendResponse(405, null, 'Method not allowed');
        break;
}
