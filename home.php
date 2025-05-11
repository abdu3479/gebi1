<?php
session_start();

// Session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'billing_officer'])) {
    header('Location: login.php');
    exit;
}

// Load dependencies (Twilio SDK and Dotenv)
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
try {
    $dotenv->load();
} catch (Exception $e) {
    error_log("Failed to load .env file: " . $e->getMessage(), 3, 'C:\xampp\logs\sms_errors.log');
    $_SESSION['error'] = "ስርዓቱን ማዋቀር አልተሳካም። እባክዎ አስተዳዳሪውን ያነጋግሩ።";
}

// Configuration
define('TWILIO_SID', $_ENV['TWILIO_SID'] ?? '');
define('TWILIO_TOKEN', $_ENV['TWILIO_TOKEN'] ?? '');
define('TWILIO_FROM_NUMBER', $_ENV['TWILIO_FROM_NUMBER'] ?? '');

// Validate Twilio credentials
$twilio_config_errors = [];
if (!TWILIO_SID) $twilio_config_errors[] = "TWILIO_SID ይጎድላል";
if (!TWILIO_TOKEN) $twilio_config_errors[] = "TWILIO_TOKEN ይጎድላል";
if (!TWILIO_FROM_NUMBER) $twilio_config_errors[] = "TWILIO_FROM_NUMBER ይጎድላል";
if (!empty($twilio_config_errors)) {
    $error_message = "Twilio የምዝገባ መረጃ ስህተት: " . implode(', ', $twilio_config_errors);
    error_log($error_message, 3, 'C:\xampp\logs\sms_errors.log');
    $_SESSION['error'] = $error_message;
}

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'aziyan';

// Connect to database
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error, 3, 'C:\xampp\logs\sms_errors.log');
    die("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle SMS sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    if ($_POST['action'] === 'send_sms' && !empty($_POST['bill_ids'])) {
        $bill_ids = array_map('intval', $_POST['bill_ids']);
        sendSMSNotifications($mysqli, $bill_ids, 'payment_reminder_am');
    }
}

// Function to normalize phone numbers
function normalizePhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (preg_match('/^0[0-9]{9}$/', $phone)) {
        $phone = '+251' . substr($phone, 1);
    } elseif (preg_match('/^9[0-9]{8}$/', $phone)) {
        $phone = '+251' . $phone;
    }
    if (preg_match('/^\+251[0-9]{9}$/', $phone)) {
        return $phone;
    }
    error_log("Invalid phone number: $phone", 3, 'C:\xampp\logs\sms_errors.log');
    return null;
}

