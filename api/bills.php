<?php
header('Content-Type: application/json');
require_once '../Bill.php';

try {
    $db = new mysqli('localhost', 'root', '', 'aziyan');
    $bill = new Bill($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $filters = [];
        if (!empty($_GET['lease_id'])) {
            $filters['lease_id'] = $_GET['lease_id'];
        }
        echo json_encode($bill->getAll($filters));
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'lease_id' => $_POST['lease_id'],
            'amount' => $_POST['amount'],
            'billing_date' => $_POST['billing_date'],
            'due_date' => $_POST['due_date'],
            'description' => $_POST['description'] ?? null
        ];
        $id = $bill->create($data);
        echo json_encode(['id' => $id]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>