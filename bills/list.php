<?php
session_start();
require_once '../config/Database.php';
require_once '../config/lang.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'billing_officer', 'clerk'])) {
    header("Location: /login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'];

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'am'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'en';
$translations = $lang === 'am' ? $translations['am'] : $translations['en'];

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(htmlspecialchars($translations['init_error'] ?? 'System initialization failed. Please contact the administrator.', ENT_QUOTES, 'UTF-8'));
}

// Set up filters
$filters = [];
$filter_params = [];
$filter_types = '';
$filter_query = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['status'])) {
        $filter_query .= " AND b.status = ?";
        $filter_params[] = $_GET['status'];
        $filter_types .= 's';
        $filters['status'] = $_GET['status'];
    }
    if (!empty($_GET['lease_id'])) {
        $filter_query .= " AND b.lease_id = ?";
        $filter_params[] = (int)$_GET['lease_id'];
        $filter_types .= 'i';
        $filters['lease_id'] = (int)$_GET['lease_id'];
    }
    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
        $filter_query .= " AND b.billing_date BETWEEN ? AND ?";
        $filter_params[] = $_GET['from_date'];
        $filter_params[] = $_GET['to_date'];
        $filter_types .= 'ss';
        $filters['date_range'] = [$_GET['from_date'], $_GET['to_date']];
    }
    if (!empty($_GET['search'])) {
        $filter_query .= " AND (b.bill_number LIKE ? OR l.lease_number LIKE ? OR o.full_name LIKE ?)";
        $search = '%' . $_GET['search'] . '%';
        $filter_params[] = $search;
        $filter_params[] = $search;
        $filter_params[] = $search;
        $filter_types .= 'sss';
        $filters['search'] = $_GET['search'];
    }
}

