<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('payments.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . sanitize($e->getMessage());
    redirect('payments.php');
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM payments WHERE payment_id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Payment deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to delete payment.";
    }
    $stmt->close();
    redirect('payments.php');
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assessment_id = filter_input(INPUT_POST, 'assessment_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?: null;
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

    // Validation
    if (!$assessment_id || !$amount || !$payment_date || !$payment_method) {
        $_SESSION['error_message'] = "All required fields must be filled.";
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate payment date
    if (!DateTime::createFromFormat('Y-m-d', $payment_date)) {
        $_SESSION['error_message'] = "Invalid payment date format.";
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate payment method
    $valid_methods = ['cash', 'credit_card', 'bank_transfer', 'mobile_payment'];
    if (!in_array($payment_method, $valid_methods)) {
        $_SESSION['error_message'] = "Invalid payment method.";
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Verify assessment exists and is not paid
    $stmt = $conn->prepare("SELECT tax_amount, status FROM tax_assessments WHERE assessment_id = ?");
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessment = $result->fetch_assoc();
    $stmt->close();

    if (!$assessment) {
        $_SESSION['error_message'] = "Invalid assessment selected.";
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($assessment['status'] == 'paid' && !$edit_id) {
        $_SESSION['error_message'] = "Cannot add payment to a fully paid assessment.";
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate amount
    if ($amount <= 0) {
        $_SESSION['error_message'] = "Amount must be greater than zero.";
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Check total payments against tax amount
    $stmt = $conn->prepare("SELECT SUM(amount) AS total_paid FROM payments WHERE assessment_id = ? AND payment_id != ?");
    $edit_id = $edit_id ?? 0;
    $stmt->bind_param("ii", $assessment_id, $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_paid = $result->fetch_assoc()['total_paid'] ?? 0;
    $stmt->close();

    if (($total_paid + $amount) > $assessment['tax_amount']) {
        $_SESSION['error_message'] = "Total payment amount exceeds the tax amount.";
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($edit_id) {
        // Update payment
        $stmt = $conn->prepare("UPDATE payment SET assessment_id = ?, amount = ?, payment_date = ?, payment_method = ?, notes = ? WHERE payment_id = ?");
        $stmt->bind_param("idsssi", $assessment_id, $amount, $payment_date, $payment_method, $notes, $edit_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Payment updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update payment.";
        }
    } else {
        // Add new payment
        $stmt = $conn->prepare("INSERT INTO payment (assessment_id, amount, payment_date, payment_method, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("idsss", $assessment_id, $amount, $payment_date, $payment_method, $notes);
        if ($stmt->execute()) {
            // Update assessment status if fully paid
            $new_total_paid = $total_paid + $amount;
            if (abs($new_total_paid - $assessment['tax_amount']) < 0.01) {
                $stmt = $conn->prepare("UPDATE tax_assessments SET status = 'paid' WHERE assessment_id = ?");
                $stmt->bind_param("i", $assessment_id);
                $stmt->execute();
                $stmt->close();
            }
            $_SESSION['success_message'] = "Payment added successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to add payment.";
        }
    }
    $stmt->close();
    redirect('payments.php');
}
?>