<?php
session_start();
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and authorized
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_messages'][] = translate('unauthorized_access');
    redirect('rooms.php');
}

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_messages'][] = translate('database_error') . ": " . sanitize($e->getMessage());
    redirect('rooms.php');
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && isset($_GET['csrf_token'])) {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $_SESSION['error_messages'][] = translate('invalid_csrf_token');
        redirect('rooms.php');
    }

    $delete_id = (int)$_GET['delete'];
    try {
        // Check if room has associated transactions or contracts
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM transactions WHERE room_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        if ($count > 0) {
            $_SESSION['error_messages'][] = translate('cannot_delete_room_with_transactions');
            redirect('rooms.php');
        }

        // Delete room
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['success_messages'][] = translate('room_deleted_successfully');
        } else {
            $_SESSION['error_messages'][] = translate('failed_to_delete_room');
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_deleting_room') . ": " . sanitize($e->getMessage());
    }
    redirect('rooms.php');
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_messages'][] = translate('invalid_csrf_token');
        redirect('rooms.php' . (isset($_POST['edit_id']) ? '?edit=' . (int)$_POST['edit_id'] : ''));
    }

    // Sanitize and validate inputs
    $building_id = filter_input(INPUT_POST, 'building_id', FILTER_VALIDATE_INT);
    $type_id = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
    $taxpayer_id = filter_input(INPUT_POST, 'taxpayer_id', FILTER_VALIDATE_INT) ?: null;
    $room_number = filter_input(INPUT_POST, 'room_number', FILTER_SANITIZE_STRING);
    $floor_number = filter_input(INPUT_POST, 'floor_number', FILTER_VALIDATE_INT);
    $area = filter_input(INPUT_POST, 'area', FILTER_VALIDATE_FLOAT);
    $monthly_rent = filter_input(INPUT_POST, 'monthly_rent', FILTER_VALIDATE_FLOAT);
    $tax_rate = filter_input(INPUT_POST, 'tax_rate', FILTER_VALIDATE_FLOAT);
    $is_occupied = isset($_POST['is_occupied']) ? 1 : 0;
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

    // Basic validation
    if (!$building_id || !$type_id || !$room_number || $floor_number === false || $area === false || $monthly_rent === false || $tax_rate === false) {
        $_SESSION['error_messages'][] = translate('all_fields_required');
        redirect('rooms.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    try {
        // Verify building exists
        $stmt = $conn->prepare("SELECT id FROM buildings WHERE id = ?");
        $stmt->bind_param("i", $building_id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $_SESSION['error_messages'][] = translate('invalid_building');
            redirect('rooms.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();

        // Verify room type exists
        $stmt = $conn->prepare("SELECT id FROM room_types WHERE id = ?");
        $stmt->bind_param("i", $type_id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $_SESSION['error_messages'][] = translate('invalid_room_type');
            redirect('rooms.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();

        // Verify taxpayer exists (if provided)
        if ($taxpayer_id) {
            $stmt = $conn->prepare("SELECT id FROM taxpayers WHERE id = ?");
            $stmt->bind_param("i", $taxpayer_id);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $_SESSION['error_messages'][] = translate('invalid_taxpayer');
                redirect('rooms.php' . ($edit_id ? '?edit=' . $edit_id : ''));
            }
            $stmt->close();
        }

        // Check for duplicate room number in the same building
        $sql = "SELECT id FROM rooms WHERE building_id = ? AND room_number = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $effective_edit_id = $edit_id ?? 0;
        $stmt->bind_param("isi", $building_id, $room_number, $effective_edit_id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $_SESSION['error_messages'][] = translate('room_number_exists');
            redirect('rooms.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();

        // Calculate tax amount
        $tax_amount = $monthly_rent * ($tax_rate / 100);

        if ($edit_id) {
            // Update room
            $stmt = $conn->prepare("UPDATE rooms SET building_id = ?, type_id = ?, taxpayer_id = ?, room_number = ?, floor_number = ?, area = ?, monthly_rent = ?, tax_rate = ?, tax_amount = ?, is_occupied = ? WHERE id = ?");
            $stmt->bind_param("iiisidddii", $building_id, $type_id, $taxpayer_id, $room_number, $floor_number, $area, $monthly_rent, $tax_rate, $tax_amount, $is_occupied, $edit_id);
            if ($stmt->execute()) {
                $_SESSION['success_messages'][] = translate('room_updated_successfully');
            } else {
                $_SESSION['error_messages'][] = translate('failed_to_update_room');
            }
        } else {
            // Add new room
            $stmt = $conn->prepare("INSERT INTO rooms (building_id, type_id, taxpayer_id, room_number, floor_number, area, monthly_rent, tax_rate, tax_amount, is_occupied) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisiddddi", $building_id, $type_id, $taxpayer_id, $room_number, $floor_number, $area, $monthly_rent, $tax_rate, $tax_amount, $is_occupied);
            if ($stmt->execute()) {
                $_SESSION['success_messages'][] = translate('room_added_successfully');
            } else {
                $_SESSION['error_messages'][] = translate('failed_to_add_room');
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_processing_room') . ": " . sanitize($e->getMessage());
    }
    redirect('rooms.php');
}

$_SESSION['error_messages'][] = translate('invalid_request');
redirect('rooms.php');
?>