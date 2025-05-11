<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../classes/Bill.php';

session_start();
if (!in_array($_SESSION['role'] ?? '', ['admin', 'billing_officer', 'clerk'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$billObj = new Bill($conn);

$lease_id = isset($_GET['lease_id']) ? (int)$_GET['lease_id'] : 0;

if ($lease_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid lease ID']);
    exit;
}

try {
    $bills = $billObj->getAll([
        'lease_id' => $lease_id,
        'status' => ['pending', 'overdue']
    ]);
    $response = array_map(function($bill) {
        return [
            'id' => $bill['id'],
            'bill_number' => $bill['bill_number'],
            'total_amount' => $bill['total_amount']
        ];
    }, $bills);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch bills']);
    error_log("Error fetching bills for lease $lease_id: " . $e->getMessage());
}
?>