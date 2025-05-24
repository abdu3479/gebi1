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
            'confirm_delete_type' => 'Are you sure you want to delete this room type?'
        ],
        'am' => [
            'dashboard' => 'ዳሽቦርድ',
            'property_owners' => 'የንብረት ባለቤቶች',
            'properties' => 'ንብረቶች',
            'buildings' => 'ህንፃዎች',
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
            'construction_year' => 'የግንባታ ዓመት',
            'address' => 'አድራሻ',
            'building_type' => 'የህንፃ አይነት',
            'select_construction_type' => 'የግንባታ አይነት ምረጥ',
            'select_building_type' => 'የህንፃ አይነት ምረጥ',
            'wood' => 'እንጨት',
            'concrete' => 'ኮንክሪት',
            'mixed' => 'ድብልቅ',
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
    'start_date' => 'የመጀመሪያ ቀን',
    'end_date' => 'የመጨረሻ ቀን',
    'monthly_rent' => 'ወርሃዊ ኪራይ',
    'notes' => 'ማስታወሻዎች',
    'vacancy_reports' => 'የባዶ ክፍሎች ሪፖርቶች',
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
    'report_submitted' => 'ሪፖርት በተሳካ ሁኔታ ቀርቧል',
    'report_submit_failed' => 'ሪፖርት ማስፈር አልተሳካም',
    'report_error' => 'በሪፖርት ሂደት ላይ ስህተት',
    'duplicate_report' => 'ለዚህ ጊዜ ያለፈ ሪፖርት አለ',
    'no_vacant_rooms' => 'ሪፖርት ለማድረግ ባዶ ክፍሎች የሉም',
    'invalid_report_params' => 'የማይሰራ የሪፖርት መለኪያ',
    'error_fetching_vacancies' => 'ባዶ ክፍሎችን በማግኘት ላይ ስህተት',
    'error_fetching_reports' => 'ሪፖርቶችን በማግኘት ላይ ስህተት',
    'stats_error' => 'ስታቲስቲክስ በማግኘት ላይ ስህተት',
    'notifications_error' => 'ማሳወቂያዎችን በማግኘት ላይ ስህተት',
    'january' => 'ጃንዩወሪ',
    'february' => 'ፌብሩወሪ',
    'march' => 'ማርች',
    'april' => 'ኤፕሪል',
    'may' => 'ሜይ',
    'june' => 'ጁን',
    'july' => 'ጁላይ',
    'august' => 'ኦገስት',
    'september' => 'ሴፕቴምበር',
    'october' => 'ኦክቶበር',
    'november' => 'ኖቬምበር',
    'december' => 'ዲሴምበር',
    'contract_not_found' => 'ኪራይ ውል አልተገኘም።',
    'invalid_contract' => 'የማያገለግል ወይም ንቁ ያልሆነ ኪራይ ውል።',
    'contract_added' => 'ኪራይ ውል በተሳካ ሁኔታ ታክሏል።',
    'contract_updated' => 'ኪራይ ውል በተሳካ ሁኔታ ተሻሽሏል።',
    'contract_deleted' => 'ኪራይ ውል በተሳካ ሁኔታ ተሰርዟል።',
    'contract_status_changed' => 'የኪራይ ውል ሁኔታ ተቀይሯል።',
            'Residential' => 'መኖሪያ',
            'Commercial' => 'ንግድ',
            'Mixed' => 'ድብልቅ',
            'Other' => 'ሌላ',
            'submit' => 'አስገባ',
            'update' => 'አዘምን',
            'cancel' => 'ተወው',
            'actions' => 'ተግባራት',
            'edit' => 'አስተካክል',
            'delete' => 'ሰርዝ',
            'property' => 'ንብረት',
            'owner' => 'ባለቤት',
            'notifications' => 'ማሳወቂያዎች',
            'pending_tax_assessments' => 'በመጠባበቅ ላይ ያሉ የግብር ግምገማዎች',
            'see_all_notifications' => 'ሁሉንም ማሳወቂያዎች ተመልከት',
            'user_menu' => 'የተጠቃሚ ምናሌ',
            'profile' => 'የእኔ መገለጫ',
            'logout' => 'ውጣ',
            'unauthorized_access' => 'ያልተፈቀደ መዳረሻ።',
            'invalid_form_data' => 'የማይሰራ የቅጽ ውሂብ።',
            'add_new_property' => 'አዲስ ንብረት ጨምር',
            'edit_property' => 'ንብረት አስተካክል',
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
            'buildings' => 'ህንፃዎች',
            'Active' => 'ንቁ',
            'Exempt' => 'ከግብር ነፃ',
            'Delinquent' => 'ያልተከፈለ',
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
    'rooms' => 'ክፍሎች',
    'status' => 'ሁኔታ',
    'invalid_type_name' => 'የክፍል አይነት ስም ያስፈልጋል እና ከ100 ቁምፊዎች ያነሰ መሆን አለበት።',
    'duplicate_type_name' => 'ይህ የክፍል አይነት ስም አስቀድሞ አለ።',
    'error_processing_type' => 'የክፍል አይነት በማስተካከል ላይ ስህተት',
    'type_has_rooms' => 'ከክፍሎች ጋር የተያያዘ የክፍል አይነት ሊሰረዝ አይችልም።',
    'error_deleting_type' => 'የክፍል አይነት በሚሰረዝበት ጊዜ ስህተት',
    'type_added' => 'የክፍል አይነት በተሳካ ሁኔታ ታክሏል።',
    'type_updated' => 'የክፍል አይነት በተሳካ ሁኔታ ተሻሽሏል።',
    'type_deleted' => 'የክፍል አይነት በተሳካ ሁኔታ ተሰርዟል።',
    'confirm_delete_type' => 'ይህን የክፍል አይነት መሰረዝ መፈለግዎን እርግጠኛ ነዎት?',
    'add_new_room_type' => 'አዲስ የክፍል አይነት ጨምር',
    'edit_room_type' => 'የክፍል አይነት አስተካክል',
    'type_name' => 'የአይነት ስም',
    'list_of_room_types' => 'የክፍል አይነቶች ዝርዝር',
    'room_types' => 'የክፍል አይነቶች'

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