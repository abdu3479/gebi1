<?php
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuration
define('TWILIO_SID', $_ENV['TWILIO_SID'] ?? 'AC6e9b8900825d5bcfcd5da7bd31197d39');
define('TWILIO_TOKEN', $_ENV['TWILIO_TOKEN'] ?? '[AuthToken]');
define('TWILIO_FROM_NUMBER', $_ENV['TWILIO_FROM_NUMBER'] ?? '+12295970703');

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'aziyan';

// Connect to database
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit;
}
$mysqli->set_charset('utf8mb4');

// Fetch bills due in 7 days
$today = date('Y-m-d');
$due_date_limit = date('Y-m-d', strtotime('+7 days'));
$bills_query = "
    SELECT 
        b.id AS bill_id, b.bill_number, b.total_amount, b.late_fee, b.tax_amount, b.additional_charges,
        b.due_date, b.status, a.property_name, a.asset_code,
        o.id AS owner_id, o.full_name, o.phone
    FROM outstanding_bills b
    JOIN lease_agreements la ON b.lease_id = la.id
    JOIN assets a ON la.asset_id = a.id
    JOIN owners o ON la.owner_id = o.id
    WHERE b.status = 'pending'
    AND b.due_date BETWEEN ? AND ?
    AND NOT EXISTS (
        SELECT 1
        FROM sms_notifications sn
        WHERE sn.recipient_id = o.id
        AND sn.recipient_type = 'owner'
        AND sn.template_id = 6
        AND sn.send_date >= NOW() - INTERVAL 7 DAY
    )
";
$bills_stmt = $mysqli->prepare($bills_query);
if (!$bills_stmt) {
    error_log("Bills query preparation failed: " . $mysqli->error);
    exit;
}
$bills_stmt->bind_param('ss', $today, $due_date_limit);
if (!$bills_stmt->execute()) {
    error_log("Bills query execution failed: " . $bills_stmt->error);
    exit;
}
$bills_result = $bills_stmt->get_result();

$bill_ids = [];
while ($bill = $bills_result->fetch_assoc()) {
    $bill_ids[] = $bill['bill_id'];
}

$bills_stmt->close();

if (!empty($bill_ids)) {
    sendSMSNotifications($mysqli, $bill_ids, 'upcoming_payment_reminder_am');
}

$mysqli->close();

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
        JOIN owners o ON la.owner_id = o.id
        WHERE b.id IN ($bill_ids_str)
        AND o.phone REGEXP '^\+251[0-9]{9}$'
    ";
    $result = $mysqli->query($query);
    if (!$result) {
        error_log("SMS query failed: " . $mysqli->error);
        return;
    }

    $notifications = [];

    $template_query = "SELECT template, variables FROM sms_templates WHERE name = ? AND is_active = 1";
    $template_stmt = $mysqli->prepare($template_query);
    $template_stmt->bind_param('s', $template_name);
    $template_stmt->execute();
    $template_result = $template_stmt->get_result();
    $template = $template_result->fetch_assoc();
    $template_stmt->close();
    if (!$template) {
        error_log("Template $template_name not found");
        return;
    }
    $template_text = $template['template'];

    try {
        $twilio = new Client(TWILIO_SID, TWILIO_TOKEN);
    } catch (Exception $e) {
        error_log("Twilio initialization failed: " . $e->getMessage());
        return;
    }

    while ($bill = $result->fetch_assoc()) {
        $replacements = [
            '{{amount}}' => number_format($bill['total_amount'], 2),
            '{{due_date}}' => $bill['due_date'],
            '{{property}}' => $bill['property_name'] ?: $bill['asset_code'],
            '{{late_fee}}' => number_format($bill['late_fee'], 2),
            '{{tax_amount}}' => number_format($bill['tax_amount'], 2),
            '{{additional_charges}}' => number_format($bill['additional_charges'], 2),
            '{{full_name}}' => $bill['full_name']
        ];
        $message = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template_text
        );
        $message .= " በቴሌብር (*127#) ይክፈሉ።";

        $notifications[] = [
            'bill_id' => $bill['bill_id'],
            'owner_id' => $bill['owner_id'],
            'phone' => $bill['phone'],
            'message' => $message,
            'send_date' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'message_id' => null,
            'error_message' => null,
            'template_id' => ($template_name === 'payment_reminder_am' ? 4 : ($template_name === 'upcoming_payment_reminder_am' ? 6 : 5))
        ];
    }

    if (empty($notifications)) {
        error_log("No valid phone numbers found for selected bills");
        return;
    }

    $success_count = 0;
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
        }
        usleep(500000); // 0.5 seconds delay
    }

    logNotifications($mysqli, $notifications);
    error_log("$success_count SMS notifications sent successfully");
}

function logNotifications($mysqli, $notifications, $default_status = 'pending', $default_message_id = null, $error_message = null) {
    $notif_query = "
        INSERT INTO sms_notifications (
            template_id, recipient_type, recipient_id, phone_number, message, 
            status, message_id, error_message, attempts, send_date, created_by
        ) VALUES (?, 'owner', ?, ?, ?, ?, ?, ?, 1, ?, ?)
    ";
    $log_query = "
        INSERT INTO sms_delivery_logs (
            notification_id, phone_number, status, response, created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ";

    $notif_stmt = $mysqli->prepare($notif_query);
    if (!$notif_stmt) {
        error_log("Notification log preparation failed: " . $mysqli->error);
        return;
    }
    $log_stmt = $mysqli->prepare($log_query);
    if (!$log_stmt) {
        error_log("Delivery log preparation failed: " . $mysqli->error);
        return;
    }

    foreach ($notifications as $notif) {
        $template_id = $notif['template_id'] ?? ($template_name === 'payment_reminder_am' ? 4 : ($template_name === 'upcoming_payment_reminder_am' ? 6 : 5));
        $status = $notif['status'] ?? $default_status;
        $message_id = $notif['message_id'] ?? $default_message_id;
        $error = $notif['error_message'] ?? $error_message;
        $created_by = 0; // System-triggered
        $notif_stmt->bind_param(
            'iissssssi',
            $template_id,
            $notif['owner_id'],
            $notif['phone'],
            $notif['message'],
            $status,
            $message_id,
            $error,
            $notif['send_date'],
            $created_by
        );
        if (!$notif_stmt->execute()) {
            error_log("Notification log insert failed: " . $notif_stmt->error);
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
            error_log("Delivery log insert failed: " . $log_stmt->error);
        }
    }

    $notif_stmt->close();
    $log_stmt->close();
}
?>