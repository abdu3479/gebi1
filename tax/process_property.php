<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_messages'][] = translate('unauthorized_access');
    redirect('properties.php');
}

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_messages'][] = "Database connection failed: " . sanitize($e->getMessage());
    redirect('properties.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract and validate input data
    $owner_id = isset($_POST['owner_id']) && is_numeric($_POST['owner_id']) ? (int)$_POST['owner_id'] : 0;
    $type_id = isset($_POST['type_id']) && is_numeric($_POST['type_id']) ? (int)$_POST['type_id'] : 0;
    $property_name = isset($_POST['property_name']) ? trim($_POST['property_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $area = isset($_POST['area']) && is_numeric($_POST['area']) ? (float)$_POST['area'] : 0;
    $registration_number = isset($_POST['registration_number']) ? trim($_POST['registration_number']) : null;
    $construction_year = isset($_POST['construction_year']) && is_numeric($_POST['construction_year']) ? (int)$_POST['construction_year'] : null;
    $valuation = isset($_POST['valuation']) && is_numeric($_POST['valuation']) ? (float)$_POST['valuation'] : null;
    $zoning = isset($_POST['zoning']) ? trim($_POST['zoning']) : null;
    $registration_date = isset($_POST['registration_date']) && !empty($_POST['registration_date']) ? $_POST['registration_date'] : null;
    $tax_status = isset($_POST['tax_status']) && in_array($_POST['tax_status'], ['Active', 'Exempt', 'Delinquent']) ? $_POST['tax_status'] : 'Active';
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    // Validate required fields
    if ($owner_id <= 0 || $type_id <= 0 || empty($property_name) || strlen($property_name) > 100 || empty($address) || $area <= 0 || empty($tax_status)) {
        $_SESSION['error_messages'][] = translate('invalid_form_data');
        redirect('properties.php' . ($edit_id > 0 ? '?edit=' . $edit_id : ''));
    }

    // Validate registration_number length
    if ($registration_number !== null && strlen($registration_number) > 50) {
        $_SESSION['error_messages'][] = translate('invalid_form_data');
        redirect('properties.php' . ($edit_id > 0 ? '?edit=' . $edit_id : ''));
    }

    // Validate construction_year
    if ($construction_year !== null && ($construction_year < 1900 || $construction_year > date('Y'))) {
        $_SESSION['error_messages'][] = translate('invalid_form_data');
        redirect('properties.php' . ($edit_id > 0 ? '?edit=' . $edit_id : ''));
    }

    try {
        // Validate owner_id
        $stmt = $conn->prepare("SELECT id FROM owners WHERE id = ?");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $_SESSION['error_messages'][] = translate('invalid_form_data');
            redirect('properties.php' . ($edit_id > 0 ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();

        // Validate type_id
        $stmt = $conn->prepare("SELECT id FROM property_types WHERE id = ?");
        $stmt->bind_param("i", $type_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $_SESSION['error_messages'][] = translate('invalid_form_data');
            redirect('properties.php' . ($edit_id > 0 ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();

        // Check for duplicate property name (excluding the current property during edit)
        $sql = "SELECT property_id FROM properties WHERE property_name = ? AND owner_id = ?";
        if ($edit_id > 0) {
            $sql .= " AND property_id != ?";
        }
        $stmt = $conn->prepare($sql);
        if ($edit_id > 0) {
            $stmt->bind_param("sii", $property_name, $owner_id, $edit_id);
        } else {
            $stmt->bind_param("si", $property_name, $owner_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['error_messages'][] = "A property with this name already exists for this owner.";
            redirect('properties.php' . ($edit_id > 0 ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();

        // Check for duplicate registration number (if provided, excluding the current property during edit)
        if ($registration_number !== null) {
            $sql = "SELECT property_id FROM properties WHERE registration_number = ?";
            if ($edit_id > 0) {
                $sql .= " AND property_id != ?";
            }
            $stmt = $conn->prepare($sql);
            if ($edit_id > 0) {
                $stmt->bind_param("si", $registration_number, $edit_id);
            } else {
                $stmt->bind_param("s", $registration_number);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $_SESSION['error_messages'][] = "A property with this registration number already exists.";
                redirect('properties.php' . ($edit_id > 0 ? '?edit=' . $edit_id : ''));
            }
            $stmt->close();
        }

        if ($edit_id > 0) {
            // Update existing property
            $stmt = $conn->prepare("UPDATE properties SET owner_id = ?, type_id = ?, property_name = ?, address = ?, area = ?, registration_number = ?, construction_year = ?, valuation = ?, zoning = ?, registration_date = ?, tax_status = ? WHERE property_id = ?");
            $stmt->bind_param("iissdsidsssi", $owner_id, $type_id, $property_name, $address, $area, $registration_number, $construction_year, $valuation, $zoning, $registration_date, $tax_status, $edit_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_messages'][] = translate('Property updated successfully.');
            } else {
                $_SESSION['error_messages'][] = translate('Error updating property.');
            }
            $stmt->close();
        } else {
            // Insert new property
            $stmt = $conn->prepare("INSERT INTO properties (owner_id, type_id, property_name, address, area, registration_number, construction_year, valuation, zoning, registration_date, tax_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissdsidsss", $owner_id, $type_id, $property_name, $address, $area, $registration_number, $construction_year, $valuation, $zoning, $registration_date, $tax_status);
            
            if ($stmt->execute()) {
                $_SESSION['success_messages'][] = translate('Property added successfully.');
            } else {
                $_SESSION['error_messages'][] = translate('Error adding property.');
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = "Database error: " . sanitize($e->getMessage());
    }
    redirect('properties.php');
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $delete_id = (int)$_GET['delete'];
    
    try {
        // Check if property has associated buildings
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM buildings WHERE property_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['count'] > 0) {
            $_SESSION['error_messages'][] = translate('Cannot delete property with associated buildings.');
            redirect('properties.php');
        }
        
        // Delete the property
        $stmt = $conn->prepare("DELETE FROM properties WHERE property_id = ?");
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_messages'][] = translate('Property deleted successfully.');
        } else {
            $_SESSION['error_messages'][] = translate('Error deleting property.');
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = "Database error: " . sanitize($e->getMessage());
    }
    redirect('properties.php');
}

// Invalid request
$_SESSION['error_messages'][] = translate('invalid_form_data');
redirect('properties.php');

$conn->close();
?>