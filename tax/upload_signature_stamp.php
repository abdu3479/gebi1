<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is authenticated and is admin
ensureAuthenticated();
if (!isAdmin()) {
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('assessments.php');
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid security token.";
    redirect('assessments.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . sanitize($e->getMessage());
    redirect('assessments.php');
}

// Function to validate and process file upload
function processFile($file, $type, $conn, $user_id) {
    $maxFileSize = 1024 * 1024; // 1MB
    $allowedTypes = ['image/jpg','image/png','image/jpeg'];

    // Check if file was uploaded
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return; // No file uploaded, skip processing
    }

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error uploading $type.");
    }
    if ($file['size'] > $maxFileSize) {
        throw new Exception("$type file size exceeds 1MB.");
    }
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("$type must be a PNG image.");
    }

    // Read file content
    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) {
        throw new Exception("Failed to read $type file.");
    }

    // Check if a record already exists for this user and type
    $stmt = $conn->prepare("SELECT id FROM signatures_stamps WHERE user_id = ? AND type = ?");
    $stmt->bind_param("is", $user_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    // Insert or update the record
    if ($existing) {
        $stmt = $conn->prepare("UPDATE signatures_stamps SET image = ?, file_name = ?, file_size = ? WHERE id = ?");
        $stmt->bind_param("ssii", $imageData, $file['name'], $file['size'], $existing['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO signatures_stamps (user_id, type, image, file_name, file_size) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $user_id, $type, $imageData, $file['name'], $file['size']);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to save $type to database.");
    }
    $stmt->close();
}

// Process uploads
$conn->begin_transaction();
try {
    $user_id = $_SESSION['user_id'];

    // Process stamp
    if (isset($_FILES['stamp']) && $_FILES['stamp']['error'] !== UPLOAD_ERR_NO_FILE) {
        processFile($_FILES['stamp'], 'stamp', $conn, $user_id);
    }

    // Process signature
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] !== UPLOAD_ERR_NO_FILE) {
        processFile($_FILES['signature'], 'signature', $conn, $user_id);
    }

    $conn->commit();
    $_SESSION['success_message'] = "Stamp and/or signature uploaded successfully.";
    logAction($conn, $user_id, "Uploaded stamp/signature", null, 'signature_stamp');
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
} finally {
    redirect('assessments.php');
}
?>