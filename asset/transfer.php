<?php
require_once '../config/database.php';

//session_start(); // Uncomment if not started in nav.php
$lang = $_SESSION['lang'] ?? 'en';
$role = $_SESSION['role'] ?? 'viewer';
$current_page = basename($_SERVER['PHP_SELF']);
$is_viewer = $role === 'viewer';

// Redirect viewers to dashboard
if (!$is_viewer) {
    header("Location: ../dashboard.php");
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(htmlspecialchars($lang === 'am' ? 'ስርዓቱ ማስጀመር አልተሳካም። እባክዎ አስተዳዳሪውን ያነጋግሩ።' : 'System initialization failed. Please contact the administrator.', ENT_QUOTES, 'UTF-8'));
}

$errors = [];
$success = '';
$asset = null;
$transfer_history = [];
$owners = [];
$reasons = [];

// Validate asset ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errors[] = $lang === 'am' ? 'የተሳሳተ የንብረት መለያ ቁጥር' : 'Invalid asset ID';
} else {
    // Fetch asset details
    try {
        $asset_id = (int)$_GET['id'];
        $query = "SELECT a.id, a.asset_code, a.property_name, a.status, 
                         o.full_name AS owner_name
                  FROM assets a
                  LEFT JOIN lease_agreements l ON a.id = l.asset_id AND l.status = 'active'
                  LEFT JOIN owners o ON l.owner_id = o.id
                  WHERE a.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $asset_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $asset = $result->fetch_assoc();
        $stmt->close();

        if (!$asset) {
            $errors[] = $lang === 'am' ? 'ንብረት አልተገኘም' : 'Asset not found';
        } else {
            // Log missing owner_name
            if (empty($asset['owner_name']) && !empty($asset['asset_code'])) {
                error_log("Asset ID {$asset['id']} (Code: {$asset['asset_code']}) has missing owner_name.");
            }

            // Fetch transfer history
            $query = "SELECT t.transferred_at, 
                             old_o.full_name AS old_owner_name, 
                             new_o.full_name AS new_owner_name, 
                             tr.reason AS reason_text, 
                             u.username AS transferred_by
                      FROM asset_transfers t
                      LEFT JOIN owners old_o ON t.old_owner_id = old_o.id
                      LEFT JOIN owners new_o ON t.new_owner_id = new_o.id
                      LEFT JOIN reasons tr ON t.reason_id = tr.id
                      LEFT JOIN users u ON t.transferred_by = u.id
                      WHERE t.asset_id = ?
                      ORDER BY t.transferred_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $asset_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $transfer_history = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } catch (Exception $e) {
        $errors[] = htmlspecialchars($lang === 'am' ? 'የውሂብ ማከማቻ ስህተት: ' . $e->getMessage() : 'Database error: ' . $e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Fetch owners
try {
    $query = "SELECT id, full_name FROM owners WHERE status = 'active' ORDER BY full_name";
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query failed for owners: " . $conn->error);
        throw new Exception("Database query failed for owners.");
    }
    $owners = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $errors[] = htmlspecialchars($lang === 'am' ? 'የባለቤቶች ዝርዝር መጫን አልተሳካም: ' . $e->getMessage() : 'Failed to load owners: ' . $e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// Fetch transfer reasons
try {
    $query = "SELECT id, reason FROM reasons ORDER BY reason";
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query failed for transfer reasons: " . $conn->error);
        throw new Exception("Database query failed for transfer reasons.");
    }
    $reasons = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $errors[] = htmlspecialchars($lang === 'am' ? 'የዝውውር ምክንያቶች መጫን አልተሳካም: ' . $e->getMessage() : 'Failed to load transfer reasons: ' . $e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $new_owner_id = !empty($_POST['new_owner_id']) ? (int)$_POST['new_owner_id'] : null;
    $reason_id = !empty($_POST['reason_id']) ? (int)$_POST['reason_id'] : null;
    $user_id = $_SESSION['user_id'] ?? 0;

    if (empty($new_owner_id)) {
        $errors[] = $lang === 'am' ? 'እባክዎ አዲስ ባለቤት ይምረጡ' : 'Please select a new owner';
    } elseif (empty($reason_id)) {
        $errors[] = $lang === 'am' ? 'እባክዎ የዝውውር ምክንያት ይምረጡ' : 'Please select a transfer reason';
    } elseif ($asset) {
        $conn->begin_transaction();
        try {
            // Get current owner_id
            $query = "SELECT owner_id FROM lease_agreements WHERE asset_id = ? AND status = 'active'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $asset['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_lease = $result->fetch_assoc();
            $old_owner_id = $current_lease ? $current_lease['owner_id'] : null;
            $stmt->close();

            if ($new_owner_id == $old_owner_id) {
                throw new Exception($lang === 'am' ? 'አዲሱ ባለቤት ከአሁኑ ባለቤት ጋር አንድ ነው' : 'New owner is the same as the current owner');
            }

            // Update lease_agreements
            if ($old_owner_id) {
                $query = "UPDATE lease_agreements SET owner_id = ?, updated_at = NOW(), updated_by = ? WHERE asset_id = ? AND status = 'active'";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $new_owner_id, $user_id, $asset['id']);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update lease agreement: " . $stmt->error);
                }
                $stmt->close();
            } else {
                // Create new lease if none exists
                $query = "INSERT INTO lease_agreements (asset_id, owner_id, lease_number, start_date, monthly_rent, yearly_rent_rate, lease_term_of_years, status, created_by, updated_by)
                          VALUES (?, ?, CONCAT('LEASE-', ?), NOW(), 0, 0, 5, 'active', ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iisii', $asset['id'], $new_owner_id, $asset['id'], $user_id, $user_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create lease agreement: " . $stmt->error);
                }
                $stmt->close();
            }

            // Log transfer in asset_transfers
            $query = "INSERT INTO asset_transfers (asset_id, old_owner_id, new_owner_id, reason_id, transferred_by, transferred_at)
                      VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iiiii', $asset['id'], $old_owner_id, $new_owner_id, $reason_id, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to log transfer: " . $stmt->error);
            }
            $transfer_id = $conn->insert_id;
            $stmt->close();

            $conn->commit();
            $success = $lang === 'am' ? 'ንብረት በተሳካ ሁኔታ ተላልፏል' : 'Asset transferred successfully';

            // Refresh asset and transfer history
            $query = "SELECT a.id, a.asset_code, a.property_name, a.status, 
                             o.full_name AS owner_name
                      FROM assets a
                      LEFT JOIN lease_agreements l ON a.id = l.asset_id AND l.status = 'active'
                      LEFT JOIN owners o ON l.owner_id = o.id
                      WHERE a.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $asset['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $asset = $result->fetch_assoc();
            $stmt->close();

            $query = "SELECT t.transferred_at, 
                             old_o.full_name AS old_owner_name, 
                             new_o.full_name AS new_owner_name, 
                             tr.reason AS reason_text, 
                             u.username AS transferred_by
                      FROM asset_transfers t
                      LEFT JOIN owners old_o ON t.old_owner_id = old_o.id
                      LEFT JOIN owners new_o ON t.new_owner_id = new_o.id
                      LEFT JOIN transfer_reasons tr ON t.reason_id = tr.id
                      LEFT JOIN users u ON t.transferred_by = u.id
                      WHERE t.asset_id = ?
                      ORDER BY t.transferred_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $asset['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $transfer_history = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = htmlspecialchars($lang === 'am' ? 'የዝውውር ስህተት: ' . $e->getMessage() : 'Transfer error: ' . $e->getMessage(), ENT_QUOTES, 'UTF-8');
            error_log("Transfer failed for asset ID {$asset['id']}: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang === 'am' ? 'am' : 'en', ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lang === 'am' ? 'ንብረት ማስተላለፍ - ወራቤ መሬት ሊዝ' : 'Transfer Asset - Werabe Land Lease', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <?php if ($lang === 'am'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&display=swap">
        <style>
            body { font-family: 'Noto Sans Ethiopic', Arial, sans-serif; }
            .content-wrapper, .card, form { text-align: right; }
            .form-group label { float: right; width: 100%; }
            .form-control, .btn { direction: rtl; }
            .table th, .table td { text-align: right; }
        </style>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="../dashboard.php" class="brand-link">
            <img src="/dist/img/AdminLTELogo.png" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo htmlspecialchars($lang === 'am' ? 'ወራቤ መሬት ሊዝ' : 'Werabe Land Lease', ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="../dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p><?php echo htmlspecialchars($lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard', ENT_QUOTES, 'UTF-8'); ?></p>
                        </a>
                    </li>
                    <?php if (!$is_viewer): ?>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php']) && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    <?php echo htmlspecialchars($lang === 'am' ? 'ባለቤቶች' : 'Owners', ENT_QUOTES, 'UTF-8'); ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../owner/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ባለቤት ጨምር' : 'Add Owner', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../owner/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ዝርዝር ባለቤቶች' : 'List Owners', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php', 'history.php', 'transfer.php']) && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p>
                                    <?php echo htmlspecialchars($lang === 'am' ? 'ንብረቶች' : 'Assets', ENT_QUOTES, 'UTF-8'); ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../asset/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ንብረት ጨምር' : 'Add Asset', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../asset/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ዝርዝር ንብረቶች' : 'List Assets', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../asset/history.php" class="nav-link <?php echo $current_page === 'history.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'የሽግግር ታሪክ' : 'Transfer History', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../asset/transfer.php" class="nav-link <?php echo $current_page === 'transfer.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ንብረት ሽግግር' : 'Transfer Asset', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-file-contract"></i>
                                <p>
                                    <?php echo htmlspecialchars($lang === 'am' ? 'ሊዞች' : 'Leases', ENT_QUOTES, 'UTF-8'); ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../lease/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ሊዝ ጨምር' : 'Add Lease', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../lease/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ዝርዝር ሊዞች' : 'List Leases', ENT_QUOTES, 'UTF-8'); ?></p>
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
                                    <?php echo htmlspecialchars($lang === 'am' ? 'ቢሎች' : 'Bills', ENT_QUOTES, 'UTF-8'); ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../bills/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ቢል ጨምር' : 'Add Bill', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../bills/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ዝርዝር ቢሎች' : 'List Bills', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-money-check-alt"></i>
                                <p>
                                    <?php echo htmlspecialchars($lang === 'am' ? 'ክፍያዎች' : 'Payments', ENT_QUOTES, 'UTF-8'); ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../payments/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ክፍያ ጨምር' : 'Add Payment', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../payments/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Payments', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add_charge.php', 'list_charges.php']) && str_contains($_SERVER['REQUEST_URI'], 'charges/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>
                                    <?php echo htmlspecialchars($lang === 'am' ? 'ክፍያዎች' : 'Charges', ENT_QUOTES, 'UTF-8'); ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../charges/add_charge.php" class="nav-link <?php echo $current_page === 'add_charge.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ክፍያ ጨምር' : 'Add Charge', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../charges/list_charges.php" class="nav-link <?php echo $current_page === 'list_charges.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Charges', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item <?php echo in_array($current_page, ['financial_report.php', 'lease_report.php']) ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>
                                <?php echo htmlspecialchars($lang === 'am' ? 'ሪፖርቶች' : 'Reports', ENT_QUOTES, 'UTF-8'); ?>
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="../reports/financial_report.php" class="nav-link <?php echo $current_page === 'financial_report.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo htmlspecialchars($lang === 'am' ? 'የፋይናንስ ሪፖርት' : 'Financial Report', ENT_QUOTES, 'UTF-8'); ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../reports/lease_report.php" class="nav-link <?php echo $current_page === 'lease_report.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo htmlspecialchars($lang === 'am' ? 'የሊዝ ሪፖርት' : 'Lease Report', ENT_QUOTES, 'UTF-8'); ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item <?php echo in_array($current_page, ['register.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], '/user') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-user-cog"></i>
                                <p>
                                    <?php echo htmlspecialchars($lang === 'am' ? 'ተጠቃሚዎች' : 'Users', ENT_QUOTES, 'UTF-8'); ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../register.php" class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ተጠቃሚ ጨምር' : 'Add User', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../users/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'users/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo htmlspecialchars($lang === 'am' ? 'ዝርዝር ተጠቃሚዎች' : 'List Users', ENT_QUOTES, 'UTF-8'); ?></p>
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
                        <h1><?php echo htmlspecialchars($lang === 'am' ? 'ንብረት ማስተላለፍ' : 'Transfer Asset', ENT_QUOTES, 'UTF-8'); ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php if ($asset): ?>
                            <h4><?php echo htmlspecialchars($lang === 'am' ? 'የንብረት ዝርዝር' : 'Asset Details', ENT_QUOTES, 'UTF-8'); ?></h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong><?php echo htmlspecialchars($lang === 'am' ? 'ኮድ' : 'Code', ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($asset['asset_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><strong><?php echo htmlspecialchars($lang === 'am' ? 'ስም' : 'Name', ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($asset['property_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong><?php echo htmlspecialchars($lang === 'am' ? 'የአሁኑ ባለቤት' : 'Current Owner', ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($asset['owner_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><strong><?php echo htmlspecialchars($lang === 'am' ? 'ሁኔታ' : 'Status', ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($asset['status'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($transfer_history)): ?>
                                <h5><?php echo htmlspecialchars($lang === 'am' ? 'የዝውውር ታሪክ' : 'Transfer History', ENT_QUOTES, 'UTF-8'); ?></h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th><?php echo htmlspecialchars($lang === 'am' ? 'ቀን' : 'Date', ENT_QUOTES, 'UTF-8'); ?></th>
                                                <th><?php echo htmlspecialchars($lang === 'am' ? 'ከ' : 'From', ENT_QUOTES, 'UTF-8'); ?></th>
                                                <th><?php echo htmlspecialchars($lang === 'am' ? 'ወደ' : 'To', ENT_QUOTES, 'UTF-8'); ?></th>
                                                <th><?php echo htmlspecialchars($lang === 'am' ? 'ምክንያት' : 'Reason', ENT_QUOTES, 'UTF-8'); ?></th>
                                                <th><?php echo htmlspecialchars($lang === 'am' ? 'በ' : 'By', ENT_QUOTES, 'UTF-8'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transfer_history as $transfer): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($transfer['transferred_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($transfer['old_owner_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($transfer['new_owner_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($transfer['reason_text'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($transfer['transferred_by'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($owners) && !empty($reasons)): ?>
                                <hr>
                                <form method="POST" action="" id="transferForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="new_owner_id"><?php echo htmlspecialchars($lang === 'am' ? 'አዲስ ባለቤት' : 'New Owner', ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                                                <select class="form-control" id="new_owner_id" name="new_owner_id" required>
                                                    <option value=""><?php echo htmlspecialchars($lang === 'am' ? 'ባለቤት ይምረጡ' : 'Select Owner', ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php foreach ($owners as $owner): ?>
                                                        <option value="<?php echo htmlspecialchars($owner['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($owner['id'] == ($_POST['new_owner_id'] ?? '')) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($owner['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="reason_id"><?php echo htmlspecialchars($lang === 'am' ? 'የዝውውር ምክንያት' : 'Transfer Reason', ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                                                <select class="form-control" id="reason_id" name="reason_id" required>
                                                    <option value=""><?php echo htmlspecialchars($lang === 'am' ? 'ምክንያት ይምረጡ' : 'Select Reason', ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php foreach ($reasons as $reason): ?>
                                                        <option value="<?php echo htmlspecialchars($reason['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($reason['id'] == ($_POST['reason_id'] ?? '')) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($reason['reason'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group text-right">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-exchange-alt"></i> <?php echo htmlspecialchars($lang === 'am' ? 'ማስተላለፍ' : 'Transfer', ENT_QUOTES, 'UTF-8'); ?>
                                        </button>
                                        <a href="view_asset.php?id=<?php echo htmlspecialchars($asset['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-default">
                                            <i class="fas fa-times"></i> <?php echo htmlspecialchars($lang === 'am' ? 'ይቅርታ' : 'Cancel', ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
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
<script>
$(document).ready(function() {
    $('#transferForm').on('submit', function(e) {
        var ownerId = $('#new_owner_id').val();
        var reasonId = $('#reason_id').val();
        
        if (!ownerId) {
            e.preventDefault();
            alert('<?php echo htmlspecialchars($lang === 'am' ? 'እባክዎ አዲስ ባለቤት ይምረጡ' : 'Please select a new owner', ENT_QUOTES, 'UTF-8'); ?>');
            $('#new_owner_id').focus();
            return false;
        }
        
        if (!reasonId) {
            e.preventDefault();
            alert('<?php echo htmlspecialchars($lang === 'am' ? 'እባክዎ የዝውውር ምክንያት ይምረጡ' : 'Please select a transfer reason', ENT_QUOTES, 'UTF-8'); ?>');
            $('#reason_id').focus();
            return false;
        }
        
        var currentOwner = '<?php echo htmlspecialchars($asset['owner_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';
        var newOwner = $('#new_owner_id option:selected').text();
        var reason = $('#reason_id option:selected').text();

        return confirm(
            '<?php echo htmlspecialchars($lang === 'am' ? 'እርግጠኛ ነዎት ይህን ንብረት ለሌላ ባለቤት መላልፍ ይፈልጋሉ?' : 'Are you sure you want to transfer this asset to a new owner?', ENT_QUOTES, 'UTF-8'); ?>\n\n' +
            '<?php echo htmlspecialchars($lang === 'am' ? 'አሁን ያለው ባለቤት:' : 'Current Owner:', ENT_QUOTES, 'UTF-8'); ?> ' + currentOwner + '\n' +
            '<?php echo htmlspecialchars($lang === 'am' ? 'አዲስ ባለቤት:' : 'New Owner:', ENT_QUOTES, 'UTF-8'); ?> ' + newOwner + '\n' +
            '<?php echo htmlspecialchars($lang === 'am' ? 'ምክንያት:' : 'Reason:', ENT_QUOTES, 'UTF-8'); ?> ' + reason
        );
    });
});
</script>
</body>
</html>