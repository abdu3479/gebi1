<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('rent_contracts.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . sanitize($e->getMessage());
    redirect('rent_contracts.php');
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $contract_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT room_id FROM rent_contracts WHERE contract_id = ?");
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        $room_id = $row['room_id'];
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM rent_contracts WHERE contract_id = ?");
            $stmt->bind_param("i", $contract_id);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $conn->prepare("UPDATE rooms SET is_occupied = 0 WHERE room_id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            $_SESSION['success_message'] = "Contract deleted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to delete contract: " . sanitize($e->getMessage());
        }
    } else {
        $_SESSION['error_message'] = "Contract not found.";
    }
    redirect('rent_contracts.php');
}

// Handle status change
if (isset($_GET['change_status']) && is_numeric($_GET['change_status']) && isset($_GET['new_status'])) {
    $contract_id = (int)$_GET['change_status'];
    $validStatuses = ['active', 'terminated', 'completed'];
    $new_status = in_array($_GET['new_status'], $validStatuses) ? $_GET['new_status'] : null;
    
    if (!$new_status) {
        $_SESSION['error_message'] = "Invalid status.";
        redirect('rent_contracts.php');
    }
    
    $stmt = $conn->prepare("SELECT room_id, status FROM rent_contracts WHERE contract_id = ?");
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        $room_id = $row['room_id'];
        $current_status = $row['status'];
        
        if ($current_status === $new_status) {
            $_SESSION['error_message'] = "Contract is already in the selected status.";
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
            $stmt->bind_param("ii", $is_occupied, $room_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            $_SESSION['success_message'] = "Contract status updated to " . translate($new_status) . ".";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to update status: " . sanitize($e->getMessage());
        }
    } else {
        $_SESSION['error_message'] = "Contract not found.";
    }
    redirect('rent_contracts.php');
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $tenant_name = filter_input(INPUT_POST, 'tenant_name', FILTER_SANITIZE_STRING);
    $tenant_phone = filter_input(INPUT_POST, 'tenant_phone', FILTER_SANITIZE_STRING);
    $tenant_id_number = filter_input(INPUT_POST, 'tenant_id_number', FILTER_SANITIZE_STRING);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
    $monthly_rent = filter_input(INPUT_POST, 'monthly_rent', FILTER_VALIDATE_FLOAT);
    $deposit_amount = filter_input(INPUT_POST, 'deposit_amount', FILTER_VALIDATE_FLOAT);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

    // Validation
    if (!$room_id || !$tenant_name || !$start_date || !$end_date || $monthly_rent === false) {
        $_SESSION['error_message'] = "All required fields must be filled correctly.";
        redirect('rent_contracts.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate dates
    if (strtotime($start_date) >= strtotime($end_date)) {
        $_SESSION['error_message'] = "End date must be after start date.";
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
        $_SESSION['error_message'] = "Invalid room selected.";
        redirect('rent_contracts.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    
    if (!$edit_id && $room['is_occupied']) {
        $_SESSION['error_message'] = "Selected room is already occupied.";
        redirect('rent_contracts.php');
    }

    // Check for overlapping contracts
    $sql = "SELECT contract_id FROM rent_contracts 
            WHERE room_id = ? AND contract_id != ? AND status = 'active' 
            AND (start_date <= ? AND end_date >= ?)";
    $stmt = $conn->prepare($sql);
    $edit_id = $edit_id ?? 0;
    $stmt->bind_param("iiss", $room_id, $edit_id, $end_date, $start_date);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        $_SESSION['error_message'] = "Another active contract exists for this room during the selected period.";
        redirect('rent_contracts.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    $conn->begin_transaction();
    try {
        if ($edit_id) {
            // Update contract
            $stmt = $conn->prepare("UPDATE rent_contracts SET room_id = ?, tenant_name = ?, tenant_phone = ?, tenant_id_number = ?, start_date = ?, end_date = ?, monthly_rent = ?, deposit_amount = ?, notes = ? WHERE contract_id = ?");
            $stmt->bind_param("isssssddsi", $room_id, $tenant_name, $tenant_phone, $tenant_id_number, $start_date, $end_date, $monthly_rent, $deposit_amount, $notes, $edit_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success_message'] = "Contract updated successfully.";
        } else {
            // Add new contract
            $stmt = $conn->prepare("INSERT INTO rent_contracts (room_id, tenant_name, tenant_phone, tenant_id_number, start_date, end_date, monthly_rent, deposit_amount, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("isssssdds", $room_id, $tenant_name, $tenant_phone, $tenant_id_number, $start_date, $end_date, $monthly_rent, $deposit_amount, $notes);
            $stmt->execute();
            $stmt->close();
            
            // Mark room as occupied
            $stmt = $conn->prepare("UPDATE rooms SET is_occupied = 1 WHERE room_id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success_message'] = "Contract added successfully.";
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Failed to save contract: " . sanitize($e->getMessage());
    }
    redirect('rent_contracts.php');
}
?>