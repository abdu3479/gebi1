<?php
session_start();
$username = $_SESSION['username'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';

// Handle language switch via GET
if (isset($_GET['lang'])) {
    $new_lang = in_array($_GET['lang'], ['en', 'am']) ? $_GET['lang'] : 'en';
    $_SESSION['lang'] = $new_lang;
    // Preserve other query parameters
    $query_params = $_GET;
    unset($query_params['lang']); // Remove lang to avoid duplication
    $redirect_url = $_SERVER['PHP_SELF'];
    if (!empty($query_params)) {
        $redirect_url .= '?' . http_build_query($query_params);
    }
    header('Location: ' . $redirect_url);
    exit;
}
?>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="/gebi1/dashboard.php" class="nav-link"><?php echo $lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard'; ?></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Language Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-globe"></i> <?php echo $lang === 'am' ? 'ቋንቋ' : 'Language'; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a href="?lang=en" class="dropdown-item">English</a>
                <a href="?lang=am" class="dropdown-item">አማርኛ</a>
            </div>
        </li>
        <!-- User Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a href="/gebi1/profile.php" class="dropdown-item">
                    <i class="fas fa-user-circle mr-2"></i> <?php echo $lang === 'am' ? 'መገለጫ' : 'Profile'; ?>
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item">
                    <i class="fas fa-sign-out-alt mr-2"></i> <?php echo $lang === 'am' ? 'ውጣ' : 'Logout'; ?>
                </a>
            </div>
        </li>
    </ul>
</nav>
<!-- /.navbar -->