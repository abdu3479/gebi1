<?php
include '../includes/nav.php';
require_once '../config/database.php';

// Session variables for role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

// Detect current page for active link
$current_page = basename($_SERVER['PHP_SELF']);

$db = new Database();
$conn = $db->getConnection();

$filters = [];
$payments = [];
$errors = [];

// Handle filters from GET/POST
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters['status'] = $_REQUEST['status'] ?? '';
    $filters['search'] = trim($_REQUEST['search'] ?? '');
    $filters['date_range'] = isset($_REQUEST['date_range']) && is_array($_REQUEST['date_range']) ? $_REQUEST['date_range'] : [];

    // Sanitize filters
    if (empty($filters['status'])) unset($filters['status']);
    if (empty($filters['search'])) unset($filters['search']);
    if (empty($filters['date_range']) || count($filters['date_range']) !== 2) unset($filters['date_range']);

    try {
        // Build query
        $query = "SELECT p.id, p.lease_id, p.payment_date, p.amount, p.status, p.reference_number, la.lease_number 
                  FROM payments p 
                  LEFT JOIN lease_agreements la ON la.id = p.lease_id 
                  WHERE 1=1";
        $params = [];
        $types = '';

        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND p.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        if (!empty($filters['search'])) {
            $query .= " AND la.lease_number LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
            $types .= 's';
        }
        if (!empty($filters['date_range'])) {
            $query .= " AND p.payment_date BETWEEN ? AND ?";
            $params[] = $filters['date_range'][0];
            $params[] = $filters['date_range'][1];
            $types .= 'ss';
        }

        $query .= " ORDER BY p.payment_date DESC";

        // Execute query
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $errors[] = $lang === 'am' ? 'ስህተት ተከስቷል: ' . $e->getMessage() : 'An error occurred: ' . $e->getMessage();
        error_log("Error fetching payments: " . $e->getMessage());
    }
}

// Translations for bilingual support
$translations = [
    'list_payments' => ['en' => 'List Payments', 'am' => 'ዝርዝር ክፍያዎች'],
    'payment_date' => ['en' => 'Payment Date', 'am' => 'የክፍያ ቀን'],
    'lease_number' => ['en' => 'Lease Number', 'am' => 'የሊዝ ቁጥር'],
    'amount' => ['en' => 'Amount (ETB)', 'am' => 'መጠን (ብር)'],
    'status' => ['en' => 'Status', 'am' => 'ሁኔታ'],
    'reference_number' => ['en' => 'Reference Number', 'am' => 'የማጣቀሻ ቁጥር'],
    'actions' => ['en' => 'Actions', 'am' => 'ድርጊቶች'],
    'filter' => ['en' => 'Filter', 'am' => 'አጣራ'],
    'clear' => ['en' => 'Clear', 'am' => 'አጽዳ'],
    'search' => ['en' => 'Search', 'am' => 'ፈልግ'],
    'date_range' => ['en' => 'Date Range', 'am' => 'የቀን ክልል'],
    'no_payments' => ['en' => 'No payments found', 'am' => 'ምንም ክፍያዎች አልተገኙም'],
    'view_lease_details' => ['en' => 'View Lease Details', 'am' => 'የሊዝ ዝርዝሮችን ይመልከቱ']
];

