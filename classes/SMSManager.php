<?php
class SMSManager {
    private $conn;
    private $smsClient;
    
    public function __construct($db) {
        $this->conn = $db->getConnection();
        $this->smsClient = new EthioTelecomSms(
            getenv('ET_USERNAME'),
            getenv('ET_PASSWORD'),
            getenv('ET_SENDER_ID')
        );
    }
    
    // Create new SMS notification
    public function createNotification($data) {
        $required = ['phone_number', 'message', 'send_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        $query = "INSERT INTO sms_notifications 
                 (template_id, recipient_type, recipient_id, phone_number, 
                  message, status, send_date, created_by)
                 VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "isissii",
            $data['template_id'],
            $data['recipient_type'],
            $data['recipient_id'],
            $data['phone_number'],
            $data['message'],
            $data['send_date'],
            $data['created_by'] ?? null
        );
        
        $stmt->execute();
        return $this->conn->insert_id;
    }
    
    // Process pending notifications
    public function processPendingNotifications() {
        $notifications = $this->getPendingNotifications();
        $results = [];
        
        foreach ($notifications as $notification) {
            try {
                $result = $this->sendNotification($notification);
                $results[] = $result;
            } catch (Exception $e) {
                error_log("Failed to process notification {$notification['id']}: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    // Get pending notifications
    private function getPendingNotifications() {
        $query = "SELECT * FROM sms_notifications 
                 WHERE status = 'pending' 
                 AND send_date <= NOW() 
                 AND attempts < 3
                 ORDER BY send_date ASC";
        
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Send individual notification
    private function sendNotification($notification) {
        $this->conn->begin_transaction();
        
        try {
            // Send via Ethio Telecom API
            $response = $this->smsClient->sendSms(
                $notification['phone_number'],
                $notification['message']
            );
            
            // Update notification status
            $status = $response['status'] === 'success' ? 'sent' : 'failed';
            $this->updateNotification(
                $notification['id'],
                $status,
                $response['message'] ?? null,
                $response['message_id'] ?? null
            );
            
            // Log delivery
            $this->logDelivery(
                $notification['id'],
                $notification['phone_number'],
                $status,
                $response
            );
            
            $this->conn->commit();
            return [
                'id' => $notification['id'],
                'status' => $status,
                'message_id' => $response['message_id'] ?? null
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->handleFailedNotification($notification['id'], $e);
            throw $e;
        }
    }
    
    // Update notification status
    private function updateNotification($id, $status, $error = null, $messageId = null) {
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
    
    // Log delivery result
    private function logDelivery($notificationId, $phone, $status, $response) {
        $query = "INSERT INTO sms_delivery_logs 
                 (notification_id, phone_number, status, response)
                 VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $responseJson = json_encode($response);
        $stmt->bind_param("isss", $notificationId, $phone, $status, $responseJson);
        $stmt->execute();
    }
    
    // Handle failed notifications
    private function handleFailedNotification($id, $exception) {
        $this->updateNotification(
            $id,
            'failed',
            substr($exception->getMessage(), 0, 255)
        );
        
        error_log("Notification $id failed: " . $exception->getMessage());
    }
    
    // Template management
    public function getTemplate($id) {
        $query = "SELECT * FROM sms_templates WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function createTemplate($name, $template, $variables) {
        $query = "INSERT INTO sms_templates 
                 (name, template, variables)
                 VALUES (?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sss", $name, $template, $variables);
        $stmt->execute();
        return $this->conn->insert_id;
    }
    
    // Generate message from template
    public function renderTemplate($templateId, $variables) {
        $template = $this->getTemplate($templateId);
        if (!$template) {
            throw new Exception("Template not found");
        }
        
        $message = $template['template'];
        foreach ($variables as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        
        return $message;
    }
}
?>