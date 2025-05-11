<?php
// Start session
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
session_start();

// Debugging: Log session details
error_log("Session ID: " . session_id());

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Require dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/lang.php';
require_once __DIR__ . '/../classes/Lease.php';

// Ensure translations array is defined
if (!isset($translations) || !is_array($translations)) {
    $translations = ['en' => [], 'am' => []]; // Fallback
}

// Handle language selection
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'am'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$lang = $_SESSION['lang'] ?? 'en';

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize database and Lease class
try {
    $db = new Database();
    $conn = $db->getConnection();
    $lease = new Lease($conn);
} catch (Exception $e) {
    error_log("Initialization failed: " . $e->getMessage());
    die($translations[$lang]['init_error'] ?? 'System initialization failed. Please contact the administrator.');
}

// Fetch all leases
$leases = $lease->getAll();
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translations[$lang]['lease_list_title'] ?? 'Lease Agreements'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Abyssinica+SIL&display=swap" rel="stylesheet">
    <style>
        .amharic { font-family: 'Abyssinica SIL', sans-serif; }
        .table th, .table td { vertical-align: middle; }
        .card-header { background-color: #007bff; color: white; }
        .dropdown-menu { min-width: 100px; }
        .action-column { width: 120px; }
        /* Status background colors */
        .status-active { background-color: #d4edda; color: #155724; }
        .status-expired { background-color: #f8d7da; color: #721c24; }
        .status-terminated { background-color: #fff3cd; color: #856404; }
        .status-pending { background-color: #cce5ff; color: #004085; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="../profile.php" class="dropdown-item"><?php echo htmlspecialchars($translations[$lang]['profile'] ?? 'Profile'); ?></a>
                    <a href="../logout.php" class="dropdown-item"><?php echo htmlspecialchars($translations[$lang]['logout'] ?? 'Logout'); ?></a>
                </div>
            </li>
            <li class="nav-item">
                <a href="?lang=en" class="nav-link <?php echo $lang === 'en' ? 'active' : ''; ?>">EN</a>
            </li>
            <li class="nav-item">
                <a href="?lang=am" class="nav-link <?php echo $lang === 'am' ? 'active' : ''; ?>">አማ</a>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="../dashboard.php" class="brand-link">
            <img src="../assets/images/logo.jpg" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light amharic"><?php echo htmlspecialchars($translations[$lang]['app_name'] ?? 'Gebi System'); ?></span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="../assets/images/logo.jpg" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="../profile.php" class="d-block"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></a>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="../dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['dashboard'] ?? 'Dashboard'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../create_records_form.php" class="nav-link">
                            <i class="nav-icon fas fa-plus"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['create_records'] ?? 'Create Records'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="list.php" class="nav-link active">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['lease_agreements'] ?? 'Lease Agreements'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../owners/list.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['owners'] ?? 'Owners'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../assets/list.php" class="nav-link">
                            <i class="nav-icon fas fa-building"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['assets'] ?? 'Assets'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['logout'] ?? 'Logout'); ?></p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 amharic"><?php echo htmlspecialchars($translations[$lang]['lease_list_heading'] ?? 'Lease Agreements'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../dashboard.php"><?php echo htmlspecialchars($translations[$lang]['home'] ?? 'Home'); ?></a></li>
                            <li class="breadcrumb-item active amharic"><?php echo htmlspecialchars($translations[$lang]['lease_agreements'] ?? 'Lease Agreements'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title amharic"><?php echo htmlspecialchars($translations[$lang]['lease_list_title'] ?? 'List of Lease Agreements'); ?></h3>
                                <div class="card-tools">
                                    <a href="../create_records_form.php" class="btn btn-primary btn-sm amharic">
                                        <i class="fas fa-plus"></i> <?php echo htmlspecialchars($translations[$lang]['create_new'] ?? 'Create New'); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($leases)): ?>
                                    <div class="alert alert-info">
                                        <?php echo htmlspecialchars($translations[$lang]['no_leases'] ?? 'No lease agreements found.'); ?>
                                    </div>
                                <?php else: ?>
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['lease_number'] ?? 'Lease Number'); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['owner'] ?? 'Owner'); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['asset_code'] ?? 'Asset Code'); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['start_date'] ?? 'Start Date'); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['end_date'] ?? 'End Date'); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['yearly_rent_rate'] ?? 'Annual Rent'); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['payment_terms'] ?? 'Payment Terms'); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['status'] ?? 'Status'); ?></th>
                                                <th class="amharic"><?php echo htmlspecialchars($translations[$lang]['created_at'] ?? 'Created At'); ?></th>
                                                <th class="amharic action-column"><?php echo htmlspecialchars($translations[$lang]['actions'] ?? 'Actions'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($leases as $lease): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($lease['lease_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($lease['owner_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($lease['asset_code'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($lease['start_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($lease['end_date'] ?? 'N/A'); ?></td>
                                                    <td><?php echo number_format($lease['yearly_rent_rate'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($lease['payment_terms']); ?></td>
                                                    <td class="status-<?php echo strtolower($lease['status']); ?>">
                                                        <?php echo htmlspecialchars($translations[$lang]['status_' . strtolower($lease['status'])] ?? $lease['status']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($lease['created_at']); ?></td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenu_<?php echo $lease['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fas fa-cog"></i>
                                                            </button>
                                                            <div class="dropdown-menu" aria-labelledby="dropdownMenu_<?php echo $lease['id']; ?>">
                                                                <a class="dropdown-item amharic" href="view_detail.php?id=<?php echo $lease['id']; ?>">
                                                                    <i class="fas fa-eye"></i> <?php echo htmlspecialchars($translations[$lang]['view'] ?? 'View'); ?>
                                                                </a>
                                                                <a class="dropdown-item amharic" href="edit.php?id=<?php echo $lease['id']; ?>">
                                                                    <i class="fas fa-edit"></i> <?php echo htmlspecialchars($translations[$lang]['edit'] ?? 'Edit'); ?>
                                                                </a>
                                                                <form action="delete.php" method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars($translations[$lang]['confirm_delete'] ?? 'Are you sure you want to delete this lease?'); ?>');">
                                                                    <input type="hidden" name="id" value="<?php echo $lease['id']; ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                    <button type="submit" class="dropdown-item amharic text-danger">
                                                                        <i class="fas fa-trash"></i> <?php echo htmlspecialchars($translations[$lang]['delete'] ?? 'Delete'); ?>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong><?php echo htmlspecialchars($translations[$lang]['copyright'] ?? 'Copyright'); ?> © 2025 <a href="#">Gebi System</a>.</strong>
        <?php echo htmlspecialchars($translations[$lang]['all_rights_reserved'] ?? 'All rights reserved'); ?>. 
        <div class="float-right d-none d-sm-inline-block">
            <b><?php echo htmlspecialchars($translations[$lang]['version'] ?? 'Version'); ?></b> 1.0.0
        </div>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>