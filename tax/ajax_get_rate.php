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

$rate_id = isset($_GET['rate_id']) && is_numeric($_GET['rate_id']) ? (int)$_GET['rate_id'] : null;

if ($rate_id) {
    $stmt = $conn->prepare("SELECT rate_percentage FROM tax_rates WHERE rate_id = ? AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
    $stmt->bind_param("i", $rate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    header('Content-Type: application/json');
    if ($row) {
        echo json_encode(['rate_percentage' => (float)$row['rate_percentage']]);
    } else {
        echo json_encode([]);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([]);
}
?>