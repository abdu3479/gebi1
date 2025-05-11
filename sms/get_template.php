<?php
require_once '../../config/database.php';
require_once '../../classes/SMSService.php';

header('Content-Type: application/json');

$id = $_POST['id'] ?? 0;
$db = new Database();
$smsService = new SMSService($db);

try {
    $template = $smsService->getTemplateById($id);
    echo json_encode([
        'content' => $template['template'],
        'variables' => explode(',', $template['variables'])
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>