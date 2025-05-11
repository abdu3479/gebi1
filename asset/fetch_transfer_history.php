<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Asset.php';

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("CSRF validation failed. Received: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . ($_SESSION['csrf_token'] ?? 'none'));
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $asset = new Asset($conn);

    // Get DataTable parameters
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

    // Fetch transfer history
    $data = $asset->getAllTransferHistory($start, $length, $search);
    $totalRecords = $asset->getTransferHistoryCount($search);

    // Format response for DataTables
    $response = [
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ];

    header('Content-Type: application/json');
    ob_clean();
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Error in fetch_transfer_history: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>