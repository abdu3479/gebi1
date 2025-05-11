<?php
header('Content-Type: application/json');
require_once '../Owner.php';

try {
    $db = new mysqli('localhost', 'root', '', 'aziyan');
    $owner = new Owner($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode($owner->getAll());
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'full_name' => $_POST['full_name'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'] ?? null,
            'address' => $_POST['address'] ?? null
        ];
        if (!empty($_FILES['id_document']['name'])) {
            $data['id_document'] = $_FILES['id_document'];
        }
        $id = $owner->create($data);
        echo json_encode(['id' => $id]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>