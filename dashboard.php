<?php
// Start session with secure settings
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);


include 'includes/nav.php';
include 'config/lang.php';
require_once 'config/database.php';
require_once 'config/twilio.php'; // New config file for Twilio
require_once 'classes/Asset.php';
require_once 'classes/Bill.php';
require_once 'classes/Lease.php';
require_once 'classes/Owner.php';
require_once 'classes/Payment.php';
require_once 'utils/sms.php';
require_once 'vendor/autoload.php';

use Twilio\Rest\Client;

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set role and language, default to 'viewer' and 'en'
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';
$current_page = basename($_SERVER['PHP_SELF']);

// CSRF token (regenerated after POST for security)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize database and classes
try {
    $db = new Database();
    $conn = $db->getConnection();
    $asset = new Asset($conn);
    $bill = new Bill($conn);
    $lease = new Lease($conn);
    $owner = new Owner($conn);
    $payment = new Payment($conn);
} catch (Exception $e) {
    error_log("Initialization failed: " . $e->getMessage());
    die(htmlspecialchars($translations[$lang]['init_error'] ?? ($lang === 'am' ? 'ስርዓት መጀመር አልተሳካም። እባክዎ ቆይተው ይሞክሩ።' : 'System initialization failed. Please try again later.')));
}

// Validate Twilio credentials
if (empty(TWILIO_ACCOUNT_SID) || empty(TWILIO_AUTH_TOKEN) || empty(TWILIO_PHONE_NUMBER)) {
    error_log("Twilio credentials are missing or invalid.");
    $_SESSION['warning'] = htmlspecialchars($translations[$lang]['twilio_config_error'] ?? ($lang === 'am' ? 'Twilio መለያ መረጃ አልተዋቀረም።' : 'Twilio credentials not configured.'));
}