// Function to send SMS notifications using Twilio
function sendSMSNotifications($mysqli, $bill_ids, $template_name) {
    $bill_ids_str = implode(',', array_map('intval', $bill_ids));
    $query = "
        SELECT 
            b.id AS bill_id, b.bill_number, b.total_amount, b.late_fee, b.tax_amount, b.additional_charges,
            b.due_date, b.status, a.property_name, a.asset_code,
            o.id AS owner_id, o.full_name, o.phone
        FROM outstanding_bills b
        JOIN lease_agreements la ON b.lease_id = la.id
        JOIN assets a ON la.asset_id = a.id
        JOIN owners o ON b.owner_id = o.id
        WHERE b.id IN ($bill_ids_str)
    ";
    $result = $mysqli->query($query);
    if (!$result) {
        $error = "SMS query failed: " . $mysqli->error;
        error_log($error, 3, 'C:\xampp\logs\sms_errors.log');
        $_SESSION['error'] = "የኤስኤምኤስ መልእክት መላክ ጥያቄ አልተሳካም: " . $mysqli->error;
        return;
    }

    $notifications = [];
    $invalid_phones = [];

    $template_query = "SELECT id, template, variables FROM sms_templates WHERE name = ? AND is_active = 1";
    $template_stmt = $mysqli->prepare($template_query);
    $template_stmt->bind_param('s', $template_name);
    $template_stmt->execute();
    $template_result = $template_stmt->get_result();
    $template = $template_result->fetch_assoc();
    $template_stmt->close();
    if (!$template) {
        $error = "Template $template_name not found";
        error_log($error, 3, 'C:\xampp\logs\sms_errors.log');
        $_SESSION['error'] = "የ$template_name አብነት አልተገኘም።";
        return;
    }
    $template_id = $template['id'];
    $template_text = $template['template'];

    // Initialize Twilio client
    $twilio = null;
    if (TWILIO_SID && TWILIO_TOKEN && TWILIO_FROM_NUMBER) {
        try {
            $twilio = new Client(TWILIO_SID, TWILIO_TOKEN);
        } catch (Exception $e) {
            $error = "Twilio initialization failed: " . $e->getMessage();
            error_log($error, 3, 'C:\xampp\logs\sms_errors.log');
            $_SESSION['error'] = "Twilio ኤፒአይ ማስጀመር አልተሳካም: " . $e->getMessage();
        }
    }

    while ($bill = $result->fetch_assoc()) {
        $phone = normalizePhone($bill['phone']);
        if (!$phone) {
            $invalid_phones[] = [
                'owner_id' => $bill['owner_id'],
                'full_name' => $bill['full_name'],
                'phone' => $bill['phone']
            ];
            continue;
        }

        $replacements = [
            '{{amount}}' => number_format($bill['total_amount'], 2),
            '{{due_date}}' => $bill['due_date'],
            '{{property}}' => $bill['property_name'] ?: $bill['asset_code'],
            '{{late_fee}}' => number_format($bill['late_fee'], 2),
            '{{tax_amount}}' => number_format($bill['tax_amount'], 2),
            '{{additional_charges}}' => number_format($bill['additional_charges'], 2),
            '{{full_name}}' => $bill['full_name'],
            '{{bill_number}}' => $bill['bill_number']
        ];
        $message = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template_text
        );
        if ($template_name !== 'payment_confirmation_am' && $template_name !== 'thank_you_am') {
            $message .= " በቴሌብር (*127#) ይክፈሉ።";
        }

        $notifications[] = [
            'bill_id' => $bill['bill_id'],
            'owner_id' => $bill['owner_id'],
            'phone' => $phone,
            'message' => $message,
            'send_date' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'message_id' => null,
            'error_message' => null,
            'template_id' => $template_id
        ];
    }

    if (!empty($invalid_phones)) {
        $_SESSION['invalid_phones'] = $invalid_phones;
    }

    if (empty($notifications)) {
        $error = "No valid phone numbers found for selected bills: " . json_encode($invalid_phones, JSON_UNESCAPED_UNICODE);
        error_log($error, 3, 'C:\xampp\logs\sms_errors.log');
        $_SESSION['error'] = "ለተመረጡት ቢሎች ተገቢ የሆነ ስልክ ቁጥር አልተገኘም።";
        return;
    }

    // Send SMS messages using Twilio
    $success_count = 0;
    if ($twilio) {
        foreach ($notifications as $index => &$notif) {
            try {
                $twilio_message = $twilio->messages->create(
                    $notif['phone'],
                    [
                        'from' => TWILIO_FROM_NUMBER,
                        'body' => $notif['message']
                    ]
                );
                $notif['status'] = 'sent';
                $notif['message_id'] = $twilio_message->sid;
                $success_count++;
            } catch (Exception $e) {
                $notif['status'] = 'failed';
                $notif['error_message'] = $e->getMessage();
                error_log("SMS send failed for {$notif['phone']}: " . $e->getMessage(), 3, 'C:\xampp\logs\sms_errors.log');
            }
            usleep(500000); // 0.5-second delay to avoid rate limits
        }
    } else {
        foreach ($notifications as $index => &$notif) {
            $notif['status'] = 'failed';
            $notif['error_message'] = 'Twilio client not initialized';
            error_log("SMS failed for {$notif['phone']}: Twilio client not initialized", 3, 'C:\xampp\logs\sms_errors.log');
        }
    }

    // Log notifications
    logNotifications($mysqli, $notifications);

    if ($success_count > 0) {
        $_SESSION['success'] = "$success_count የኤስኤምኤስ መልእክቶች በተሳካ ሁኔታ ተልከዋል።";
    } else {
        $_SESSION['error'] = "ምንም ኤስኤምኤስ መልእክቶች መላክ አልተቻለም። እባክዎ Twilio መረጃዎችን ያረጋግጡ።";
    }
}

