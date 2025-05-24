<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure script runs only in CLI context
if (php_sapi_name() !== 'cli') {
    error_log('ERROR: cron_vacancy_report.php can only be run via CLI');
    exit(1);
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    error_log('ERROR: Database connection failed in cron_vacancy_report.php: ' . $e->getMessage());
    exit(1);
}

/**
 * Submit automated vacancy report for the previous month
 * @return bool True on success or no data to report, false on failure
 */
function submit_automated_vacancy_report($conn) {
    // Get previous month
    $period_month = (int)date('n', strtotime('last month'));
    $period_year = (int)date('Y', strtotime('last month'));

    // Get system admin user ID
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_row = $result->fetch_assoc();
    $stmt->close();

    if (!$admin_row) {
        error_log("ERROR: No admin user found for automated vacancy report ($period_month/$period_year)");
        return false;
    }
    $admin_id = (int)$admin_row['user_id'];

    // Check for existing report
    $stmt = $conn->prepare("SELECT report_id FROM government_reports WHERE period_month = ? AND period_year = ?");
    $stmt->bind_param("ii", $period_month, $period_year);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        error_log("INFO: Report already exists for $period_month/$period_year");
        $stmt->close();
        return true;
    }
    $stmt->close();

    // Get vacant room data
    $stmt = $conn->prepare("SELECT r.room_id, r.room_number, r.days_vacant, 
                                   b.building_name, p.property_name, p.address,
                                   o.full_name AS owner_name, o.id_number AS owner_id
                            FROM rooms r
                            JOIN buildings b ON r.building_id = b.building_id
                            JOIN properties p ON b.property_id = p.property_id
                            JOIN owners o ON p.owner_id = o.owner_id
                            WHERE r.is_occupied = 0 AND r.days_vacant > 30");
    $stmt->execute();
    $result = $stmt->get_result();
    $report_data = [];

    while ($row = $result->fetch_assoc()) {
        $report_data[] = [
            'room_id' => (int)$row['room_id'],
            'room_number' => sanitize($row['room_number']),
            'days_vacant' => (int)$row['days_vacant'],
            'building_name' => sanitize($row['building_name']),
            'property_name' => sanitize($row['property_name']),
            'address' => sanitize($row['address']),
            'owner_name' => sanitize($row['owner_name']),
            'owner_id' => sanitize($row['owner_id'])
        ];
    }
    $stmt->close();

    if (empty($report_data)) {
        error_log("INFO: No vacant rooms to report for $period_month/$period_year");
        return true;
    }

    // Prepare report
    $json_data = json_encode($report_data);
    $submission_method = 'email'; // Default to email (configurable)
    $reference_number = sprintf("VR-%04d%02d-%04d", $period_year, $period_month, rand(1, 9999));

    // Save report transactionally
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO government_reports 
                                (report_date, period_month, period_year, submitted_by, 
                                 submission_method, reference_number, report_data)
                                VALUES (CURDATE(), ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissss", $period_month, $period_year, $admin_id, 
                         $submission_method, $reference_number, $json_data);
        $stmt->execute();
        $stmt->close();

        // Update room reporting status
        $stmt = $conn->prepare("UPDATE rooms SET reporting_status = 'reported', last_reported_date = CURDATE() 
                                WHERE is_occupied = 0 AND days_vacant > 30");
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        error_log("SUCCESS: Automated vacancy report submitted for $period_month/$period_year (Ref: $reference_number)");
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("ERROR: Failed to submit automated vacancy report for $period_month/$period_year: " . $e->getMessage());
        return false;
    }
}

// Run the automated report
$success = submit_automated_vacancy_report($conn);
$conn->close();
exit($success ? 0 : 1);
?>