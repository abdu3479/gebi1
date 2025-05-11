<?php
require_once 'config/database.php';
require_once 'classes/Owner.php';

// Initialize database
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    error_log("Initialization failed: " . $e->getMessage());
    exit("System initialization failed.");
}

// SMS Configuration
define('SMS_TO_API_URL', 'https://api.sms.to/sms/send');
define('SMS_TO_API_KEY', '<YOUR_SMS_TO_API_KEY>');
define('SENDER_ID', '<YOUR_SENDER_ID>');
define('CALLBACK_URL', 'https://yourdomain.com/sms_callback.php');

// Set language (default to Amharic for automatic SMS)
$lang = 'am';

// Function to send SMS notifications
function sendSMSNotifications($conn, $bill_ids, $lang, $type = 'automatic') {
    $placeholders = implode(',', array_fill(0, count($bill_ids), '?'));
    $query = "
        SELECT 
            b.id AS bill_id, b.bill_number, b.total_amount, b.late_fee, b.tax_amount, b.additional_charges,
            b.due_date, b.status, a.property_name, a.asset_code,
            o.id AS owner_id, o.full_name, o.phone
        FROM outstanding_bills b
        JOIN lease_agreements la ON b.lease_id = la.id
        JOIN assets a ON la.asset_id = a.id
        JOIN owners o ON la.owner_id = o.id
        WHERE b.id IN ($placeholders)
        AND o.phone LIKE '+251%'
        AND LENGTH(o.phone) = 12
    ";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log(($lang === 'am' ? 'የኤስኤምኤስ መልእክት ጥያቄ አልተሳካም: ' : 'SMS query failed: ') . $conn->error);
        return;
    }
    $stmt->bind_param(str_repeat('i', count($bill_ids)), ...$bill_ids);
    if (!$stmt->execute()) {
        error_log(($lang === 'am' ? 'የኤስኤምኤስ መልእክት ጥያቄ ማስፈጸም አልተሳካም: ' : 'SMS query execution failed: ') . $stmt->error);
        return;
    }
    $result = $stmt->get_result();

    $messages = [];
    $notifications = [];

    $template_name = 'upcoming_payment_am';
    $template_query = "SELECT template, variables FROM sms_templates WHERE name = ? AND is_active = 1";
    $template_stmt = $conn->prepare($template_query);
    $template_stmt->bind_param('s', $template_name);
    $template_stmt->execute();
    $template_result = $template_stmt->get_result();
    if (!$template_result->num_rows) {
        error_log($lang === 'am' ? "የ$template_name አብነት አልተገኘም።" : "$template_name template not found.");
        return;
    }
    $template = $template_result->fetch_assoc();
    $template_text = $template['template'];

    while ($bill = $result->fetch_assoc()) {
        $replacements = [
            '{{amount}}' => number_format($bill['total_amount'], 2),
            '{{due_date}}' => $bill['due_date'],
            '{{property}}' => $bill['property_name'] ?: $bill['asset_code'],
            '{{late_fee}}' => number_format($bill['late_fee'], 2),
            '{{tax_amount}}' => number_format($bill['tax_amount'], 2),
            '{{additional_charges}}' => number_format($bill['additional_charges'], 2)
        ];
        $message = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template_text
        );
        $message .= ($lang === 'am' ? ' በቴሌብር (*127#) ይክፈሉ።' : ' Pay via Telebirr (*127#).');

        $messages[] = [
            'to' => $bill['phone'],
            'message' => $message
        ];

        $notifications[] = [
            'bill_id' => $bill['bill_id'],
            'owner_id' => $bill['owner_id'],
            'phone' => $bill['phone'],
            'message' => $message,
            'send_date' => date('Y-m-d H:i:s'),
            'type' => $type
        ];
    }

    if (empty($messages)) {
        error_log($lang === 'am' ? 'ለተመረጡት ቢሎች ተገቢ የሆነ ስልክ ቁጥር አልተገኘም።' : 'No valid phone numbers found for selected bills.');
        return;
    }

    $payload = [
        'messages' => $messages,
        'sender_id' => SENDER_ID,
        'callback_url' => CALLBACK_URL
    ];

    $curl = curl_init(SMS_TO_API_URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . SMS_TO_API_KEY,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response_data = json_decode($response, true);
    curl_close($curl);

    if ($http_code == 200 && isset($response_data['messages'])) {
        foreach ($response_data['messages'] as $index => $msg_response) {
            $notifications[$index]['status'] = $msg_response['status'] ?? 'sent';
            $notifications[$index]['message_id'] = $msg_response['ID'] ?? null;
            $notifications[$index]['error_message'] = $msg_response['error'] ?? null;
        }
        logNotifications($conn, $notifications, $lang);
        error_log(count($messages) . ($lang === 'am' ? ' የኤስኤምኤስ መልእክቶች በተሳካ ሁኔታ ተልከዋል።' : ' SMS messages sent successfully.'));
    } else {
        $error_msg = $response_data['message'] ?? ($lang === 'am' ? 'ያልታወቀ ስህተት' : 'Unknown error');
        logNotifications($conn, $notifications, $lang, 'failed', null, "API error: HTTP $http_code - $error_msg");
        error_log(($lang === 'am' ? 'ኤስኤምኤስ መላክ አልተሳካም: ' : 'SMS sending failed: ') . $error_msg);
    }
}

