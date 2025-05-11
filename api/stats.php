<?php
header('Content-Type: application/json');
require_once '../Bill.php';
require_once '../Payment.php';

try {
    $db = new mysqli('localhost', 'root', '', 'aziyan');
    $bill = new Bill($db);
    $payment = new Payment($db);
    $stats = [
        'bills' => $bill->getStatistics(),
        'payments' => $payment->getStatistics()
    ];
    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>