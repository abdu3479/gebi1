<?php
header('Content-Type: application/json');
require_once '../Payment.php';

try {
    $db = new mysqli('localhost', 'root', '', 'aziyan');
    $payment = new Payment($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'lease_id' => $_POST['lease_id'],
            'bill_id' => $_POST['bill_id'],
            'amount' => $_POST['amount'],
            'payment_method' => $_POST['payment_method'],
            'description' => $_POST['description'] ?? null
        ];
        if (!empty($_FILES['receipt']['name'])) {
            $data['receipt'] = $_FILES['receipt'];
        }
        $result = $payment->create($data);
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>