// Function to log notifications
function logNotifications($conn, $notifications, $lang, $default_status = 'pending', $default_message_id = null, $error_message = null) {
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
    if (!$notif_stmt) {
        error_log(($lang === 'am' ? 'የማሳሰቢያ መዝገብ ዝግጅት አልተሳካም: ' : 'Notification log preparation failed: ') . $conn->error);
        return;
    }
    $log_stmt = $conn->prepare($log_query);
    if (!$log_stmt) {
        error_log(($lang === 'am' ? 'የማስተላለፍ መዝገብ ዝግጅት አልተሳካም: ' : 'Delivery log preparation failed: ') . $conn->error);
        return;
    }

    foreach ($notifications as $notif) {
        $template_id = $notif['type'] === 'automatic' ? 5 : 4;
        $status = $notif['status'] ?? $default_status;
        $message_id = $notif['message_id'] ?? $default_message_id;
        $error = $notif['error_message'] ?? $error_message;
        $created_by = 0; // No user session in cron job
        $type = $notif['type'];
        $notif_stmt->bind_param(
            'iiisssssssis',
            $template_id,
            $notif['owner_id'],
            $notif['bill_id'],
            $notif['phone'],
            $notif['message'],
            $status,
            $message_id,
            $error,
            $notif['send_date'],
            $type,
            $created_by
        );
        if (!$notif_stmt->execute()) {
            error_log(($lang === 'am' ? 'የማሳሰቢያ መዝገብ ማስገባት አልተሳካም: ' : 'Notification log insertion failed: ') . $notif_stmt->error);
            continue;
        }
        $notification_id = $conn->insert_id;

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
            error_log(($lang === 'am' ? 'የማስተላለፍ መዝገብ ማስገባት አልተሳካም: ' : 'Delivery log insertion failed: ') . $log_stmt->error);
        }
    }

    $notif_stmt->close();
    $log_stmt->close();
}

// Main execution
$today = date('Y-m-d');
$due_date_limit = date('Y-m-d', strtotime('+7 days'));
$query = "
    SELECT 
        b.id AS bill_id, b.bill_number, b.total_amount, b.late_fee, b.tax_amount, b.additional_charges,
        b.due_date, b.status, a.property_name, a.asset_code,
        o.id AS owner_id, o.full_name, o.phone
    FROM outstanding_bills bリング
    JOIN lease_agreements la ON b.lease_id = la.id
    JOIN assets a ON la.asset_id = a.id
    JOIN owners o ON la.owner_id = o.id
    WHERE b.status = 'pending'
    AND b.due_date BETWEEN ? AND ?
    AND o.phone LIKE '+251%'
    AND LENGTH(o.phone) = 12
    AND NOT EXISTS (
        SELECT 1 FROM sms_notifications sn
        WHERE sn.recipient_id = o.id
        AND sn.bill_id = b.id
        AND sn.type = 'automatic'
        AND sn.send_date >= NOW() - INTERVAL 24 HOUR
    )
";
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log(($lang === 'am' ? 'የአውቶማቲክ ኤስኤምኤስ ጥያቔ ዝግጅት አልተሳካም: ' : 'Automatic SMS query preparation failed: ') . $conn->error);
    exit;
}
$stmt->bind_param('ss', $today, $due_date_limit);
if (!$stmt->execute()) {
    error_log(($lang === 'am' ? 'የአውቶማቲክ ኤስኤምኤስ ጥያቔ ማስፈጸም አልተሳካም: ' : 'Automatic SMS query execution failed: ') . $stmt->error);
    exit;
}
$result = $stmt->get_result();
$bill_ids = [];
while ($row = $result->fetch_assoc()) {
    $bill_ids[] = $row['bill_id'];
}
$stmt->close();

if (!empty($bill_ids)) {
    sendSMSNotifications($conn, $bill_ids, $lang, 'automatic');
}

$conn->close();
?>