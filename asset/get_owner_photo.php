<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

if (isset($_GET['owner_id']) && is_numeric($_GET['owner_id'])) {
    $owner_id = (int)$_GET['owner_id'];
    $stmt = $conn->prepare("SELECT photo FROM owners WHERE id = ?");
    $stmt->bind_param('i', $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $owner = $result->fetch_assoc();
    $stmt->close();
    
    $photo = $owner['photo'] ?? '/uploads/owners/no-photo.png';
    header('Content-Type: application/json');
    echo json_encode(['photo' => $photo]);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid owner ID']);
}
?>