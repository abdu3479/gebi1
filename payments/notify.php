<?php
require_once '../config/database.php';
require_once '../classes/Payment.php';

// Telebirr API configuration (replace with actual credentials)
$telebirr_config = [
    'app_key' => 'YOUR_TELEBIRR_APP_KEY',
    'public_key' => 'YOUR_TELEBIRR_PUBLIC_KEY'
];

// CBE-Birr API configuration (replace with actual credentials)
$cbebirr_config = [
    'api_key' => 'YOUR_CBE_BIRR_API_KEY'
];

$db = new Database();
$conn = $db->getConnection();
$paymentObj = new Payment($conn);

// Read raw POST data
$raw_input = file_get_contents('php://input');

// Handle Telebirr's encrypted payload
if (isset($_SERVER['HTTP_X_PAYMENT_METHOD']) && $_SERVER['HTTP_X_PAYMENT_METHOD'] === 'telebirr') {
    // Decrypt Telebirr payload (assumes RSA encryption with public key)
    $decrypted_data = '';
    if (openssl_public_decrypt(base64_decode($raw_input), $decrypted_data, $telebirr_config['public_key'])) {
        $data = json_decode($decrypted_data, true);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to decrypt Telebirr payload']);
        exit;
    }

    $payment_method = 'telebirr';
} else {
    // Assume CBE-Birr or other JSON payload
    $data = json_decode($raw_input, true);
    $payment_method = $data['payment_method'] ?? 'cbe_birr';
}

if (!$data || !in_array($payment_method, ['telebirr', 'cbe_birr'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request or payment method']);
    exit;
}

// Verify signature
$received_sign = $data['sign'] ?? '';
unset($data['sign']);
ksort($data);
$string_to_sign = '';
foreach ($data as $key => $value) {
    $string_to_sign .= "$key=$value&";
}
$string_to_sign = rtrim($string_to_sign, '&');
$expected_sign = hash_hmac(
    'sha256',
    $string_to_sign,
    $payment_method === 'telebirr' ? $telebirr_config['app_key'] : $cbebirr_config['api_key']
);

if ($received_sign !== $expected_sign) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Validate timestamp (within 1 minute of server time)
$received_timestamp = $data['timestamp'] ?? 0;
if (abs(time() - $received_timestamp) > 60) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid timestamp']);
    exit;
}

// Process payment notification
try {
    $out_trade_no = $data['outTradeNo'] ?? ($data['payment_code'] ?? '');
    $transaction_status = $data['tradeStatus'] ?? ($data['transaction_status'] ?? '');
    $payment_id = $paymentObj->getPaymentIdByOutTradeNo($out_trade_no);

    if (!$payment_id) {
        http_response_code(404);
        echo json_encode(['error' => 'Payment not found']);
        exit;
    }

    $status = $transaction_status === 'SUCCESS' ? 'completed' : 'failed';
    $paymentObj->updateStatus($payment_id, $status);

    // Log to audit_logs
    $conn->query(
        "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent, created_at) 
         VALUES (0, 'update', 'payments', {$payment_id}, '" . json_encode(['status' => $status, 'payment_method' => $payment_method], JSON_UNESCAPED_UNICODE) . "', 
                 '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())"
    );

    http_response_code(200);
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>