<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SMSService.php';

// Initialize
try {
    $db = new Database();
    $smsService = new SMSService($db);
    
    // Process pending notifications
    $results = $smsService->processPendingNotifications();
    
    // Log processing summary
    $successCount = count(array_filter($results, fn($r) => $r['status'] === 'sent'));
    $errorCount = count($results) - $successCount;
    
    file_put_contents(
        __DIR__ . '/../logs/sms_processing.log',
        date('Y-m-d H:i:s') . " - Processed " . count($results) . 
        " notifications ($successCount success, $errorCount failed)\n",
        FILE_APPEND
    );
    
    exit(0);
} catch (Exception $e) {
    error_log("SMS processing failed: " . $e->getMessage());
    file_put_contents(
        __DIR__ . '/../logs/sms_processing.log',
        date('Y-m-d H:i:s') . " - Processing failed: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    exit(1);
}
?>