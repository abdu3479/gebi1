<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = translate('unauthorized_access');
    redirect('rental_tax.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = translate('error_processing_assessment') . ': ' . sanitize($e->getMessage());
    redirect('rental_tax.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract_id = filter_input(INPUT_POST, 'contract_id', FILTER_VALIDATE_INT);
    $rate_id = filter_input(INPUT_POST, 'rate_id', FILTER_VALIDATE_INT);
    $assessment_date = filter_input(INPUT_POST, 'assessment_date', FILTER_SANITIZE_STRING);
    $period_month = filter_input(INPUT_POST, 'period_month', FILTER_VALIDATE_INT);
    $fiscal_year = filter_input(INPUT_POST, 'fiscal_year', FILTER_VALIDATE_INT);
    $assessed_value = filter_input(INPUT_POST, 'assessed_value', FILTER_VALIDATE_FLOAT);
    $tax_amount = filter_input(INPUT_POST, 'tax_amount', FILTER_VALIDATE_FLOAT);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    // Validate inputs
    if (!$contract_id || !$rate_id || !$assessment_date || !$period_month || !$fiscal_year || $assessed_value === false || $tax_amount === false) {
        $_SESSION['error_message'] = translate('invalid_form_data');
        redirect('rental_tax.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($period_month < 1 || $period_month > 12) {
        $_SESSION['error_message'] = translate('invalid_period_month');
        redirect('rental_tax.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($fiscal_year < 2000 || $fiscal_year > 2100) {
        $_SESSION['error_message'] = translate('invalid_fiscal_year');
        redirect('rental_tax.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($assessed_value <= 0 || $tax_amount <= 0) {
        $_SESSION['error_message'] = translate('invalid_assessed_value');
        redirect('rental_tax.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Verify contract and rate
    $stmt = $conn->prepare("SELECT status FROM rent_contracts WHERE contract_id = ?");
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contract = $result->fetch_assoc();
    $stmt->close();

    if (!$contract || $contract['status'] != 'active') {
        $_SESSION['error_message'] = translate('invalid_contract');
        redirect('rental_tax.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    $stmt = $conn->prepare("SELECT rate_percentage FROM tax_rates WHERE rate_id = ? AND applicable_to = 'rental' AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
    $stmt->bind_param("i", $rate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rate = $result->fetch_assoc();
    $stmt->close();

    if (!$rate) {
        $_SESSION['error_message'] = translate('invalid_tax_rate');
        redirect('rental_tax.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Verify tax_amount
    $expected_tax = $assessed_value * ($rate['rate_percentage'] / 100);
    if (abs($tax_amount - $expected_tax) > 0.01) {
        $_SESSION['error_message'] = translate('invalid_tax_amount');
        redirect('rental_tax.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Check for duplicate assessment
    $stmt = $conn->prepare("SELECT assessment_id FROM tax_assessments WHERE contract_id = ? AND period_month = ? AND fiscal_year = ? AND assessment_id != ?");
    $edit_id = $edit_id ?? 0;
    $stmt->bind_param("iiii", $contract_id, $period_month, $fiscal_year, $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        $_SESSION['error_message'] = translate('duplicate_assessment');
        $stmt->close();
        redirect('rental_tax.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    try {
        if ($edit_id) {
            $stmt = $conn->prepare("UPDATE tax_assessments SET contract_id = ?, rate_id = ?, assessment_date = ?, period_month = ?, fiscal_year = ?, assessed_value = ?, tax_amount = ?, notes = ? WHERE assessment_id = ? AND is_rental = TRUE");
            $stmt->bind_param("iisiiddsi", $contract_id, $rate_id, $assessment_date, $period_month, $fiscal_year, $assessed_value, $tax_amount, $notes, $edit_id);
            $message = translate('assessment_updated');
        } else {
            $stmt = $conn->prepare("INSERT INTO tax_assessments (contract_id, rate_id, assessment_date, period_month, fiscal_year, assessed_value, tax_amount, notes, is_rental) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)");
            $stmt->bind_param("iisiidds", $contract_id, $rate_id, $assessment_date, $period_month, $fiscal_year, $assessed_value, $tax_amount, $notes);
            $message = translate('assessment_added');
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = $message;
            $stmt->close();
            redirect('rental_tax.php' . (isset($_GET['contract']) ? '?contract=' . (int)$_GET['contract'] : ''));
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = translate('error_processing_assessment') . ': ' . sanitize($e->getMessage());
        redirect('rental_tax.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $delete_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    $contract_id = filter_input(INPUT_GET, 'contract', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    if (!$delete_id) {
        $_SESSION['error_message'] = translate('invalid_delete_request');
        redirect('rental_tax.php' . ($contract_id ? '?contract=' . $contract_id : ''));
    }

    // Check for associated payments
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM payments WHERE assessment_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row['count'] > 0) {
        $_SESSION['error_message'] = translate('assessment_has_payments');
        redirect('rental_tax.php' . ($contract_id ? '?contract=' . $contract_id : ''));
    }

    try {
        $stmt = $conn->prepare("DELETE FROM tax_assessments WHERE assessment_id = ? AND is_rental = TRUE");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = translate('assessment_deleted');
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
        redirect('rental_tax.php' . ($contract_id ? '?contract=' . $contract_id : ''));
    } catch (Exception $e) {
        $_SESSION['error_message'] = translate('error_deleting_assessment') . ': ' . sanitize($e->getMessage());
        redirect('rental_tax.php' . ($contract_id ? '?contract=' . $contract_id : ''));
    }
}

// Handle status change
if (isset($_GET['change_status']) && isset($_GET['new_status'])) {
    $assessment_id = filter_input(INPUT_GET, 'change_status', FILTER_VALIDATE_INT);
    $new_status = filter_input(INPUT_GET, 'new_status', FILTER_SANITIZE_STRING);
    $contract_id = filter_input(INPUT_GET, 'contract', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    
    if (!$assessment_id || !in_array($new_status, ['paid', 'overdue'])) {
        $_SESSION['error_message'] = translate('invalid_status_change');
        redirect('rental_tax.php' . ($contract_id ? '?contract=' . $contract_id : ''));
    }

    try {
        $stmt = $conn->prepare("UPDATE tax_assessments SET status = ? WHERE assessment_id = ? AND is_rental = TRUE");
        $stmt->bind_param("si", $new_status, $assessment_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = translate('status_updated');
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
        redirect('rental_tax.php' . ($contract_id ? '?contract=' . $contract_id : ''));
    } catch (Exception $e) {
        $_SESSION['error_message'] = translate('error_updating_status') . ': ' . sanitize($e->getMessage());
        redirect('rental_tax.php' . ($contract_id ? '?contract=' . $contract_id : ''));
    }
}

$conn->close();
?>