// Handle manual SMS sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_sms') {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = htmlspecialchars($translations[$lang]['csrf_error'] ?? ($lang === 'am' ? 'የማረጋገጫ ስህተት' : 'Invalid CSRF token'));
        error_log("CSRF token validation failed: POST=" . ($_POST['csrf_token'] ?? 'none') . ", SESSION=" . $_SESSION['csrf_token']);
    } elseif (empty($_POST['bill_ids'])) {
        $_SESSION['error'] = htmlspecialchars($translations[$lang]['no_bills_selected'] ?? ($lang === 'am' ? 'ምንም ቢሎች አልተመረጡም።' : 'No bills selected.'));
    } else {
        // Validate bill_ids are positive integers
        $bill_ids = array_filter(array_map('intval', $_POST['bill_ids']), fn($id) => $id > 0);
        if (empty($bill_ids)) {
            $_SESSION['error'] = htmlspecialchars($translations[$lang]['invalid_bills'] ?? ($lang === 'am' ? 'ልክ ያልሆኑ ቢሎች ተመርጠዋል።' : 'Invalid bills selected.'));
        } else {
            sendSMSNotifications($conn, $bill_ids, $lang, 'manual');
        }
    }
    // Regenerate CSRF token after POST
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to send SMS using Twilio
function sendSMS($phone, $message) {
    try {
        $twilio = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
        $sms = $twilio->messages->create(
            $phone,
            [
                'from' => TWILIO_PHONE_NUMBER,
                'body' => $message,
                'statusCallback' => TWILIO_STATUS_CALLBACK
            ]
        );
        return [
            'status' => 'success',
            'sid' => $sms->sid
        ];
    } catch (Exception $e) {
        error_log("Twilio SMS error for phone $phone: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// Function to send SMS notifications
function sendSMSNotifications($conn, $bill_ids, $lang, $type = 'manual') {
    $placeholders = implode(',', array_fill(0, count($bill_ids), '?'));
    $query = "
        SELECT 
            b.id AS bill_id, b.bill_number, b.total_amount, b.late_fee, b.tax_amount, b.additional_charges,
            b.due_date, b.status, a.property_name, a.asset_code,
            o.id AS owner_id, o.full_name, o.phone, o.address
        FROM outstanding_bills b
        JOIN lease_agreements la ON b.lease_id = la.id
        JOIN assets a ON la.asset_id = a.id
        JOIN owners o ON la.owner_id = o.id
        WHERE b.id IN ($placeholders)
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $_SESSION['error'] = htmlspecialchars($translations[$lang]['sms_query_error'] ?? ($lang === 'am' ? 'የኤስኤምኤስ ጥያቄ አልተሳካም: ' : 'SMS query failed: ')) . $conn->error;
        error_log("SMS query preparation failed: " . $conn->error);
        return;
    }
    $stmt->bind_param(str_repeat('i', count($bill_ids)), ...$bill_ids);
    if (!$stmt->execute()) {
        $_SESSION['error'] = htmlspecialchars($translations[$lang]['sms_query_error'] ?? ($lang === 'am' ? 'የኤስኤምኤስ ጥያቄ ማስፈጸም አልተሳካም: ' : 'SMS query execution failed: ')) . $stmt->error;
        error_log("SMS query execution failed: " . $stmt->error);
        $stmt->close();
        return;
    }

    $stmt->bind_result(
        $bill_id, $bill_number, $total_amount, $late_fee, $tax_amount, $additional_charges,
        $due_date, $status, $property_name, $asset_code,
        $owner_id, $full_name, $phone, $address
    );

    $notifications = [];
    $invalid_phones = [];

    $template_name = $type === 'scheduled' ? 'upcoming_payment_am' : 'payment_reminder_am';
    $template_query = "SELECT id, template, variables FROM sms_templates WHERE name = ? AND is_active = 1";
    $template_stmt = $conn->prepare($template_query);
    if (!$template_stmt) {
        $_SESSION['error'] = htmlspecialchars($translations[$lang]['sms_template_error'] ?? ($lang === 'am' ? 'የኤስኤምኤስ አብነት ጥያቄ አልተሳካም: ' : 'SMS template query failed: ')) . $conn->error;
        error_log("Template query preparation failed: " . $conn->error);
        $stmt->close();
        return;
    }
    $template_stmt->bind_param('s', $template_name);
    $template_stmt->execute();
    $template_result = $template_stmt->get_result();
    if (!$template_result->num_rows) {
        $_SESSION['error'] = htmlspecialchars($translations[$lang]['sms_template_not_found'] ?? ($lang === 'am' ? "የ$template_name አብነት አልተገኘም።" : "$template_name template not found."));
        error_log("Template not found: $template_name");
        $stmt->close();
        $template_stmt->close();
        return;
    }
    $template = $template_result->fetch_assoc();
    $template_id = $template['id'];
    $template_text = $template['template'];
    $variables = array_map('trim', explode(',', $template['variables']));
    $template_stmt->close();

    while ($stmt->fetch()) {
        if (empty($phone) || !isValidPhoneNumber($phone)) {
            $invalid_phones[] = $full_name . " (Bill: " . $bill_number . ")";
            error_log("Invalid or missing phone number for bill ID $bill_id: " . ($phone ?? 'none'));
            continue;
        }

        $replacements = [
            '{{amount}}' => number_format($total_amount, 2),
            '{{due_date}}' => $due_date,
            '{{property}}' => $property_name ?: $asset_code,
            '{{late_fee}}' => number_format($late_fee, 2),
            '{{tax_amount}}' => number_format($tax_amount, 2),
            '{{additional_charges}}' => number_format($additional_charges, 2),
            '{{bill_number}}' => $bill_number,
            '{{full_name}}' => $full_name,
            '{{address}}' => $address ?: 'N/A',
        ];
        $message = $template_text;
        foreach ($variables as $var) {
            $key = "{{$var}}";
            $message = str_replace($key, $replacements[$key] ?? '', $message);
        }
        $message .= ($lang === 'am' ? ' በቴሌብር (*127#) ይክፈሉ።' : ' Pay via Telebirr (*127#).');

        $result = sendSMS($phone, $message);
        $status = $result['status'] === 'success' ? 'sent' : 'failed';
        $message_id = $result['status'] === 'success' ? $result['sid'] : null;
        $error_message = $result['status'] === 'error' ? $result['message'] : null;

        $notifications[] = [
            'bill_id' => $bill_id,
            'owner_id' => $owner_id,
            'phone' => $phone,
            'message' => $message,
            'send_date' => date('Y-m-d H:i:s'),
            'type' => $type,
            'status' => $status,
            'message_id' => $message_id,
            'error_message' => $error_message,
            'template_id' => $template_id
        ];
    }

    $stmt->close();

    if (!empty($invalid_phones)) {
        $_SESSION['error'] = htmlspecialchars($translations[$lang]['invalid_phones'] ?? ($lang === 'am' ? 'የስልክ ቁጥሮች ልክ አይደሉም ለ: ' : 'Invalid phone numbers for: ')) . implode(', ', $invalid_phones);
    }

    if (empty($notifications)) {
        $_SESSION['error'] = $_SESSION['error'] ?? htmlspecialchars($translations[$lang]['no_valid_phones'] ?? ($lang === 'am' ? 'ለተመረጡት ቢሎች ተገቢ የሆነ ስልክ ቁጥር አልተገኘም።' : 'No valid phone numbers found for selected bills.'));
        return;
    }

    logNotifications($conn, $notifications, $lang);
    $_SESSION['success'] = count($notifications) . htmlspecialchars($translations[$lang]['sms_sent'] ?? ($lang === 'am' ? ' የኤስኤምኤስ መልእክቶች በተሳካ ሁኔታ ተልከዋል።' : ' SMS messages sent successfully.'));
}

// Function to log notifications
function logNotifications($conn, $notifications, $lang) {
    $notif_query = "
        INSERT INTO sms_notifications (
            template_id, recipient_type, recipient_id, bill_id, phone_number, message, 
            status, message_id, error_message, attempts, send_date, type, created_by
        ) VALUES (?, 'owner', ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
    ";
    $log_query = "
        INSERT INTO sms_delivery_logs (
            notification_id, phone_number, status, response, created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ";

    $notif_stmt = $conn->prepare($notif_query);
    $log_stmt = $conn->prepare($log_query);
    if (!$notif_stmt || !$log_stmt) {
        $_SESSION['error'] = htmlspecialchars($translations[$lang]['log_error'] ?? ($lang === 'am' ? 'የማሳሰቢያ መዝገብ ዝግጅት አልተሳካም: ' : 'Notification log preparation failed: ')) . $conn->error;
        error_log("Log preparation failed: " . $conn->error);
        return;
    }

    foreach ($notifications as $notif) {
        $created_by = $_SESSION['user_id'] ?? 0;
        $notif_stmt->bind_param(
            'iiissssssis',
            $notif['template_id'],
            $notif['owner_id'],
            $notif['bill_id'],
            $notif['phone'],
            $notif['message'],
            $notif['status'],
            $notif['message_id'],
            $notif['error_message'],
            $notif['send_date'],
            $notif['type'],
            $created_by
        );
        if (!$notif_stmt->execute()) {
            error_log("Notification log insertion failed: " . $notif_stmt->error);
            continue;
        }
        $notification_id = $conn->insert_id;

        $log_status = $notif['status'] === 'failed' ? 'failed' : 'sent';
        $response = $notif['error_message'] ?? json_encode($notif, JSON_UNESCAPED_UNICODE);
        $log_stmt->bind_param('isss', $notification_id, $notif['phone'], $log_status, $response);
        $log_stmt->execute();
    }

    $notif_stmt->close();
    $log_stmt->close();
}

// Function to send scheduled SMS
function sendAutomaticSMS($conn, $lang) {
    $today = date('Y-m-d');
    $due_date_limit = date('Y-m-d', strtotime('+7 days'));
    $query = "
        SELECT 
            b.id AS bill_id, b.bill_number, b.total_amount, b.late_fee, b.tax_amount, b.additional_charges,
            b.due_date, b.status, a.property_name, a.asset_code,
            o.id AS owner_id, o.full_name, o.phone, o.address
        FROM outstanding_bills b
        JOIN lease_agreements la ON b.lease_id = la.id
        JOIN assets a ON la.asset_id = a.id
        JOIN owners o ON la.owner_id = o.id
        LEFT JOIN sms_notifications sn ON sn.recipient_id = o.id 
            AND sn.bill_id = b.id 
            AND sn.type = 'scheduled' 
            AND sn.send_date >= NOW() - INTERVAL 24 HOUR
        WHERE b.status = 'pending'
        AND b.due_date BETWEEN ? AND ?
        AND o.phone LIKE '+251%'
        AND LENGTH(o.phone) = 12
        AND sn.id IS NULL
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Scheduled SMS query preparation failed: " . $conn->error);
        return;
    }
    $stmt->bind_param('ss', $today, $due_date_limit);
    $stmt->execute();
    $stmt->bind_result(
        $bill_id, $bill_number, $total_amount, $late_fee, $tax_amount, $additional_charges,
        $due_date, $status, $property_name, $asset_code,
        $owner_id, $full_name, $phone, $address
    );

    $bill_ids = [];
    while ($stmt->fetch()) {
        $bill_ids[] = $bill_id;
    }
    $stmt->close();

    if (!empty($bill_ids)) {
        sendSMSNotifications($conn, $bill_ids, $lang, 'scheduled');
    }
}

// Fetch outstanding bills
$today = date('Y-m-d');
$due_date_limit = date('Y-m-d', strtotime('+7 days'));
$bills_query = "
    SELECT 
        b.id AS bill_id, b.bill_number, b.total_amount, b.late_fee, b.tax_amount, b.additional_charges,
        b.due_date, b.status, a.property_name, a.asset_code,
        o.full_name, o.phone, o.address
    FROM outstanding_bills b
    JOIN lease_agreements la ON b.lease_id = la.id
    JOIN assets a ON la.asset_id = a.id
    JOIN owners o ON la.owner_id = o.id
    WHERE b.status IN ('pending', 'overdue')
    AND (b.due_date <= ? OR b.status = 'overdue')
    ORDER BY b.due_date ASC
";
$bills_stmt = $conn->prepare($bills_query);
$bills_stmt->bind_param('s', $due_date_limit);
$bills_stmt->execute();
$bills_result = $bills_stmt->get_result();

// Fetch recent SMS notifications
$notifications_query = "
    SELECT sn.id, sn.phone_number, sn.message, sn.status, sn.send_date, sn.message_id, sn.error_message,
           sn.type, o.full_name
    FROM sms_notifications sn
    JOIN owners o ON sn.recipient_id = o.id
    WHERE sn.recipient_type = 'owner'
    ORDER BY sn.send_date DESC
    LIMIT 10
";
$notifications_result = $conn->query($notifications_query);

// Send scheduled SMS for admin/billing_officer
if (in_array($role, ['admin', 'billing_officer'])) {
    sendAutomaticSMS($conn, $lang);
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translations[$lang]['dashboard_title'] ?? ($lang === 'am' ? 'ዳሽቦርድ - ወራቤ መሬት ሊዝ' : 'Dashboard - Werabe Land Lease')); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Abyssinica+SIL&display=swap" rel="stylesheet">
    <style>
        .amharic { font-family: 'Abyssinica SIL', sans-serif; }
        .telebirr-logo { height: 40px; width: auto; border-radius: 50%; }
        .sms-section { background: linear-gradient(135deg, #00A859 0%, #FFC107 100%); padding: 20px; border-radius: 10px; }
        .sms-table th, .sms-table td { border: 1px solid #E0E0E0; }
        .sms-table th { background: #00A859; color: white; }
        /* Aligned with list.php status colors */
        .sms-table .status-pending { background: #cce5ff; color: #004085; }
        .sms-table .status-overdue { background: #f8d7da; color: #721c24; }
        .sms-table .status-delivered { background: #d4edda; color: #155724; }
        .sms-table .status-failed { background: #f8d7da; color: #721c24; }
        .sms-table .status-sent { background: #17a2b8; color: white; }
        .btn-telebirr { background: #00A859; color: white; }
        .btn-telebirr:hover { background: #008040; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="dashboard.php" class="brand-link">
            <img src="assets/images/logo.jpg" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo htmlspecialchars($translations[$lang]['app_name'] ?? ($lang === 'am' ? 'ወራቤ መሬት ሊዝ' : 'Werabe Land Lease')); ?></span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['dashboard'] ?? ($lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard')); ?></p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['owners'] ?? ($lang === 'am' ? 'ባለቤቶች' : 'Owners')); ?> <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (!$is_viewer): ?>
                                <li class="nav-item">
                                    <a href="create_records_form.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['add_owner'] ?? ($lang === 'am' ? 'ባለቤት ጨምር' : 'Add Owner')); ?></p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a href="owner/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['list_owners'] ?? ($lang === 'am' ? 'ዝርዝር ባለቤቶች' : 'List Owners')); ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-building"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['assets'] ?? ($lang === 'am' ? 'ንብረቶች' : 'Assets')); ?> <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (!$is_viewer): ?>
                                <li class="nav-item">
                                    <a href="asset/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['add_asset'] ?? ($lang === 'am' ? 'ንብረት ጨምር' : 'Add Asset')); ?></p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a href="asset/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['list_assets'] ?? ($lang === 'am' ? 'ዝርዝር ንብረቶች' : 'List Assets')); ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['leases'] ?? ($lang === 'am' ? 'ሊዝ ስምምነቶች' : 'Leases')); ?> <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (!$is_viewer): ?>
                                <li class="nav-item">
                                    <a href="lease/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['add_lease'] ?? ($lang === 'am' ? 'ሊዝ ጨምር' : 'Add Lease')); ?></p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a href="lease/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['list_leases'] ?? ($lang === 'am' ? 'ዝርዝር ሊዝ ስምምነቶች' : 'List Leases')); ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-file-invoice"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['bills'] ?? ($lang === 'am' ? 'ቢሎች' : 'Bills')); ?> <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (!$is_viewer): ?>
                                <li class="nav-item">
                                    <a href="bills/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['add_bill'] ?? ($lang === 'am' ? 'ቢል ጨምር' : 'Add Bill')); ?></p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a href="bills/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['list_bills'] ?? ($lang === 'am' ? 'ዝርዝር ቢሎች' : 'List Bills')); ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-money-check-alt"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['payments'] ?? ($lang === 'am' ? 'ክፍያዎች' : 'Payments')); ?> <i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (!$is_viewer): ?>
                                <li class="nav-item">
                                    <a href="payments/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['add_payment'] ?? ($lang === 'am' ? 'ክፍያ ጨምር' : 'Add Payment')); ?></p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a href="payments/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['list_payments'] ?? ($lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Payments')); ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php if (!$is_viewer): ?>
                        <li class="nav-item">
                            <a href="create_records_form.php" class="nav-link <?php echo $current_page === 'create_records_form.php' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-plus"></i>
                                <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['create_records_form'] ?? ($lang === 'am' ? 'መዝገቦችን ይፍጠሩ' : 'Create Records')); ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!$is_viewer): ?>
                        <li class="nav-item">
                            <a href="tax/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-plus"></i>
                                <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['tax'] ?? ($lang === 'am' ? 'መዝገቦችን ይፍጠሩ' : 'tax')); ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                            <a href="../vot/index.html" class="nav-link <?php echo $current_page === 'index.html' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-plus"></i>
                                <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['CSSMS'] ?? ($lang === 'am' ? 'መዝገቦችን ይፍጠሩ' : 'CSSMS')); ?></p>
                            </a>
                        </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 amharic"><?php echo htmlspecialchars($translations[$lang]['dashboard'] ?? ($lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard')); ?></h1>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['warning'])): ?>
                    <div class="alert alert-warning alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($_SESSION['warning']); unset($_SESSION['warning']); ?>
                    </div>
                <?php endif; ?>

                <!-- Metrics Cards -->
                <div class="row">
                    <div class="col-lg-4 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <?php
                                try {
                                    $assets = $asset->getAll(['limit' => 1000]);
                                    echo "<h3>" . count($assets) . "</h3>";
                                } catch (Exception $e) {
                                    echo "<h3>Error</h3>";
                                    error_log("Asset count failed: " . $e->getMessage());
                                }
                                ?>
                                <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['total_assets'] ?? ($lang === 'am' ? 'ጠቅላላ ንብረቶች' : 'Total Assets')); ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <a href="asset/list.php" class="small-box-footer">
                                <?php echo htmlspecialchars($translations[$lang]['more_info'] ?? ($lang === 'am' ? 'ተጨማሪ መረጃ' : 'More info')); ?> <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <?php
                                try {
                                    $owners = $owner->getAll();
                                    echo "<h3>" . count($owners) . "</h3>";
                                } catch (Exception $e) {
                                    echo "<h3>Error</h3>";
                                    error_log("Owner count failed: " . $e->getMessage());
                                }
                                ?>
                                <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['total_owners'] ?? ($lang === 'am' ? 'ጠቅላላ ባለቤቶች' : 'Total Owners')); ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <a href="owner/list.php" class="small-box-footer">
                                <?php echo htmlspecialchars($translations[$lang]['more_info'] ?? ($lang === 'am' ? 'ተጨማሪ መረጃ' : 'More info')); ?> <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <?php
                                try {
                                    $bills = $bill->getAll();
                                    echo "<h3>" . count($bills) . "</h3>";
                                } catch (Exception $e) {
                                    echo "<h3>Error</h3>";
                                    error_log("Bill count failed: " . $e->getMessage());
                                }
                                ?>
                                <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['total_bills'] ?? ($lang === 'am' ? 'ጠቅላላ ቢሎች' : 'Total Bills')); ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <a href="bills/list.php" class="small-box-footer">
                                <?php echo htmlspecialchars($translations[$lang]['more_info'] ?? ($lang === 'am' ? 'ተጨማሪ መረጃ' : 'More info')); ?> <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <?php
                                try {
                                    $outstanding_bills = $bill->getAll(['status' => ['pending', 'overdue']]);
                                    $outstanding_count = 0;
                                    foreach ($outstanding_bills as $b) {
                                        if ($b['total_amount'] > 0) {
                                            $outstanding_count++;
                                        }
                                    }
                                    echo "<h3>" . $outstanding_count . "</h3>";
                                } catch (Exception $e) {
                                    echo "<h3>Error</h3>";
                                    error_log("Outstanding bills count failed: " . $e->getMessage());
                                }
                                ?>
                                <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['outstanding_bills'] ?? ($lang === 'am' ? 'ያልተከፈሉ ቢሎች' : 'Outstanding Bills')); ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <a href="bills/list.php" class="small-box-footer">
                                <?php echo htmlspecialchars($translations[$lang]['more_info'] ?? ($lang === 'am' ? 'ተጨማሪ መረጃ' : 'More info')); ?> <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <?php
                                try {
                                    $total_payments = $payment->countTotalPayments();
                                    echo "<h3>" . $total_payments . "</h3>";
                                } catch (Exception $e) {
                                    echo "<h3>Error</h3>";
                                    error_log("Payment count failed: " . $e->getMessage());
                                }
                                ?>
                                <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['total_payments'] ?? ($lang === 'am' ? 'ጠቅላላ ክፍያዎች' : 'Total Payments')); ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-money-check-alt"></i>
                            </div>
                            <a href="payments/list.php" class="small-box-footer">
                                <?php echo htmlspecialchars($translations[$lang]['more_info'] ?? ($lang === 'am' ? 'ተጨማሪ መረጃ' : 'More info')); ?> <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- SMS Management Section -->
                <div class="row" id="sms-management">
                    <div class="col-12">
                        <div class="card sms-section">
                            <div class="card-header">
                                <h3 class="card-title amharic">
                                    <img src="assets/images/logo.jpg" alt="Telebirr Logo" class="telebirr-logo mr-2" onerror="this.src='https://via.placeholder.com/40/00A859/FFFFFF?text=Telebirr';">
                                    <?php echo htmlspecialchars($translations[$lang]['sms_management'] ?? ($lang === 'am' ? 'ኤስኤምኤስ አስተዳደር' : 'SMS Management')); ?>
                                </h3>
                            </div>
                            <div class="card-body">
                                <h4 class="amharic"><?php echo htmlspecialchars($translations[$lang]['outstanding_bills_manual'] ?? ($lang === 'am' ? 'ያልተከፈሉ ቢሎች (ለእጅ መልእክት)' : 'Outstanding Bills (Manual SMS)')); ?></h4>
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="send_sms">
                                    <div class="table-responsive">
                                        <table class="table table-bordered sms-table">
                                            <thead>
                                                <tr>
                                                    <th><input type="checkbox" id="select-all"></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['bill_number'] ?? ($lang === 'am' ? 'የቢል ቁጥር' : 'Bill Number')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['owner'] ?? ($lang === 'am' ? 'ባለቤት' : 'Owner')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['address'] ?? ($lang === 'am' ? 'አድራሻ' : 'Address')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['phone_number'] ?? ($lang === 'am' ? 'የስልክ ቁጥር' : 'Phone Number')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['property'] ?? ($lang === 'am' ? 'ንብረት' : 'Property')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['amount_etb'] ?? ($lang === 'am' ? 'መጠን (ብር)' : 'Amount (ETB)')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['late_fee'] ?? ($lang === 'am' ? 'የዘገየ ክፍያ' : 'Late Fee')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['tax_amount'] ?? ($lang === 'am' ? 'የታክስ መጠን' : 'Tax Amount')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['additional_charges'] ?? ($lang === 'am' ? 'ተጨማሪ ክፍያዎች' : 'Additional Charges')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['due_date'] ?? ($lang === 'am' ? 'የክፍያ ቀን' : 'Due Date')); ?></th>
                                                    <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['status'] ?? ($lang === 'am' ? 'ሁኔታ' : 'Status')); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($bill = $bills_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><input type="checkbox" name="bill_ids[]" value="<?php echo $bill['bill_id']; ?>" class="bill-checkbox"></td>
                                                        <td><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                                        <td class="amharic"><?php echo htmlspecialchars($bill['full_name']); ?></td>
                                                        <td class="amharic"><?php echo htmlspecialchars($bill['address'] ?: ($lang === 'am' ? 'የለም' : 'None')); ?></td>
                                                        <td><?php echo htmlspecialchars($bill['phone'] ?: ($lang === 'am' ? 'የለም' : 'None')); ?></td>
                                                        <td><?php echo htmlspecialchars($bill['property_name'] ?: $bill['asset_code']); ?></td>
                                                        <td><?php echo number_format($bill['total_amount'], 2); ?></td>
                                                        <td><?php echo number_format($bill['late_fee'], 2); ?></td>
                                                        <td><?php echo number_format($bill['tax_amount'], 2); ?></td>
                                                        <td><?php echo number_format($bill['additional_charges'], 2); ?></td>
                                                        <td><?php echo htmlspecialchars($bill['due_date']); ?></td>
                                                        <td>
                                                            <span class="badge status-<?php echo $bill['status']; ?> amharic">
                                                                <?php echo htmlspecialchars($translations[$lang]['status_' . $bill['status']] ?? ($bill['status'] === 'overdue' ? ($lang === 'am' ? 'ከጊዜ ያለፈ' : 'Overdue') : ($lang === 'am' ? 'በመጠባበቅ ላይ' : 'Pending'))); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="submit" class="btn btn-telebirr mt-3 amharic">
                                        <?php echo htmlspecialchars($translations[$lang]['send_sms'] ?? ($lang === 'am' ? 'የኤስኤምኤስ ማሳሰቢያዎችን ላክ' : 'Send SMS Notifications')); ?>
                                    </button>
                                </form>

                                <!-- Recent SMS Notifications -->
                                <h4 class="amharic mt-4"><?php echo htmlspecialchars($translations[$lang]['recent_sms'] ?? ($lang === 'am' ? 'የቅርብ ጊዜ ኤስኤምኤስ ማሳሰቢያዎች' : 'Recent SMS Notifications')); ?></h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered sms-table">
                                        <thead>
                                            <tr>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['phone_number'] ?? ($lang === 'am' ? 'የስልክ ቁጥር' : 'Phone Number')); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['recipient'] ?? ($lang === 'am' ? 'ተቀባይ' : 'Recipient')); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['message'] ?? ($lang === 'am' ? 'መልእክት' : 'Message')); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['status'] ?? ($lang === 'am' ? 'ሁኔታ' : 'Status')); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['sent_at'] ?? ($lang === 'am' ? 'የተላከበት ጊዜ' : 'Sent At')); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['message_id'] ?? ($lang === 'am' ? 'የመልእክት መለያ' : 'Message ID')); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['type'] ?? ($lang === 'am' ? 'አይነት' : 'Type')); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($notif = $notifications_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($notif['phone_number']); ?></td>
                                                    <td class="amharic"><?php echo htmlspecialchars($notif['full_name']); ?></td>
                                                    <td class="amharic"><?php echo htmlspecialchars(substr($notif['message'], 0, 50)) . (strlen($notif['message']) > 50 ? '...' : ''); ?></td>
                                                    <td>
                                                        <span class="badge status-<?php echo $notif['status']; ?> amharic">
                                                            <?php echo htmlspecialchars($translations[$lang]['status_' . $notif['status']] ?? ($notif['status'] === 'delivered' ? ($lang === 'am' ? 'ደርሷል' : 'Delivered') : ($notif['status'] === 'failed' ? ($lang === 'am' ? 'አልተሳካም' : 'Failed') : ($notif['status'] === 'sent' ? ($lang === 'am' ? 'ተልኳል' : 'Sent') : ($lang === 'am' ? 'በመጠባበቅ ላይ' : 'Pending'))))); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($notif['send_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($notif['message_id'] ?: ($lang === 'am' ? 'የለም' : 'None')); ?></td>
                                                    <td class="amharic"><?php echo htmlspecialchars($translations[$lang]['sms_type_' . $notif['type']] ?? ($notif['type'] === 'scheduled' ? ($lang === 'am' ? 'ተይዟል' : 'Scheduled') : ($lang === 'am' ? 'በእጅ' : 'Manual'))); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Section -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title amharic"><?php echo htmlspecialchars($translations[$lang]['bills_by_status'] ?? ($lang === 'am' ? 'ቢሎች በሁኔታ' : 'Bills by Status')); ?></h3>
                            </div>
                            <div class="card-body">
                                <canvas id="billsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title amharic"><?php echo htmlspecialchars($translations[$lang]['payments_by_method'] ?? ($lang === 'am' ? 'ክፍያዎች በዘዴ' : 'Payments by Method')); ?></h3>
                            </div>
                            <div class="card-body">
                                <canvas id="paymentsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong class="amharic"><?php echo htmlspecialchars($translations[$lang]['app_name'] ?? ($lang === 'am' ? 'ወራቤ መሬት ሊዝ' : 'Werabe Land Lease')); ?></strong> - Powered by AdminLTE 3
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    $('#select-all').on('change', function() {
        $('.bill-checkbox').prop('checked', this.checked);
    });

    // Bills by Status Chart
    <?php
    try {
        $bills = $bill->getAll();
        $status_counts = ['pending' => 0, 'paid' => 0, 'overdue' => 0, 'cancelled' => 0, 'other' => 0];
        foreach ($bills as $b) {
            $status = $b['status'];
            if (isset($status_counts[$status])) {
                $status_counts[$status]++;
            } else {
                $status_counts['other']++;
            }
        }
        $statuses = array_keys($status_counts);
        $counts = array_values($status_counts);
    } catch (Exception $e) {
        $statuses = [];
        $counts = [];
        error_log("Bills status chart failed: " . $e->getMessage());
    }
    $status_translations = [
        'pending' => $translations[$lang]['status_pending'] ?? ($lang === 'am' ? 'በመጠባበቅ ላይ' : 'Pending'),
        'paid' => $translations[$lang]['status_paid'] ?? ($lang === 'am' ? 'ተከፍሏል' : 'Paid'),
        'overdue' => $translations[$lang]['status_overdue'] ?? ($lang === 'am' ? 'ጊዜው አልፏል' : 'Overdue'),
        'cancelled' => $translations[$lang]['status_cancelled'] ?? ($lang === 'am' ? 'ተሰርዟል' : 'Cancelled'),
        'other' => $translations[$lang]['status_other'] ?? ($lang === 'am' ? 'ሌላ' : 'Other')
    ];
    $translated_statuses = array_map(function($status) use ($status_translations) {
        return $status_translations[$status] ?? $status;
    }, $statuses);
    ?>
    var billsCtx = document.getElementById('billsChart').getContext('2d');
    new Chart(billsCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($translated_statuses); ?>,
            datasets: [{
                data: <?php echo json_encode($counts); ?>,
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: {
                    display: true,
                    text: '<?php echo htmlspecialchars($translations[$lang]['bills_by_status'] ?? ($lang === 'am' ? 'ቢሎች በሁኔታ' : 'Bills by Status')); ?>'
                }
            }
        }
    });

    // Payments by Method Chart
    <?php
    try {
        $payments = $payment->getAll(['limit' => 1000]);
        $method_counts = ['cash' => 0, 'bank_transfer' => 0, 'check' => 0, 'mobile_money' => 0, 'telebirr' => 0, 'cbe_birr' => 0, 'other' => 0];
        foreach ($payments as $p) {
            $method = $p['payment_method'];
            if (isset($method_counts[$method])) {
                $method_counts[$method]++;
            } else {
                $method_counts['other']++;
            }
        }
        $methods = array_keys($method_counts);
        $method_counts = array_values($method_counts);
    } catch (Exception $e) {
        $methods = [];
        $method_counts = [];
        error_log("Payments method chart failed: " . $e->getMessage());
    }
    $method_translations = [
        'cash' => $translations[$lang]['method_cash'] ?? ($lang === 'am' ? 'ጥሬ ገንዘብ' : 'Cash'),
        'bank_transfer' => $translations[$lang]['method_bank_transfer'] ?? ($lang === 'am' ? 'ባንክ ማስተላለፍ' : 'Bank Transfer'),
        'check' => $translations[$lang]['method_check'] ?? ($lang === 'am' ? 'ቼክ' : 'Check'),
        'mobile_money' => $translations[$lang]['method_mobile_money'] ?? ($lang === 'am' ? 'ሞባይል ገንዘብ' : 'Mobile Money'),
        'telebirr' => $translations[$lang]['method_telebirr'] ?? ($lang === 'am' ? 'ቴሌብር' : 'Telebirr'),
        'cbe_birr' => $translations[$lang]['method_cbe_birr'] ?? ($lang === 'am' ? 'ሲቢኢ ብር' : 'CBE-Birr'),
        'other' => $translations[$lang]['method_other'] ?? ($lang === 'am' ? 'ሌላ' : 'Other')
    ];
    $translated_methods = array_map(function($method) use ($method_translations) {
        return $method_translations[$method] ?? $method;
    }, $methods);
    ?>
    var paymentsCtx = document.getElementById('paymentsChart').getContext('2d');
    new Chart(paymentsCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($translated_methods); ?>,
            datasets: [{
                data: <?php echo json_encode($method_counts); ?>,
                backgroundColor: ['#17a2b8', '#28a745', '#ffc107', '#dc3545', '#007bff', '#6f42c1', '#6c757d'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: {
                    display: true,
                    text: '<?php echo htmlspecialchars($translations[$lang]['payments_by_method'] ?? ($lang === 'am' ? 'ክፍያዎች በዘዴ' : 'Payments by Method')); ?>'
                }
            }
        }
    });
});
</script>
</body>
</html>