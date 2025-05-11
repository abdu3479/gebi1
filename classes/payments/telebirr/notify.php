<?php
// api/payments/telebirr/notify.php
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input");
    }

    $payment = new Payment($db);
    $result = $payment->handleTelebirrNotification($data);
    
    echo json_encode(['success' => $result]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>