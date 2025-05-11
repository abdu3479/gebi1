<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/sms.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $query = "SELECT id, full_name, phone FROM owners";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        if (!isValidPhoneNumber($row['phone'])) {
            echo "Invalid phone: ID={$row['id']}, Name={$row['full_name']}, Phone={$row['phone']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking phones: " . $e->getMessage() . "\n";
}
?>