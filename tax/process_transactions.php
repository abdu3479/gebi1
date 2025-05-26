<?php
session_start();
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and authorized
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_messages'][] = translate('unauthorized_access');
    redirect('transactions.php');
}

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_messages'][] = translate('database_error') . ": " . sanitize($e->getMessage());
    redirect('transactions.php');
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && isset($_GET['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $_SESSION['error_messages'][] = translate('invalid_csrf_token');
        redirect('transactions.php');
    }

    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['success_messages'][] = translate('transaction_deleted_successfully');
        } else {
            $_SESSION['error_messages'][] = translate('failed_to_delete_transaction');
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_deleting_transaction') . ": " . sanitize($e->getMessage());
    }
    redirect('transactions.php');
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_messages'][] = translate('invalid_csrf_token');
        redirect('transactions.php' . (isset($_POST['edit_id']) ? '?edit=' . (int)$_POST['edit_id'] : ''));
    }

    // Sanitize and validate inputs
    $taxpayer_id = filter_input(INPUT_POST, 'taxpayer_id', FILTER_VALIDATE_INT);
    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT) ?: null;
    $good_id = filter_input(INPUT_POST, 'good_id', FILTER_VALIDATE_INT);
    $transaction_type = filter_input(INPUT_POST, 'transaction_type', FILTER_SANITIZE_STRING);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
    $unit_price = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
    $transaction_date = filter_input(INPUT_POST, 'transaction_date', FILTER_SANITIZE_STRING);
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

    // Basic validation
    if (!$taxpayer_id || !$good_id || !in_array($transaction_type, ['sale', 'purchase']) || $quantity === false || $unit_price === false || !$transaction_date) {
        $_SESSION['error_messages'][] = translate('all_fields_required');
        redirect('transactions.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    try {
        // Verify taxpayer exists
        $stmt = $conn->prepare("SELECT id, taxpayer_category FROM taxpayers WHERE id = ?");
        $stmt->bind_param("i", $taxpayer_id);
        $stmt->execute();
        $taxpayer = $stmt->get_result()->fetch_assoc();
        if (!$taxpayer) {
            $_SESSION['error_messages'][] = translate('invalid_taxpayer');
            redirect('transactions.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();

        // Verify room exists (if provided)
        if ($room_id) {
            $stmt = $conn->prepare("SELECT id, taxpayer_id FROM rooms WHERE id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $room = $stmt->get_result()->fetch_assoc();
            if (!$room || ($room['taxpayer_id'] && $room['taxpayer_id'] != $taxpayer_id)) {
                $_SESSION['error_messages'][] = translate('invalid_room_taxpayer_mismatch');
                redirect('transactions.php' . ($edit_id ? '?edit=' . $edit_id : ''));
            }
            $stmt->close();
        }

        // Verify good exists
        $stmt = $conn->prepare("SELECT id, tot_rate, is_vat_exempt FROM goods WHERE id = ?");
        $stmt->bind_param("i", $good_id);
        $stmt->execute();
        $good = $stmt->get_result()->fetch_assoc();
        if (!$good) {
            $_SESSION['error_messages'][] = translate('invalid_good');
            redirect('transactions.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();

        // Calculate total and tax amounts
        $total_amount = $quantity * $unit_price;
        $tax_amount = 0;
        if ($taxpayer['taxpayer_category'] === 'A' && !$good['is_vat_exempt']) {
            $tax_amount = $total_amount * 0.15; // 15% VAT
        } elseif (in_array($taxpayer['taxpayer_category'], ['B', 'C'])) {
            $tax_amount = $total_amount * ($good['tot_rate'] / 100); // TOT
        }

        if ($edit_id) {
            // Update transaction
            $stmt = $conn->prepare("UPDATE transactions SET taxpayer_id = ?, room_id = ?, good_id = ?, transaction_type = ?, quantity = ?, unit_price = ?, total_amount = ?, tax_amount = ?, transaction_date = ? WHERE id = ?");
            $stmt->bind_param("iiisidddsi", $taxpayer_id, $room_id, $good_id, $transaction_type, $quantity, $unit_price, $total_amount, $tax_amount, $transaction_date, $edit_id);
            if ($stmt->execute()) {
                $_SESSION['success_messages'][] = translate('transaction_updated_successfully');
            } else {
                $_SESSION['error_messages'][] = translate('failed_to_update_transaction');
            }
        } else {
            // Add new transaction
            $stmt = $conn->prepare("INSERT INTO transactions (taxpayer_id, room_id, good_id, transaction_type, quantity, unit_price, total_amount, tax_amount, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisidds", $taxpayer_id, $room_id, $good_id, $transaction_type, $quantity, $unit_price, $total_amount, $tax_amount, $transaction_date);
            if ($stmt->execute()) {
                $_SESSION['success_messages'][] = translate('transaction_added_successfully');
            } else {
                $_SESSION['error_messages'][] = translate('failed_to_add_transaction');
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_processing_transaction') . ": " . sanitize($e->getMessage());
    }
    redirect('transactions.php');
}

$_SESSION['error_messages'][] = translate('invalid_request');
redirect('transactions.php');
?>