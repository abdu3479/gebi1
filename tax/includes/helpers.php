<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the current user has admin role
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if the current user has admin or billing officer role
 * @return bool
 */
function isOfficer() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'billing_officer']);
}

/**
 * Sanitize output to prevent XSS attacks
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a specified URL
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if user is authenticated, redirect to login if not
 */
function ensureAuthenticated() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

/**
 * Format currency amounts for display
 * @param float $amount
 * @param string $currency
 * @return string
 */
function formatCurrency($amount, $currency = 'ETB ') {
    return $currency . number_format($amount, 2);
}

/**
 * Log an action to the system logs
 * @param mysqli $conn Database connection
 * @param int $user_id User performing the action
 * @param string $action Description of the action
 * @param int|null $record_id ID of the affected record
 * @param string|null $record_type Type of the affected record
 * @return bool
 */
function logAction($conn, $user_id, $action, $record_id = null, $record_type = null) {
    try {
        // Get additional context information
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        if ($conn) {
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
            
            $stmt->close();
            return true;
        }
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
    }
    
    return false;
}

/**
 * Update contract statuses based on start and end dates
 * @param mysqli $conn Database connection
 */
function updateContractStatuses($conn) {
    $today = date('Y-m-d');
    $conn->begin_transaction();
    try {
        // Fetch contracts that are not terminated
        $stmt = $conn->prepare("SELECT contract_id, room_id, start_date, end_date, status FROM rent_contracts WHERE status != 'terminated'");
        $stmt->execute();
        $result = $stmt->get_result();
        $contracts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($contracts as $contract) {
            $new_status = $contract['status'];
            if ($contract['start_date'] > $today) {
                $new_status = 'pending';
            } elseif ($contract['start_date'] <= $today && $contract['end_date'] >= $today) {
                $new_status = 'active';
            } elseif ($contract['end_date'] < $today) {
                $new_status = 'completed';
            }

            // Update contract status if changed
            if ($new_status !== $contract['status']) {
                $stmt = $conn->prepare("UPDATE rent_contracts SET status = ? WHERE contract_id = ?");
                $stmt->bind_param("si", $new_status, $contract['contract_id']);
                $stmt->execute();
                $stmt->close();

                // Update room occupancy
                $is_occupied = ($new_status === 'active') ? 1 : 0;
                $stmt = $conn->prepare("UPDATE rooms SET is_occupied = ? WHERE room_id = ?");
                $stmt->bind_param("ii", $is_occupied, $contract['room_id']);
                $stmt->execute();
                $stmt->close();

                // Log the status change
                logAction($conn, $_SESSION['user_id'] ?? 0, "Auto-updated contract status to $new_status", $contract['contract_id'], 'rent_contract');
            }
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        // Optionally log the error to a file or database
        error_log("Error updating contract statuses: " . $e->getMessage());
    }
}

/**
 * Get the translated string based on language
 * @param string $key
 * @return string
 */
function translate($key) {
    $translations = [
        'en' => [
            'dashboard' => 'Dashboard',
            'property_owners' => 'Property Owners',
            'properties' => 'Properties',
            'buildings' => 'Buildings',
            'rooms' => 'Rooms',
            'room_types' => 'Room Types',
            'rent_contracts' => 'Rent Contracts',
            'tax_assessments' => 'Tax Assessments',
            'payments' => 'Payments',
            'rental_payments' => 'Rental Payments',
            'vacancy_reports' => 'Vacancy Reports',
            'system_users' => 'System Users',
            'reports' => 'Reports',
            'profile' => 'My Profile',
            'logout' => 'Logout',
            'notifications' => 'Notifications',
            'pending_tax_assessments' => 'pending tax assessments',
            'see_all_notifications' => 'See All Notifications',
            'user_menu' => 'User Menu',
            'toggle_sidebar' => 'Toggle sidebar',
            'add_new_owner' => 'Add New Owner',
            'edit_owner' => 'Edit Owner',
            'full_name' => 'Full Name',
            'id_number' => 'ID Number',
            'phone' => 'Phone',
            'email' => 'Email',
            'address' => 'Address',
            'submit' => 'Submit',
            'update' => 'Update',
            'cancel' => 'Cancel',
            'list_of_property_owners' => 'List of Property Owners',
            'actions' => 'Actions',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'view_properties' => 'View Properties',
            'new_assessment' => 'New Assessment',
            'edit_assessment' => 'Edit Assessment',
            'property' => 'Property',
            'building' => 'Building',
            'tax_rate' => 'Tax Rate',
            'assessment_date' => 'Assessment Date',
            'assessed_value' => 'Assessed Value',
            'fiscal_year' => 'Fiscal Year',
            'notes' => 'Notes',
            'list_of_assessments' => 'List of Tax Assessments',
            'all_statuses' => 'All Statuses',
            'pending' => 'Pending',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'all_properties' => 'All Properties',
            'property_building' => 'Property/Building',
            'value' => 'Value',
            'tax' => 'Tax',
            'year' => 'Year',
            'status' => 'Status',
            'mark_as_paid' => 'Mark as Paid',
            'mark_as_overdue' => 'Mark as Overdue',
            'view_payments' => 'View Payments',
            'add_new_property' => 'Add New Property',
            'edit_property' => 'Edit Property',
            'owner' => 'Owner',
            'property_name' => 'Property Name',
            'property_type' => 'Property Type',
            'select_owner' => 'Select Owner',
            'select_property_type' => 'Select Property Type',
            'registration_number' => 'Registration Number',
            'construction_year' => 'Construction Year',
            'valuation' => 'Valuation',
            'zoning' => 'Zoning',
            'registration_date' => 'Registration Date',
            'tax_status' => 'Tax Status',
            'select_tax_status' => 'Select Tax Status',
            'list_of_properties' => 'List of Properties',
            'show_all_properties' => 'Show All Properties',
            'view_buildings' => 'View Buildings',
            'confirm_delete_property' => 'Are you sure you want to delete this property?',
            'add_new_building' => 'Add New Building',
            'edit_building' => 'Edit Building',
            'building_name' => 'Building Name',
            'description' => 'Description',
            'select_property' => 'Select Property',
            'list_of_buildings' => 'List of Buildings',
            'show_all_buildings' => 'Show All Buildings',
            'floor_count' => 'Floor Count',
            'room_count' => 'Room Count',
            'building_area' => 'Building Area (sqm)',
            'construction_type' => 'Construction Type',
            'building_type' => 'Building Type',
            'select_construction_type' => 'Select Construction Type',
            'select_building_type' => 'Select Building Type',
            'wood' => 'Wood',
            'concrete' => 'Concrete',
            'mixed' => 'Mixed',
            'Residential' => 'Residential',
            'Commercial' => 'Commercial',
            'Mixed' => 'Mixed',
            'Other' => 'Other',
            'add_new_payment' => 'Add New Payment',
            'edit_payment' => 'Edit Payment',
            'assessment' => 'Assessment',
            'select_assessment' => 'Select Assessment',
            'amount' => 'Amount',
            'payment_date' => 'Payment Date',
            'payment_method' => 'Payment Method',
            'select_payment_method' => 'Select Payment Method',
            'cash' => 'Cash',
            'credit_card' => 'Credit Card',
            'bank_transfer' => 'Bank Transfer',
            'mobile_payment' => 'Mobile Payment',
            'list_of_payments' => 'List of Payments',
            'show_all_payments' => 'Show All Payments',
            'report_options' => 'Report Options',
            'report_type' => 'Report Type',
            'tax_collection' => 'Tax Collection',
            'property_taxes' => 'Property Taxes',
            'building_taxes' => 'Building Taxes',
            'delinquent_taxes' => 'Delinquent Taxes',
            'date_range' => 'Date Range',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'generate_report' => 'Generate Report',
            'tax_collection_report' => 'Tax Collection Report',
            'property_tax_report' => 'Property Tax Report',
            'building_tax_report' => 'Building Tax Report',
            'delinquent_tax_report' => 'Delinquent Tax Report',
            'print' => 'Print',
            'export' => 'Export',
            'tax_collection_summary' => 'Tax Collection Summary',
            'total_collected' => 'Total Collected',
            'property_id' => 'Property ID',
            'total_tax' => 'Total Tax',
            'balance' => 'Balance',
            'grand_total' => 'Grand Total',
            'building_id' => 'Building ID',
            'assessment_id' => 'Assessment ID',
            'tax_amount' => 'Tax Amount',
            'due_date' => 'Due Date',
            'days_overdue' => 'Days Overdue',
            'total_delinquent_taxes' => 'Total Delinquent Taxes',
            'tax_collected' => 'Tax Collected',
            'for_fiscal_year' => 'for Fiscal Year',
            'room_management' => 'Room Management',
            'add_new_room' => 'Add New Room',
            'edit_room' => 'Edit Room',
            'select_building' => 'Select Building',
            'room_type' => 'Room Type',
            'select_type' => 'Select Type',
            'room_number' => 'Room Number',
            'floor_number' => 'Floor Number',
            'area' => 'Area',
            'monthly_rent' => 'Monthly Rent',
            'currently_occupied' => 'Currently Occupied',
            'list_of_rooms' => 'List of Rooms',
            'all_buildings' => 'All Buildings',
            'all_rooms' => 'All Rooms',
            'occupied' => 'Occupied',
            'vacant' => 'Vacant',
            'id' => 'ID',
            'building_property' => 'Building/Property',
            'floor' => 'Floor',
            'type' => 'Type',
            'rent' => 'Rent',
            'confirm_delete_room' => 'Are you sure you want to delete this room?',
            'view_contracts' => 'View Contracts',
            'tax_assessment' => 'Tax Assessment',
            'new_contract' => 'New Contract',
            'edit_contract' => 'Edit Contract',
            'select_room' => 'Select Room',
            'tenant_name' => 'Tenant Name',
            'tenant_phone' => 'Tenant Phone',
            'tenant_id_number' => 'Tenant ID Number',
            'deposit_amount' => 'Deposit Amount',
            'list_of_rent_contracts' => 'List of Rent Contracts',
            'room' => 'Room',
            'tenant' => 'Tenant',
            'period' => 'Period',
            'deposit' => 'Deposit',
            'active' => 'Active',
            'terminated' => 'Terminated',
            'completed' => 'Completed',
            'confirm_delete_contract' => 'Are you sure you want to delete this contract?',
            'activate' => 'Activate',
            'terminate' => 'Terminate',
            'mark_completed' => 'Mark as Completed',
            'view_payments' => 'View Payments',
            'generate_vacancy_report' => 'Generate Vacancy Report',
            'reporting_period' => 'Reporting Period',
            'submission_method' => 'Submission Method',
            'manual_submission' => 'Manual Submission',
            'api_submission' => 'API Submission',
            'email_submission' => 'Email Submission',
            'report_contents' => 'Report Contents',
            'vacancy_report_info' => 'This report will include all rooms vacant for more than 30 days as of the end of the reporting period.',
            'quick_stats' => 'Quick Stats',
            'currently_vacant_rooms' => 'Currently Vacant Rooms',
            'unreported_vacancies' => 'Unreported Long-Term Vacancies',
            'vacant_rooms_requiring_reporting' => 'Vacant Rooms Requiring Reporting',
            'owner' => 'Owner',
            'days_vacant' => 'Days Vacant',
            'last_reported' => 'Last Reported',
            'never' => 'Never',
            'reported' => 'Reported',
            'non-compliant' => 'Non-compliant',
            'submitted_reports_history' => 'Submitted Reports History',
            'report_id' => 'Report ID',
            'submitted_on' => 'Submitted On',
            'vacancies_reported' => 'Vacancies Reported',
            'reference' => 'Reference',
            'generate_submit_report' => 'Generate & Submit Report',
            'new_payment' => 'New Payment',
            'rent_contract' => 'Rent Contract',
            'select_contract' => 'Select Contract',
            'period_month' => 'Period Month',
            'select_month' => 'Select Month',
            'period_year' => 'Period Year',
            'receipt_number' => 'Receipt Number',
            'received_by' => 'Received By',
            'room_tenant' => 'Room/Tenant',
            'date' => 'Date',
            'method' => 'Method',
            'receipt' => 'Receipt',
            'contract_details' => 'Contract Details',
            'contract_period' => 'Contract Period',
            'print_receipt' => 'Print Receipt',
            'january' => 'January',
            'february' => 'February',
            'march' => 'March',
            'april' => 'April',
            'may' => 'May',
            'june' => 'June',
            'july' => 'July',
            'august' => 'August',
            'september' => 'September',
            'october' => 'October',
            'november' => 'November',
            'december' => 'December',
            'unauthorized_access' => 'Unauthorized access.',
            'invalid_form_data' => 'Invalid form data.',
            'invalid_contract' => 'Invalid or inactive contract.',
            'duplicate_payment_period' => 'A payment for this contract and period already exists.',
            'error_processing_payment' => 'Error processing payment',
            'invalid_delete_request' => 'Invalid delete request.',
            'payment_not_found' => 'Payment not found.',
            'error_deleting_payment' => 'Error deleting payment',
            'payment_added' => 'Payment added successfully.',
            'payment_updated' => 'Payment updated successfully.',
            'payment_deleted' => 'Payment deleted successfully.',
            'confirm_delete_payment' => 'Are you sure you want to delete this payment?',
            'error_fetching_rent' => 'Error fetching rent amount.',
            'add_new_room_type' => 'Add New Room Type',
            'edit_room_type' => 'Edit Room Type',
            'type_name' => 'Type Name',
            'list_of_room_types' => 'List of Room Types',
            'rooms' => 'Rooms',
            'invalid_type_name' => 'Type name is required and must be 100 characters or less.',
            'duplicate_type_name' => 'This room type name already exists.',
            'error_processing_type' => 'Error processing room type',
            'type_has_rooms' => 'Cannot delete room type with associated rooms.',
            'error_deleting_type' => 'Error deleting room type',
            'type_added' => 'Room type added successfully.',
            'type_updated' => 'Room type updated successfully.',
            'type_deleted' => 'Room type deleted successfully.',
            'confirm_delete_type' => 'Are you sure you want to delete this room type?',
            'contract_not_found' => 'Contract not found.',
            'contract_already_in_status' => 'Contract is already in this status.',
            'invalid_status' => 'Invalid status.',
            'status_must_match_dates' => 'Status must match the contract dates.',
            'contract_status_updated' => 'Contract status updated to',
            'failed_to_update_status' => 'Failed to update contract status',
            'contract_deleted' => 'Contract deleted successfully.',
            'failed_to_delete_contract' => 'Failed to delete contract',
            'invalid_csrf_token' => 'Invalid CSRF token.',
            'required_fields_missing' => 'Required fields are missing.',
            'end_date_must_be_after_start_date' => 'End date must be after start date.',
            'invalid_room_selected' => 'Invalid room selected.',
            'room_not_available' => 'Room is not available.',
            'overlapping_contract_exists' => 'An overlapping contract already exists.',
            'failed_to_save_contract' => 'Failed to save contract',
            'invalid_request' => 'Invalid request.',
            // New keys for tax assessments page
            'rental_tax' => 'Rental Tax',
            'assessment_not_found' => 'Assessment not found.',
            'select_tax_rate' => 'Select Tax Rate',
            'due_date' => 'Due Date',
            'generate_invoice' => 'Generate Invoice',
            'confirm_delete_assessment' => 'Are you sure you want to delete this assessment?'
        ],
        'am' => [
            'dashboard' => 'ዳሽቦርድ',
            'property_owners' => 'የንብረት ባለቤቶች',
            'properties' => 'ንብረቶች',
            'buildings' => 'ህንፃዎች',
            'due_date' => 'የክፍያ ጊዜ',
            'rooms' => 'ክፍሎች',
            'room_types' => 'የክፍል አይነቶች',
            'rent_contracts' => 'የኪራይ ውሎች',
            'tax_assessments' => 'የግብር ግምገማዎች',
            'payments' => 'ክፍያዎች',
            'rental_payments' => 'የኪራይ ክፍያዎች',
            'vacancy_reports' => 'የባዶ ክፍሎች ሪፖርቶች',
            'system_users' => 'የስርዓት ተጠቃሚዎች',
            'reports' => 'ሪፖርቶች',
            'profile' => 'የእኔ መገለጫ',
            'logout' => 'ውጣ',
            'notifications' => 'ማሳወቂያዎች',
            'pending_tax_assessments' => 'በመጠባበቅ ላይ ያሉ የግብር ግምገማዎች',
            'see_all_notifications' => 'ሁሉንም ማሳወቂያዎች ተመልከት',
            'user_menu' => 'የተጠቃሚ ምናሌ',
            'toggle_sidebar' => 'የጎን አሞሌን ቀያይር',
            'add_new_owner' => 'አዲስ ባለቤት ጨምር',
            'edit_owner' => 'ባለቤት አስተካክል',
            'full_name' => 'ሙሉ ስም',
            'id_number' => 'የመለያ ቁጥር',
            'phone' => 'ስልክ',
            'email' => 'ኢሜይል',
            'address' => 'አድራሻ',
            'submit' => 'አስገባ',
            'update' => 'አዘምን',
            'cancel' => 'ተወው',
            'list_of_property_owners' => 'የንብረት ባለቤቶች ዝርዝር',
            'actions' => 'ተግባራት',
            'edit' => 'አስተካክል',
            'delete' => 'ሰርዝ',
            'view_properties' => 'ንብረቶችን ተመልከት',
            'new_assessment' => 'አዲስ ግምገማ',
            'edit_assessment' => 'ግምገማ አስተካክል',
            'property' => 'ንብረት',
            'building' => 'ህንፃ',
            'tax_rate' => 'የግብር መጠን',
            'assessment_date' => 'የግምገማ ቀን',
            'assessed_value' => 'የተገመተ ዋጋ',
            'fiscal_year' => 'የበጀት ዓመት',
            'notes' => 'ማስታወሻዎች',
            'list_of_assessments' => 'የግምገማዎች ዝርዝር',
            'all_statuses' => 'ሁሉም ሁኔታዎች',
            'pending' => 'በመጠባበቅ ላይ',
            'paid' => 'ተከፍሏል',
            'overdue' => 'ከሰአቱ ያለፈ',
            'all_properties' => 'ሁሉም ንብረቶች',
            'property_building' => 'ንብረት/ህንፃ',
            'value' => 'ዋጋ',
            'tax' => 'ግብር',
            'year' => 'ዓመት',
            'status' => 'ሁኔታ',
            'mark_as_paid' => 'እንደተከፈለ ምልክት አድርግ',
            'mark_as_overdue' => 'ከሰአቱ እንዳለፈ ምልክት አድርግ',
            'view_payments' => 'ክፍያዎችን ተመልከት',
            'add_new_property' => 'አዲስ ንብረት ጨምር',
            'edit_property' => 'ንብረት አስተካክል',
            'owner' => 'ባለቤት',
            'property_name' => 'የንብረት ስም',
            'property_type' => 'የንብረት አይነት',
            'select_owner' => 'ባለቤት ምረጥ',
            'select_property_type' => 'የንብረት አይነት ምረጥ',
            'registration_number' => 'የምዝገባ ቁጥር',
            'construction_year' => 'የግንባታ ዓመት',
            'valuation' => 'ዋጋ መገምገም',
            'zoning' => 'የዞን ክፍፍል',
            'registration_date' => 'የምዝገባ ቀን',
            'tax_status' => 'የግብር ሁኔታ',
            'select_tax_status' => 'የግብር ሁኔታ ምረጥ',
            'list_of_properties' => 'የንብረቶች ዝርዝር',
            'show_all_properties' => 'ሁሉንም ንብረቶች አሳይ',
            'view_buildings' => 'ህንፃዎችን ተመልከት',
            'confirm_delete_property' => 'ይህን ንብረት መሰረዝ መፈለግዎን እርግጠኛ ነዎት?',
            'add_new_building' => 'አዲስ ህንፃ ጨምር',
            'edit_building' => 'ህንፃ አስተካክል',
            'building_name' => 'የህንፃ ስም',
            'description' => 'መግለጫ',
            'select_property' => 'ንብረት ምረጥ',
            'list_of_buildings' => 'የህንፃዎች ዝርዝር',
            'show_all_buildings' => 'ሁሉንም ህንፃዎች አሳይ',
            'floor_count' => 'የወለል ብዛት',
            'room_count' => 'የክፍሎች ብዛት',
            'building_area' => 'የህንፃ ስፋት (ካሬ ሜትር)',
            'construction_type' => 'የግንባታ አይነት',
            'building_type' => 'የህንፃ አይነት',
            'select_construction_type' => 'የግንባታ አይነት ምረጥ',
            'select_building_type' => 'የህንፃ አይነት ምረጥ',
            'wood' => 'እንጨት',
            'concrete' => 'ኮንክሪት',
            'mixed' => 'ድብልቅ',
            'Residential' => 'መኖሪያ',
            'Commercial' => 'ንግድ',
            'Mixed' => 'ድብልቅ',
            'Other' => 'ሌላ',
            'add_new_payment' => 'አዲስ ክፍያ ጨምር',
            'edit_payment' => 'ክፍያ አስተካክል',
            'assessment' => 'ግምገማ',
            'select_assessment' => 'ግምገማ ምረጥ',
            'amount' => 'መጠን',
            'payment_date' => 'የክፍያ ቀን',
            'payment_method' => 'የክፍያ ዘዴ',
            'select_payment_method' => 'የክፍያ ዘዴ ምረጥ',
            'cash' => 'ጥሬ ገንዘብ',
            'credit_card' => 'ክሬዲት ካርድ',
            'bank_transfer' => 'የባንክ ዝውውር',
            'mobile_payment' => 'የሞባይል ክፍያ',
            'list_of_payments' => 'የክፍያዎች ዝርዝር',
            'show_all_payments' => 'ሁሉንም ክፍያዎች አሳይ',
            'report_options' => 'የሪፖርት አማራጮች',
            'report_type' => 'የሪፖርት አይነት',
            'tax_collection' => 'የግብር ልኮ',
            'property_taxes' => 'የንብረት ግብሮች',
            'building_taxes' => 'የህንፃ ግብሮች',
            'delinquent_taxes' => 'ያልተከፈሉ ግብሮች',
            'date_range' => 'የቀን ገደብ',
            'start_date' => 'የመጀመሪያ ቀን',
            'end_date' => 'የመጨረሻ ቀን',
            'generate_report' => 'ሪፖርት ፍጠር',
            'tax_collection_report' => 'የግብር ልኮ ሪፖርት',
            'property_tax_report' => 'የንብረት ግብር ሪፖርት',
            'building_tax_report' => 'የህንፃ ግብር ሪፖርት',
            'delinquent_tax_report' => 'ያልተከፈሉ ግብሮች ሪፖርት',
            'print' => 'ተጭን',
            'export' => 'ላክ',
            'tax_collection_summary' => 'የግብር ልኮ ማጠቃለያ',
            'total_collected' => 'ጠቅላላ የተሰበሰበ',
            'property_id' => 'የንብረት መለያ',
            'total_tax' => 'ጠቅላላ ግብር',
            'balance' => 'ቀሪ',
            'grand_total' => 'አጠቃላይ ድምር',
            'building_id' => 'የህንፃ መለያ',
            'assessment_id' => 'የግምገማ መለያ',
            'tax_amount' => 'የግብር መጠን',
            'due_date' => 'የማብቂያ ቀን',
            'days_overdue' => 'ያለፉ ቀናት',
            'total_delinquent_taxes' => 'ጠቅላላ ያልተከፈሉ ግብሮች',
            'tax_collected' => 'የተሰበሰበ ግብር',
            'for_fiscal_year' => 'ለበጀት ዓመት',
            'room_management' => 'የክፍል አስተዳደር',
            'add_new_room' => 'አዲስ ክፍል ጨምር',
            'edit_room' => 'ክፍል አስተካክል',
            'select_building' => 'ህንፃ ምረጥ',
            'room_type' => 'የክፍል አይነት',
            'select_type' => 'አይነት ምረጥ',
            'room_number' => 'የክፍል ቁጥር',
            'floor_number' => 'የወለል ቁጥር',
            'area' => 'ስፋት',
            'monthly_rent' => 'ወርሃዊ ኪራይ',
            'currently_occupied' => 'አሁን የተያዘ',
            'list_of_rooms' => 'የክፍሎች ዝርዝር',
            'all_buildings' => 'ሁሉም ህንፃዎች',
            'all_rooms' => 'ሁሉም ክፍሎች',
            'occupied' => 'የተያዘ',
            'vacant' => 'ባዶ',
            'id' => 'መለያ',
            'building_property' => 'ህንፃ/ንብረት',
            'floor' => 'ወለል',
            'type' => 'አይነት',
            'rent' => 'ኪራይ',
            'confirm_delete_room' => 'ይህን ክፍል መሰረዝ መፈለግዎን እርግጠኛ ነዎት?',
            'view_contracts' => 'ኮንትራቶችን ተመልከት',
            'tax_assessment' => 'የግብር ግምገማ',
            'new_contract' => 'አዲስ ኪራይ ውል',
            'edit_contract' => 'ኪራይ ውል አስተካክል',
            'select_room' => 'ክፍል ምረጥ',
            'tenant_name' => 'የተከራይ ስም',
            'tenant_phone' => 'የተከራይ ስልክ',
            'tenant_id_number' => 'የተከራይ መለያ ቁጥር',
            'deposit_amount' => 'ዋስትና መጠን',
            'list_of_rent_contracts' => 'የኪራይ ውሎች ዝርዝር',
            'room' => 'ክፍል',
            'tenant' => 'ተከራይ',
            'period' => 'ጊዜ',
            'deposit' => 'ዋስትና',
            'active' => 'ንቁ',
            'terminated' => 'የተቋረጠ',
            'completed' => 'የተጠናቀቀ',
            'confirm_delete_contract' => 'ይህን ኪራይ ውል መሰረዝ መፈለግዎን እርግጠኛ ነዎት?',
            'activate' => 'ንቃተው',
            'terminate' => 'ተዉው',
            'mark_completed' => 'እንደተጠናቀቀ ምልክት አድርግ',
            'view_payments' => 'ክፍያዎችን ተመልከት',
            'generate_vacancy_report' => 'የባዶ ክፍሎች ሪፖርት ይፍጠሩ',
            'reporting_period' => 'የሪፖርት ጊዜ',
            'submission_method' => 'የማስፈር ዘዴ',
            'manual_submission' => 'እጅ በእጅ ማስፈር',
            'api_submission' => 'API በኩል ማስፈር',
            'email_submission' => 'ኢሜይል በኩል ማስፈር',
            'report_contents' => 'የሪፖርት ይዘት',
            'vacancy_report_info' => 'ይህ ሪፖርት ከሪፖርት ጊዜ መጨረሻ ከ30 ቀናት በላይ የቆዩ ባዶ ክፍሎችን ያካትታል።',
            'quick_stats' => 'ፈጣን ስታቲስቲክስ',
            'currently_vacant_rooms' => 'አሁን ያሉ ባዶ ክፍሎች',
            'unreported_vacancies' => 'ያልተለጠፉ ረጅም ጊዜ ያለፉ ባዶ ክፍሎች',
            'vacant_rooms_requiring_reporting' => 'ሪፖርት የሚያስፈልጋቸው ባዶ ክፍሎች',
            'owner' => 'ባለቤት',
            'days_vacant' => 'ባዶ የሆኑት ቀናት',
            'last_reported' => 'ያለፈው ሪፖርት',
            'never' => 'ፈጽሞ',
            'reported' => 'ተለጥፏል',
            'non-compliant' => 'ያልተላለፈ',
            'submitted_reports_history' => 'የቀረቡ ሪፖርቶች ታሪክ',
            'report_id' => 'የሪፖርት መለያ',
            'submitted_on' => 'ቀርቧል በ',
            'vacancies_reported' => 'የተለጠፉ ባዶ ክፍሎች',
            'reference' => 'ማጣቀሻ',
            'generate_submit_report' => 'ሪፖርት ፍጠር እና አስፈር',
            'new_payment' => 'አዲስ ክፍያ',
            'rent_contract' => 'ኪራይ ውል',
            'select_contract' => 'ውል ምረጥ',
            'period_month' => 'የጊዜ ወር',
            'select_month' => 'ወር ምረጥ',
            'period_year' => 'የጊዜ ዓመት',
            'receipt_number' => 'የደረሰኝ ቁጥር',
            'received_by' => 'የተቀበለው በ',
            'room_tenant' => 'ክፍል/ተከራይ',
            'date' => 'ቀን',
            'method' => 'ዘዴ',
            'receipt' => 'ደረሰኝ',
            'contract_details' => 'የውል ዝርዝሮች',
            'contract_period' => 'የውል ጊዜ',
            'print_receipt' => 'ደረሰኝ ተጭን',
            'january' => 'ጥር',
            'february' => 'ይካቲት',
            'march' => 'መጋቢት',
            'april' => 'ሚያዝያ',
            'may' => 'ግንቦት',
            'june' => 'ሰኔ',
            'july' => 'ሐምሌ',
            'august' => 'ነሐሴ',
            'september' => 'መስከረም',
            'october' => 'ጥቅምት',
            'november' => 'ህዳር',
            'december' => 'ታህሳስ',
            'unauthorized_access' => 'ያልተፈቀደ መዳረሻ።',
            'invalid_form_data' => 'የማይሰራ የቅጽ ውሂብ።',
            'invalid_contract' => 'የማያገለግል ወይም ንቁ ያልሆነ ኪራይ ውል።',
            'duplicate_payment_period' => 'ለዚህ ውል እና ጊዜ ክፍያ ቀድሞውኑ አለ።',
            'error_processing_payment' => 'ክፍያ በማስኬድ ላይ ስህተት',
            'invalid_delete_request' => 'የማይሰራ የመሰረዝ ጥያቄ።',
            'payment_not_found' => 'ክፍያ አልተገኘም።',
            'error_deleting_payment' => 'ክፍያ በሚሰረዝበት ጊዜ ስህተት',
            'payment_added' => 'ክፍያ በተሳካ ሁኔታ ታክሏል።',
            'payment_updated' => 'ክፍያ በተሳካ ሁኔታ ተሻሽሏል።',
            'payment_deleted' => 'ክፍያ በተሳካ ሁኔታ ተሰርዟል።',
            'confirm_delete_payment' => 'ይህን ክፍያ መሰረዝ መፈለግዎን እርግጠኛ ነዎት?',
            'error_fetching_rent' => 'የኪራይ መጠን በማግኘት ላይ ስህተት።',
            'add_new_room_type' => 'አዲስ የክፍል አይነት ጨምር',
            'edit_room_type' => 'የክፍል አይነት አስተካክል',
            'type_name' => 'የአይነት ስም',
            'list_of_room_types' => 'የክፍል አይነቶች ዝርዝር',
            'invalid_type_name' => 'የክፍል አይነት ስም ያስፈልጋል እና ከ100 ቁምፊዎች ያነሰ መሆን አለበት።',
            'duplicate_type_name' => 'ይህ የክፍል አይነት ስም አስቀድሞ አለ።',
            'error_processing_type' => 'የክፍል አይነት በማስተካከል ላይ ስህተት',
            'type_has_rooms' => 'ከክፍሎች ጋር የተያያዘ የክፍል አይነት ሊሰረዝ አይችልም።',
            'error_deleting_type' => 'የክፍል አይነት በሚሰረዝበት ጊዜ ስህተት',
            'type_added' => 'የክፍል አይነት በተሳካ ሁኔታ ታክሏል።',
            'type_updated' => 'የክፍል አይነት በተሳካ ሁኔታ ተሻሽሏል።',
            'type_deleted' => 'የክፍል አይነት በተሳካ ሁኔታ ተሰርዟል።',
            'confirm_delete_type' => 'ይህን የክፍል አይነት መሰረዝ መፈለግዎን እርግጠኛ ነዎት?',
            'contract_not_found' => 'ኪራይ ውል አልተገኘም።',
            'contract_already_in_status' => 'ኪራይ ውል ቀድሞውኑ በዚህ ሁኔታ ላይ ነው።',
            'invalid_status' => 'የማይሰራ ሁኔታ።',
            'status_must_match_dates' => 'ሁኔታው ከውሉ ቀኖች ጋር መጣጣም አለበት።',
            'contract_status_updated' => 'የኪራይ ውል ሁኔታ ወደ',
            'failed_to_update_status' => 'የኪራይ ውል ሁኔታን ማዘመን አልተሳካም',
            'contract_deleted' => 'ኪራይ ውል በተሳካ ሁኔታ ተሰርዟል።',
            'failed_to_delete_contract' => 'ኪራይ ውል መሰረዝ አልተሳካም',
            'invalid_csrf_token' => 'የማይሰራ CSRF ማስመሰያ።',
            'required_fields_missing' => 'አስፈላጊ መስኮች ጎድለዋል።',
            'end_date_must_be_after_start_date' => 'የመጨረሻ ቀን ከመጀመሪያ ቀን በኋላ መሆን አለበት።',
            'invalid_room_selected' => 'የማይሰራ ክፍል ተመርጧል።',
            'room_not_available' => 'ክፍሉ አይገኝም።',
            'overlapping_contract_exists' => 'ተደራራቢ ኪራይ ውል ቀድሞውኑ አለ።',
            'failed_to_save_contract' => 'ኪራይ ውል መቆጠብ አልተሳካም',
            'invalid_request' => 'የማይሰራ ጥያቄ።',
            // New keys for tax assessments page
            'rental_tax' => 'የኪራይ ግብር',
            'assessment_not_found' => 'ግምገማ አልተገኘም።',
            'select_tax_rate' => 'የግብር መጠን ምረጥ',
            'due_date' => 'የማብቂያ ቀን',
            'generate_invoice' => 'ደረሰኝ ፍጠር',
            'confirm_delete_assessment' => 'ይህን ግምገማ መሰረዝ መፈለግዎን እርግጠኛ ነዎት?'
        ]
    ];
    $lang = $_SESSION['lang'] ?? 'en';
    return $translations[$lang][$key] ?? $key;
}

/**
 * Display session-based success or error messages, supporting both single messages and arrays
 */
function displayMessages() {
    $output = '';

    // Handle success messages (array or single)
    if (isset($_SESSION['success_messages']) && is_array($_SESSION['success_messages']) && !empty($_SESSION['success_messages'])) {
        foreach ($_SESSION['success_messages'] as $message) {
            $output .= '<div class="alert alert-success alert-dismissible">';
            $output .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>';
            $output .= sanitize($message);
            $output .= '</div>';
        }
        unset($_SESSION['success_messages']);
    } elseif (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
        $output .= '<div class="alert alert-success alert-dismissible">';
        $output .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>';
        $output .= sanitize($_SESSION['success_message']);
        $output .= '</div>';
        unset($_SESSION['success_message']);
    }

    // Handle error messages (array or single)
    if (isset($_SESSION['error_messages']) && is_array($_SESSION['error_messages']) && !empty($_SESSION['error_messages'])) {
        foreach ($_SESSION['error_messages'] as $message) {
            $output .= '<div class="alert alert-danger alert-dismissible">';
            $output .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>';
            $output .= sanitize($message);
            $output .= '</div>';
        }
        unset($_SESSION['error_messages']);
    } elseif (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
        $output .= '<div class="alert alert-danger alert-dismissible">';
        $output .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>';
        $output .= sanitize($_SESSION['error_message']);
        $output .= '</div>';
        unset($_SESSION['error_message']);
    }

    echo $output;
}
?>