<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use AfricasTalking\SDK\AfricasTalking;

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    $username = 'your_africas_talking_username';
    $apiKey = 'your_africas_talking_api_key';
    $senderId = 'your_sender_id';

    $AT = new AfricasTalking($username, $apiKey);
    $sms = $AT->sms();

    $phone = $_POST['phone'] ?? '+251912345678';
    $message = $_POST['message'] ?? 'Test SMS from Werabe Land Lease System';

    if (!preg_match('/^\+251[79]\d{8}$/', $phone)) {
        throw new Exception("Invalid phone number format");
    }

    $response = $sms->send([
        'to' => $phone,
        'message' => $message,
        'from' => $senderId
    ]);

    echo json_encode(['status' => 'success', 'response' => $response['data']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>