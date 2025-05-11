<?php
session_start();
require_once __DIR__ . '../config/database.php';
require_once __DIR__ . '../classes/LateFeeCalculator.php';

header('Content-Type: application/json');

try {
    // Validate CSRF token
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        throw new Exception("Invalid CSRF token");
    }

    // Validate inputs
    $baseAmount = (float)($_POST['base_amount'] ?? 0);
    $dueDate = $_POST['due_date'] ?? '';
    $billingDate = $_POST['billing_date'] ?? '';

    if ($baseAmount <= 0) {
        throw new Exception("Base amount must be positive");
    }

    if (empty($dueDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        throw new Exception("Invalid due date format");
    }

    if (empty($billingDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $billingDate)) {
        throw new Exception("Invalid billing date format");
    }

    $db = new Database();
    $conn = $db->getConnection();
    $calculator = new LateFeeCalculator($conn);

    $result = $calculator->calculateLateFee($baseAmount, $dueDate);
    
    echo json_encode([
        'success' => true,
        'late_fee' => $result['late_fee'],
        'message' => $result['message'],
        'grace_period' => $result['grace_period'] ?? 0,
        'days_late' => $result['days_late'] ?? 0,
        'effective_days_late' => $result['effective_days_late'] ?? 0,
        'rule_id' => $result['rule_id'] ?? null,
        'fee_type' => $result['fee_type'] ?? null,
        'fee_value' => $result['fee_value'] ?? null,
        'max_fee' => $result['max_fee'] ?? null
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}