<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['role'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'config/database.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $errors[] = "Username and password are required.";
    } else {
        // Prepare query to fetch user
        $stmt = $conn->prepare("SELECT id, username,password_hash, role FROM users WHERE username = ? AND is_active = 1");
        if ($stmt === false) {
            $errors[] = "Database error: Unable to prepare statement.";
            error_log("Prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['lang'] = $user['lang'] ?? 'en';
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Invalid username or password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($_SESSION['lang'] ?? 'en') === 'am' ? 'ግባ - ወራቤ መሬት ሊዝ' : 'Login - Werabe Land Lease'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="#"><b><?php echo ($_SESSION['lang'] ?? 'en') === 'am' ? 'ወራቤ መሬት ሊዝ' : 'PMT'; ?></b></a>
    </div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg"><?php echo ($_SESSION['lang'] ?? 'en') === 'am' ? 'ወደ ስርዓቱ ለመግባት ይግቡ' : 'Sign in to start your session'; ?></p>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="<?php echo ($_SESSION['lang'] ?? 'en') === 'am' ? 'የተጠቃሚ ስም' : 'Username'; ?>" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="<?php echo ($_SESSION['lang'] ?? 'en') === 'am' ? 'የይለፍ ቃል' : 'Password'; ?>" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block"><?php echo ($_SESSION['lang'] ?? 'en') === 'am' ? 'ግባ' : 'Sign In'; ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
</body>
</html>