// Fetch bills
try {
    $query = "SELECT b.*, l.lease_number, l.owner_id, l.asset_id, a.lot_number, a.size, o.full_name AS owner_name
              FROM bills b
              JOIN lease_agreements l ON b.lease_id = l.id
              JOIN assets a ON l.asset_id = a.id
              LEFT JOIN owners o ON l.owner_id = o.id
              WHERE 1=1" . $filter_query . " ORDER BY b.billing_date DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed for bills query: " . $conn->error);
        throw new Exception("Database query preparation failed.");
    }

    if (!empty($filter_params)) {
        $stmt->bind_param($filter_types, ...$filter_params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $bills = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Log missing data
    foreach ($bills as $bill) {
        if (empty($bill['owner_id'])) {
            error_log("Bill ID {$bill['id']} has no associated owner_id in lease_agreements.");
        }
        if (empty($bill['asset_id'])) {
            error_log("Bill ID {$bill['id']} has no associated asset_id in lease_agreements.");
        }
        if (empty($bill['owner_name'])) {
            error_log("Bill ID {$bill['id']} has missing owner_name (owner_id: {$bill['owner_id']}).");
        }
        if (empty($bill['lot_number'])) {
            error_log("Bill ID {$bill['id']} has missing lot_number (asset_id: {$bill['asset_id']}).");
        }
    }
} catch (Exception $e) {
    $error = htmlspecialchars($translations['database_error'] ?? 'Database error: ' . $e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// Fetch active leases for filter dropdown
try {
    $query = "SELECT l.id, l.lease_number, a.property_name
              FROM lease_agreements l
              JOIN assets a ON l.asset_id = a.id
              WHERE l.status = 'active'
              ORDER BY l.start_date DESC";
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query failed for leases: " . $conn->error);
        throw new Exception("Database query failed for leases.");
    }
    $allLeases = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = htmlspecialchars($translations['database_error'] ?? 'Database error: ' . $e->getMessage(), ENT_QUOTES, 'UTF-8');
}

$statusOptions = [
    'pending' => $lang === 'am' ? 'በመጠባበቅ ላይ' : 'Pending',
    'paid' => $lang === 'am' ? 'የተከፈለ' : 'Paid',
    'overdue' => $lang === 'am' ? 'ያልተከፈለ' : 'Overdue',
    'cancelled' => $lang === 'am' ? 'ተሰርዟል' : 'Cancelled'
];

$pageTitle = $lang === 'am' ? 'የቢሎች ዝርዝር' : 'Bills List';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> - Werabe Land Lease</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <style>
        .filter-section { background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .bill-status { font-weight: bold; }
        .status-pending { color: #ffc107; }
        .status-paid { color: #28a745; }
        .status-overdue { color: #dc3545; }
        .status-cancelled { color: #6c757d; }
        @media (max-width: 768px) { .table-responsive { overflow-x: auto; } }
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
                    <i class="fas fa-globe"></i> <?php echo $lang === 'am' ? 'ቋንቋ' : 'Language'; ?>
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
                    <a href="/profile.php" class="dropdown-item"><i class="fas fa-user mr-2"></i> <?php echo $lang === 'am' ? 'መገለጫ' : 'Profile'; ?></a>
                    <div class="dropdown-divider"></div>
                    <a href="/logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt mr-2"></i> <?php echo $lang === 'am' ? 'ውጣ' : 'Logout'; ?></a>
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="/dashboard.php" class="brand-link">
            <img src="/dist/img/AdminLTELogo.png" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo $lang === 'am' ? 'ወራቤ መሬት ሊዝ' : 'Werabe Land Lease'; ?></span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p><?php echo $lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard'; ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/owner/list.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p><?php echo $lang === 'am' ? 'ባለቤቶች' : 'Owners'; ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/asset/list.php" class="nav-link">
                            <i class="nav-icon fas fa-building"></i>
                            <p><?php echo $lang === 'am' ? 'ንብረቶች' : 'Assets'; ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/lease/list.php" class="nav-link">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p><?php echo $lang === 'am' ? 'ሊዞች' : 'Leases'; ?></p>
                        </a>
                    </li>
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && strpos($_SERVER['REQUEST_URI'], 'bills/') !== false ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-file-invoice"></i>
                            <p><?php echo $lang === 'am' ? 'ቢሎች' : 'Bills'; ?><i class="fas fa-angle-left right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="/bills/add.php" class="nav-link <?php echo $current_page === 'add.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ቢል ጨምር' : 'Add Bill'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/bills/list.php" class="nav-link <?php echo $current_page === 'list.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ዝርዝር ቢሎች' : 'List Bills'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> <?php echo htmlspecialchars($lang === 'am' ? 'አዲስ ቢል' : 'New Bill', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="filter-section">
                    <form method="get" action="">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo htmlspecialchars($lang === 'am' ? 'ሁኔታ' : 'Status', ENT_QUOTES, 'UTF-8'); ?></label>
                                    <select name="status" class="form-control">
                                        <option value=""><?php echo htmlspecialchars($lang === 'am' ? 'ሁሉም' : 'All', ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php foreach ($statusOptions as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($_GET['status'] ?? '') === $value ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo htmlspecialchars($lang === 'am' ? 'ሊዝ' : 'Lease', ENT_QUOTES, 'UTF-8'); ?></label>
                                    <select name="lease_id" class="form-control">
                                        <option value=""><?php echo htmlspecialchars($lang === 'am' ? 'ሁሉም' : 'All', ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php foreach ($allLeases as $lease): ?>
                                            <option value="<?php echo htmlspecialchars($lease['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($_GET['lease_id'] ?? '') == $lease['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lease['lease_number'] . ' - ' . $lease['property_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo htmlspecialchars($lang === 'am' ? 'ከቀን' : 'From Date', ENT_QUOTES, 'UTF-8'); ?></label>
                                    <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($_GET['from_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo htmlspecialchars($lang === 'am' ? 'ለቀን' : 'To Date', ENT_QUOTES, 'UTF-8'); ?></label>
                                    <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($_GET['to_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label><?php echo htmlspecialchars($lang === 'am' ? 'ፈልግ' : 'Search', ENT_QUOTES, 'UTF-8'); ?></label>
                                    <input type="text" name="search" class="form-control" placeholder="<?php echo htmlspecialchars($lang === 'am' ? 'በቢል ቁጥር፣ በሊዝ ቁጥር ወይም በባለቤት ስም ይፈልጉ' : 'Search by bill number, lease number, or owner name', ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="col-md-3 text-right" style="padding-top: 30px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> <?php echo htmlspecialchars($lang === 'am' ? 'አጣራ' : 'Filter', ENT_QUOTES, 'UTF-8'); ?>
                                </button>
                                <a href="list.php" class="btn btn-default">
                                    <i class="fas fa-sync-alt"></i> <?php echo htmlspecialchars($lang === 'am' ? 'አጽዳ' : 'Clear', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                         <th><?php echo htmlspecialchars($lang === 'am' ? 'የባለቤት ስም' : 'Owner Name', ENT_QUOTES, 'UTF-8'); ?></th>
                                           <th><?php echo htmlspecialchars($lang === 'am' ? 'ሊዝ' : 'Lease', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th><?php echo htmlspecialchars($lang === 'am' ? 'ቢል #' : 'Bill #', ENT_QUOTES, 'UTF-8'); ?></th>
                                      
                                       
                                        <th><?php echo htmlspecialchars($lang === 'am' ? 'የሎት ቁጥር' : 'Plot Number', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th><?php echo htmlspecialchars($lang === 'am' ? 'መጠን' : 'area', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th><?php echo htmlspecialchars($lang === 'am' ? 'የቢል ቀን' : 'Billing Date', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th><?php echo htmlspecialchars($lang === 'am' ? 'የመጨረሻ ቀን' : 'Due Date', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th><?php echo htmlspecialchars($lang === 'am' ? 'ጠቅላላ' : 'Total', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th><?php echo htmlspecialchars($lang === 'am' ? 'ሁኔታ' : 'Status', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th><?php echo htmlspecialchars($lang === 'am' ? 'ድርጊቶች' : 'Actions', ENT_QUOTES, 'UTF-8'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($bills)): ?>
                                        <?php foreach ($bills as $bill): ?>
                                            <tr>
                                                 <td><?php echo htmlspecialchars($bill['owner_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                                  <td><?php echo htmlspecialchars($bill['lease_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($bill['bill_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                               
                                               
                                                <td><?php echo htmlspecialchars($bill['lot_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo $bill['size'] !== null ? number_format($bill['size'], 2) . ' m²' : 'N/A'; ?></td>
                                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($bill['billing_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="<?php echo (strtotime($bill['due_date']) < time() && $bill['status'] === 'pending') ? 'text-danger' : ''; ?>">
                                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($bill['due_date'])), ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo number_format($bill['total_amount'], 2); ?> ETB</td>
                                                <td>
                                                    <span class="bill-status status-<?php echo htmlspecialchars($bill['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo htmlspecialchars($statusOptions[$bill['status']] ?? $bill['status'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view.php?id=<?php echo htmlspecialchars($bill['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-info" title="<?php echo htmlspecialchars($lang === 'am' ? 'እይታ' : 'View', ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (in_array($_SESSION['role'], ['admin', 'billing_officer'])): ?>
                                                        <a href="edit.php?id=<?php echo htmlspecialchars($bill['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-primary" title="<?php echo htmlspecialchars($lang === 'am' ? 'አስተካክል' : 'Edit', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($_SESSION['role'] === 'admin' && $bill['status'] !== 'paid'): ?>
                                                        <a href="delete.php?id=<?php echo htmlspecialchars($bill['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-danger" title="<?php echo htmlspecialchars($lang === 'am' ? 'ሰርዝ' : 'Delete', ENT_QUOTES, 'UTF-8'); ?>" onclick="return confirm('<?php echo htmlspecialchars($lang === 'am' ? 'ይህን ቢል ለማስወግድ እርግጠኛ ነዎት?' : 'Are you sure you want to delete this bill?', ENT_QUOTES, 'UTF-8'); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center">
                                                <?php echo htmlspecialchars($lang === 'am' ? 'ምንም ቢሎች አልተገኙም' : 'No bills found', ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <b><?php echo htmlspecialchars($lang === 'am' ? 'ስሪት' : 'Version', ENT_QUOTES, 'UTF-8'); ?></b> 1.0.0
        </div>
        <strong><?php echo htmlspecialchars($lang === 'am' ? 'በ' : 'Copyright', ENT_QUOTES, 'UTF-8'); ?> © <?php echo date('Y'); ?> <a href="#">Werabe Land Lease</a>.</strong> 
        <?php echo htmlspecialchars($lang === 'am' ? 'ሁሉም መብቶች የተጠበቁ ናቸው' : 'All rights reserved', ENT_QUOTES, 'UTF-8'); ?>.
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    $('[title]').tooltip();
    $('.text-danger').closest('tr').addClass('bg-danger-light');
});
</script>
</body>
</html>