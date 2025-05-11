<?php
/**
 * Werabe Land Lease - Main Entry Point
 * 
 * Handles initial routing and system bootstrap
 */



// Load environment configuration
//require_once APP_PATH . '/config/config.php';

// Initialize database connection
require_once 'config/database.php';

// Check authentication status
if (isset($_SESSION['user_id'])) {
    // User is authenticated - redirect to dashboard
    header('Location: dashboard.php');
    exit();
} else {
    // User is not authenticated - redirect to login
    header('Location: login.php');
    exit();
}

// Fallback HTML if redirect fails
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werabe Land Lease System</title>
    <!-- Load minimal CSS for redirect page -->
    <style>
        .loading-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            background-color: #f8f9fa;
            z-index: 9999;
        }
        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .logo {
            height: 60px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <img src="<?= PUBLIC_PATH ?>/assets/img/werabe-logo.png" alt="Werabe Logo" class="logo">
        <div class="spinner"></div>
        <p>Loading Werabe Land Lease System...</p>
    </div>
    
    <script>
        // Client-side redirect fallback
        setTimeout(function() {
            window.location.href = "<?= PUBLIC_PATH ?>/login.php";
        }, 3000);
    </script>
</body>
</html>