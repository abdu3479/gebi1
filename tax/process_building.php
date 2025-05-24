<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('buildings.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error connecting to database: " . sanitize($e->getMessage());
    redirect('buildings.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
    $building_name = filter_input(INPUT_POST, 'building_name', FILTER_SANITIZE_STRING);
    $floor_count = filter_input(INPUT_POST, 'floor_count', FILTER_VALIDATE_INT);
    $room_count = filter_input(INPUT_POST, 'room_count', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $building_area = filter_input(INPUT_POST, 'building_area', FILTER_VALIDATE_FLOAT);
    $construction_type = filter_input(INPUT_POST, 'construction_type', FILTER_SANITIZE_STRING);
    $construction_year = filter_input(INPUT_POST, 'construction_year', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $building_type = filter_input(INPUT_POST, 'building_type', FILTER_SANITIZE_STRING);
    $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    // Validate inputs
    if (!$property_id || !$building_name || !$floor_count || $building_area === false || !$construction_type || !$building_type) {
        $_SESSION['error_message'] = "Invalid form data. Required fields are missing or invalid.";
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($floor_count < 1) {
        $_SESSION['error_message'] = "Floor count must be at least 1.";
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($room_count !== null && $room_count < 0) {
        $_SESSION['error_message'] = "Room count cannot be negative.";
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($building_area < 0) {
        $_SESSION['error_message'] = "Building area must be a positive number.";
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if (!in_array($construction_type, ['wood', 'concrete', 'mixed'])) {
        $_SESSION['error_message'] = "Invalid construction type.";
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($construction_year && ($construction_year < 1900 || $construction_year > date('Y'))) {
        $_SESSION['error_message'] = "Invalid construction year.";
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if (!in_array($building_type, ['Residential', 'Commercial', 'Mixed', 'Other'])) {
        $_SESSION['error_message'] = "Invalid building type.";
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Verify property exists
    $stmt = $conn->prepare("SELECT property_id FROM properties WHERE property_id = ?");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result->fetch_assoc()) {
        $_SESSION['error_message'] = "Invalid property selected.";
        $stmt->close();
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    // Check for duplicate building name within property
    $stmt = $conn->prepare("SELECT building_id FROM buildings WHERE property_id = ? AND building_name = ? AND building_id != ?");
    $edit_id = $edit_id ?? 0;
    $stmt->bind_param("isi", $property_id, $building_name, $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        $_SESSION['error_message'] = "A building with this name already exists for the selected property.";
        $stmt->close();
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    try {
        if ($edit_id) {
            $stmt = $conn->prepare("UPDATE buildings SET property_id = ?, building_name = ?, floor_count = ?, room_count = ?, building_area = ?, construction_type = ?, construction_year = ?, description = ?, address = ?, building_type = ? WHERE building_id = ?");
            $stmt->bind_param("isiiisssisi", $property_id, $building_name, $floor_count, $room_count, $building_area, $construction_type, $construction_year, $description, $address, $building_type, $edit_id);
            $message = "Building updated successfully.";
        } else {
            $stmt = $conn->prepare("INSERT INTO buildings (property_id, building_name, floor_count, room_count, building_area, construction_type, construction_year, description, address, building_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiiisssis", $property_id, $building_name, $floor_count, $room_count, $building_area, $construction_type, $construction_year, $description, $address, $building_type);
            $message = "Building added successfully.";
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = $message;
            $stmt->close();
            redirect('buildings.php' . (isset($_GET['property']) ? '?property=' . (int)$_GET['property'] : ''));
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error processing building: " . sanitize($e->getMessage());
        redirect('buildings.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $property_id = filter_input(INPUT_GET, 'property', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    // Check for associated rooms
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM rooms WHERE building_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row['count'] > 0) {
        $_SESSION['error_message'] = "Cannot delete building because it has associated rooms.";
        redirect('buildings.php' . ($property_id ? '?property=' . $property_id : ''));
    }

    try {
        $stmt = $conn->prepare("DELETE FROM buildings WHERE building_id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Building deleted successfully.";
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
        redirect('buildings.php' . ($property_id ? '?property=' . $property_id : ''));
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error deleting building: " . sanitize($e->getMessage());
        redirect('buildings.php' . ($property_id ? '?property=' . $property_id : ''));
    }
}

$conn->close();
?>