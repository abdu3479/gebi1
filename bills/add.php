<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Bill.php';
require_once '../classes/Lease.php';
require_once '../classes/Owner.php';
require_once '../config/lang.php';

// Check user role and permissions
$allowed_roles = ['admin', 'billing_officer'];
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: /login.php");
    exit;
}

// Set default timezone
date_default_timezone_set('Africa/Addis_Ababa');

// Language handling
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'am'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'en';
$translations = $translations[$lang] ?? $translations['en'];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $billObj = new Bill($conn);
    $leaseObj = new Lease($conn);
    $ownerObj = new Owner($conn);
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(htmlspecialchars($translations['init_error'] ?? 'System initialization failed. Please contact the administrator.', ENT_QUOTES, 'UTF-8'));
}

$errors = [];
$success = '';
$owner_cache = [];

try {
    $leases = $leaseObj->getAll(['status' => 'active', 'limit' => 1000]);
} catch (Exception $e) {
    $errors[] = htmlspecialchars($translations['lease_fetch_error'] ?? 'Failed to load leases.', ENT_QUOTES, 'UTF-8');
    error_log("Lease fetch failed: " . $e->getMessage());
    $leases = [];
}

$bill_number = $billObj->generateBillNumber();
$default_billing_date = date('Y-m-d');
$default_due_date = date('Y-m-d', strtotime('+30 days'));

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception($translations['csrf_error'] ?? 'Invalid CSRF token');
        }

        $data = [
            'bill_number' => $bill_number,
            'lease_id' => filter_input(INPUT_POST, 'lease_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
            'billing_date' => filter_input(INPUT_POST, 'billing_date', FILTER_SANITIZE_STRING),
            'due_date' => filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING),
            'base_amount' => filter_input(INPUT_POST, 'base_amount', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.01]]),
            'tax_amount' => filter_input(INPUT_POST, 'tax_amount', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]),
            'additional_charges' => filter_input(INPUT_POST, 'additional_charges', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]),
            'notes' => filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING),
            'created_by' => $_SESSION['user_id']
        ];

        // Validate required fields
        if ($data['lease_id'] === false) {
            throw new Exception($translations['invalid_lease'] ?? 'Please select a valid lease');
        }
        if (empty($data['billing_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['billing_date'])) {
            throw new Exception($translations['invalid_billing_date'] ?? 'Invalid billing date');
        }
        if (empty($data['due_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
            throw new Exception($translations['invalid_due_date'] ?? 'Invalid due date');
        }
        if ($data['base_amount'] === false) {
            throw new Exception($translations['invalid_base_amount'] ?? 'Base amount must be greater than zero');
        }
        if ($data['tax_amount'] === false) {
            $data['tax_amount'] = 0;
        }
        if ($data['additional_charges'] === false) {
            $data['additional_charges'] = 0;
        }
        if (strtotime($data['due_date']) < strtotime($data['billing_date'])) {
            throw new Exception($translations['invalid_date_order'] ?? 'Due date cannot be before billing date');
        }

        // Set additional fields
        $data['total_amount'] = $data['base_amount'] + $data['tax_amount'] + $data['additional_charges'];
        $data['status'] = 'pending';
        $data['late_fee'] = 0;
        $data['late_fee_applied'] = 0;
        $data['late_fee_amount'] = 0;

        // Create the bill
        $result = $billObj->create($data);
        if (!$result['success']) {
            throw new Exception($translations['bill_creation_failed'] ?? 'Failed to create bill: ' . $result['message']);
        }

        // Find the selected lease for the success message
        $selected_lease = null;
        foreach ($leases as $lease) {
            if ($lease['id'] == $data['lease_id']) {
                $selected_lease = $lease;
                break;
            }
        }

        if ($selected_lease) {
            $success = sprintf(
                $translations['success_message'] ?? 'Owner: %s, Property: %s, Bill #%s created',
                htmlspecialchars($selected_lease['owner_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($selected_lease['property_name'] ?? $selected_lease['asset_code'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($data['bill_number'], ENT_QUOTES, 'UTF-8')
            );
        } else {
            $success = sprintf(
                $translations['success_message_generic'] ?? 'Bill #%s created successfully',
                htmlspecialchars($data['bill_number'], ENT_QUOTES, 'UTF-8')
            );
        }

        $bill_number = $billObj->generateBillNumber();
        $_POST = [];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $errors[] = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        error_log("Bill creation error: " . $e->getMessage());
        $billObj->logAudit('create_failed', null, [], $data ?? [], $_SESSION['user_id']);
    }
}

$role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translations['add_new_bill'] ?? 'Add New Bill', ENT_QUOTES, 'UTF-8'); ?> - Werabe Land Lease</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <style>
        .amount-input { max-width: 200px; }
        .form-section { margin-bottom: 20px; }
        .invalid-feedback { display: none; }
        .is-invalid ~ .invalid-feedback { display: block; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
     <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-globe"></i> <?php echo htmlspecialchars($translations['language'] ?? ($lang === 'am' ? 'ቋንቋ' : 'Language'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="?lang=en" class="dropdown-item">English</a>
                    <a href="?lang=am" class="dropdown-item">አማርኛ</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="/profile.php" class="dropdown-item"><i class="fas fa-user mr-2"></i> <?php echo htmlspecialchars($translations['profile'] ?? ($lang === 'am' ? 'መገለጫ' : 'Profile'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <div class="dropdown-divider"></div>
                    <a href="/logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt mr-2"></i> <?php echo htmlspecialchars($translations['logout'] ?? ($lang === 'am' ? 'ውጣ' : 'Logout'), ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="/dashboard.php" class="brand-link">
            <img src="/dist/img/AdminLTELogo.png" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo htmlspecialchars($translations['app_name'] ?? ($lang === 'am' ? 'ገቢ ስርዓት' : 'Gebi System'), ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p><?php echo htmlspecialchars($translations['dashboard'] ?? ($lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </a>
                    </li>
                    <?php if ($role !== 'viewer'): ?>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php']) && strpos($_SERVER['REQUEST_URI'], 'owner/') !== false ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p><?php echo htmlspecialchars($translations['owners'] ?? ($lang === 'am' ? 'ባለቤቶች' : 'Owners'), ENT_QUOTES, 'UTF-8'); ?><i class="fas fa-angle-left right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../owner/add.php" class="nav-link <?php echo $current_page === 'add.php' && strpos($_SERVER['REQUEST_URI'], 'owner/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['create_records'] ?? ($lang === 'am' ? 'መዝገቦችን ይፍጠሩ' : 'Create Records'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../owner/list.php" class="nav-link <?php echo $current_page === 'list.php' && strpos($_SERVER['REQUEST_URI'], 'owner/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['owners'] ?? ($lang === 'am' ? 'ባለቤቶች' : 'Owners'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php', 'history.php', 'transfer.php']) && strpos($_SERVER['REQUEST_URI'], 'asset/') !== false ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p><?php echo htmlspecialchars($translations['assets'] ?? ($lang === 'am' ? 'ንብረቶች' : 'Assets'), ENT_QUOTES, 'UTF-8'); ?><i class="fas fa-angle-left right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../asset/add.php" class="nav-link <?php echo $current_page === 'add.php' && strpos($_SERVER['REQUEST_URI'], 'asset/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['create_records'] ?? ($lang === 'am' ? 'መዝገቦችን ይፍጠሩ' : 'Create Records'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../asset/list.php" class="nav-link <?php echo $current_page === 'list.php' && strpos($_SERVER['REQUEST_URI'], 'asset/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['assets'] ?? ($lang === 'am' ? 'ንብረቶች' : 'Assets'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../asset/history.php" class="nav-link <?php echo $current_page === 'history.php' && strpos($_SERVER['REQUEST_URI'], 'asset/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['transfer_history'] ?? ($lang === 'am' ? 'የሽግግር ታሪክ' : 'Transfer History'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && strpos($_SERVER['REQUEST_URI'], 'lease/') !== false ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-file-contract"></i>
                                <p><?php echo htmlspecialchars($translations['lease_agreements'] ?? ($lang === 'am' ? 'የኪራይ ስምምነቶች' : 'Lease Agreements'), ENT_QUOTES, 'UTF-8'); ?><i class="fas fa-angle-left right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../lease/add.php" class="nav-link <?php echo $current_page === 'add.php' && strpos($_SERVER['REQUEST_URI'], 'lease/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['create_records'] ?? ($lang === 'am' ? 'መዝገቦችን ይፍጠሩ' : 'Create Records'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../lease/list.php" class="nav-link <?php echo $current_page === 'list.php' && strpos($_SERVER['REQUEST_URI'], 'lease/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['lease_agreements'] ?? ($lang === 'am' ? 'የኪራይ ስምምነቶች' : 'Lease Agreements'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin', 'billing_officer', 'clerk'])): ?>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && strpos($_SERVER['REQUEST_URI'], 'bills/') !== false ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p><?php echo htmlspecialchars($translations['bills'] ?? ($lang === 'am' ? 'ቢሎች' : 'Bills'), ENT_QUOTES, 'UTF-8'); ?><i class="fas fa-angle-left right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="add.php" class="nav-link <?php echo $current_page === 'add.php' && strpos($_SERVER['REQUEST_URI'], 'bills/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['add_new_bill'] ?? ($lang === 'am' ? 'አዲስ ቢል ያስገቡ' : 'Add New Bill'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="list.php" class="nav-link <?php echo $current_page === 'list.php' && strpos($_SERVER['REQUEST_URI'], 'bills/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['list_bills'] ?? ($lang === 'am' ? 'ዝርዝር ቢሎች' : 'List Bills'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && strpos($_SERVER['REQUEST_URI'], 'payments/') !== false ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-money-check-alt"></i>
                                <p><?php echo htmlspecialchars($translations['payments'] ?? ($lang === 'am' ? 'ክፍያዎች' : 'Payments'), ENT_QUOTES, 'UTF-8'); ?><i class="fas fa-angle-left right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../payments/add.php" class="nav-link <?php echo $current_page === 'add.php' && strpos($_SERVER['REQUEST_URI'], 'payments/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['add_payment'] ?? ($lang === 'am' ? 'ክፍያ ጨምር' : 'Add Payment'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../payments/list.php" class="nav-link <?php echo $current_page === 'list.php' && strpos($_SERVER['REQUEST_URI'], 'payments/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['list_payments'] ?? ($lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Payments'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add_charge.php', 'list_charges.php']) && strpos($_SERVER['REQUEST_URI'], 'charges/') !== false ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p><?php echo htmlspecialchars($translations['charges'] ?? ($lang === 'am' ? 'ክፍያዎች' : 'Charges'), ENT_QUOTES, 'UTF-8'); ?><i class="fas fa-angle-left right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../charges/add_charge.php" class="nav-link <?php echo $current_page === 'add_charge.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['add_charge'] ?? ($lang === 'am' ? 'ክፍያ ጨምር' : 'Add Charge'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../charges/list_charges.php" class="nav-link <?php echo $current_page === 'list_charges.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['list_charges'] ?? ($lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Charges'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item <?php echo in_array($current_page, ['financial_report.php', 'lease_report.php']) ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p><?php echo htmlspecialchars($translations['reports'] ?? ($lang === 'am' ? 'ሪፖርቶች' : 'Reports'), ENT_QUOTES, 'UTF-8'); ?><i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="/reports/financial_report.php" class="nav-link <?php echo $current_page === 'financial_report.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo htmlspecialchars($translations['financial_report'] ?? ($lang === 'am' ? 'የፋይናንስ ሪፖርት' : 'Financial Report'), ENT_QUOTES, 'UTF-8'); ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/reports/lease_report.php" class="nav-link <?php echo $current_page === 'lease_report.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo htmlspecialchars($translations['lease_report'] ?? ($lang === 'am' ? 'የሊዝ ሪፖርት' : 'Lease Report'), ENT_QUOTES, 'UTF-8'); ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item <?php echo in_array($current_page, ['register.php', 'list.php']) && strpos($_SERVER['REQUEST_URI'], '/user') !== false ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-user-cog"></i>
                                <p><?php echo htmlspecialchars($translations['users'] ?? ($lang === 'am' ? 'ተጠቃሚዎች' : 'Users'), ENT_QUOTES, 'UTF-8'); ?><i class="fas fa-angle-left right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="/register.php" class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['add_user'] ?? ($lang === 'am' ? 'ተጠቃሚ ጨምር' : 'Add User'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/users/list.php" class="nav-link <?php echo $current_page === 'list.php' && strpos($_SERVER['REQUEST_URI'], 'users/') !== false ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($translations['list_users'] ?? ($lang === 'am' ? 'ዝርዝር ተጠቃሚዎች' : 'List Users'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo htmlspecialchars($translations['add_new_bill'] ?? 'Add New Bill', ENT_QUOTES, 'UTF-8'); ?></h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <span class="badge badge-info"><?php echo htmlspecialchars($translations['bill_number'] ?? 'Bill #', ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($bill_number, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="billForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            
                            <div class="form-section">
                                <h5><?php echo htmlspecialchars($translations['bill_information'] ?? 'Bill Information', ENT_QUOTES, 'UTF-8'); ?></h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="lease_id"><?php echo htmlspecialchars($translations['lease'] ?? 'Lease *', ENT_QUOTES, 'UTF-8'); ?></label>
                                            <select name="lease_id" id="lease_id" class="form-control" required>
                                                <option value=""><?php echo htmlspecialchars($translations['select_lease'] ?? 'Select Lease', ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php foreach ($leases as $lease): ?>
                                                    <option value="<?php echo htmlspecialchars($lease['id'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                            data-monthly-rent="<?php echo htmlspecialchars($lease['monthly_rent'] ?? 0, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-annual-rent="<?php echo htmlspecialchars($lease['annual_rent'] ?? 0, ENT_QUOTES, 'UTF-8'); ?>"
                                                            <?php echo (isset($_POST['lease_id']) && $_POST['lease_id'] == $lease['id'] ? 'selected' : ''); ?>>
                                                        <?php echo htmlspecialchars(
                                                            sprintf(
                                                                "%s - %s (%s) - %s/month",
                                                                $lease['lease_number'] ?? 'N/A',
                                                                $lease['property_name'] ?? $lease['asset_code'] ?? 'Unknown Property',
                                                                $lease['owner_name'] ?? 'Unknown Owner',
                                                                number_format($lease['monthly_rent'] ?? 0, 2)
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><?php echo htmlspecialchars($translations['bill_number'] ?? 'Bill Number', ENT_QUOTES, 'UTF-8'); ?></label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($bill_number, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><?php echo htmlspecialchars($translations['dates'] ?? 'Dates', ENT_QUOTES, 'UTF-8'); ?></h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="billing_date"><?php echo htmlspecialchars($translations['billing_date'] ?? 'Billing Date *', ENT_QUOTES, 'UTF-8'); ?></label>
                                            <input type="date" class="form-control" id="billing_date" name="billing_date" 
                                                   value="<?php echo htmlspecialchars($_POST['billing_date'] ?? $default_billing_date, ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="due_date"><?php echo htmlspecialchars($translations['due_date'] ?? 'Due Date *', ENT_QUOTES, 'UTF-8'); ?></label>
                                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                                   value="<?php echo htmlspecialchars($_POST['due_date'] ?? $default_due_date, ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><?php echo htmlspecialchars($translations['amounts_etb'] ?? 'Amounts (ETB)', ENT_QUOTES, 'UTF-8'); ?></h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="base_amount"><?php echo htmlspecialchars($translations['base_amount'] ?? 'Base Amount *', ENT_QUOTES, 'UTF-8'); ?></label>
                                            <input type="number" step="0.01" class="form-control amount-input" id="base_amount" 
                                                   name="base_amount" value="<?php echo htmlspecialchars($_POST['base_amount'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="tax_amount"><?php echo htmlspecialchars($translations['tax_amount'] ?? 'Tax Amount', ENT_QUOTES, 'UTF-8'); ?></label>
                                            <input type="number" step="0.01" class="form-control amount-input" id="tax_amount" 
                                                   name="tax_amount" value="<?php echo htmlspecialchars($_POST['tax_amount'] ?? '0.00', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="additional_charges"><?php echo htmlspecialchars($translations['additional_charges'] ?? 'Additional Charges', ENT_QUOTES, 'UTF-8'); ?></label>
                                            <input type="number" step="0.01" class="form-control amount-input" id="additional_charges" 
                                                   name="additional_charges" value="<?php echo htmlspecialchars($_POST['additional_charges'] ?? '0.00', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    </div>
                                    <input type="hidden" name="late_fee" id="late_fee" value="0.00">
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label><?php echo htmlspecialchars($translations['total_amount'] ?? 'Total Amount', ENT_QUOTES, 'UTF-8'); ?></label>
                                            <input type="text" class="form-control amount-input" id="total_amount" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><?php echo htmlspecialchars($translations['additional_information'] ?? 'Additional Information', ENT_QUOTES, 'UTF-8'); ?></h5>
                                <div class="form-group">
                                    <label for="notes"><?php echo htmlspecialchars($translations['notes'] ?? 'Notes', ENT_QUOTES, 'UTF-8'); ?></label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo htmlspecialchars($translations['submit'] ?? 'Create Bill', ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer (unchanged from previous response) -->
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    // Auto-populate base amount when lease is selected
    $('#lease_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val() === "") {
            $('#base_amount').val('');
            calculateTotal();
            return;
        }
        
        const annualRent = parseFloat(selectedOption.data('annual-rent')) || 0;
        if (annualRent > 0) {
            $('#base_amount').val(annualRent.toFixed(2));
            calculateTotal();
        }
    });

    // Populate base amount on page load if lease is pre-selected
    <?php if (isset($_POST['lease_id']) && $_POST['lease_id']): ?>
        const selectedLeaseId = <?php echo (int)($_POST['lease_id'] ?? 0); ?>;
        if (selectedLeaseId > 0) {
            const selectedOption = $('#lease_id').find('option[value="' + selectedLeaseId + '"]');
            const annualRent = parseFloat(selectedOption.data('annual-rent')) || 0;
            if (annualRent > 0 && !$('#base_amount').val()) {
                $('#base_amount').val(annualRent.toFixed(2));
            }
            calculateTotal();
        }
    <?php endif; ?>

    // Calculate total amount
    function calculateTotal() {
        const base = parseFloat($('#base_amount').val()) || 0;
        const tax = parseFloat($('#tax_amount').val()) || 0;
        const additional = parseFloat($('#additional_charges').val()) || 0;
        const total = base + tax + additional;
        $('#total_amount').val(total.toFixed(2));
    }

    // Update total amount when amounts change
    $('#base_amount, #tax_amount, #additional_charges').on('input', calculateTotal);

    // Initial calculation
    calculateTotal();

    // Form validation
    $('#billForm').on('submit', function(e) {
        const lang = '<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>';
        const messages = {
            en: {
                lease: 'Please select a lease',
                baseAmount: 'Base amount must be a positive number',
                date: 'Due date cannot be before billing date',
                invalidDate: 'Invalid date format'
            },
            am: {
                lease: 'እባክዎ ሊዝ ይምረጡ',
                baseAmount: 'መሠረታዊ መጠን አዎንታዊ ቁጥር መሆን አለበት',
                date: 'የመጨረሻ ቀን ከቢል ቀን በፊት ሊሆን አይችልም',
                invalidDate: 'የተሳሳተ የቀን ቅርጸት'
            }
        };

        function showError(field, message) {
            $(`#${field}`).addClass('is-invalid');
            $(`#${field}`).after(`<div class="invalid-feedback">${message}</div>`);
        }

        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        const leaseId = $('#lease_id').val();
        const baseAmount = parseFloat($('#base_amount').val());
        const billingDate = $('#billing_date').val();
        const dueDate = $('#due_date').val();

        if (!leaseId) {
            showError('lease_id', messages[lang].lease);
            e.preventDefault();
            return false;
        }
        if (isNaN(baseAmount) || baseAmount <= 0) {
            showError('base_amount', messages[lang].baseAmount);
            e.preventDefault();
            return false;
        }
        if (!billingDate || !dueDate) {
            showError(billingDate ? 'due_date' : 'billing_date', messages[lang].invalidDate);
            e.preventDefault();
            return false;
        }
        const billingDateObj = new Date(billingDate);
        const dueDateObj = new Date(dueDate);
        if (dueDateObj < billingDateObj) {
            showError('due_date', messages[lang].date);
            e.preventDefault();
            return false;
        }
    });
});
</script>
</body>
</html>