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
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('assessments.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . sanitize($e->getMessage());
    redirect('assessments.php');
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Verify CSRF token for delete action
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid security token.";
        redirect('assessments.php');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM tax_assessments WHERE assessment_id = ?");
        $stmt->bind_param("i", $_GET['delete']);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Assessment deleted successfully.";
            $conn->commit();
        } else {
            throw new Exception("Failed to delete assessment.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
        redirect('assessments.php');
    }
}

// Handle status change
if (isset($_GET['change_status']) && is_numeric($_GET['change_status']) && isset($_GET['new_status'])) {
    // Verify CSRF token for status change
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid security token.";
        redirect('assessments.php');
    }

    $valid_statuses = ['paid', 'overdue'];
    $new_status = in_array($_GET['new_status'], $valid_statuses) ? $_GET['new_status'] : null;
    
    if ($new_status) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE tax_assessments SET status = ? WHERE assessment_id = ?");
            $stmt->bind_param("si", $new_status, $_GET['change_status']);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Assessment status updated to " . ucfirst($new_status) . ".";
                $conn->commit();
            } else {
                throw new Exception("Failed to update assessment status.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = $e->getMessage();
        } finally {
            if (isset($stmt)) $stmt->close();
        }
    } else {
        $_SESSION['error_message'] = "Invalid status.";
    }
    redirect('assessments.php');
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for form submission
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid security token.";
        redirect('assessments.php');
    }

    // Sanitize and validate inputs
    $property_id = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
    $building_id = filter_input(INPUT_POST, 'building_id', FILTER_VALIDATE_INT) ?: null;
    $contract_id = filter_input(INPUT_POST, 'contract_id', FILTER_VALIDATE_INT) ?: null;
    $rate_id = filter_input(INPUT_POST, 'rate_id', FILTER_VALIDATE_INT);
    $assessment_date = filter_input(INPUT_POST, 'assessment_date', FILTER_SANITIZE_STRING);
    $assessed_value = filter_input(INPUT_POST, 'assessed_value', FILTER_VALIDATE_FLOAT);
    $tax_amount = filter_input(INPUT_POST, 'tax_amount', FILTER_VALIDATE_FLOAT);
    $fiscal_year = filter_input(INPUT_POST, 'fiscal_year', FILTER_VALIDATE_INT);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?: null;
    $edit_id = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

    // Validate required fields
    $required_fields = [
        'property_id' => $property_id,
        'rate_id' => $rate_id,
        'assessment_date' => $assessment_date,
        'assessed_value' => $assessed_value,
        'tax_amount' => $tax_amount,
        'fiscal_year' => $fiscal_year
    ];

    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            $_SESSION['error_message'] = "All required fields must be filled.";
            redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
    }

    // Validate assessment date format and range
    $date_obj = DateTime::createFromFormat('Y-m-d', $assessment_date);
    $today = new DateTime();
    if (!$date_obj || $date_obj > $today) {
        $_SESSION['error_message'] = "Invalid assessment date. Must be valid and not in the future.";
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate fiscal year range (e.g., 2000-current year + 1)
    if ($fiscal_year < 2000 || $fiscal_year > (date('Y') + 1)) {
        $_SESSION['error_message'] = "Invalid fiscal year.";
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate assessed value range
    if ($assessed_value <= 0 || $assessed_value > 1000000000) {
        $_SESSION['error_message'] = "Assessed value must be between 1 and 1,000,000,000.";
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate tax rate
    $stmt = $conn->prepare("SELECT rate_percentage, applicable_to FROM tax_rates WHERE rate_id = ? AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
    $stmt->bind_param("i", $rate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rate = $result->fetch_assoc();
    $stmt->close();

    if (!$rate) {
        $_SESSION['error_message'] = "Invalid or expired tax rate.";
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate tax calculation
    $calculated_tax = $assessed_value * ($rate['rate_percentage'] / 100);
    if (abs($calculated_tax - $tax_amount) > 0.01) {
        $_SESSION['error_message'] = "Tax amount does not match calculated value. Expected: " . number_format($calculated_tax, 2);
        redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Validate building belongs to property
    if ($building_id) {
        $stmt = $conn->prepare("SELECT building_id FROM buildings WHERE building_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $building_id, $property_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $_SESSION['error_message'] = "Invalid building for selected property.";
            redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();
    }

    // Validate contract exists if provided
    if ($contract_id) {
        $stmt = $conn->prepare("SELECT contract_id FROM rent_contracts WHERE contract_id = ?");
        $stmt->bind_param("i", $contract_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $_SESSION['error_message'] = "Invalid rent contract ID provided.";
            redirect('assessments.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();
    }

    // Begin database transaction
    $conn->begin_transaction();
    try {
        if ($edit_id) {
            // Update existing assessment
            $stmt = $conn->prepare("UPDATE tax_assessments SET 
                                  property_id = ?, 
                                  building_id = ?, 
                                  contract_id = ?,
                                  rate_id = ?, 
                                  tax_amount = ?, 
                                  assessed_value = ?, 
                                  fiscal_year = ?, 
                                  assessment_date = ?, 
                                  notes = ?, 
                                  status = 'pending' 
                                  WHERE assessment_id = ?");
            $stmt->bind_param("iiiddisssi", 
                $property_id, 
                $building_id,
                $contract_id,
                $rate_id, 
                $tax_amount, 
                $assessed_value, 
                $fiscal_year, 
                $assessment_date, 
                $notes, 
                $edit_id);
        } else {
            // Create new assessment
            $status = 'pending';
            $stmt = $conn->prepare("INSERT INTO tax_assessments 
                                   (property_id, building_id, contract_id, rate_id, tax_amount, assessed_value, fiscal_year, assessment_date, notes, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiddissss", 
                $property_id, 
                $building_id,
                $contract_id,
                $rate_id, 
                $tax_amount, 
                $assessed_value, 
                $fiscal_year, 
                $assessment_date, 
                $notes, 
                $status);
        }

        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = $edit_id ? "Assessment updated successfully." : "Assessment added successfully.";
            
            // Log this action
            $action = $edit_id ? "Updated assessment ID $edit_id" : "Created new assessment";
            logAction($conn, $_SESSION['user_id'], $action);
        } else {
            throw new Exception($edit_id ? "Failed to update assessment." : "Failed to add assessment.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
    }
    
    redirect('assessments.php');
}

/**
 * Log system actions for audit purposes
 */
/**
 * Logs system actions with comprehensive error handling and additional metadata
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id ID of the user performing the action
 * @param string $action Description of the action being logged
 * @param int|null $record_id (Optional) ID of the record being affected
 * @param string|null $record_type (Optional) Type of record being affected
 * @return bool Returns true if logging succeeded, false otherwise
 */
function logAction($conn, $user_id, $action, $record_id = null, $record_type = null) {
    try {
        // Get additional context information
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Check if the system_logs table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'system_logs'");
        if ($table_check->num_rows === 0) {
            // Table doesn't exist, create it
            $create_table = "CREATE TABLE IF NOT EXISTS `system_logs` (
                `log_id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `action` VARCHAR(255) NOT NULL,
                `action_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `user_agent` VARCHAR(255) DEFAULT NULL,
                `record_id` INT(11) DEFAULT NULL,
                `record_type` VARCHAR(50) DEFAULT NULL,
                PRIMARY KEY (`log_id`),
                INDEX `user_id` (`user_id`),
                INDEX `action_date` (`action_date`),
                INDEX `record` (`record_type`, `record_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            if (!$conn->query($create_table)) {
                throw new Exception("Failed to create system_logs table");
            }
        }

        // Prepare and execute the insert statement
        $stmt = $conn->prepare("INSERT INTO system_logs 
                               (user_id, action, ip_address, user_agent, record_id, record_type) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssis", 
            $user_id, 
            $action, 
            $ip_address,
            $user_agent,
            $record_id,
            $record_type
        );
        
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Failed to log action: " . $stmt->error);
        }
        
        return true;
    } catch (Exception $e) {
        // Fallback to file logging if database logging fails
        $logMessage = sprintf(
            "[%s] [User:%d] [IP:%s] %s %s%s",
            date('Y-m-d H:i:s'),
            $user_id,
            $ip_address ?? 'unknown',
            $action,
            $record_id ? "(Record: $record_type#$record_id)" : "",
            PHP_EOL
        );
        
        // Log to file
        file_put_contents(
            __DIR__ . '/../logs/action_errors.log', 
            $logMessage, 
            FILE_APPEND
        );
        
        // Log to PHP error log
        error_log("Action logging failed: " . $e->getMessage());
        
        return false;
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

// If script reaches here without any action being taken
redirect('assessments.php');
?>