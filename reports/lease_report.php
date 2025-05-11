<?php
require_once '../config/database.php';

// Set session variables
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Error: Unable to connect to the database.");
}

// Fetch active leases
try {
    $leases_query = "
        SELECT al.id, al.asset_code, al.property_name, al.owner_name, al.property_type, 
               al.size, al.annual_lease_rate, al.status, al.registration_date
        FROM active_leases al
    ";
    $leases_stmt = $conn->prepare($leases_query);
    $leases_stmt->execute();
    $leases_result = $leases_stmt->get_result();
    $active_leases = $leases_result->fetch_all(MYSQLI_ASSOC);
    $leases_stmt->close();
} catch (Exception $e) {
    error_log("Error fetching active leases: " . $e->getMessage());
    $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ ንቁ የሊዝ ዝርዝሮችን መጫን አልተሳካም። ' . $e->getMessage() : 'Error: Failed to load active leases. ' . $e->getMessage();
    $active_leases = [];
}

// Fetch lease status counts
try {
    $status_query = "SELECT status, COUNT(*) as count FROM lease_agreements GROUP BY status";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    $lease_statuses = [];
    while ($row = $status_result->fetch_assoc()) {
        $lease_statuses[$row['status']] = $row['count'];
    }
    $status_stmt->close();
} catch (Exception $e) {
    error_log("Error fetching lease statuses: " . $e->getMessage());
    $lease_statuses = [];
}

// Fetch recent asset transfers
try {
    $transfers_query = "
        SELECT at.id, a.asset_code, o1.full_name AS old_owner_name, o2.full_name AS new_owner_name, 
               r.reason, at.transferred_at
        FROM asset_transfers at
        JOIN assets a ON at.asset_id = a.id
        JOIN owners o1 ON at.old_owner_id = o1.id
        JOIN owners o2 ON at.new_owner_id = o2.id
        JOIN reasons r ON at.reason_id = r.id
        ORDER BY at.transferred_at DESC
        LIMIT 5
    ";
    $transfers_stmt = $conn->prepare($transfers_query);
    $transfers_stmt->execute();
    $transfers_result = $transfers_stmt->get_result();
    $recent_transfers = $transfers_result->fetch_all(MYSQLI_ASSOC);
    $transfers_stmt->close();
} catch (Exception $e) {
    error_log("Error fetching recent transfers: " . $e->getMessage());
    $recent_transfers = [];
}

