<?php
header('Content-Type: application/json');
require_once '../Asset.php';

try {
    $db = new mysqli('localhost', 'root', '', 'aziyan');
    $asset = new Asset($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode($asset->getAll());
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>