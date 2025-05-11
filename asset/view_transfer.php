<?php
//session_start();
include '../includes/nav.php';
require_once '../config/database.php';
require_once '../classes/Asset.php';

// Validate session and language
$lang = isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'am']) ? $_SESSION['lang'] : 'en';
$role = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'editor', 'viewer', 'billing_officer', 'clerk']) ? $_SESSION['role'] : 'viewer';

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
    $assetObj = new Asset($conn);
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Error: Unable to connect to the database.");
}

// Validate transfer_id
if (!isset($_GET['transfer_id']) || !is_numeric($_GET['transfer_id'])) {
    $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ የሽግግር መለያ ተገቢ አይደለም።' : 'Error: Invalid transfer ID.';
    header("Location: history.php");
    exit;
}

$transfer_id = (int)$_GET['transfer_id'];

// Fetch transfer details
try {
    $query = "
        SELECT 
            at.id, a.asset_code, a.property_name, pt.name AS property_type,
            a.block, a.lot_number,
            o1.full_name AS old_owner_name, o1.photo AS old_owner_photo, o1.phone AS old_owner_phone,
            o2.full_name AS new_owner_name, o2.photo AS new_owner_photo, o2.phone AS new_owner_phone,
            r.reason, at.transferred_at, u.username
        FROM asset_transfers at
        JOIN assets a ON at.asset_id = a.id
        LEFT JOIN property_types pt ON a.property_type_id = pt.id
        LEFT JOIN owners o1 ON at.old_owner_id = o1.id
        LEFT JOIN owners o2 ON at.new_owner_id = o2.id
        LEFT JOIN reasons r ON at.reason_id = r.id
        LEFT JOIN users u ON at.transferred_by = u.id
        WHERE at.id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $transfer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transfer = $result->fetch_assoc();
    $stmt->close();

    if (!$transfer) {
        throw new Exception("Transfer not found.");
    }
} catch (Exception $e) {
    error_log("Error fetching transfer: " . $e->getMessage());
    $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ የሽግግር ዝርዝሮችን መጫን አልተሳካም።' : 'Error: Failed to load transfer details.';
    header("Location: history.php");
    exit;
}

define('DEFAULT_OWNER_PHOTO', 'Uploads/owners/no-photo.jpg');
?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'የሽግግር ዝርዝሮች - ወራቤ መሬት ሊዝ' : 'Transfer Details - Werabe Land Lease'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <?php if ($lang === 'am'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&display=swap">
        <style>
            body { font-family: 'Noto Sans Ethiopic', Arial, sans-serif; }
            .content-wrapper { text-align: left; }
            .owner-photo { max-width: 100px; height: auto; border-radius: 50%; }
        </style>
    <?php else: ?>
        <style>
            .owner-photo { max-width: 100px; height: auto; border-radius: 50%; }
        </style>
    <?php endif; ?>
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
                        <h1><?php echo $lang === 'am' ? 'የሽግግር ዝርዝሮች' : 'Transfer Details'; ?></h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="history.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> <?php echo $lang === 'am' ? 'ተመለስ' : 'Back'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $lang === 'am' ? 'የሽግግር መለያ #' . $transfer['id'] : 'Transfer ID #' . $transfer['id']; ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><?php echo $lang === 'am' ? 'ንብረት ኮድ' : 'Asset Code'; ?>:</strong> <?php echo htmlspecialchars($transfer['asset_code']); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'የንብረት ስም' : 'Property Name'; ?>:</strong> <?php echo htmlspecialchars($transfer['property_name'] ?? 'N/A'); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'የንብረት ዓይነት' : 'Property Type'; ?>:</strong> <?php echo htmlspecialchars($transfer['property_type'] ?? 'N/A'); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'ብሎክ' : 'Block'; ?>:</strong> <?php echo htmlspecialchars($transfer['block'] ?? 'N/A'); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'የሎት ቁጥር' : 'Lot Number'; ?>:</strong> <?php echo htmlspecialchars($transfer['lot_number'] ?? 'N/A'); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'ምክንያት' : 'Reason'; ?>:</strong> <?php echo htmlspecialchars($transfer['reason'] ?? 'N/A'); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'የተላለፈበት ቀን' : 'Transferred At'; ?>:</strong> <?php echo date('Y-m-d H:i:s', strtotime($transfer['transferred_at'])); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'ተጠቃሚ' : 'User'; ?>:</strong> <?php echo htmlspecialchars($transfer['username'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><?php echo $lang === 'am' ? 'የቀድሞ ባለቤት' : 'Previous Owner'; ?>:</strong> <?php echo htmlspecialchars($transfer['old_owner_name'] ?? 'N/A'); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'የቀድሞ ባለቤት ስልክ' : 'Previous Owner Phone'; ?>:</strong> <?php echo htmlspecialchars($transfer['old_owner_phone'] ?? 'N/A'); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'የቀድሞ ባለቤት ፎቶ' : 'Previous Owner Photo'; ?>:</strong></p>
                                <img src="../<?php echo $transfer['old_owner_photo'] ? htmlspecialchars($transfer['old_owner_photo']) : DEFAULT_OWNER_PHOTO; ?>" class="owner-photo" alt="Previous Owner">
                                <p><strong><?php echo $lang === 'am' ? 'አዲስ ባለቤት' : 'New Owner'; ?>:</strong> <?php echo htmlspecialchars($transfer['new_owner_name'] ?? 'N/A'); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'አዲስ ባለቤት ስልክ' : 'New Owner Phone'; ?>:</strong> <?php echo htmlspecialchars($transfer['new_owner_phone'] ?? 'N/A'); ?></p>
                                <p><strong><?php echo $lang === 'am' ? 'አዲስ ባለቤት ፎቶ' : 'New Owner Photo'; ?>:</strong></p>
                                <img src="../<?php echo $transfer['new_owner_photo'] ? htmlspecialchars($transfer['new_owner_photo']) : DEFAULT_OWNER_PHOTO; ?>" class="owner-photo" alt="New Owner">
                            </div>
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