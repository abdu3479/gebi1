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

// Fetch all charges
try {
    $charges_query = "
        SELECT c.id, c.bill_id, b.bill_number, c.charge_type, c.amount, c.description, c.created_at
        FROM charges c
        JOIN bills b ON c.bill_id = b.id
        ORDER BY c.created_at DESC
    ";
    $charges_stmt = $conn->prepare($charges_query);
    $charges_stmt->execute();
    $charges_result = $charges_stmt->get_result();
    $charges = $charges_result->fetch_all(MYSQLI_ASSOC);
    $charges_stmt->close();
} catch (Exception $e) {
    error_log("Error fetching charges: " . $e->getMessage());
    $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ ክፍያዎችን መጫን አልተሳካም።' : 'Error: Failed to load charges.';
    $charges = [];
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && in_array($role, ['admin', 'billing_officer', 'clerk'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $delete_query = "DELETE FROM charges WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param('i', $delete_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        $_SESSION['success'] = $lang === 'am' ? 'ክፍያ በተሳካ ሁኔታ ተሰርዟል።' : 'Charge deleted successfully.';
        header('Location: list_charges.php');
        exit;
    } catch (Exception $e) {
        error_log("Error deleting charge: " . $e->getMessage());
        $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ ክፍያ መሰረዝ አልተሳካም።' : 'Error: Failed to delete charge.';
    }
}

?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'ዝርዝር ክፍያዎች - ወራቤ መሬት ሊዝ' : 'List Charges - Werabe Land Lease'; ?></title>
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
        <a href="../dashboard.php" class="brand-link">
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
                                    <a href="../owner/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ባለቤት ጨምር' : 'Add Owner'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../owner/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
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
                                    <a href="../asset/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ንብረት ጨምር' : 'Add Asset'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../asset/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ንብረቶች' : 'List Assets'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../asset/history.php" class="nav-link <?php echo $current_page === 'history.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
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
                                    <a href="../lease/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ሊዝ ጨምር' : 'Add Lease'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../lease/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
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
                                    <a href="../bills/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ቢል ጨምር' : 'Add Bill'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../bills/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
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
                                    <a href="../payments/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Payment'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../payments/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
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
                                    <a href="../charges/add_charge.php" class="nav-link <?php echo $current_page === 'add_charge.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Charge'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../charges/list_charges.php" class="nav-link <?php echo $current_page === 'list_charges.php' ? 'active' : ''; ?>">
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
                                <a href="../reports/financial_report.php" class="nav-link <?php echo $current_page === 'financial_report.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'የፋይናንስ ሪፖርት' : 'Financial Report'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../reports/lease_report.php" class="nav-link <?php echo $current_page === 'lease_report.php' ? 'active' : ''; ?>">
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
                                    <a href="../register.php" class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ተጠቃሚ ጨምር' : 'Add User'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../users/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'users/') ? 'active' : ''; ?>">
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
                        <h1><?php echo $lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Charges'; ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $lang === 'am' ? 'ሁሉም ክፍያዎች' : 'All Charges'; ?></h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo $lang === 'am' ? 'የሂሳብ ቁጥር' : 'Bill Number'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የክፍያ አይነት' : 'Charge Type'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'መጠን' : 'Amount'; ?> (ETB)</th>
                                    <th><?php echo $lang === 'am' ? 'መግለጫ' : 'Description'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የተፈጠረበት ቀን' : 'Created At'; ?></th>
                                    <?php if (in_array($role, ['admin', 'billing_officer', 'clerk'])): ?>
                                        <th><?php echo $lang === 'am' ? 'ተግባራት' : 'Actions'; ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($charges as $charge): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($charge['bill_number']); ?></td>
                                        <td><?php echo htmlspecialchars($charge['charge_type']); ?></td>
                                        <td><?php echo number_format($charge['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($charge['description'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($charge['created_at'])); ?></td>
                                        <?php if (in_array($role, ['admin', 'billing_officer', 'clerk'])): ?>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="delete_id" value="<?php echo $charge['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo $lang === 'am' ? 'ይህን ክፍያ መሰረዝ እርግጠኛ ነዎት?' : 'Are you sure you want to delete this charge?'; ?>')">
                                                        <i class="fas fa-trash"></i> <?php echo $lang === 'am' ? 'ሰርዝ' : 'Delete'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
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