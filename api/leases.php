<?php
header('Content-Type: application/json');
require_once '../Lease.php';

try {
    $db = new mysqli('localhost', 'root', '', 'aziyan');
    $lease = new Lease($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode($lease->getAll());
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'owner_id' => $_POST['owner_id'],
            'asset_id' => $_POST['asset_id'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'monthly_rent' => $_POST['monthly_rent'],
            'description' => $_POST['description'] ?? null
        ];
        $id = $lease->create($data);
        echo json_encode(['id' => $id]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>