<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('owners.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . sanitize($e->getMessage());
    redirect('owners.php');
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS property_count FROM properties WHERE owner_id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['property_count'] > 0) {
        $_SESSION['error_message'] = "Cannot delete owner with associated properties.";
    } else {
        $stmt = $conn->prepare("DELETE FROM owners WHERE owner_id = ?");
        $stmt->bind_param("i", $_GET['delete']);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Owner deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete owner.";
        }
    }
    $stmt->close();
    redirect('owners.php');
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $id_number = filter_input(INPUT_POST, 'id_number', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING) ?: null;
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: null;
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING) ?: null;
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

    // Basic validation
    if (!$full_name || !$id_number) {
        $_SESSION['error_message'] = "Full name and ID number are required.";
        redirect('owners.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        redirect('owners.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Check for duplicate ID number
    $stmt = $conn->prepare("SELECT owner_id FROM owners WHERE id_number = ? AND owner_id != ?");
    $stmt->bind_param("si", $id_number, $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "ID number already exists.";
        redirect('owners.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    if ($edit_id) {
        // Update owner
        $stmt = $conn->prepare("UPDATE owners SET full_name = ?, id_number = ?, phone = ?, email = ?, address = ? WHERE owner_id = ?");
        $stmt->bind_param("sssssi", $full_name, $id_number, $phone, $email, $address, $edit_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Owner updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update owner.";
        }
    } else {
        // Add new owner
        $stmt = $conn->prepare("INSERT INTO owners (full_name, id_number, phone, email, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full_name, $id_number, $phone, $email, $address);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Owner added successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to add owner.";
        }
    }
    $stmt->close();
    redirect('owners.php');
}
?>