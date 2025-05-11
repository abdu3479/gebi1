<?php
require_once  '../config/database.php';
require_once '../classes/Payment.php';
require_once  '../vendor/autoload.php';

use phpseclib3\Crypt\PublicKeyLoader;

try {
    // Load config
    $config_path = '../config/telebirr.php';
    if (!file_exists($config_path)) {
        error_log("Telebirr config file not found: $config_path");
        http_response_code(500);
        exit("Config file not found");
    }
    $config = require_once $config_path;

    // Get raw POST data
    $raw_data = file_get_contents('php://input');
    if (empty($raw_data)) {
        error_log("No POST data received in telebirr_notify.php");
        http_response_code(400);
        exit("No POST data");
    }

    $data = json_decode($raw_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid JSON in telebirr_notify.php: " . json_last_error_msg());
        http_response_code(400);
        exit("Invalid JSON");
    }

    // Mock response for testing
    $out_trade_no = $data['outTradeNo'] ?? '';
    $transaction_id = $data['transactionId'] ?? 'MOCK_TRANS_' . time();
    $status = $data['status'] ?? 'SUCCESS';

    if (empty($out_trade_no)) {
        error_log("Missing outTradeNo in telebirr_notify.php");
        http_response_code(400);
        exit("Missing outTradeNo");
    }

    // Update payment status
    $db = new Database();
    $conn = $db->getConnection();
    $paymentObj = new Payment($conn);

    $stmt = $conn->prepare("SELECT id FROM payments WHERE out_trade_no = ?");
    $stmt->bind_param('s', $out_trade_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        error_log("Payment not found for out_trade_no: $out_trade_no");
        http_response_code(404);
        exit("Payment not found");
    }

    if ($status === 'SUCCESS') {
        $paymentObj->updatePaymentStatus($payment['id'], 'completed', $transaction_id);
        error_log("Payment updated to completed for out_trade_no: $out_trade_no, transaction_id: $transaction_id");
    } else {
        $paymentObj->updatePaymentStatus($payment['id'], 'failed', $transaction_id);
        error_log("Payment updated to failed for out_trade_no: $out_trade_no, transaction_id: $transaction_id");
    }

    http_response_code(200);
    echo "SUCCESS";
} catch (Exception $e) {
    error_log("Error in telebirr_notify.php: " . $e->getMessage());
    http_response_code(500);
    exit("Error processing notification");
}
?>