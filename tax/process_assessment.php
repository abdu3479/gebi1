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
    redirect('assessments.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = translate('database_error') . ': ' . sanitize($e->getMessage());
    redirect('assessments.php');
}

// Function to validate CSRF token
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        $error = translate('invalid_csrf_token');
        logAction(null, $_SESSION['user_id'] ?? 0, "CSRF token validation failed: $error", null, 'security');
        $_SESSION['error_message'] = $error;
        redirect('assessments.php');
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    validateCsrfToken($_GET['csrf_token'] ?? '');

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT assessment_id FROM tax_assessments WHERE assessment_id = ?");
        $stmt->bind_param("i", $_GET['delete']);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            throw new Exception(translate('assessment_not_found'));
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM tax_assessments WHERE assessment_id = ?");
        $stmt->bind_param("i", $_GET['delete']);
        if ($stmt->execute()) {
            logAction($conn, $_SESSION['user_id'] ?? 0, "Deleted assessment ID {$_GET['delete']}", $_GET['delete'], 'tax_assessments');
            $conn->commit();
            $_SESSION['success_message'] = translate('assessment_deleted');
        } else {
            throw new Exception(translate('error_deleting_assessment'));
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    }
    redirect('assessments.php');
}

// Handle assessment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');

    // Sanitize and validate inputs
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
    $property_id = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
    $building_id = filter_input(INPUT_POST, 'building_id', FILTER_VALIDATE_INT) ?: null;
    $rate_id = filter_input(INPUT_POST, 'rate_id', FILTER_VALIDATE_INT);
    $assessment_date = filter_input(INPUT_POST, 'assessment_date', FILTER_SANITIZE_STRING);
    $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);
    $assessed_value = filter_input(INPUT_POST, 'assessed_value', FILTER_VALIDATE_FLOAT);
    $tax_amount = filter_input(INPUT_POST, 'tax_amount', FILTER_VALIDATE_FLOAT);
    $fiscal_year = filter_input(INPUT_POST, 'fiscal_year', FILTER_VALIDATE_INT);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?: null;

    // Validate required fields
    $required_fields = [
        'property_id' => $property_id,
        'rate_id' => $rate_id,
        'assessment_date' => $assessment_date,
        'due_date' => $due_date,
        'assessed_value' => $assessed_value,
        'tax_amount' => $tax_amount,
        'fiscal_year' => $fiscal_year
    ];

    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            $_SESSION['error_message'] = translate('required_fields_missing');
            redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
    }

    // Validate dates
    $assessment_date_obj = DateTime::createFromFormat('Y-m-d', $assessment_date);
    $due_date_obj = DateTime::createFromFormat('Y-m-d', $due_date);
    $today = new DateTime();
    if (!$assessment_date_obj || $assessment_date_obj > $today) {
        $_SESSION['error_message'] = translate('invalid_assessment_date');
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    if (!$due_date_obj || $due_date_obj < $assessment_date_obj) {
        $_SESSION['error_message'] = translate('invalid_due_date');
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate property
    $stmt = $conn->prepare("SELECT property_id FROM properties WHERE property_id = ?");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result->fetch_assoc()) {
        $_SESSION['error_message'] = translate('invalid_property');
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    // Validate building if provided
    if ($building_id) {
        $stmt = $conn->prepare("SELECT building_id FROM buildings WHERE building_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $building_id, $property_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            $_SESSION['error_message'] = translate('invalid_building');
            redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();
    }

    // Validate tax rate
    $stmt = $conn->prepare("SELECT rate_percentage, applicable_to FROM tax_rates WHERE rate_id = ? AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
    $stmt->bind_param("i", $rate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rate = $result->fetch_assoc();
    $stmt->close();
    if (!$rate) {
        $_SESSION['error_message'] = translate('invalid_tax_rate');
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate tax amount
    $expected_tax = $assessed_value * ($rate['rate_percentage'] / 100);
    if (abs($tax_amount - $expected_tax) > 0.01) {
        $_SESSION['error_message'] = translate('invalid_tax_amount');
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate fiscal year
    if ($fiscal_year < 2000 || $fiscal_year > (int)date('Y') + 1) {
        $_SESSION['error_message'] = translate('invalid_fiscal_year');
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate assessed value
    if ($assessed_value <= 0 || $assessed_value > 100000000) {
        $_SESSION['error_message'] = translate('invalid_assessed_value');
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Set status to pending for new assessments
    $status = $edit_id ? null : 'pending';

    // Begin transaction
    $conn->begin_transaction();
    try {
        if ($edit_id) {
            // Update existing assessment
            $stmt = $conn->prepare("
                UPDATE tax_assessments SET 
                    property_id = ?, 
                    building_id = ?, 
                    rate_id = ?, 
                    assessment_date = ?, 
                    due_date = ?, 
                    assessed_value = ?, 
                    tax_amount = ?, 
                    fiscal_year = ?, 
                    notes = ?
                WHERE assessment_id = ?
            ");
            $stmt->bind_param("iiisssdisi", 
                $property_id, 
                $building_id, 
                $rate_id, 
                $assessment_date, 
                $due_date, 
                $assessed_value, 
                $tax_amount, 
                $fiscal_year, 
                $notes, 
                $edit_id
            );
        } else {
            // Insert new assessment
            $stmt = $conn->prepare("
                INSERT INTO tax_assessments (property_id, building_id, rate_id, assessment_date, due_date, assessed_value, tax_amount, fiscal_year, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiisssdiss", 
                $property_id, 
                $building_id, 
                $rate_id, 
                $assessment_date, 
                $due_date, 
                $assessed_value, 
                $tax_amount, 
                $fiscal_year, 
                $status, 
                $notes
            );
        }

        if ($stmt->execute()) {
            $assessment_id = $edit_id ?: $conn->insert_id;
            $action = $edit_id ? "Updated assessment ID $assessment_id" : "Created assessment ID $assessment_id";
            logAction($conn, $_SESSION['user_id'] ?? 0, $action, $assessment_id, 'tax_assessments');
            $conn->commit();
            $_SESSION['success_message'] = $edit_id ? translate('assessment_updated') : translate('assessment_created');
        } else {
            throw new Exception($edit_id ? translate('error_updating_assessment') : translate('error_creating_assessment'));
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    redirect('assessments.php');
}

// If no valid action
$_SESSION['error_message'] = translate('invalid_request');
redirect('assessments.php');
?>