$statusTranslations = [
    'completed' => ['en' => 'Completed', 'am' => 'ተጠናቋል'],
    'pending' => ['en' => 'Pending', 'am' => 'በመጠባበቅ ላይ']
];
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'ዝርዝር ክፍያዎች - ወራቤ መሬት ሊዝ' : 'List Payments - Werabe Land Lease'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <style>
        .table-responsive { overflow-x: auto; }
        @media (max-width: 768px) {
            .card-header .row { flex-direction: column; }
            .card-header .col-md-3 { margin-bottom: 1rem; }
        }
        .dropdown-action .btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .dropdown-action .dropdown-menu { min-width: 120px; }
        .badge { font-size: 0.9rem; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php
    $is_viewer = $role === 'viewer';
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="../dashboard.php" class="brand-link">
            <img src="../assets/images/logo.jpg" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo $lang === 'am' ? 'ወራቤ መሬት ሊዝ' : 'Werabe Land Lease'; ?></span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="../dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p><?php echo $lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard'; ?></p>
                        </a>
                    </li>
                    <?php if (!$is_viewer): ?>
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
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር �ዝዎች' : 'List Leases'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin', 'billing_officer', 'clerk'])): ?>
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
                                    <a href="add_payment.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Payment'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Payments'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
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
        </div>
    </aside>


    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo $translations['list_payments'][$lang]; ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-3 col-sm-12">
                                    <div class="form-group">
                                        <label for="status"><?php echo $translations['status'][$lang]; ?></label>
                                        <select class="form-control" id="status" name="status">
                                            <option value=""><?php echo $lang === 'am' ? 'ሁሉንም' : 'All'; ?></option>
                                            <option value="completed" <?php echo ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>
                                                <?php echo $statusTranslations['completed'][$lang]; ?>
                                            </option>
                                            <option value="pending" <?php echo ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>
                                                <?php echo $statusTranslations['pending'][$lang]; ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    <div class="form-group">
                                        <label for="search"><?php echo $translations['search'][$lang]; ?></label>
                                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo $lang === 'am' ? 'የሊዝ ቁጥር' : 'Lease Number'; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    <div class="form-group">
                                        <label><?php echo $translations['date_range'][$lang]; ?></label>
                                        <input type="date" class="form-control" name="date_range[]" value="<?php echo htmlspecialchars($filters['date_range'][0] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="date" class="form-control mt-2" name="date_range[]" value="<?php echo htmlspecialchars($filters['date_range'][1] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    <div class="form-group mt-4">
                                        <button type="submit" class="btn btn-primary"><?php echo $translations['filter'][$lang]; ?></button>
                                        <a href="list.php" class="btn btn-secondary"><?php echo $translations['clear'][$lang]; ?></a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th><?php echo $translations['payment_date'][$lang]; ?></th>
                                        <th><?php echo $translations['lease_number'][$lang]; ?></th>
                                        <th><?php echo $translations['amount'][$lang]; ?></th>
                                        <th><?php echo $translations['status'][$lang]; ?></th>
                                        <th><?php echo $translations['reference_number'][$lang]; ?></th>
                                        <th><?php echo $translations['actions'][$lang]; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payments)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center"><?php echo $translations['no_payments'][$lang]; ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['payment_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($payment['lease_number'] ?: ($lang === 'am' ? 'ያልታወቀ' : 'Unknown'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo number_format($payment['amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $payment['status'] === 'completed' ? 'badge-success' : 'badge-warning'; ?>">
                                                        <?php echo htmlspecialchars($statusTranslations[$payment['status']][$lang] ?? ucfirst($payment['status']), ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($payment['reference_number'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <div class="dropdown dropdown-action">
                                                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-cog"></i> <?php echo $translations['actions'][$lang]; ?>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a class="dropdown-item" href="../lease/view_detail.php?id=<?php echo $payment['lease_id']; ?>">
                                                                <i class="fas fa-eye"></i> <?php echo $translations['view_lease_details'][$lang]; ?>
                                                            </a>
                                                            <?php if (!$is_viewer): ?>
                                                                <!-- Placeholder for edit/delete actions -->
                                                                <!-- <a class="dropdown-item" href="edit.php?id=<?php echo $payment['id']; ?>">
                                                                    <i class="fas fa-edit"></i> <?php echo $lang === 'am' ? 'አስተካክል' : 'Edit'; ?>
                                                                </a>
                                                                <a class="dropdown-item" href="delete.php?id=<?php echo $payment['id']; ?>" onclick="return confirm('<?php echo $lang === 'am' ? 'እርግጠኛ ኖት ይህን ክፍያ መሰረዝ ይፈልጋሉ?' : 'Are you sure you want to delete this payment?'; ?>')">
                                                                    <i class="fas fa-trash"></i> <?php echo $lang === 'am' ? 'ሰርዝ' : 'Delete'; ?>
                                                                </a> -->
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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