?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'የሊዝ ሪፖርት - ወራቤ መሬት ሊዝ' : 'Lease Report - Werabe Land Lease'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <?php if ($lang === 'am'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&display=swap">
        <style>
            body { font-family: 'Noto Sans Ethiopic', Arial, sans-serif; }
            .content-wrapper { text-align: right; }
            .table th, .table td { text-align: right; }
        </style>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="dashboard.php" class="brand-link">
            <img src="/dist/img/AdminLTELogo.png" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo $lang === 'am' ? 'ወራቤ መሬት ሊዝ' : 'Werabe Land Lease'; ?></span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="../dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p><?php echo $lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard'; ?></p>
                        </a>
                    </li>

                    <?php if (!$is_viewer): ?>
                        <!-- Manage Owners -->
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php']) && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ባለቤቶች' : 'Owners'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="owner/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ባለቤት ጨምር' : 'Add Owner'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="owner/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ባለቤቶች' : 'List Owners'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Manage Assets -->
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php', 'history.php', 'transfer.php']) && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ንብረቶች' : 'Assets'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="asset/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ንብረት ጨምር' : 'Add Asset'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="asset/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ንብረቶች' : 'List Assets'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="asset/history.php" class="nav-link <?php echo $current_page === 'history.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'የሽግግር ታሪክ' : 'Transfer History'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Manage Leases -->
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-file-contract"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ሊዞች' : 'Leases'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="lease/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ሊዝ ጨምር' : 'Add Lease'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="lease/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ሊዞች' : 'List Leases'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($role, ['admin', 'billing_officer', 'clerk'])): ?>
                        <!-- Manage Bills -->
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ቢሎች' : 'Bills'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="bills/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ቢል ጨምር' : 'Add Bill'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="bills/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ቢሎች' : 'List Bills'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Manage Payments -->
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-money-check-alt"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ክፍያዎች' : 'Payments'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="payments/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Payment'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="payments/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Payments'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Manage Charges -->
                        <li class="nav-item <?php echo in_array($current_page, ['add_charge.php', 'list_charges.php']) && str_contains($_SERVER['REQUEST_URI'], 'charges/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ክፍያዎች' : 'Charges'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="charges/add_charge.php" class="nav-link <?php echo $current_page === 'add_charge.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Charge'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="charges/list_charges.php" class="nav-link <?php echo $current_page === 'list_charges.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Charges'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Reports -->
                    <li class="nav-item <?php echo in_array($current_page, ['financial_report.php', 'lease_report.php']) ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>
                                <?php echo $lang === 'am' ? 'ሪፖርቶች' : 'Reports'; ?>
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="financial_report.php" class="nav-link <?php echo $current_page === 'financial_report.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'የፋይናንስ ሪፖርት' : 'Financial Report'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="lease_report.php" class="nav-link <?php echo $current_page === 'lease_report.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'የሊዝ ሪፖርት' : 'Lease Report'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <?php if ($role === 'admin'): ?>
                        <!-- Manage Users -->
                        <li class="nav-item <?php echo in_array($current_page, ['register.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], '/user') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-user-cog"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ተጠቃሚዎች' : 'Users'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="register.php" class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ተጠቃሚ ጨምር' : 'Add User'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="users/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'users/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ተጠቃሚዎች' : 'List Users'; ?></p>
                                    </a>
                                </li>
                            </ul>
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
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo $lang === 'am' ? 'የሊዝ ሪፖርት' : 'Lease Report'; ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo $lang === 'am' ? 'የሊዝ ሁኔታ' : 'Lease Status'; ?></h3>
                            </div>
                            <div class="card-body">
                                <p><?php echo $lang === 'am' ? 'ንቁ: ' : 'Active: '; ?><?php echo $lease_statuses['active'] ?? 0; ?></p>
                                <p><?php echo $lang === 'am' ? 'ተቋርጧል: ' : 'Terminated: '; ?><?php echo $lease_statuses['terminated'] ?? 0; ?></p>
                                <p><?php echo $lang === 'am' ? 'ጊዜው አልቋል: ' : 'Expired: '; ?><?php echo $lease_statuses['expired'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo $lang === 'am' ? 'ንቁ የሊዝ ብዛት' : 'Active Leases Count'; ?></h3>
                            </div>
                            <div class="card-body">
                                <p><?php echo count($active_leases); ?> <?php echo $lang === 'am' ? 'ሊዝ' : 'Leases'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Leases Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $lang === 'am' ? 'ንቁ የሊዝ ዝርዝሮች' : 'Active Leases'; ?></h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo $lang === 'am' ? 'ንብረት ኮድ' : 'Asset Code'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የንብረት ስም' : 'Property Name'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ባለቤት' : 'Owner'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'መጠን' : 'Size'; ?> (m²)</th>
                                    <th><?php echo $lang === 'am' ? 'ዓመታዊ የሊዝ መጠን' : 'Annual Lease Rate'; ?> (ETB)</th>
                                    <th><?php echo $lang === 'am' ? 'ሁኔታ' : 'Status'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_leases as $lease): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lease['asset_code']); ?></td>
                                        <td><?php echo htmlspecialchars($lease['property_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($lease['owner_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($lease['size'], 2); ?></td>
                                        <td><?php echo number_format($lease['annual_lease_rate'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($lease['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Transfers Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $lang === 'am' ? 'የቅርብ ጊዜ የንብረት ሽግግሮች' : 'Recent Asset Transfers'; ?></h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo $lang === 'am' ? 'ንብረት ኮድ' : 'Asset Code'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የቀድሞ ባለቤት' : 'Previous Owner'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'አዲስ ባለቤት' : 'New Owner'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ምክንያት' : 'Reason'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የተላለፈበት ቀን' : 'Transferred At'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transfers as $transfer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transfer['asset_code']); ?></td>
                                        <td><?php echo htmlspecialchars($transfer['old_owner_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($transfer['new_owner_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($transfer['reason']); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($transfer['transferred_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
</body>
</html>