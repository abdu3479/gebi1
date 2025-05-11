<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * Payment Management Class for Werabe Land Lease System
 * Integrates with payments/add.php and supports Telebirr/CBE-Birr
 */
class Payment {
    private $conn;
    private $allowedMethods = ['cash', 'bank_transfer', 'check', 'mobile_money', 'telebirr', 'cbe_birr'];
    private $allowedStatuses = ['pending', 'completed', 'failed', 'refunded'];

    public function __construct($db) {
        $this->conn = $db;
        if (!$this->conn) {
            throw new Exception("Database connection is null");
        }
        if ($this->conn->connect_error) {
            throw new Exception("Database connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset('utf8mb4'); // Support Amharic
    }

    private function isConnectionActive() {
        return $this->conn && !$this->conn->connect_error && $this->conn->ping();
    }

    public function countTotalPayments() {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $query = "SELECT COUNT(*) as count FROM payments";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        return (int)$count;
    }

    private function logAction($record_id, $action_type, $user_id, $details = []) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent, created_at) 
                 VALUES (?, ?, 'payments', ?, ?, ?, ?, NOW())"
            );
            $new_values = json_encode($details, JSON_UNESCAPED_UNICODE);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $stmt->bind_param('isisss', $user_id, $action_type, $record_id, $new_values, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error logging action for payment ID $record_id: " . $e->getMessage());
        }
    }

    private function generatePaymentNumber() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM payments WHERE YEAR(payment_date) = YEAR(NOW())");
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'] + 1;
        $stmt->close();
        return sprintf("PAY-%s-%04d", date('Y'), $count);
    }

    private function validateData($data, $isUpdate = false) {
        $errors = [];
        $requiredFields = ['lease_id', 'bill_id', 'amount', 'payment_method', 'payment_date'];

        foreach ($requiredFields as $field) {
            if (!$isUpdate || isset($data[$field])) {
                if (empty($data[$field]) && $data[$field] !== 0) {
                    $errors[] = "Field '$field' is required";
                }
            }
        }

        if (isset($data['lease_id'])) {
            $stmt = $this->conn->prepare("SELECT id FROM lease_agreements WHERE id = ? AND status = 'active'");
            $stmt->bind_param('i', $data['lease_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $errors[] = "Invalid or inactive lease ID: {$data['lease_id']}";
            }
            $stmt->close();
        }

        if (isset($data['bill_id']) && isset($data['lease_id'])) {
            $stmt = $this->conn->prepare("SELECT id, total_amount, status, lease_id FROM bills WHERE id = ?");
            $stmt->bind_param('i', $data['bill_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $errors[] = "Invalid bill ID: {$data['bill_id']}";
            } else {
                $bill = $result->fetch_assoc();
                if ($bill['status'] === 'paid') {
                    $errors[] = "Bill ID {$data['bill_id']} is already paid";
                }
                if ($bill['lease_id'] != $data['lease_id']) {
                    $errors[] = "Bill ID {$data['bill_id']} does not belong to lease ID {$data['lease_id']}";
                }
                if (isset($data['amount']) && $data['amount'] > $bill['total_amount']) {
                    $errors[] = "Payment amount exceeds bill total: {$bill['total_amount']}";
                }
            }
            $stmt->close();
        }

        if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            $errors[] = "Amount must be a positive number";
        }

        if (isset($data['payment_method']) && !in_array(strtolower($data['payment_method']), $this->allowedMethods)) {
            $errors[] = "Invalid payment method: {$data['payment_method']}";
        }

        if (isset($data['status']) && !in_array(strtolower($data['status']), $this->allowedStatuses)) {
            $errors[] = "Invalid status: {$data['status']}";
        }

        if (isset($data['payment_date']) && !strtotime($data['payment_date'])) {
            $errors[] = "Invalid payment date";
        }

        if (isset($data['notes']) && !mb_check_encoding($data['notes'], 'UTF-8')) {
            $errors[] = "Notes contain invalid characters";
        }

        return $errors;
    }

    public function create($data) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            throw new Exception("Validation failed: " . implode("; ", $errors));
        }
        $this->conn->begin_transaction();
        try {
            $query = "INSERT INTO payments (
                lease_id, bill_id, payment_number, payment_date, amount, 
                payment_method, status, notes, received_by, transaction_reference
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $data = array_merge([
                'payment_number' => $this->generatePaymentNumber(),
                'status' => 'pending',
                'notes' => null,
                'payment_date' => date('Y-m-d', strtotime($data['payment_date'])),
                'received_by' => $_SESSION['user_id'] ?? 1,
                'transaction_reference' => null
            ], $data);
            $stmt->bind_param(
                'iissdsssis',
                $data['lease_id'],
                $data['bill_id'],
                $data['payment_number'],
                $data['payment_date'],
                $data['amount'],
                $data['payment_method'],
                $data['status'],
                $data['notes'],
                $data['received_by'],
                $data['transaction_reference']
            );
            $stmt->execute();
            $id = $this->conn->insert_id;
            $stmt->close();

            $payment_response = ['payment_id' => $id];
            if (strtolower($data['payment_method']) === 'telebirr') {
                $telebirr_response = $this->initiateTelebirrPayment($id, $data['amount'], $data['notes']);
                $data['transaction_reference'] = $telebirr_response['out_trade_no'];
                $payment_response['telebirr_url'] = $telebirr_response['toPayUrl'];
                $stmt = $this->conn->prepare("UPDATE payments SET transaction_reference = ? WHERE id = ?");
                $stmt->bind_param('si', $data['transaction_reference'], $id);
                $stmt->execute();
                $stmt->close();
            } elseif (strtolower($data['payment_method']) === 'cbe_birr') {
                $cbebirr_response = $this->initiateCBEBirrPayment($id, $data['amount'], $data['notes']);
                $data['transaction_reference'] = $cbebirr_response['transaction_id'];
                $payment_response['cbe_birr_url'] = $cbebirr_response['payment_url'];
                $stmt = $this->conn->prepare("UPDATE payments SET transaction_reference = ? WHERE id = ?");
                $stmt->bind_param('si', $data['transaction_reference'], $id);
                $stmt->execute();
                $stmt->close();
            }
            if ($data['status'] === 'completed') {
                $this->updateBillStatus($data['bill_id']);
                $stmt = $this->conn->prepare("UPDATE payments SET completed_at = NOW() WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
            if (!empty($data['receipt']) && is_array($data['receipt']) && $data['receipt']['error'] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => [$data['receipt']['name']],
                    'tmp_name' => [$data['receipt']['tmp_name']],
                    'type' => [$data['receipt']['type']],
                    'size' => [$data['receipt']['size']],
                    'error' => [$data['receipt']['error']]
                ];
                $this->uploadFiles($id, $file, $data['received_by']);
            }
            $this->logAction($id, 'create', $data['received_by'], $data);
            $this->conn->commit();
            return $payment_response;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Payment creation failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function update($id, $data) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $errors = $this->validateData($data, true);
        if (!empty($errors)) {
            throw new Exception("Validation failed: " . implode("; ", $errors));
        }
        $this->conn->begin_transaction();
        try {
            $query = "UPDATE payments SET 
                      lease_id = COALESCE(?, lease_id), 
                      bill_id = COALESCE(?, bill_id), 
                      amount = COALESCE(?, amount), 
                      payment_method = COALESCE(?, payment_method), 
                      status = COALESCE(?, status), 
                      payment_date = COALESCE(?, payment_date),
                      notes = COALESCE(?, notes),
                      updated_at = NOW()
                      WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $data = array_merge([
                'lease_id' => null,
                'bill_id' => null,
                'amount' => null,
                'payment_method' => null,
                'status' => null,
                'payment_date' => null,
                'notes' => null
            ], $data);
            $stmt->bind_param(
                'iisdsssi',
                $data['lease_id'],
                $data['bill_id'],
                $data['amount'],
                $data['payment_method'],
                $data['status'],
                $data['payment_date'],
                $data['notes'],
                $id
            );
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            if ($affected_rows > 0 && isset($data['status']) && $data['status'] === 'completed') {
                $this->updateBillStatus($data['bill_id']);
                $stmt = $this->conn->prepare("UPDATE payments SET completed_at = NOW() WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
            if ($affected_rows > 0) {
                $this->logAction($id, 'update', $_SESSION['user_id'] ?? 1, $data);
            }
            $this->conn->commit();
            return $affected_rows > 0;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Payment update failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function updateBillStatus($bill_id) {
        $stmt = $this->conn->prepare(
            "UPDATE bills b
             SET b.status = 'paid', b.updated_at = NOW()
             WHERE b.id = ? AND b.status != 'paid'
             AND b.total_amount <= (
                 SELECT COALESCE(SUM(p.amount), 0)
                 FROM payments p
                 WHERE p.bill_id = b.id AND p.status = 'completed'
             )"
        );
        $stmt->bind_param('i', $bill_id);
        $stmt->execute();
        $stmt->close();
    }

    public function delete($id) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("SELECT status FROM payments WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$payment) {
                throw new Exception("Payment not found");
            }
            if ($payment['status'] === 'completed') {
                throw new Exception("Cannot delete completed payment");
            }
            $stmt = $this->conn->prepare("DELETE FROM payments WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            if ($affected_rows > 0) {
                $this->logAction($id, 'delete', $_SESSION['user_id'] ?? 1);
            }
            $this->conn->commit();
            return $affected_rows > 0;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Payment deletion failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function getById($id) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $query = "SELECT p.*, l.lease_number, l.lease_term_of_years, b.bill_number, 
                         a.asset_code, a.kebele, a.size, o.full_name as owner_name
                  FROM payments p
                  LEFT JOIN lease_agreements l ON p.lease_id = l.id
                  LEFT JOIN bills b ON p.bill_id = b.id
                  LEFT JOIN assets a ON l.asset_id = a.id
                  LEFT JOIN owners o ON l.owner_id = o.id
                  WHERE p.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment = $result->fetch_assoc();
        $stmt->close();
        return $payment ?: null;
    }

    public function getAll($filters = []) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $query = "SELECT p.*, l.lease_number, l.lease_term_of_years, b.bill_number, 
                         a.asset_code, a.kebele, a.size, o.full_name as owner_name
                  FROM payments p
                  LEFT JOIN lease_agreements l ON p.lease_id = l.id
                  LEFT JOIN bills b ON p.bill_id = b.id
                  LEFT JOIN assets a ON l.asset_id = a.id
                  LEFT JOIN owners o ON l.owner_id = o.id
                  WHERE 1=1";
        $params = [];
        $types = '';
        if (!empty($filters['lease_id'])) {
            $query .= " AND p.lease_id = ?";
            $params[] = $filters['lease_id'];
            $types .= 'i';
        }
        if (!empty($filters['bill_id'])) {
            $query .= " AND p.bill_id = ?";
            $params[] = $filters['bill_id'];
            $types .= 'i';
        }
        if (!empty($filters['payment_method'])) {
            $query .= " AND p.payment_method = ?";
            $params[] = $filters['payment_method'];
            $types .= 's';
        }
        if (!empty($filters['status'])) {
            $query .= " AND p.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        if (!empty($filters['kebele'])) {
            $query .= " AND a.kebele LIKE ?";
            $params[] = "%{$filters['kebele']}%";
            $types .= 's';
        }
        if (!empty($filters['search'])) {
            $query .= " AND (p.payment_number LIKE ? OR l.lease_number LIKE ? OR b.bill_number LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $types .= 'sss';
        }
        $query .= " ORDER BY p.payment_date DESC";
        $limit = max(1, (int)($filters['limit'] ?? 100));
        $offset = max(0, (int)($filters['offset'] ?? 0));
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $payments = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $payments;
    }

    public function uploadFiles($payment_id, $files, $user_id) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $uploadDir = __DIR__ . '/../../public/uploads/payments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $allowedTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $maxFiles = 3;
        $uploadedFiles = [];
        if (empty($files['name'][0])) {
            return [];
        }
        if (count($files['name']) > $maxFiles) {
            throw new Exception("Maximum $maxFiles files allowed");
        }
        $this->conn->begin_transaction();
        try {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    throw new Exception("Upload error for file {$files['name'][$i]}");
                }
                if ($files['size'][$i] > $maxSize) {
                    throw new Exception("File {$files['name'][$i]} exceeds 5MB limit");
                }
                $fileType = mime_content_type($files['tmp_name'][$i]);
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("File {$files['name'][$i]} type not allowed");
                }
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $uniqueName = $payment_id . '_' . time() . '_' . uniqid() . '.' . $ext;
                $filePath = $uploadDir . $uniqueName;
                if (!move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                    throw new Exception("Failed to upload file {$files['name'][$i]}");
                }
                $query = "INSERT INTO payment_files (payment_id, file_name, file_path, file_type) 
                          VALUES (?, ?, ?, ?)";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param('isss', $payment_id, $files['name'][$i], $uniqueName, $fileType);
                $stmt->execute();
                $file_id = $this->conn->insert_id;
                $stmt->close();
                $this->logAction($payment_id, 'upload_file', $user_id, [
                    'file_id' => $file_id,
                    'file_name' => $files['name'][$i]
                ]);
                $uploadedFiles[] = $uniqueName;
            }
            $this->conn->commit();
            return $uploadedFiles;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("File upload failed for payment $payment_id: " . $e->getMessage());
            throw $e;
        }
    }

    private function initiateTelebirrPayment($payment_id, $amount, $notes) {
        try {
            // Load config
            $config_path = '../config/telebirr.php';
            if (!file_exists($config_path)) {
                error_log("Telebirr config file not found: $config_path");
                throw new Exception("Telebirr config file not found: $config_path");
            }
            $config = require_once $config_path;
            if (!is_array($config)) {
                error_log("Telebirr config file did not return an array: $config_path");
                throw new Exception("Invalid Telebirr config file: $config_path");
            }

            // Validate config
            if (empty($config['app_id']) || $config['app_id'] === 'YOUR_TELEBIRR_APP_ID' || $config['app_id'] === 'TEST_APP_ID') {
                throw new Exception("Telebirr app_id not configured");
            }
            if (empty($config['app_key']) || $config['app_key'] === 'YOUR_TELEBIRR_APP_KEY' || $config['app_key'] === 'TEST_APP_KEY') {
                throw new Exception("Telebirr app_key not configured");
            }
            if (empty($config['public_key']) || strpos($config['public_key'], 'BEGIN PUBLIC KEY') === false) {
                throw new Exception("Invalid or missing Telebirr public_key");
            }
            if (empty($config['short_code']) || $config['short_code'] === 'YOUR_TELEBIRR_SHORT_CODE' || $config['short_code'] === 'TEST_SHORT_CODE') {
                throw new Exception("Telebirr short_code not configured");
            }

            // Fetch lease details
            $stmt = $this->conn->prepare("SELECT l.lease_number, l.lease_term_of_years FROM payments p JOIN lease_agreements l ON p.lease_id = l.id WHERE p.id = ?");
            $stmt->bind_param('i', $payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $lease = $result->fetch_assoc();
            $stmt->close();
            if (!$lease) {
                throw new Exception("Lease not found for payment ID: $payment_id");
            }

            // Generate payload
            $nonce = bin2hex(random_bytes(8));
            $out_trade_no = "PAY{$payment_id}_" . time();
            $timestamp = strval(intval(microtime(true) * 1000));
            $subject = $notes ?: "Lease Payment for {$lease['lease_number']}" . ($lease['lease_term_of_years'] ? ", Term: {$lease['lease_term_of_years']} years" : '');
            $ussd_json = [
                'appId' => $config['app_id'],
                'nonce' => $nonce,
                'notifyUrl' => $config['notify_url'],
                'outTradeNo' => $out_trade_no,
                'receiveName' => $config['receive_name'],
                'returnUrl' => $config['return_url'],
                'shortCode' => $config['short_code'],
                'subject' => $subject,
                'timeoutExpress' => $config['timeout_express'],
                'timestamp' => $timestamp,
                'totalAmount' => number_format($amount, 2, '.', '')
            ];
            $ussd_string = json_encode($ussd_json, JSON_UNESCAPED_UNICODE);

            // Encrypt payload
            try {
                $public_key = PublicKeyLoader::load($config['public_key']);
            } catch (Exception $e) {
                throw new Exception("Failed to load public key: " . $e->getMessage());
            }
            $encrypted = base64_encode($public_key->encrypt($ussd_string));

            // Generate signature
            $sign_string = $config['app_id'] . $ussd_string . $config['app_key'];
            $sign = hash('sha256', $sign_string);

            // API request
            $payload = [
                'appId' => $config['app_id'],
                'sign' => $sign,
                'ussd' => $encrypted
            ];
            error_log("Telebirr payload for payment $payment_id: " . json_encode($payload));

            // Mock API response
            $response = json_encode(['toPayUrl' => 'https://mock.telebirr.et/payment/' . $out_trade_no]);
            $http_code = 200;
            $curl_error = '';
            $headers = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n";
            $body = $response;

            error_log("Telebirr mock response for payment $payment_id: HTTP $http_code, Headers: $headers, Body: $body, Error: $curl_error");

            if ($response === false) {
                throw new Exception("Telebirr API request failed: $curl_error");
            }
            if ($http_code !== 200) {
                throw new Exception("Telebirr API request failed: HTTP $http_code, Headers: $headers, Body: " . ($body ?: 'No response'));
            }
            $response_data = json_decode($body, true);
            if (empty($response_data['toPayUrl'])) {
                throw new Exception("Invalid Telebirr response: No toPayUrl, Body: " . json_encode($response_data));
            }

            $this->logAction($payment_id, 'telebirr_initiate', $_SESSION['user_id'] ?? 1, [
                'out_trade_no' => $out_trade_no,
                'toPayUrl' => $response_data['toPayUrl']
            ]);

            return [
                'out_trade_no' => $out_trade_no,
                'toPayUrl' => $response_data['toPayUrl']
            ];
        } catch (Exception $e) {
            error_log("Telebirr payment initiation failed for payment $payment_id: " . $e->getMessage());
            throw new Exception("Failed to initiate Telebirr payment: " . $e->getMessage());
        }
    }

    private function initiateCBEBirrPayment($payment_id, $amount, $notes) {
        try {
            $stmt = $this->conn->prepare("SELECT l.lease_number, l.lease_term_of_years FROM payments p JOIN lease_agreements l ON p.lease_id = l.id WHERE p.id = ?");
            $stmt->bind_param('i', $payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $lease = $result->fetch_assoc();
            $stmt->close();

            $config = [
                'merchant_id' => 'YOUR_CBE_BIRR_MERCHANT_ID',
                'api_key' => 'YOUR_CBE_BIRR_API_KEY',
                'api_url' => 'https://api.cbebirr.et/v1/payments/initiate',
                'notify_url' => 'https://yourdomain.com/payments/cbebirr_notify.php',
                'return_url' => 'https://yourdomain.com/payments/return.php',
                'merchant_name' => 'Werabe Land Lease',
                'timeout' => '1800' // 30 minutes
            ];
            $transaction_id = "CBE{$payment_id}_" . time();
            $timestamp = date('Y-m-d\TH:i:s\Z');
            $description = $notes ?: "Lease Payment for {$lease['lease_number']}" . ($lease['lease_term_of_years'] ? ", Term: {$lease['lease_term_of_years']} years" : '');
            $payload = [
                'merchantId' => $config['merchant_id'],
                'transactionId' => $transaction_id,
                'amount' => number_format($amount, 2, '.', ''),
                'description' => $description,
                'notifyUrl' => $config['notify_url'],
                'returnUrl' => $config['return_url'],
                'timestamp' => $timestamp,
                'merchantName' => $config['merchant_name'],
                'timeout' => $config['timeout']
            ];
            $sign_string = $config['merchant_id'] . $transaction_id . $payload['amount'] . $timestamp . $config['api_key'];
            $signature = hash_hmac('sha256', $sign_string, $config['api_key']);
            $payload['signature'] = $signature;

            $ch = curl_init($config['api_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new Exception("CBE-Birr API request failed: $curl_error");
            }
            if ($http_code !== 200) {
                throw new Exception("CBE-Birr API request failed: HTTP $http_code");
            }
            $response_data = json_decode($response, true);
            if (empty($response_data['paymentUrl'])) {
                throw new Exception("Invalid CBE-Birr response: No paymentUrl");
            }

            $this->logAction($payment_id, 'cbe_birr_initiate', $_SESSION['user_id'] ?? 1, [
                'transaction_id' => $transaction_id,
                'payment_url' => $response_data['paymentUrl']
            ]);

            return [
                'transaction_id' => $transaction_id,
                'payment_url' => $response_data['paymentUrl']
            ];
        } catch (Exception $e) {
            error_log("CBE-Birr payment initiation failed for payment $payment_id: " . $e->getMessage());
            throw new Exception("Failed to initiate CBE-Birr payment: Contact CBE support for assistance");
        }
    }

    public function getStatistics() {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $query = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                    SUM(CASE WHEN payment_method = 'telebirr' THEN 1 ELSE 0 END) as telebirr_payments,
                    SUM(CASE WHEN payment_method = 'cbe_birr' THEN 1 ELSE 0 END) as cbe_birr_payments,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_revenue,
                    SUM(CASE WHEN l.lease_term_of_years = 1 THEN 1 ELSE 0 END) as payments_1_year,
                    SUM(CASE WHEN l.lease_term_of_years = 2 THEN 1 ELSE 0 END) as payments_2_years,
                    SUM(CASE WHEN l.lease_term_of_years = 5 THEN 1 ELSE 0 END) as payments_5_years,
                    SUM(CASE WHEN l.lease_term_of_years NOT IN (1, 2, 5) OR l.lease_term_of_years IS NULL THEN 1 ELSE 0 END) as payments_other
                  FROM payments p
                  LEFT JOIN lease_agreements l ON p.lease_id = l.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        return [
            'total_payments' => (int)$stats['total_payments'],
            'completed_payments' => (int)$stats['completed_payments'],
            'pending_payments' => (int)$stats['pending_payments'],
            'failed_payments' => (int)$stats['failed_payments'],
            'telebirr_payments' => (int)$stats['telebirr_payments'],
            'cbe_birr_payments' => (int)$stats['cbe_birr_payments'],
            'total_revenue' => (float)$stats['total_revenue'],
            'payments_by_term' => [
                '1_year' => (int)$stats['payments_1_year'],
                '2_years' => (int)$stats['payments_2_years'],
                '5_years' => (int)$stats['payments_5_years'],
                'other' => (int)$stats['payments_other']
            ]
        ];
    }
}
?>