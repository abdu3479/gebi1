<?php
include '../includes/nav.php';
require_once '../config/database.php';

//session_start(); // Uncomment if not started in nav.php
$lang = $_SESSION['lang'] ?? 'en';
$role = $_SESSION['role'] ?? 'viewer';
$is_viewer = $role === 'viewer';

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(htmlspecialchars($lang === 'am' ? 'ስርዓቱ ማስጀመር አልተሳካም። እባክዎ አስተዳዳሪውን ያነጋግሩ።' : 'System initialization failed. Please contact the administrator.', ENT_QUOTES, 'UTF-8'));
}

// Fetch assets
try {
    $query = "SELECT a.id, a.asset_code, a.property_name, a.block, a.lot_number, a.size, a.annual_lease_rate, 
                     a.status, a.registration_date, a.document_path, pt.name AS property_type,
                     o.full_name AS owner_name
              FROM assets a
              LEFT JOIN property_types pt ON a.property_type_id = pt.id
              LEFT JOIN lease_agreements l ON a.id = l.asset_id AND l.status = 'active'
              LEFT JOIN owners o ON l.owner_id = o.id
              ORDER BY a.registration_date DESC";
    
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query failed for assets: " . $conn->error);
        throw new Exception("Database query failed for assets.");
    }
    $assets = $result->fetch_all(MYSQLI_ASSOC);

    // Log missing data
    foreach ($assets as $asset) {
        if (empty($asset['owner_name']) && !empty($asset['asset_code'])) {
            error_log("Asset ID {$asset['id']} (Code: {$asset['asset_code']}) has missing owner_name.");
        }
        if (empty($asset['lot_number'])) {
            error_log("Asset ID {$asset['id']} (Code: {$asset['asset_code']}) has missing lot_number.");
        }
        if (empty($asset['property_type'])) {
            error_log("Asset ID {$asset['id']} (Code: {$asset['asset_code']}) has missing property_type (property_type_id: {$asset['property_type_id']}).");
        }
    }
} catch (Exception $e) {
    $error = htmlspecialchars($lang === 'am' ? 'የውሂብ ማከማቻ ስህተት: ' . $e->getMessage() : 'Database error: ' . $e->getMessage(), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang === 'am' ? 'am' : 'en', ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lang === 'am' ? 'ዝርዝር ንብረቶች - ወራቤ መሬት ሊዝ' : 'List Assets - Werabe Land Lease', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">
    <?php if ($lang === 'am'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&display=swap">
        <style>
            body { font-family: 'Noto Sans Ethiopic', Arial, sans-serif; }
            .content-wrapper, .card, table { text-align: left; }
            .dataTables_wrapper { direction: ltr; }
            .dropdown-menu { text-align: right; }
        </style>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
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
                    <?php endif; ?>
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
                        <h1><?php echo htmlspecialchars($lang === 'am' ? 'ዝርዝር ንብረቶች' : 'List Assets', ENT_QUOTES, 'UTF-8'); ?></h1>
                    </div>
                    <?php if (!$is_viewer): ?>
                        <div class="col-sm-6 text-right">
                            <a href="../asset/add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo htmlspecialchars($lang === 'am' ? 'አዲስ ንብረት' : 'New Asset', ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo htmlspecialchars($lang === 'am' ? 'ንብረቶች' : 'list Asset ', ENT_QUOTES, 'UTF-8'); ?></h3>
                    </div>
                    <div class="card-body">
                        <table id="assetsTable" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'ንብረት ኮድ' : 'Asset Code', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'የንብረት ስም' : 'Name', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'ብሎክ' : 'Block', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'የሎት ቁጥር' : 'Lot Number', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'የባለቤት ስም' : 'Owner Name', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'የንብረት አይነት' : 'Type', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'መጠን' : 'area', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'ዓመታዊ የሊዝ ተመን' : 'Lease Rate', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'ሁኔታ' : 'Status', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'የመመዝገቢያ ቀን' : 'Reg. Date', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($lang === 'am' ? 'ሰነድ' : 'Document', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <?php if (!$is_viewer): ?>
                                        <th><?php echo htmlspecialchars($lang === 'am' ? 'ተግባራት' : 'Actions', ENT_QUOTES, 'UTF-8'); ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($assets)): ?>
                                    <?php foreach ($assets as $asset): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($asset['asset_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['property_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['block'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['lot_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['owner_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['property_type'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo $asset['size'] !== null ? number_format($asset['size'], 2) . ' m²' : 'N/A'; ?></td>
                                            <td><?php echo $asset['annual_lease_rate'] !== null ? number_format($asset['annual_lease_rate'], 2) . ' ETB' : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($asset['status'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo $asset['registration_date'] ? date('M d, Y', strtotime($asset['registration_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <?php
                                                if (!empty($asset['document_path']) && file_exists('../Uploads/assets/' . $asset['document_path'])) {
                                                    $file_url = '/gebi1/Uploads/assets/' . rawurlencode($asset['document_path']);
                                                    echo '<a href="' . htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="btn btn-sm btn-info">';
                                                    echo htmlspecialchars($lang === 'am' ? 'ይመልከቱ' : 'View', ENT_QUOTES, 'UTF-8');
                                                    echo '</a>';
                                                } else {
                                                    echo htmlspecialchars('N/A', ENT_QUOTES, 'UTF-8');
                                                }
                                                ?>
                                            </td>
                                            <?php if (!$is_viewer): ?>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-cog"></i> <?php echo htmlspecialchars($lang === 'am' ? 'ተግባራት' : 'Actions', ENT_QUOTES, 'UTF-8'); ?>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a class="dropdown-item text-primary" href="/gebi1/asset/edit.php?id=<?php echo htmlspecialchars($asset['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                <i class="fas fa-edit"></i> <?php echo htmlspecialchars($lang === 'am' ? 'አስተካክል' : 'Edit', ENT_QUOTES, 'UTF-8'); ?>
                                                            </a>
                                                            <a class="dropdown-item text-info" href="transfer.php?id=<?php echo htmlspecialchars($asset['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                <i class="fas fa-exchange-alt"></i> <?php echo htmlspecialchars($lang === 'am' ? 'ቀይር' : 'Transfer', ENT_QUOTES, 'UTF-8'); ?>
                                                            </a>
                                                            <a class="dropdown-item text-danger" href="/gebi1/asset/delete.php?id=<?php echo htmlspecialchars($asset['id'], ENT_QUOTES, 'UTF-8'); ?>" onclick="return confirm('<?php echo htmlspecialchars($lang === 'am' ? 'እርግጠኛ ነዎት ይህን ንብረት መሰረዝ ይፈልጋሉ?' : 'Are you sure you want to delete this asset?', ENT_QUOTES, 'UTF-8'); ?>')">
                                                                <i class="fas fa-trash"></i> <?php echo htmlspecialchars($lang === 'am' ? 'ሰርዝ' : 'Delete', ENT_QUOTES, 'UTF-8'); ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?php echo $is_viewer ? 11 : 12; ?>" class="text-center">
                                                <?php echo htmlspecialchars($lang === 'am' ? 'ምንም ንብረቶች አልተገኙም' : 'No assets found', ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
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
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#assetsTable').DataTable({
            "dom": '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            "buttons": [
                <?php if (!$is_viewer): ?>
                {
                    extend: 'copy',
                    text: '<?php echo htmlspecialchars($lang === 'am' ? 'ቅዳ' : 'Copy', ENT_QUOTES, 'UTF-8'); ?>'
                },
                {
                    extend: 'csv',
                    text: '<?php echo htmlspecialchars($lang === 'am' ? 'CSV አውርድ' : 'CSV', ENT_QUOTES, 'UTF-8'); ?>'
                },
                {
                    extend: 'excel',
                    text: '<?php echo htmlspecialchars($lang === 'am' ? 'Excel አውርድ' : 'Excel', ENT_QUOTES, 'UTF-8'); ?>'
                },
                {
                    extend: 'pdf',
                    text: '<?php echo htmlspecialchars($lang === 'am' ? 'PDF አውርድ' : 'PDF', ENT_QUOTES, 'UTF-8'); ?>'
                },
                {
                    extend: 'print',
                    text: '<?php echo htmlspecialchars($lang === 'am' ? 'ፕሪንት' : 'Print', ENT_QUOTES, 'UTF-8'); ?>'
                }
                <?php endif; ?>
            ],
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "url": "<?php echo $lang === 'am' ? '//cdn.datatables.net/plug-ins/1.13.7/i18n/am.json' : '//cdn.datatables.net/plug-ins/1.13.7/i18n/en-GB.json'; ?>"
            },
            "columnDefs": [
                { "orderable": false, "targets": <?php echo $is_viewer ? 10 : 11; ?> } // Disable sorting on Document (and Actions if present)
            ]
        });
    });
    </script>
</body>
</html>