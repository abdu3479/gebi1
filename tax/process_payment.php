<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = translate('unauthorized_access');
    redirect('payments.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = translate('database_error') . ': ' . sanitize($e->getMessage());
    redirect('payments.php');
}

// Function to validate CSRF token
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        $error = translate('invalid_csrf_token');
        logAction(null, $_SESSION['user_id'] ?? 0, "CSRF token validation failed: $error", null, 'security');
        $_SESSION['error_message'] = $error;
        redirect('payments.php');
    }
}

/**
 * Update assessment status to paid if fully paid
 * @param mysqli $conn Database connection
 * @param int $assessment_id Assessment ID to check
 */
function updateAssessmentStatusAfterPayment($conn, $assessment_id) {
    $conn->begin_transaction();
    try {
        // Check if assessment is fully paid
        $stmt = $conn->prepare("
            SELECT ta.tax_amount, ta.status, COALESCE(SUM(p.amount), 0) AS total_paid
            FROM tax_assessments ta
            LEFT JOIN payment p ON ta.assessment_id = p.assessment_id
            WHERE ta.assessment_id = ? AND ta.status IN ('pending', 'overdue')
            GROUP BY ta.tax_amount, ta.status
        ");
        $stmt->bind_param("i", $assessment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if ($data && $data['total_paid'] >= $data['tax_amount']) {
            $new_status = 'paid';
            $stmt = $conn->prepare("UPDATE tax_assessments SET status = ? WHERE assessment_id = ?");
            $stmt->bind_param("si", $new_status, $assessment_id);
            if ($stmt->execute()) {
                logAction($conn, $_SESSION['user_id'] ?? 0, "Auto-updated assessment ID $assessment_id to $new_status after payment", $assessment_id, 'tax_assessments');
            } else {
                throw new Exception(translate('error_updating_status'));
            }
            $stmt->close();
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating assessment status after payment: " . $e->getMessage());
        throw $e;
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    validateCsrfToken($_GET['csrf_token'] ?? '');

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT assessment_id FROM payment WHERE payment_id = ?");
        $stmt->bind_param("i", $_GET['delete']);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment = $result->fetch_assoc();
        $stmt->close();

        if (!$payment) {
            throw new Exception(translate('payment_not_found'));
        }

        $assessment_id = $payment['assessment_id'];

        $stmt = $conn->prepare("DELETE FROM payment WHERE payment_id = ?");
        $stmt->bind_param("i", $_GET['delete']);
        if ($stmt->execute()) {
            logAction($conn, $_SESSION['user_id'] ?? 0, "Deleted payment ID {$_GET['delete']}", $_GET['delete'], 'payment');
            updateAssessmentStatusAfterPayment($conn, $assessment_id);
            $conn->commit();
            $_SESSION['success_message'] = translate('payment_deleted');
        } else {
            throw new Exception(translate('error_deleting_payment'));
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    }
    redirect('payments.php');
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');

    // Sanitize and validate inputs
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
    $assessment_id = filter_input(INPUT_POST, 'assessment_id', FILTER_VALIDATE_INT);
    $tax_assessment_id = filter_input(INPUT_POST, 'tax_assessment_id', FILTER_SANITIZE_STRING);
    $tax_assessment_id = $tax_assessment_id !== false ? $tax_assessment_id : null;
    $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);
    $payment_date = $payment_date !== false ? $payment_date : date('Y-m-d');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $receipt_number = filter_input(INPUT_POST, 'receipt_number', FILTER_SANITIZE_STRING);
    $received_by = filter_input(INPUT_POST, 'received_by', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $notes = $notes !== false ? $notes : null;

    // Validate required fields
    $required_fields = [
        'assessment_id' => $assessment_id,
        'payment_date' => $payment_date,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'receipt_number' => $receipt_number,
        'received_by' => $received_by
    ];

    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            $_SESSION['error_message'] = translate('required_fields_missing');
            redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
    }

    // Validate payment date
    $payment_date_obj = DateTime::createFromFormat('Y-m-d', $payment_date);
    $today = new DateTime();
    if (!$payment_date_obj || $payment_date_obj > $today) {
        $_SESSION['error_message'] = translate('invalid_payment_date');
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate amount
    if ($amount <= 0 || $amount > 10000000) {
        $_SESSION['error_message'] = translate('invalid_payment_amount');
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate payment method
    $valid_methods = ['cash', 'bank', 'mobile'];
    if (!in_array($payment_method, $valid_methods)) {
        $_SESSION['error_message'] = translate('invalid_payment_method');
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate assessment exists and is not paid
    $stmt = $conn->prepare("SELECT tax_amount, status, COALESCE(SUM(p.amount), 0) AS total_paid 
                           FROM tax_assessments ta 
                           LEFT JOIN payment p ON ta.assessment_id = p.assessment_id 
                           WHERE ta.assessment_id = ? 
                           GROUP BY ta.tax_amount, ta.status");
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessment = $result->fetch_assoc();
    $stmt->close();

    if (!$assessment) {
        $_SESSION['error_message'] = translate('invalid_assessment');
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($assessment['status'] === 'paid') {
        $_SESSION['error_message'] = translate('assessment_already_paid');
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate payment does not exceed remaining balance
    $remaining_balance = $assessment['tax_amount'] - $assessment['total_paid'];
    if ($edit_id) {
        $stmt = $conn->prepare("SELECT amount FROM payment WHERE payment_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_payment = $result->fetch_assoc();
        $stmt->close();
        if ($old_payment) {
            $remaining_balance += $old_payment['amount'];
        }
    }
    if ($amount > $remaining_balance) {
        $_SESSION['error_message'] = translate('payment_exceeds_balance') . ': ' . formatCurrency($remaining_balance);
        redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate receipt number uniqueness
    // Validate receipt number uniqueness
$stmt = $conn->prepare("SELECT payment_id FROM payment WHERE receipt_number = ? AND payment_id != ?");
$receipt_number_bind = $receipt_number;
$edit_id_bind = $edit_id ?: 0;
$stmt->bind_param("si", $receipt_number_bind, $edit_id_bind);
$stmt->execute();
$result = $stmt->get_result();
if ($result->fetch_assoc()) {
    $_SESSION['error_message'] = translate('receipt_number_exists');
    redirect('payments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
}
$stmt->close();

    // Begin transaction
    $conn->begin_transaction();
    try {
        if ($edit_id) {
            // Update existing payment
            $stmt = $conn->prepare("
                UPDATE payment SET 
                    tax_assessment_id = ?, 
                    assessment_id = ?, 
                    payment_date = ?, 
                    amount = ?, 
                    payment_method = ?, 
                    receipt_number = ?, 
                    received_by = ?, 
                    notes = ?
                WHERE payment_id = ?
            ");
            
            // Create bind variables
            $bind_tax_assessment_id = $tax_assessment_id;
            $bind_assessment_id = $assessment_id;
            $bind_payment_date = $payment_date;
            $bind_amount = $amount;
            $bind_payment_method = $payment_method;
            $bind_receipt_number = $receipt_number;
            $bind_received_by = $received_by;
            $bind_notes = $notes;
            $bind_edit_id = $edit_id;
            
            $stmt->bind_param("sisdssssi", 
                $bind_tax_assessment_id, 
                $bind_assessment_id, 
                $bind_payment_date, 
                $bind_amount, 
                $bind_payment_method, 
                $bind_receipt_number, 
                $bind_received_by, 
                $bind_notes, 
                $bind_edit_id
            );
        } else {
            // Insert new payment
            $stmt = $conn->prepare("
                INSERT INTO payment (tax_assessment_id, assessment_id, payment_date, amount, payment_method, receipt_number, received_by, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Create bind variables
            $bind_tax_assessment_id = $tax_assessment_id;
            $bind_assessment_id = $assessment_id;
            $bind_payment_date = $payment_date;
            $bind_amount = $amount;
            $bind_payment_method = $payment_method;
            $bind_receipt_number = $receipt_number;
            $bind_received_by = $received_by;
            $bind_notes = $notes;
            
            $stmt->bind_param("sisdssss", 
                $bind_tax_assessment_id, 
                $bind_assessment_id, 
                $bind_payment_date, 
                $bind_amount, 
                $bind_payment_method, 
                $bind_receipt_number, 
                $bind_received_by, 
                $bind_notes
            );
        }

        if ($stmt->execute()) {
            $payment_id = $edit_id ?: $conn->insert_id;
            $action = $edit_id ? "Updated payment ID $payment_id" : "Recorded payment ID $payment_id";
            logAction($conn, $_SESSION['user_id'] ?? 0, "$action for assessment ID $assessment_id", $payment_id, 'payment');

            updateAssessmentStatusAfterPayment($conn, $assessment_id);

            $conn->commit();
            $_SESSION['success_message'] = $edit_id ? translate('payment_updated') : translate('payment_recorded');
        } else {
            throw new Exception($edit_id ? translate('error_updating_payment') : translate('error_recording_payment'));
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    redirect('payments.php');
}

// If no valid action
$_SESSION['error_message'] = translate('invalid_request');
redirect('payments.php');
?>