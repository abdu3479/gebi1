<?php
session_start();
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Authentication
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_messages'][] = translate('unauthorized_access');
    redirect('index.php');
}

// Database Connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    logError($e);
    $_SESSION['error_messages'][] = translate('database_error');
    redirect('vehicle_tax.php');
}

// Handle Delete Operation
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    $csrfToken = $_GET['csrf_token'] ?? '';
    
    if (!$id || $csrfToken !== $_SESSION['csrf_token']) {
        $_SESSION['error_messages'][] = translate('invalid_request');
        redirect('vehicle_tax.php');
    }

    try {
        $stmt = $conn->prepare("DELETE FROM vehicle_taxes WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_messages'][] = translate('tax_record_deleted');
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('delete_failed');
    }
    
    redirect('vehicle_tax.php');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_messages'][] = translate('csrf_validation_failed');
        redirect('vehicle_tax.php');
    }

    $action = 'create';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    try {
        $data = [
            'plate_number' => sanitize($_POST['plate_number'] ?? ''),
            'owner_name' => sanitize($_POST['owner_name'] ?? ''),
            'tax_year' => filter_input(INPUT_POST, 'tax_year', FILTER_VALIDATE_INT),
            'tax_amount' => filter_input(INPUT_POST, 'tax_amount', FILTER_VALIDATE_FLOAT),
            'payment_status' => in_array($_POST['payment_status'], ['paid', 'pending', 'overdue']) 
                ? $_POST['payment_status'] 
                : 'pending',
            'engine_number' => sanitize($_POST['engine_number'] ?? ''),
            'chassis_number' => sanitize($_POST['chassis_number'] ?? ''),
            'user_id' => $_SESSION['user_id']
        ];

        if ($id) {
            // Update existing record
            $stmt = $conn->prepare("
                UPDATE vehicle_taxes SET 
                plate_number = ?, owner_name = ?, tax_year = ?, tax_amount = ?, 
                payment_status = ?, engine_number = ?, chassis_number = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $data[] = $id;
            $action = 'update';
        } else {
            // Create new record
            $stmt = $conn->prepare("
                INSERT INTO vehicle_taxes (
                    plate_number, owner_name, tax_year, tax_amount, payment_status, 
                    engine_number, chassis_number, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
        }

        $stmt->execute(array_values($data));
        
        if ($action === 'create') {
            $_SESSION['success_messages'][] = translate('tax_record_created');
        } else {
            $_SESSION['success_messages'][] = translate('tax_record_updated');
        }
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('operation_failed');
    }
}

redirect('vehicle_tax.php');