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

$bill_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;
if (!$bill_id) {
    $error = htmlspecialchars($translations['invalid_bill_id'] ?? 'Invalid bill ID.', ENT_QUOTES, 'UTF-8');
} else {
    // Fetch bill details with LEFT JOIN for property_types to handle NULL property_type_id
    $query = "SELECT b.*, l.lease_number, l.owner_id, l.asset_id, l.yearly_rent_rate, l.lease_term_of_years,
                     a.kebele, a.size, a.block, a.lot_number, pt.name AS property_type_name
              FROM bills b
              JOIN lease_agreements l ON b.lease_id = l.id
              JOIN assets a ON l.asset_id = a.id
              LEFT JOIN property_types pt ON a.property_type_id = pt.id
              WHERE b.id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed for bill query: " . $conn->error);
        $error = htmlspecialchars($translations['database_error'] ?? 'Database error occurred.', ENT_QUOTES, 'UTF-8');
    } else {
        $stmt->bind_param('i', $bill_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $bill = $result->fetch_assoc();
        $stmt->close();

        if (!$bill) {
            $error = htmlspecialchars($translations['bill_not_found'] ?? 'Bill not found.', ENT_QUOTES, 'UTF-8');
        } else {
            // Log if asset_id or owner_id is NULL for debugging
            if (empty($bill['asset_id'])) {
                error_log("Bill ID $bill_id has no associated asset_id in lease_agreements.");
            }
            if (empty($bill['owner_id'])) {
                error_log("Bill ID $bill_id has no associated owner_id in lease_agreements.");
            }

            // Fetch owner details if owner_id exists
            $owner = null;
            if ($bill['owner_id']) {
                $query = "SELECT full_name, phone FROM owners WHERE id = ?";
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    error_log("Prepare failed for owner query: " . $conn->error);
                } else {
                    $stmt->bind_param('i', $bill['owner_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $owner = $result->fetch_assoc();
                    $stmt->close();
                    if (!$owner) {
                        error_log("No owner found for owner_id: {$bill['owner_id']} for bill ID $bill_id.");
                    }
                }
            }
        }
    }
}

if (isset($error)) {
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($translations['error'] ?? 'Error', ENT_QUOTES, 'UTF-8'); ?> - Werabe Land Lease</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    </head>
    <body class="hold-transition">
        <div class="container-fluid mt-5">
            <div class="alert alert-danger">
                <?php echo $error; ?>
                <br>
                <a href="list.php" class="btn btn-primary mt-2"><?php echo htmlspecialchars($translations['back_to_list'] ?? 'Back to Bills List', ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$owner_name = $owner && !empty($owner['full_name']) ? htmlspecialchars($owner['full_name'], ENT_QUOTES, 'UTF-8') : 'N/A';
$owner_phone = $owner && !empty($owner['phone']) ? htmlspecialchars($owner['phone'], ENT_QUOTES, 'UTF-8') : 'N/A';
$property_type_name = !empty($bill['property_type_name']) ? htmlspecialchars($bill['property_type_name'], ENT_QUOTES, 'UTF-8') : 'N/A';
$total_lease_rent = ($bill['yearly_rent_rate'] !== null && $bill['lease_term_of_years'] !== null)
    ? number_format($bill['yearly_rent_rate'] * $bill['lease_term_of_years'], 2) . ' ETB'
    : 'N/A';

$pageTitle = $lang === 'am' ? 'የቢል ዝርዝር' : 'Bill Details';
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
        .bill-header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .bill-details { margin-bottom: 30px; }
        .detail-row { margin-bottom: 10px; }
        .detail-label { font-weight: bold; min-width: 150px; display: inline-block; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-weight: bold; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-overdue { background-color: #f8d7da; color: #721c24; }
        .status-cancelled { background-color: #e2e3e5; color: #383d41; }
        @media print {
            .no-print { display: none; }
            body { font-size: 12pt; }
            .bill-details h4 { margin-top: 20px; }
        }
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
                    <a href="?lang=en&id=<?php echo $bill_id; ?>" class="dropdown-item">English</a>
                    <a href="?lang=am&id=<?php echo $bill_id; ?>" class="dropdown-item">አማርኛ</a>
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
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'view.php', 'edit.php']) ? 'menu-open' : ''; ?>">
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
                        <div class="btn-group no-print">
                            <a href="list.php" class="btn btn-default">
                                <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars($lang === 'am' ? 'ተመለስ' : 'Back', ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <?php if ($role !== 'clerk'): ?>
                                <a href="edit.php?id=<?php echo $bill_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> <?php echo htmlspecialchars($lang === 'am' ? 'አስተካክል' : 'Edit', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            <?php endif; ?>
                            <button onclick="window.print()" class="btn btn-success">
                                <i class="fas fa-print"></i> <?php echo htmlspecialchars($lang === 'am' ? 'ለትምህርት አትም' : 'Print', ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <div class="bill-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3><?php echo htmlspecialchars($lang === 'am' ? 'የቢል መረጃ' : 'Bill Information', ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ቢል #' : 'Bill #', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo htmlspecialchars($bill['bill_number'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ሁኔታ' : 'Status', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <span class="status-badge status-<?php echo htmlspecialchars($bill['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php 
                                            $statusLabels = [
                                                'pending' => $lang === 'am' ? 'በመጠባበቅ ላይ' : 'Pending',
                                                'paid' => $lang === 'am' ? 'የተከፈለ' : 'Paid',
                                                'overdue' => $lang === 'am' ? 'ያልተከፈለ' : 'Overdue',
                                                'cancelled' => $lang === 'am' ? 'ተሰርዟል' : 'Cancelled'
                                            ];
                                            echo htmlspecialchars($statusLabels[$bill['status']] ?? $bill['status'], ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ቀን' : 'Date', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo htmlspecialchars(date('M d, Y', strtotime($bill['billing_date'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'የመጨረሻ ቀን' : 'Due Date', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo htmlspecialchars(date('M d, Y', strtotime($bill['due_date'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="bill-details">
                                    <h4><?php echo htmlspecialchars($lang === 'am' ? 'የተከፋይ መረጃ' : 'Tenant Information', ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ሊዝ #' : 'Lease #', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo htmlspecialchars($bill['lease_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ቀበሌ' : 'Kebele', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo htmlspecialchars($bill['kebele'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'መጠን' : 'Size', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo $bill['size'] !== null ? number_format($bill['size'], 2) . ' m²' : 'N/A'; ?>
                                    </div>
                                    <?php if (!empty($bill['block']) || !empty($bill['lot_number'])): ?>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ቦታ' : 'Location', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php 
                                        $location = [];
                                        if (!empty($bill['block'])) $location[] = htmlspecialchars($bill['block'], ENT_QUOTES, 'UTF-8');
                                        if (!empty($bill['lot_number'])) $location[] = htmlspecialchars($bill['lot_number'], ENT_QUOTES, 'UTF-8');
                                        echo implode(', ', $location);
                                        ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'የባለቤት ስም' : 'Owner Name', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo $owner_name; ?>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'የባለቤት ስልክ' : 'Owner Phone', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo $owner_phone; ?>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'የንብረት አይነት' : 'Property Type', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo $property_type_name; ?>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ጠቅላላ የሊዝ ኪራይ' : 'Total Lease Rent', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo htmlspecialchars($total_lease_rent, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ዓመታዊ የሊዝ ኪራይ' : 'Annual Lease Rent', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo $bill['yearly_rent_rate'] !== null ? number_format($bill['yearly_rent_rate'], 2) . ' ETB' : 'N/A'; ?>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'የሊዝ ጊዜ' : 'Lease Term', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo $bill['lease_term_of_years'] !== null ? htmlspecialchars($bill['lease_term_of_years'], ENT_QUOTES, 'UTF-8') . ($lang === 'am' ? ' ዓመታት' : ' years') : 'N/A'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bill-details">
                                    <h4><?php echo htmlspecialchars($lang === 'am' ? 'የክፍያ መረጃ' : 'Payment Information', ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'መሠረታዊ መጠን' : 'Base Amount', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo number_format($bill['base_amount'], 2); ?> ETB
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ግብር' : 'Tax', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo number_format($bill['tax_amount'], 2); ?> ETB
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ተጨማሪ ክፍያዎች' : 'Additional Charges', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo number_format($bill['additional_charges'], 2); ?> ETB
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'የተቆጣጠር ክፍያ' : 'Late Fee', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <?php echo number_format($bill['late_fee_amount'], 2); ?> ETB
                                    </div>
                                    <div class="detail-row" style="font-size: 1.2em;">
                                        <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'ጠቅላላ መጠን' : 'Total Amount', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                        <strong><?php echo number_format($bill['total_amount'], 2); ?> ETB</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($bill['notes'])): ?>
                        <div class="bill-details">
                            <h4><?php echo htmlspecialchars($lang === 'am' ? 'ማስታወሻዎች' : 'Notes', ENT_QUOTES, 'UTF-8'); ?></h4>
                            <div class="well" style="padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
                                <?php echo nl2br(htmlspecialchars($bill['notes'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="no-print">
                            <hr>
                            <div class="detail-row">
                                <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'የተፈጠረው' : 'Created By', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                <?php echo htmlspecialchars($bill['creator_name'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'የተፈጠረበት ቀን' : 'Created At', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($bill['created_at'])), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label"><?php echo htmlspecialchars($lang === 'am' ? 'የተሻሻለበት ቀን' : 'Updated At', ENT_QUOTES, 'UTF-8'); ?>:</span>
                                <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($bill['updated_at'])), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer no-print">
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
});
</script>
</body>
</html>