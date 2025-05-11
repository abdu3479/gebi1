<?php
// Set session variables
session_start();
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';
$current_page = basename($_SERVER['PHP_SELF']);

// Restrict access to admin, billing_officer, clerk
if (!in_array($role, ['admin', 'billing_officer', 'clerk'])) {
    $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ ይህን ገፅ ለመድረስ ፍቃድ የለዎትም።' : 'Error: You do not have permission to access this page.';
    header('Location: ../reports/financial_report.php');
    exit;
}

// Initialize database connection
require_once '../config/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die($lang === 'am' ? 'ስህተት፡ የውሂብ ጎታ ግንኙነት አልተሳካም።' : 'Error: Unable to connect to the database.');
}

// Fetch unpaid/active bills with owner name, lease number, and lot number
$bills = [];
try {
    $bill_query = "
        SELECT b.id, b.bill_number, l.lease_number, b.total_amount, o.full_name AS owner_name, a.lot_number
        FROM bills b
        JOIN lease_agreements l ON b.lease_id = l.id
        JOIN owners o ON l.owner_id = o.id
        JOIN assets a ON l.asset_id = a.id
        WHERE b.status IN ('unpaid', 'pending')
        ORDER BY b.bill_number";
    $bill_stmt = $conn->prepare($bill_query);
    if (!$bill_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $bill_stmt->execute();
    $bill_result = $bill_stmt->get_result();
    while ($row = $bill_result->fetch_assoc()) {
        $bills[] = $row;
    }
    $bill_stmt->close();
    if (empty($bills)) {
        $_SESSION['warning'] = $lang === 'am' ? 'ማስጠንቀቂያ፡ ምንም ያልተከፈሉ ወይም በመጠባበቅ ላይ ያሉ ሂሳቦች የሉም።' : 'Warning: No unpaid or pending bills found.';
    }
} catch (Exception $e) {
    error_log("Error fetching bills: " . $e->getMessage());
    $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ የሂሳብ ዝርዝሮችን መጫን አልተሳካም። (' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ')' : 'Error: Failed to load bill details. (' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ')';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = (int)($_POST['bill_id'] ?? 0);
    $charge_type = $_POST['charge_type'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);
    $description = $_POST['description'] ?? '';

    if ($bill_id && $charge_type && $amount > 0) {
        try {
            $insert_query = "INSERT INTO charges (bill_id, charge_type, amount, description, created_at) VALUES (?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            if (!$insert_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $insert_stmt->bind_param('isds', $bill_id, $charge_type, $amount, $description);
            $insert_stmt->execute();
            $insert_stmt->close();
            $_SESSION['success'] = $lang === 'am' ? 'ክፍያ በተሳካ ሁኔታ ተጨምሯል።' : 'Charge added successfully.';
            header('Location: ../reports/financial_report.php');
            exit;
        } catch (Exception $e) {
            error_log("Error adding charge: " . $e->getMessage());
            $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ ክፍያ መጨመር አልተሳካም። (' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ')' : 'Error: Failed to add charge. (' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ')';
        }
    } else {
        $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ እባክዎ ሁሉንም አስፈላጊ መስኮች ይሙሉ።' : 'Error: Please fill all required fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'ክፍያ ጨምር - ወራቤ መሬት ሊዝ' : 'Add Charge - Werabe Land Lease'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <?php if ($lang === 'am'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&display=swap">
        <style>
            body { font-family: 'Noto Sans Ethiopic', Arial, sans-serif; }
            .content-wrapper { text-align: right; }
            .form-label { text-align: right; }
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
                                    <a href="../payments/add_payment.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
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
                        <h1><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Charge'; ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $lang === 'am' ? 'ክፍያ ለሂሳብ መጨመር' : 'Add Charge for Bill'; ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['warning'])): ?>
                            <div class="alert alert-warning"><?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="form-group">
                                <label for="bill_id"><?php echo $lang === 'am' ? 'ሂሳብ ይምረጡ' : 'Select Bill'; ?></label>
                                <select class="form-control" id="bill_id" name="bill_id" required <?php echo empty($bills) ? 'disabled' : ''; ?>>
                                    <option value=""><?php echo $lang === 'am' ? 'ሂሳብ ይምረጡ' : 'Select Bill'; ?></option>
                                    <?php foreach ($bills as $bill): ?>
                                        <option value="<?php echo $bill['id']; ?>">
                                            <?php echo htmlspecialchars("Bill: {$bill['bill_number']} - Lease: {$bill['lease_number']} - Lot: {$bill['lot_number']} - Owner: {$bill['owner_name']} (ETB {$bill['total_amount']})", ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="charge_type"><?php echo $lang === 'am' ? 'የክፍያ አይነት' : 'Charge Type'; ?></label>
                                <select class="form-control" id="charge_type" name="charge_type" required>
                                    <option value="penalty"><?php echo $lang === 'am' ? 'ቅጣት' : 'Penalty'; ?></p>
                                    <option value="service_fee"><?php echo $lang === 'am' ? 'የአገልግሎት ክፍያ' : 'Service Fee'; ?></p>
                                    <option value="other"><?php echo $lang === 'am' ? 'ሌላ' : 'Other'; ?></p>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="amount"><?php echo $lang === 'am' ? 'መጠን (ብር)' : 'Amount (ETB)'; ?></label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                            </div>
                            <div class="form-group">
                                <label for="description"><?php echo $lang === 'am' ? 'መግለጫ' : 'Description'; ?></label>
                                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" <?php echo empty($bills) ? 'disabled' : ''; ?>>
                                <?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Charge'; ?>
                            </button>
                        </form>
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