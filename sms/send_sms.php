<?php
require_once '../../config/database.php';
require_once '../../classes/SMSService.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

$db = new Database();
$smsService = new SMSService($db);

try {
    $data = [
        'recipient_type' => $_POST['recipientType'],
        'recipient_id' => $_POST['recipientId'] ?? null,
        'custom_number' => $_POST['customNumber'] ?? null,
        'template_id' => $_POST['templateId'] ?? null,
        'message' => $_POST['message'],
        'send_date' => $_POST['sendDate'] ?? date('Y-m-d H:i:s'),
        'created_by' => $_SESSION['user_id']
    ];
    
    $result = $smsService->createNotification($data);
    
    // Process immediately if scheduled for now
    if ($_POST['schedule'] === 'now') {
        $processResult = $smsService->processSingleNotification($result['id']);
        
        if (!$processResult['success']) {
            throw new Exception("Queued but processing failed: " . $processResult['error']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'SMS notification created successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>