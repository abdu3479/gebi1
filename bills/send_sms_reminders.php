<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/classes/Bill.php';
require_once dirname(__DIR__) . '/classes/Lease.php';
require_once dirname(__DIR__) . '/classes/Owner.php';

// Load configuration from environment (better for security)
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Ethio Telecom Credentials
define('ET_API_URL', $_ENV['ET_API_URL'] ?? 'https://sms.ethiotelecom.et/api/send');
define('ET_USERNAME', $_ENV['ET_USERNAME']);
define('ET_PASSWORD', $_ENV['ET_PASSWORD']);
define('ET_SENDER_ID', $_ENV['ET_SENDER_ID'] ?? 'GEBI1');
define('SMS_MAX_LENGTH', 612);
define('MAX_RETRIES', 3);

class SmsNotificationService {
    private $conn;
    private $billObj;
    private $leaseObj;
    private $ownerObj;
    
    public function __construct($db) {
        $this->conn = $db->getConnection();
        $this->billObj = new Bill($this->conn);
        $this->leaseObj = new Lease($this->conn);
        $this->ownerObj = new Owner($this->conn);
    }
    
    public function processPendingNotifications() {
        $notifications = $this->getPendingNotifications();
        
        foreach ($notifications as $notification) {
            $this->conn->begin_transaction();
            
            try {
                $this->validateNotification($notification);
                $response = $this->sendSms($notification);
                
                if ($response['status'] === 'success') {
                    $this->updateNotificationStatus(
                        $notification['id'], 
                        'sent',
                        null,
                        $response['message_id'] ?? null
                    );
                    $this->logSuccessfulDelivery($notification, $response);
                } else {
                    throw new Exception($response['message'] ?? 'SMS sending failed');
                }
                
                $this->conn->commit();
            } catch (Exception $e) {
                $this->conn->rollback();
                $this->handleFailedNotification($notification['id'], $e);
            }
        }
    }
    
    private function getPendingNotifications() {
        $query = "SELECT id, bill_id, lease_id, phone_number, message 
                  FROM sms_notifications 
                  WHERE status = 'pending' 
                  AND send_date <= CURDATE()
                  AND attempts < ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', MAX_RETRIES);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    private function validateNotification($notification) {
        // Validate related entities exist
        if ($notification['bill_id']) {
            $bill = $this->billObj->getById($notification['bill_id']);
            if (!$bill) {
                throw new Exception("Associated bill not found");
            }
        }
        
        if ($notification['lease_id']) {
            $lease = $this->leaseObj->getById($notification['lease_id']);
            if (!$lease) {
                throw new Exception("Associated lease not found");
            }
        }
        
        // Validate owner exists and is active
        $phone = $this->sanitizePhone($notification['phone_number']);
        $owner = $this->ownerObj->getAll(['phone' => $phone, 'status' => 'active']);
        if (empty($owner)) {
            throw new Exception("No active owner found for phone: $phone");
        }
    }
    
    private function sendSms($notification, $attempt = 1) {
        $data = [
            'username' => ET_USERNAME,
            'password' => ET_PASSWORD,
            'to' => $this->sanitizePhone($notification['phone_number']),
            'text' => substr($notification['message'], 0, SMS_MAX_LENGTH),
            'senderid' => ET_SENDER_ID
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => ET_API_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error && $attempt < MAX_RETRIES) {
            sleep($attempt * 2); // Exponential backoff
            return $this->sendSms($notification, $attempt + 1);
        }
        
        if ($error) {
            throw new Exception("CURL error after $attempt attempts: $error");
        }
        
        return $this->parseApiResponse($httpCode, $response);
    }
    
    private function parseApiResponse($httpCode, $response) {
        $result = [
            'status' => ($httpCode == 200) ? 'success' : 'error',
            'http_code' => $httpCode,
            'message' => $response
        ];
        
        // Try to parse JSON response if available
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $result['api_response'] = $json;
            if (isset($json['message_id'])) {
                $result['message_id'] = $json['message_id'];
            }
        }
        
        return $result;
    }
    
    private function updateNotificationStatus($id, $status, $error = null, $messageId = null) {
        $query = "UPDATE sms_notifications 
                 SET status = ?, 
                     error_message = ?, 
                     message_id = ?,
                     attempts = attempts + 1,
                     last_attempt = NOW(),
                     updated_at = NOW() 
                 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssi", $status, $error, $messageId, $id);
        $stmt->execute();
    }
    
    private function logSuccessfulDelivery($notification, $response) {
        $query = "INSERT INTO sms_delivery_logs 
                 (notification_id, phone_number, message, message_id, 
                  response, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $responseJson = json_encode($response);
        $messageId = $response['message_id'] ?? null;
        
        $stmt->bind_param(
            "issss",
            $notification['id'],
            $notification['phone_number'],
            $notification['message'],
            $messageId,
            $responseJson
        );
        $stmt->execute();
    }
    
    private function handleFailedNotification($id, $exception) {
        error_log("Notification failed: " . $exception->getMessage());
        $this->updateNotificationStatus(
            $id,
            'failed',
            substr($exception->getMessage(), 0, 255)
        );
    }
    
    private function sanitizePhone($phone) {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert Ethiopian numbers to international format
        if (strlen($cleaned) === 9 && $cleaned[0] === '9') {
            return '251' . $cleaned;
        }
        if (strlen($cleaned) === 10 && $cleaned[0] === '0') {
            return '251' . substr($cleaned, 1);
        }
        
        return $cleaned;
    }
}

// Main execution
try {
    $db = new Database();
    $smsService = new SmsNotificationService($db);
    $smsService->processPendingNotifications();
    
    echo "SMS notifications processed successfully.\n";
    exit(0);
} catch (Exception $e) {
    error_log("System error: " . $e->getMessage());
    exit(1);
}
?>