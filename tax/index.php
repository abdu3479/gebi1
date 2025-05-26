<?php
session_start();
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Authentication
ensureAuthenticated();

// Initialize variables
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$isAdmin = isAdmin();
$isOfficer = isOfficer();

// Database Connection
try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Failed to connect to database");
    }
} catch (Exception $e) {
    logError($e);
    $_SESSION['error_messages'][] = translate('database_error');
    redirect('index.php');
}

// Fetch dashboard statistics
$stats = fetchDashboardStats($conn);

// HTML Template
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= translate('dashboard') ?> | Tax System</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE 3 (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <!-- DataTables (CDN) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed sidebar-collapse">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-dark">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="index.php" class="nav-link"><?= translate('home') ?></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> <?= translate('logout') ?>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-globe"></i> <?= strtoupper($lang) ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="?lang=en" class="dropdown-item">English</a>
                    <a href="?lang=fr" class="dropdown-item">amharic</a>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="index.php" class="brand-link">
            <span class="brand-text font-weight-light">Tax System</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p><?= translate('dashboard') ?></p>
                        </a>
                    </li>
                    <?php if ($isAdmin || $isOfficer): ?>
                        <li class="nav-item">
                            <a href="taxpayers.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p><?= translate('taxpayers') ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="vehicle_tax.php" class="nav-link">
                                <i class="nav-icon fas fa-car"></i>
                                <p><?= translate('vehicle_tax') ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="vehicle_types.php" class="nav-link">
                                <i class="nav-icon fas fa-list"></i>
                                <p><?= translate('vehicle_types') ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p><?= translate('reports') ?></p>
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a href="settings.php" class="nav-link">
                                <i class="nav-icon fas fa-cog"></i>
                                <p><?= translate('settings') ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?= translate('dashboard') ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?= translate('home') ?></a></li>
                            <li class="breadcrumb-item active"><?= translate('dashboard') ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <?php
                // Display success messages
                if (isset($_SESSION['success_messages']) && !empty($_SESSION['success_messages'])) {
                    foreach ($_SESSION['success_messages'] as $message) {
                        ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-check"></i> Success</h5>
                            <?= htmlspecialchars($message) ?>
                        </div>
                        <?php
                    }
                    unset($_SESSION['success_messages']);
                }

                // Display error messages
                if (isset($_SESSION['error_messages']) && !empty($_SESSION['error_messages'])) {
                    foreach ($_SESSION['error_messages'] as $message) {
                        ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Error</h5>
                            <?= htmlspecialchars($message) ?>
                        </div>
                        <?php
                    }
                    unset($_SESSION['error_messages']);
                }
                ?>
                
                <!-- Small Boxes (Stat cards) -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= htmlspecialchars($stats['total_taxpayers']) ?></h3>
                                <p><?= translate('total_taxpayers') ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <?php if ($isAdmin || $isOfficer): ?>
                                <a href="taxpayers.php" class="small-box-footer"><?= translate('more_info') ?> <i class="fas fa-arrow-circle-right"></i></a>
                            <?php else: ?>
                                <span class="small-box-footer"><?= translate('view_only') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?= htmlspecialchars($stats['total_vehicle_taxes']) ?></h3>
                                <p><?= translate('vehicle_taxes') ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-car"></i>
                            </div>
                            <?php if ($isAdmin || $isOfficer): ?>
                                <a href="vehicle_tax.php" class="small-box-footer"><?= translate('more_info') ?> <i class="fas fa-arrow-circle-right"></i></a>
                            <?php else: ?>
                                <span class="small-box-footer"><?= translate('view_only') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?= htmlspecialchars($stats['total_vehicle_types']) ?></h3>
                                <p><?= translate('vehicle_types') ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <?php if ($isAdmin || $isOfficer): ?>
                                <a href="vehicle_types.php" class="small-box-footer"><?= translate('more_info') ?> <i class="fas fa-arrow-circle-right"></i></a>
                            <?php else: ?>
                                <span class="small-box-footer"><?= translate('view_only') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?= htmlspecialchars($stats['pending_payments']) ?></h3>
                                <p><?= translate('pending_payments') ?></p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <?php if ($isAdmin || $isOfficer): ?>
                                <a href="vehicle_tax.php" class="small-box-footer"><?= translate('more_info') ?> <i class="fas fa-arrow-circle-right"></i></a>
                            <?php else: ?>
                                <span class="small-box-footer"><?= translate('view_only') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- /.row -->

                <!-- Optional: Recent Activity or Charts -->
                <?php if ($isAdmin || $isOfficer): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?= translate('recent_activity') ?></h3>
                            </div>
                            <div class="card-body">
                                <p><?= translate('recent_activity_placeholder') ?></p>
                                <!-- Add recent vehicle taxes or taxpayer actions here -->
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>Copyright © 2025 Tax System.</strong> All rights reserved.
    </footer>
</div>

<!-- jQuery (CDN) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<!-- DataTables (CDN) -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<!-- Custom Script -->
<script>
$(function () {
    // Initialize AdminLTE components
    $('[data-widget="pushmenu"]').PushMenu('init');
});
</script>
</body>
</html>

<?php
// Helper functions
function fetchDashboardStats($conn) {
    $stats = [
        'total_taxpayers' => 0,
        'total_vehicle_taxes' => 0,
        'total_vehicle_types' => 0,
        'pending_payments' => 0
    ];

    try {
        // Total Taxpayers
        $query = "SELECT COUNT(*) as count FROM taxpayers";
        $result = $conn->query($query);
        if ($result) {
            $stats['total_taxpayers'] = $result->fetch_assoc()['count'];
        }

        // Total Vehicle Taxes
        $query = "SELECT COUNT(*) as count FROM vehicle_taxes";
        $result = $conn->query($query);
        if ($result) {
            $stats['total_vehicle_taxes'] = $result->fetch_assoc()['count'];
        }

        // Total Vehicle Types
        $query = "SELECT COUNT(*) as count FROM vehicle_types";
        $result = $conn->query($query);
        if ($result) {
            $stats['total_vehicle_types'] = $result->fetch_assoc()['count'];
        }

        // Pending Payments
        $query = "SELECT COUNT(*) as count FROM vehicle_taxes WHERE payment_status = 'pending'";
        $result = $conn->query($query);
        if ($result) {
            $stats['pending_payments'] = $result->fetch_assoc()['count'];
        }
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('error_fetching_stats') . ': ' . $e->getMessage();
    }

    return $stats;
}
?>