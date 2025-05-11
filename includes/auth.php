<?php
namespace Werabe\LandLease;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT id, username, role, full_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function logout() {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
    $_SESSION['initialized'] = true;
}
?>