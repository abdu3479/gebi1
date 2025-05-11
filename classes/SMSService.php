<?php
class SMSService {
    private $conn;
    private $billObj;
    private $leaseObj;
    private $ownerObj;
    private $tenantObj;
    
    public function __construct($db) {
        $this->conn = $db->getConnection();
        $this->billObj = new Bill($this->conn);
        $this->leaseObj = new Lease($this->conn);
        $this->ownerObj = new Owner($this->conn);
        $this->tenantObj = new Tenant($this->conn);
    }
    
    // Core SMS functionality
    public function sendNewBillNotification($billId) {
        $bill = $this->billObj->getById($billId);
        if (!$bill) {
            throw new Exception("Bill not found");
        }
        
        $lease = $this->leaseObj->getById($bill['lease_id']);
        $owner = $this->ownerObj->getById($lease['owner_id']);
        
        $lang = $this->getUserLanguage($owner['id']);
        $template = $this->getTemplate('new_bill_' . $lang);
        
        $message = $this->renderTemplate($template, [
            'amount' => number_format($bill['total_amount'], 2),
            'due_date' => date('M j, Y', strtotime($bill['due_date'])),
            'property' => $lease['property_name'],
            'bill_number' => $bill['bill_number']
        ]);
        
        return $this->queueNotification([
            'bill_id' => $billId,
            'lease_id' => $lease['id'],
            'owner_id' => $owner['id'],
            'phone_number' => $owner['phone'],
            'message' => $message,
            'send_date' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function processPendingNotifications() {
        $notifications = $this->getPendingNotifications();
        $results = [];
        
        foreach ($notifications as $notification) {
            $this->conn->begin_transaction();
            
            try {
                $response = $this->sendViaEthioTelecom(
                    $notification['phone_number'],
                    $notification['message']
                );
                
                if ($response['status'] === 'success') {
                    $this->updateNotificationStatus(
                        $notification['id'], 
                        'sent',
                        null,
                        $response['message_id']
                    );
                    $this->logDelivery(
                        $notification['id'],
                        $notification['phone_number'],
                        'sent',
                        $response
                    );
                } else {
                    throw new Exception($response['error_message']);
                }
                
                $this->conn->commit();
                $results[] = [
                    'id' => $notification['id'],
                    'status' => 'sent',
                    'message_id' => $response['message_id']
                ];
            } catch (Exception $e) {
                $this->conn->rollback();
                $this->handleFailedNotification($notification['id'], $e);
                $results[] = [
                    'id' => $notification['id'],
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    public function processSingleNotification($notificationId) {
        $this->conn->begin_transaction();
        
        try {
            $query = "SELECT * FROM sms_notifications WHERE id = ? AND status = 'pending' LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $notificationId);
            $stmt->execute();
            $notification = $stmt->get_result()->fetch_assoc();
            
            if (!$notification) {
                throw new Exception("Notification not found or already processed");
            }
            
            $response = $this->sendViaEthioTelecom(
                $notification['phone_number'],
                $notification['message']
            );
            
            if ($response['status'] !== 'success') {
                throw new Exception($response['error_message'] ?? 'SMS sending failed');
            }
            
            $this->updateNotificationStatus(
                $notificationId,
                'sent',
                null,
                $response['message_id']
            );
            
            $this->logDelivery(
                $notificationId,
                $notification['phone_number'],
                'sent',
                $response
            );
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message_id' => $response['message_id']
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->handleFailedNotification($notificationId, $e);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Reporting and UI methods
    public function getSMSStats() {
        $query = "SELECT 
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM sms_notifications";
        
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }
    
    public function getPendingNotifications() {
        $query = "SELECT * FROM sms_notifications 
                 WHERE status = 'pending' 
                 AND send_date <= NOW() 
                 AND attempts < 3
                 ORDER BY send_date ASC";
        
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getFailedNotifications() {
        $query = "SELECT * FROM sms_notifications 
                 WHERE status = 'failed' AND attempts >= 3
                 ORDER BY updated_at DESC
                 LIMIT 50";
        
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getTemplates() {
        $query = "SELECT * FROM sms_templates WHERE is_active = 1";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getTemplateById($id) {
        $query = "SELECT * FROM sms_templates WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Template not found");
        }
        
        return $result->fetch_assoc();
    }
    
    public function createNotification($data) {
        // Validate recipient
        $phone = null;
        $ownerId = null;
        $leaseId = null;
        $billId = null;
        
        if ($data['recipient_type'] === 'custom' && !empty($data['custom_number'])) {
            $phone = $this->formatPhoneNumber($data['custom_number']);
        } else {
            // Get recipient details based on type
            switch ($data['recipient_type']) {
                case 'owner':
                    $owner = $this->ownerObj->getById($data['recipient_id']);
                    if (!$owner) throw new Exception("Owner not found");
                    $phone = $owner['phone'];
                    $ownerId = $owner['id'];
                    break;
                    
                case 'tenant':
                    $tenant = $this->tenantObj->getById($data['recipient_id']);
                    if (!$tenant) throw new Exception("Tenant not found");
                    $phone = $tenant['phone'];
                    $leaseId = $tenant['lease_id'];
                    break;
                    
                case 'group':
                    throw new Exception("Group sending not yet implemented");
                    break;
                    
                default:
                    throw new Exception("Invalid recipient type");
            }
        }
        
        // Validate message
        if (empty($data['message'])) {
            throw new Exception("Message cannot be empty");
        }
        
        // Insert notification
        $query = "INSERT INTO sms_notifications 
                 (bill_id, lease_id, owner_id, phone_number, message, send_date, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "iiisss",
            $billId,
            $leaseId,
            $ownerId,
            $phone,
            $data['message'],
            $data['send_date']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create notification: " . $stmt->error);
        }
        
        $notificationId = $this->conn->insert_id;
        
        // Log creation
        $this->logAction(
            $notificationId,
            'sms_created',
            $data['created_by'],
            [
                'recipient_type' => $data['recipient_type'],
                'phone_number' => $phone,
                'message_length' => strlen($data['message'])
            ]
        );
        
        return [
            'id' => $notificationId,
            'phone' => $phone
        ];
    }
    
    // Helper methods
    private function sendViaEthioTelecom($phone, $message) {
        $phone = $this->formatPhoneNumber($phone);
        $data = [
            'username' => getenv('ET_USERNAME'),
            'password' => getenv('ET_PASSWORD'),
            'to' => $phone,
            'text' => substr($message, 0, 612),
            'senderid' => getenv('ET_SENDER_ID')
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => getenv('ET_API_URL'),
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
        
        if ($error) {
            throw new Exception("CURL error: " . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200 || !isset($result['status'])) {
            throw new Exception("API error: " . ($result['message'] ?? 'Unknown error'));
        }
        
        return [
            'status' => $result['status'] === 'success' ? 'success' : 'error',
            'message_id' => $result['message_id'] ?? null,
            'error_message' => $result['message'] ?? null
        ];
    }
    
    private function queueNotification($data) {
        $query = "INSERT INTO sms_notifications 
                 (bill_id, lease_id, owner_id, phone_number, message, send_date, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "iiisss",
            $data['bill_id'],
            $data['lease_id'],
            $data['owner_id'],
            $data['phone_number'],
            $data['message'],
            $data['send_date']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to queue SMS notification: " . $stmt->error);
        }
        
        return $this->conn->insert_id;
    }
    
    private function updateNotificationStatus($id, $status, $error = null, $messageId = null) {
        $query = "UPDATE sms_notifications 
                 SET status = ?, 
                     error_message = ?,
                     message_id = ?,
                     attempts = attempts + 1,
                     updated_at = NOW()
                 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssi", $status, $error, $messageId, $id);
        $stmt->execute();
    }
    
    private function logDelivery($notificationId, $phone, $status, $response) {
        $query = "INSERT INTO sms_delivery_logs 
                 (notification_id, phone_number, status, response)
                 VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $responseJson = json_encode($response);
        $stmt->bind_param("isss", $notificationId, $phone, $status, $responseJson);
        $stmt->execute();
    }
    
    private function handleFailedNotification($id, $exception) {
        $this->updateNotificationStatus(
            $id,
            'failed',
            substr($exception->getMessage(), 0, 255)
        );
        
        error_log("Notification failed: " . $exception->getMessage());
    }
    
    private function logAction($notificationId, $action, $userId, $details = null) {
        $query = "INSERT INTO audit_logs 
                 (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent)
                 VALUES (?, ?, 'sms_notifications', ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $detailsJson = $details ? json_encode($details) : null;
        $stmt->bind_param(
            "isisss",
            $userId,
            $action,
            $notificationId,
            $detailsJson,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        $stmt->execute();
    }
    
    private function formatPhoneNumber($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) === 10 && $phone[0] === '0') {
            return '251' . substr($phone, 1);
        }
        
        if (strlen($phone) === 9 && $phone[0] === '9') {
            return '251' . $phone;
        }
        
        return $phone;
    }
    
    private function getUserLanguage($ownerId) {
        $query = "SELECT language FROM owner_preferences WHERE owner_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $ownerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc()['language'] : 'en';
    }
    
    private function getTemplate($name) {
        $query = "SELECT template FROM sms_templates WHERE name = ? AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Template not found: " . $name);
        }
        
        return $result->fetch_assoc()['template'];
    }
    
    private function renderTemplate($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{" . $key . "}}", $value, $template);
        }
        return $template;
    }
}
?>