// Function to log notifications
function logNotifications($mysqli, $notifications, $default_status = 'pending', $default_message_id = null, $error_message = null) {
    $notif_query = "
        INSERT INTO sms_notifications (
            template_id, recipient_type, recipient_id, bill_id, phone_number, message, 
            status, message_id, error_message, attempts, send_date, type, created_by
        ) VALUES (?, 'owner', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'manual', ?)
    ";
    $log_query = "
        INSERT INTO sms_delivery_logs (
            notification_id, phone_number, status, response, created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ";

    $notif_stmt = $mysqli->prepare($notif_query);
    if (!$notif_stmt) {
        $error = "Notification log preparation failed: " . $mysqli->error;
        error_log($error, 3, 'C:\xampp\logs\sms_errors.log');
        $_SESSION['error'] = "የማሳሰቢያ መዝገብ ዝግጅት አልተሳካም: " . $mysqli->error;
        return;
    }
    $log_stmt = $mysqli->prepare($log_query);
    if (!$log_stmt) {
        $error = "Delivery log preparation failed: " . $mysqli->error;
        error_log($error, 3, 'C:\xampp\logs\sms_errors.log');
        $_SESSION['error'] = "የማስተላለፍ መዝገብ ዝግጅት አልተሳካም: " . $mysqli->error;
        return;
    }

    foreach ($notifications as $notif) {
        $template_id = $notif['template_id'];
        $status = $notif['status'] ?? $default_status;
        $message_id = $notif['message_id'] ?? $default_message_id;
        $error = $notif['error_message'] ?? $error_message;
        $attempts = 1;
        $send_date = $notif['send_date'];
        $created_by = $_SESSION['user_id'] ?? 0;
        $notif_stmt->bind_param(
            'iiissssssii',
            $template_id,
            $notif['owner_id'],
            $notif['bill_id'],
            $notif['phone'],
            $notif['message'],
            $status,
            $message_id,
            $error,
            $attempts,
            $send_date,
            $created_by
        );
        if (!$notif_stmt->execute()) {
            $error = "Notification log insert failed: " . $notif_stmt->error;
            error_log($error, 3, 'C:\xampp\logs\sms_errors.log');
            $_SESSION['error'] = "የማሳሰቢያ መዝገብ ማስገባት አልተሳካም: " . $notif_stmt->error;
            continue;
        }
        $notification_id = $mysqli->insert_id;

        $log_status = ($status == 'failed') ? 'failed' : 'sent';
        $response = $error ?? json_encode($notif, JSON_UNESCAPED_UNICODE);
        $log_stmt->bind_param(
            'isss',
            $notification_id,
            $notif['phone'],
            $log_status,
            $response
        );
        if (!$log_stmt->execute()) {
            $error = "Delivery log insert failed: " . $log_stmt->error;
            error_log($error, 3, 'C:\xampp\logs\sms_errors.log');
            $_SESSION['error'] = "የማስተላለፍ መዝገብ ማስገባት አልተሳካም: " . $log_stmt->error;
        }
    }

    $notif_stmt->close();
    $log_stmt->close();
}

// Fetch outstanding bills
$today = date('Y-m-d');
$due_date_limit = date('Y-m-d', strtotime('+7 days'));
$bills_query = "
    SELECT 
        b.id AS bill_id, b.bill_number, b.total_amount, b.late_fee, b.tax_amount, b.additional_charges,
        b.due_date, b.status, a.property_name, a.asset_code,
        o.full_name, o.phone
    FROM outstanding_bills b
    JOIN lease_agreements la ON b.lease_id = la.id
    JOIN assets a ON la.asset_id = a.id
    JOIN owners o ON b.owner_id = o.id
    WHERE b.status IN ('pending', 'overdue')
    AND (b.due_date <= ? OR b.status = 'overdue')
    ORDER BY b.due_date ASC
";
$bills_stmt = $mysqli->prepare($bills_query);
if (!$bills_stmt) {
    error_log("Bills query preparation failed: " . $mysqli->error, 3, 'C:\xampp\logs\sms_errors.log');
    die("የቢል ጥያቄ ዝግጅት አልተሳካም: " . $mysqli->error);
}
$bills_stmt->bind_param('s', $due_date_limit);
if (!$bills_stmt->execute()) {
    error_log("Bills query execution failed: " . $bills_stmt->error, 3, 'C:\xampp\logs\sms_errors.log');
    die("የቢል ጥያቄ ማስፈጸም አልተሳካም: " . $bills_stmt->error);
}
$bills_result = $bills_stmt->get_result();

// Fetch recent SMS notifications
$notifications_query = "
    SELECT sn.id, sn.phone_number, sn.message, sn.status, sn.send_date, sn.message_id, sn.error_message,
           o.full_name
    FROM sms_notifications sn
    JOIN owners o ON sn.recipient_id = o.id
    WHERE sn.recipient_type = 'owner'
    ORDER BY sn.send_date DESC
    LIMIT 10
";
$notifications_result = $mysqli->query($notifications_query);
if (!$notifications_result) {
    error_log("Notifications query failed: " . $mysqli->error, 3, 'C:\xampp\logs\sms_errors.log');
    die("የማሳሰቢያ ጥያቄ አልተሳካም: " . $mysqli->error);
}

// Fetch invalid phone numbers
$invalid_phones_query = "
    SELECT o.id, o.full_name, o.phone
    FROM owners o
    WHERE o.phone NOT LIKE '+251%' OR LENGTH(o.phone) != 12
";
$invalid_phones = [];
try {
    $invalid_phones_result = $mysqli->query($invalid_phones_query);
    if (!$invalid_phones_result) {
        throw new Exception("Invalid phones query failed: " . $mysqli->error);
    }
    while ($row = $invalid_phones_result->fetch_assoc()) {
        $invalid_phones[] = $row;
    }
    $invalid_phones_result->close();
} catch (Exception $e) {
    error_log("Invalid phones query error: " . $e->getMessage() . "\nQuery: $invalid_phones_query", 3, 'C:\xampp\logs\sms_errors.log');
    $_SESSION['error'] = "የስልክ ቁጥር ጥያቄ አልተሳካም: " . $e->getMessage();
}

// Check logo path
$logo_path = file_exists('assets/images/logo.jpg') ? 'assets/images/logo.jpg' : 'https://via.placeholder.com/40';
?>

<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>የኤስኤምኤስ አስተዳደር ዳሽቦርድ - ወራቤ መሬት ሊዝ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Abyssinica+SIL&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #00A859 0%, #FFC107 100%);
            background-attachment: fixed;
            font-family: 'Roboto', sans-serif;
        }
        .amharic {
            font-family: 'Abyssinica SIL', sans-serif;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .header-logo {
            height: 40px;
        }
        table {
            background: #FFFFFF;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        th, td {
            border-color: #E0E0E0;
        }
        button {
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #008040;
        }
    </style>
</head>
<body>
    <div class="container mx-auto p-6 my-8">
        <header class="flex justify-between items-center mb-6">
            <div class="flex items-center">
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Telebirr Logo" class="header-logo mr-3">
                <h1 class="text-3xl font-bold text-[#00A859] amharic">የኤስኤምኤስ አስተዳደር ዳሽቦርድ</h1>
            </div>
            <div>
                <span class="text-[#333333] mr-4 amharic">እንኳን ደህና መጡ፣ <?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></span>
                <a href="logout.php" class="text-red-600 hover:underline amharic">ውጣ</a>
            </div>
        </header>

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-[#00A859] text-white p-4 rounded mb-6 amharic">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-6 amharic">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Invalid Phone Numbers -->
        <?php if (!empty($_SESSION['invalid_phones']) || !empty($invalid_phones)): ?>
            <div class="bg-yellow-100 text-yellow-700 p-4 rounded mb-6 amharic">
                <h3 class="font-semibold">የማይሰሩ ስልክ ቁጥሮች</h3>
                <ul class="list-disc pl-5">
                    <?php if (!empty($_SESSION['invalid_phones'])): ?>
                        <?php foreach ($_SESSION['invalid_phones'] as $invalid): ?>
                            <li><?php echo htmlspecialchars($invalid['full_name']); ?>: <?php echo htmlspecialchars($invalid['phone']); ?> (Owner ID: <?php echo $invalid['owner_id']; ?>)</li>
                        <?php endforeach; ?>
                        <?php unset($_SESSION['invalid_phones']); ?>
                    <?php endif; ?>
                    <?php foreach ($invalid_phones as $invalid): ?>
                        <li><?php echo htmlspecialchars($invalid['full_name']); ?>: <?php echo htmlspecialchars($invalid['phone']); ?> (Owner ID: <?php echo $invalid['id']; ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Outstanding Bills -->
        <section class="mb-8">
            <h2 class="text-2xl font-semibold mb-4 text-[#333333] amharic">ያልተከፈሉ ቢሎች</h2>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="send_sms">
                <div class="overflow-x-auto">
                    <table class="w-full shadow">
                        <thead>
                            <tr class="bg-[#00A859] text-white">
                                <th class="p-3 text-left"><input type="checkbox" id="select-all" class="mr-2"></th>
                                <th class="p-3 text-left amharic">የቢል ቁጥር</th>
                                <th class="p-3 text-left amharic">ባለቤት</th>
                                <th class="p-3 text-left amharic">ንብረት</th>
                                <th class="p-3 text-left amharic">መጠን (ብር)</th>
                                <th class="p-3 text-left amharic">የዘገየ ክፍያ (ብር)</th>
                                <th class="p-3 text-left amharic">የታክስ መጠን (ብር)</th>
                                <th class="p-3 text-left amharic">ተጨማሪ ክፍያዎች (ብር)</th>
                                <th class="p-3 text-left amharic">የክፍያ ቀን</th>
                                <th class="p-3 text-left amharic">ሁኔታ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($bill = $bills_result->fetch_assoc()): ?>
                                <tr class="border-b">
                                    <td class="p-3">
                                        <input type="checkbox" name="bill_ids[]" value="<?php echo $bill['bill_id']; ?>" class="bill-checkbox">
                                    </td>
                                    <td class="p-3"><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                    <td class="p-3 amharic"><?php echo htmlspecialchars($bill['full_name']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($bill['property_name'] ?: $bill['asset_code']); ?></td>
                                    <td class="p-3"><?php echo number_format($bill['total_amount'], 2); ?></td>
                                    <td class="p-3"><?php echo number_format($bill['late_fee'], 2); ?></td>
                                    <td class="p-3"><?php echo number_format($bill['tax_amount'], 2); ?></td>
                                    <td class="p-3"><?php echo number_format($bill['additional_charges'], 2); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($bill['due_date']); ?></td>
                                    <td class="p-3">
                                        <span class="inline-block px-2 py-1 rounded text-sm <?php echo $bill['status'] === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-[#FFC107] text-[#333333]'; ?> amharic">
                                            <?php echo $bill['status'] === 'overdue' ? 'ከጊዜ ያለፈ' : 'በመጠባበቅ ላይ'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="mt-4 bg-[#00A859] text-white px-4 py-2 rounded hover:bg-[#008040] amharic">
                    የኤስኤምኤስ ማሳሰቢያዎችን ላክ
                </button>
            </form>
        </section>

        <!-- Recent SMS Notifications -->
        <section>
            <h2 class="text-2xl font-semibold mb-4 text-[#333333] amharic">የቅርብ ጊዜ ኤስኤምኤስ ማሳሰቢያዎች</h2>
            <div class="overflow-x-auto">
                <table class="w-full shadow">
                    <thead>
                        <tr class="bg-[#00A859] text-white">
                            <th class="p-3 text-left amharic">የስልክ ቁጥር</th>
                            <th class="p-3 text-left amharic">ተቀባይ</th>
                            <th class="p-3 text-left amharic">መልእክት</th>
                            <th class="p-3 text-left amharic">ሁኔታ</th>
                            <th class="p-3 text-left amharic">የተላከበት ጊዜ</th>
                            <th class="p-3 text-left amharic">የመልእክት መለያ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($notif = $notifications_result->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="p-3"><?php echo htmlspecialchars($notif['phone_number']); ?></td>
                                <td class="p-3 amharic"><?php echo htmlspecialchars($notif['full_name']); ?></td>
                                <td class="p-3 amharic"><?php echo htmlspecialchars(substr($notif['message'], 0, 50)) . (strlen($notif['message']) > 50 ? '...' : ''); ?></td>
                                <td class="p-3">
                                    <span class="inline-block px-2 py-1 rounded text-sm amharic
                                        <?php 
                                        switch ($notif['status']) {
                                            case 'delivered': echo 'bg-[#00A859] text-white'; break;
                                            case 'failed': echo 'bg-red-100 text-red-700'; break;
                                            default: echo 'bg-[#FFC107] text-[#333333]';
                                        }
                                        ?>">
                                        <?php 
                                        switch ($notif['status']) {
                                            case 'delivered': echo 'ደርሷል'; break;
                                            case 'failed': echo 'አልተሳካም'; break;
                                            case 'sent': echo 'ተልኳል'; break;
                                            default: echo 'በመጠባበቅ ላይ';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="p-3"><?php echo htmlspecialchars($notif['send_date']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($notif['message_id'] ?: 'የለም'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.bill-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('ኤስኤምኤስ ማሳሰቢያዎችን መላክ ይፈልጋሉ?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>

<?php
$bills_stmt->close();
$notifications_result->close();
$mysqli->close();
?>