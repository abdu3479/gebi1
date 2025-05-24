<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}

$property_id = isset($_GET['property_id']) && is_numeric($_GET['property_id']) ? (int)$_GET['property_id'] : null;

if ($property_id) {
    $stmt = $conn->prepare("SELECT building_id, building_name FROM buildings WHERE property_id = ? ORDER BY building_name");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $buildings = [];
    while ($row = $result->fetch_assoc()) {
        $buildings[] = [
            'building_id' => (int)$row['building_id'],
            'building_name' => sanitize($row['building_name'])
        ];
    }
    
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($buildings);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([]);
}
?>