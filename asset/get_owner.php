<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

if (!isset($_POST['owner_id']) || !is_numeric($_POST['owner_id'])) {
    echo json_encode(['error' => 'Invalid owner ID']);
    exit;
}

$owner_id = (int)$_POST['owner_id'];
$stmt = $conn->prepare("SELECT full_name, photo FROM owners WHERE id = ?");
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'full_name' => $row['full_name'],
        'photo' => $row['photo'] ?? 'uploads/owners/no-photo.jpg'
    ]);
} else {
    echo json_encode(['error' => 'Owner not found']);
}

$stmt->close();
?>