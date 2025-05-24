<?php
require_once 'config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('rooms.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . sanitize($e->getMessage());
    redirect('rooms.php');
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Room deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to delete room.";
    }
    $stmt->close();
    redirect('rooms.php');
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $building_id = filter_input(INPUT_POST, 'building_id', FILTER_VALIDATE_INT);
    $type_id = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
    $room_number = filter_input(INPUT_POST, 'room_number', FILTER_SANITIZE_STRING);
    $floor_number = filter_input(INPUT_POST, 'floor_number', FILTER_VALIDATE_INT);
    $area = filter_input(INPUT_POST, 'area', FILTER_VALIDATE_FLOAT);
    $monthly_rent = filter_input(INPUT_POST, 'monthly_rent', FILTER_VALIDATE_FLOAT);
    $is_occupied = isset($_POST['is_occupied']) ? 1 : 0;
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

    // Validation
    if (!$building_id || !$type_id || !$room_number || $floor_number === false || $area === false || $monthly_rent === false) {
        $_SESSION['error_message'] = "All required fields must be filled correctly.";
        redirect('rooms.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Verify building exists
    $stmt = $conn->prepare("SELECT building_id FROM buildings WHERE building_id = ?");
    $stmt->bind_param("i", $building_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result->fetch_assoc()) {
        $_SESSION['error_message'] = "Invalid building selected.";
        redirect('rooms.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    // Verify room type exists
    $stmt = $conn->prepare("SELECT type_id FROM room_types WHERE type_id = ?");
    $stmt->bind_param("i", $type_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result->fetch_assoc()) {
        $_SESSION['error_message'] = "Invalid room type selected.";
        redirect('rooms.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    // Check for duplicate room number in the same building
    $sql = "SELECT room_id FROM rooms WHERE building_id = ? AND room_number = ? AND room_id != ?";
    $stmt = $conn->prepare($sql);
    $edit_id = $edit_id ?? 0;
    $stmt->bind_param("isi", $building_id, $room_number, $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        $_SESSION['error_message'] = "Room number already exists in this building.";
        redirect('rooms.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    if ($edit_id) {
        // Update room
        $stmt = $conn->prepare("UPDATE rooms SET building_id = ?, type_id = ?, room_number = ?, floor_number = ?, area = ?, monthly_rent = ?, is_occupied = ? WHERE room_id = ?");
        $stmt->bind_param("iisiddii", $building_id, $type_id, $room_number, $floor_number, $area, $monthly_rent, $is_occupied, $edit_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Room updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update room.";
        }
    } else {
        // Add new room
        $stmt = $conn->prepare("INSERT INTO rooms (building_id, type_id, room_number, floor_number, area, monthly_rent, is_occupied) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisiddi", $building_id, $type_id, $room_number, $floor_number, $area, $monthly_rent, $is_occupied);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Room added successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to add room.";
        }
    }
    $stmt->close();
    redirect('rooms.php');
}
?>