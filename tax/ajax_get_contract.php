<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$contract_id = filter_input(INPUT_GET, 'contract_id', FILTER_VALIDATE_INT);
if (!$contract_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid contract ID']);
    exit;
}

$stmt = $conn->prepare("SELECT monthly_rent FROM rent_contracts WHERE contract_id = ? AND status = 'active'");
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$result = $stmt->get_result();
$contract = $result->fetch_assoc();
$stmt->close();

if ($contract) {
    echo json_encode(['monthly_rent' => $contract['monthly_rent']]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Contract not found or inactive']);
}

$conn->close();
?>