<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = translate('unauthorized_access');
    redirect('rent_contracts.php');
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['error_message'] = translate('invalid_csrf_token');
    redirect('rent_contracts.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = translate('database_error') . ": " . sanitize($e->getMessage());
    redirect('rent_contracts.php');
}

// Handle Status Change
if (isset($_GET['change_status']) && is_numeric($_GET['change_status']) && isset($_GET['new_status']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $contract_id = (int)$_GET['change_status'];
    $new_status = sanitize($_GET['new_status']);
    $allowed_statuses = ['pending', 'active', 'terminated', 'completed'];

    if (!in_array($new_status, $allowed_statuses)) {
        $_SESSION['error_message'] = translate('invalid_status');
        redirect('rent_contracts.php');
    }

    $stmt = $conn->prepare("SELECT room_id, status, start_date, end_date FROM rent_contracts WHERE contract_id = ?");
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contract = $result->fetch_assoc();
    $stmt->close();

    if (!$contract) {
        $_SESSION['error_message'] = translate('contract_not_found');
        redirect('rent_contracts.php');
    }

    if ($contract['status'] === $new_status) {
        $_SESSION['error_message'] = translate('contract_already_in_status');
        redirect('rent_contracts.php');
    }

    // Validate manual status change against date-based logic
    $today = date('Y-m-d');
    $expected_status = $contract['status'];
    if ($contract['status'] !== 'terminated') {
        if ($contract['start_date'] > $today) {
            $expected_status = 'pending';
        } elseif ($contract['start_date'] <= $today && $contract['end_date'] >= $today) {
            $expected_status = 'active';
        } elseif ($contract['end_date'] < $today) {
            $expected_status = 'completed';
        }
    }

    // Allow termination at any time, but restrict other changes to align with dates
    if ($new_status !== 'terminated' && $new_status !== $expected_status) {
        $_SESSION['error_message'] = translate('invalid_status') . ": " . translate('status_must_match_dates');
        redirect('rent_contracts.php');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE rent_contracts SET status = ? WHERE contract_id = ?");
        $stmt->bind_param("si", $new_status, $contract_id);
        $stmt->execute();
        $stmt->close();

        $is_occupied = ($new_status === 'active') ? 1 : 0;
        $stmt = $conn->prepare("UPDATE rooms SET is_occupied = ? WHERE room_id = ?");
        $stmt->bind_param("ii", $is_occupied, $contract['room_id']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['success_message'] = translate('contract_status_updated') . " " . translate($new_status) . ".";
        logAction($conn, $_SESSION['user_id'], "Changed contract status to $new_status", $contract_id, 'rent_contract');
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = translate('failed_to_update_status') . ": " . sanitize($e->getMessage());
    }
    redirect('rent_contracts.php');
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $contract_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("SELECT room_id FROM rent_contracts WHERE contract_id = ?");
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contract = $result->fetch_assoc();
    $stmt->close();

    if ($contract) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM rent_contracts WHERE contract_id = ?");
            $stmt->bind_param("i", $contract_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE rooms SET is_occupied = 0 WHERE room_id = ?");
            $stmt->bind_param("i", $contract['room_id']);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION['success_message'] = translate('contract_deleted');
            logAction($conn, $_SESSION['user_id'], "Deleted contract", $contract_id, 'rent_contract');
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = translate('failed_to_delete_contract') . ": " . sanitize($e->getMessage());
        }
    } else {
        $_SESSION['error_message'] = translate('contract_not_found');
    }
    redirect('rent_contracts.php');
}

// Handle Form Submission (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = translate('invalid_csrf_token');
        redirect('rent_contracts.php');
    }

    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $tenant_name = filter_input(INPUT_POST, 'tenant_name', FILTER_SANITIZE_STRING);
    $tenant_phone = filter_input(INPUT_POST, 'tenant_phone', FILTER_SANITIZE_STRING);
    $tenant_id_number = filter_input(INPUT_POST, 'tenant_id_number', FILTER_SANITIZE_STRING);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
    $monthly_rent = filter_input(INPUT_POST, 'monthly_rent', FILTER_VALIDATE_FLOAT);
    $deposit_amount = filter_input(INPUT_POST, 'deposit_amount', FILTER_VALIDATE_FLOAT) ?: 0.00;
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

    // Validation
    if (!$room_id || !$tenant_name || !$start_date || !$end_date || $monthly_rent === false || $monthly_rent <= 0) {
        $_SESSION['error_message'] = translate('required_fields_missing');
        redirect('rent_contracts.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if (strtotime($start_date) >= strtotime($end_date)) {
        $_SESSION['error_message'] = translate('end_date_must_be_after_start_date');
        redirect('rent_contracts.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Verify room exists and is available
    $stmt = $conn->prepare("SELECT is_occupied FROM rooms WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    $stmt->close();

    if (!$room) {
        $_SESSION['error_message'] = translate('invalid_room_selected');
        redirect('rent_contracts.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if (!$edit_id && $room['is_occupied']) {
        $_SESSION['error_message'] = translate('room_not_available');
        redirect('rent_contracts.php');
    }

    // Check for overlapping contracts
    $sql = "SELECT contract_id FROM rent_contracts 
            WHERE room_id = ? AND contract_id != ? AND status IN ('pending', 'active')
            AND (start_date <= ? AND end_date >= ?)";
    $stmt = $conn->prepare($sql);
    $edit_id = $edit_id ?? 0;
    $stmt->bind_param("iiss", $room_id, $edit_id, $end_date, $start_date);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        $_SESSION['error_message'] = translate('overlapping_contract_exists');
        redirect('rent_contracts.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    // Set initial status based on dates
    $today = date('Y-m-d');
    $initial_status = 'pending';
    if ($start_date <= $today && $end_date >= $today) {
        $initial_status = 'active';
    } elseif ($end_date < $today) {
        $initial_status = 'completed';
    }

    $conn->begin_transaction();
    try {
        if ($edit_id) {
            // Get original room_id and status for occupancy update
            $stmt = $conn->prepare("SELECT room_id, status FROM rent_contracts WHERE contract_id = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $original = $result->fetch_assoc();
            $stmt->close();

            // Update contract
            $stmt = $conn->prepare("UPDATE rent_contracts SET room_id = ?, tenant_name = ?, tenant_phone = ?, tenant_id_number = ?, start_date = ?, end_date = ?, monthly_rent = ?, deposit_amount = ?, notes = ?, status = ? WHERE contract_id = ?");
            $stmt->bind_param("isssssddssi", $room_id, $tenant_name, $tenant_phone, $tenant_id_number, $start_date, $end_date, $monthly_rent, $deposit_amount, $notes, $initial_status, $edit_id);
            $stmt->execute();
            $stmt->close();

            // Update room occupancy if room_id or status changed
            if ($original['room_id'] !== $room_id || $original['status'] !== $initial_status) {
                if ($original['room_id'] !== $room_id) {
                    $stmt = $conn->prepare("UPDATE rooms SET is_occupied = 0 WHERE room_id = ?");
                    $stmt->bind_param("i", $original['room_id']);
                    $stmt->execute();
                    $stmt->close();
                }

                $is_occupied = ($initial_status === 'active') ? 1 : 0;
                $stmt = $conn->prepare("UPDATE rooms SET is_occupied = ? WHERE room_id = ?");
                $stmt->bind_param("ii", $is_occupied, $room_id);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            $_SESSION['success_message'] = translate('contract_updated');
            logAction($conn, $_SESSION['user_id'], "Updated contract", $edit_id, 'rent_contract');
        } else {
            // Add new contract
            $stmt = $conn->prepare("INSERT INTO rent_contracts (room_id, tenant_name, tenant_phone, tenant_id_number, start_date, end_date, monthly_rent, deposit_amount, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssddss", $room_id, $tenant_name, $tenant_phone, $tenant_id_number, $start_date, $end_date, $monthly_rent, $deposit_amount, $notes, $initial_status);
            $stmt->execute();
            $new_contract_id = $conn->insert_id;
            $stmt->close();

            // Update room occupancy if status is active
            if ($initial_status === 'active') {
                $stmt = $conn->prepare("UPDATE rooms SET is_occupied = 1 WHERE room_id = ?");
                $stmt->bind_param("i", $room_id);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            $_SESSION['success_message'] = translate('contract_created');
            logAction($conn, $_SESSION['user_id'], "Created contract", $new_contract_id, 'rent_contract');
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = translate($edit_id ? 'failed_to_save_contract' : 'failed_to_save_contract') . ": " . sanitize($e->getMessage());
    }
    redirect('rent_contracts.php' . ($edit_id ? '?edit=' . $edit_id : ''));
}

// Invalid Request
$_SESSION['error_message'] = translate('invalid_request');
redirect('rent_contracts.php');
?>