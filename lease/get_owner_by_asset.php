<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../classes/Lease.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $leaseObj = new Lease($conn);

    $asset_id = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;
    if ($asset_id <= 0) {
        throw new Exception('Invalid asset ID');
    }

    // Fetch most recent lease for the asset
    $query = "SELECT owner_id 
              FROM lease_agreements 
              WHERE asset_id = ? 
              ORDER BY created_at DESC 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lease = $result->fetch_assoc();
    $stmt->close();

    if (!$lease || !isset($lease['owner_id'])) {
        throw new Exception('No owner found for this asset');
    }

    echo json_encode([
        'success' => true,
        'owner_id' => (int)$lease['owner_id']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>