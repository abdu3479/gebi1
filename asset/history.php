<?php
//session_start(); // Ensure session is started
include '../includes/nav.php';
require_once '../config/database.php';
require_once '../classes/Asset.php';

// Validate session variables
$lang = isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'am']) ? $_SESSION['lang'] : 'en';
$role = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'editor', 'viewer', 'billing_officer', 'clerk']) ? $_SESSION['role'] : 'viewer';
$is_viewer = $role === 'viewer';

// Restrict access for viewers
if ($is_viewer) {
    header("Location: ../dashboard.php");
    exit;
}

// Initialize database connection with error handling
try {
    $db = new Database();
    $conn = $db->getConnection();
    $assetObj = new Asset($conn);
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Error: Unable to connect to the database. Please try again later.");
}

// Define constant for default photo path
define('DEFAULT_OWNER_PHOTO', 'Uploads/owners/no-photo.jpg');

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for bulk transfer action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    if ($_POST['bulk_action'] === 'export_selected' && !empty($_POST['selected_transfers'])) {
        try {
            // Handle export of selected transfers
            $selectedIds = array_map('intval', explode(',', $_POST['selected_transfers']));
            $transfers = $assetObj->getSelectedTransfers($selectedIds);
            
            if (empty($transfers)) {
                throw new Exception("No transfers found for the selected IDs.");
            }
            
            // Set headers for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=asset_transfers_export_' . date('Y-m-d') . '.csv');
            
            // Create CSV output
            $output = fopen('php://output', 'w');
            
            // CSV headers
            $headers = [
                $lang === 'am' ? 'ንብረት ኮድ' : 'Asset Code',
                $lang === 'am' ? 'የንብረት ስም' : 'Property Name',
                $lang === 'am' ? 'የንብረት ዓይነት' : 'Property Type',
                $lang === 'am' ? 'የቀድሞ ባለቤት' : 'From',
                $lang === 'am' ? 'አዲስ ባለቤት' : 'To',
                $lang === 'am' ? 'የሽግግር ምክንያት' : 'Reason',
                $lang === 'am' ? 'የተላለፈበት ቀን' : 'Transferred At',
                $lang === 'am' ? 'ተጠቃሚ' : 'User'
            ];
            
            fputcsv($output, $headers);
            
            // Add data rows
            foreach ($transfers as $transfer) {
                fputcsv($output, [
                    $transfer['asset_code'],
                    $transfer['property_name'],
                    $transfer['property_type'],
                    $transfer['old_owner_name'] ?? 'N/A',
                    $transfer['new_owner_name'] ?? 'N/A',
                    $transfer['reason'],
                    $transfer['transferred_at'],
                    $transfer['username']
                ]);
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log("Error in bulk export: " . $e->getMessage());
            $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ የተመረጡትን ሽግግሮች መላክ አልተሳካም።' : 'Error: Failed to export selected transfers.';
            header("Location: history.php");
            exit;
        }
    }
}

// Detect current page for active link
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'የንብረት ሽግግር ታሪክ - ወራቤ መሬት ሊዝ' : 'Asset Transfer History - Werabe Land Lease'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css">
    <?php if ($lang === 'am'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&display=swap">
        <style>
            body { font-family: 'Noto Sans Ethiopic', Arial, sans-serif; }
            .content-wrapper, .card, table { text-align: right; }
            .dataTables_wrapper { direction: ltr; }
            .owner-photo { 
                max-width: 50px; 
                height: auto; 
                cursor: pointer; 
                border-radius: 50%;
                transition: transform 0.3s;
            }
            .owner-photo:hover {
                transform: scale(1.1);
            }
            .dt-buttons { float: left !important; }
            .bulk-actions {
                margin-bottom: 15px;
            }
            .select-info {
                margin-left: 0;
                margin-right: 10px;
            }
            .action-link i {
                font-size: 1.2em;
            }
            .action-link:hover {
                color: #007bff;
            }
        </style>
    <?php else: ?>
        <style>
            .owner-photo { 
                max-width: 50px; 
                height: auto; 
                cursor: pointer; 
                border-radius: 50%;
                transition: transform 0.3s;
            }
            .owner-photo:hover {
                transform: scale(1.1);
            }
            .bulk-actions {
                margin-bottom: 15px;
            }
            .action-link i {
                font-size: 1.2em;
            }
            .action-link:hover {
                color: #007bff;
            }
        </style>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="../dashboard.php" class="brand-link">
            <img src="../assets/images/logo.jpg" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
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
                    <?php endif; ?>

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

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo $lang === 'am' ? 'የንብረት ሽግግር ታሪክ' : 'Asset Transfer History'; ?></h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="transfer.php" class="btn btn-primary">
                            <i class="fas fa-exchange-alt"></i> <?php echo $lang === 'am' ? 'አዲስ ሽግግር' : 'New Transfer'; ?>
                        </a>
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
                <div class="card">
                    <div class="card-body">
                        <!-- Bulk Actions Form -->
                        <form id="bulkActionForm" method="post" class="bulk-actions">
                            <div class="row">
                                <div class="col-md-4">
                                    <select name="bulk_action" class="form-control" required>
                                        <option value=""><?php echo $lang === 'am' ? 'የጅምላ እርምጃ ይምረጡ' : 'Select Bulk Action'; ?></option>
                                        <option value="export_selected"><?php echo $lang === 'am' ? 'ተመርጠው ያሉትን ወደ CSV ላክ' : 'Export Selected to CSV'; ?></option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-play"></i> <?php echo $lang === 'am' ? 'አስፈጽም' : 'Execute'; ?>
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" name="selected_transfers" id="selectedTransfers" value="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        </form>

                        <table id="transferHistoryTable" class="table table-bordered table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th data-orderable="false">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th><?php echo $lang === 'am' ? 'ንብረት ኮድ' : 'Asset Code'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የንብረት ስም' : 'Property Name'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የንብረት ዓይነት' : 'Property Type'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የቀድሞ ባለቤት' : 'From'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ፎቶ' : 'Photo'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'አዲስ ባለቤት' : 'To'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ፎቶ' : 'Photo'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ምክንያት' : 'Reason'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ቀን' : 'Date'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ተጠቃሚ' : 'User'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ድርጊቶች' : 'Actions'; ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel"><?php echo $lang === 'am' ? 'የባለቤት ፎቶ' : 'Owner Photo'; ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalPhoto" src="" class="img-fluid" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo $lang === 'am' ? 'ዝጋ' : 'Close'; ?></button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#transferHistoryTable').DataTable({
        dom: '<"top"Bf>rt<"bottom"lip><"clear">',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                title: '<?php echo $lang === 'am' ? 'የንብረት_ሽግግር_ታሪክ' : 'Asset_Transfer_History'; ?>',
                exportOptions: {
                    columns: [1, 2, 3, 4, 6, 8, 9, 10] // Exclude checkbox, photos, and actions
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                title: '<?php echo $lang === 'am' ? 'የንብረት ሽግግር ታሪክ' : 'Asset Transfer History'; ?>',
                exportOptions: {
                    columns: [1, 2, 3, 4, 6, 8, 9, 10]
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> <?php echo $lang === 'am' ? 'ማተም' : 'Print'; ?>',
                title: '<?php echo $lang === 'am' ? 'የንብረት ሽግግር ታሪክ' : 'Asset Transfer History'; ?>',
                exportOptions: {
                    columns: [1, 2, 3, 4, 6, 8, 9, 10]
                }
            }
        ],
        processing: true,
        serverSide: true,
        ajax: {
            url: "fetch_transfer_history.php",
            type: "POST",
            data: function(d) {
                d.csrf_token = '<?php echo $_SESSION['csrf_token']; ?>';
                return d;
            },
            error: function(xhr, error, thrown) {
                alert('<?php echo $lang === 'am' ? 'ስህተት፡ የውሂብ ጭነት አልተሳካም። እባክዎ እንደገና ይሞክሩ።' : 'Error: Failed to load data. Please try again.'; ?>');
                console.error('AJAX error:', error, thrown);
            }
        },
        columns: [
            {
                data: null,
                defaultContent: '<input type="checkbox" class="row-checkbox">',
                className: 'select-checkbox',
                orderable: false
            },
            { data: "asset_code" },
            { data: "property_name" },
            { data: "property_type" },
            { data: "old_owner_name" },
            { 
                data: "old_owner_photo",
                render: function(data, type, row) {
                    return data ? 
                        '<img src="../' + data + '" class="owner-photo" data-toggle="modal" data-target="#photoModal" data-src="../' + data + '" title="' + (row.old_owner_name || '') + '">' : 
                        '<img src="../<?php echo DEFAULT_OWNER_PHOTO; ?>" class="owner-photo" data-toggle="modal" data-target="#photoModal" data-src="../<?php echo DEFAULT_OWNER_PHOTO; ?>" title="' + (row.old_owner_name || '') + '">';
                },
                orderable: false
            },
            { data: "new_owner_name" },
            { 
                data: "new_owner_photo",
                render: function(data, type, row) {
                    return data ? 
                        '<img src="../' + data + '" class="owner-photo" data-toggle="modal" data-target="#photoModal" data-src="../' + data + '" title="' + (row.new_owner_name || '') + '">' : 
                        '<img src="../<?php echo DEFAULT_OWNER_PHOTO; ?>" class="owner-photo" data-toggle="modal" data-target="#photoModal" data-src="../<?php echo DEFAULT_OWNER_PHOTO; ?>" title="' + (row.new_owner_name || '') + '">';
                },
                orderable: false
            },
            { data: "reason" },
            { 
                data: "transferred_at",
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : '';
                }
            },
            { data: "username" },
            {
                data: "id",
                render: function(data, type, row) {
                    return '<a href="view_transfer.php?transfer_id=' + data + '" class="action-link" title="<?php echo $lang === "am" ? "ዝርዝር ይመልከቱ" : "View Details"; ?>"><i class="fas fa-eye"></i></a>';
                },
                orderable: false,
                searchable: false
            }
        ],
        language: {
            url: "<?php echo $lang === 'am' ? '//cdn.datatables.net/plug-ins/1.13.7/i18n/am.json' : '//cdn.datatables.net/plug-ins/1.13.7/i18n/en.json'; ?>"
        },
        responsive: true,
        order: [[9, 'desc']],
        select: {
            style: 'multi',
            selector: 'td:first-child .row-checkbox'
        },
        rowId: 'id' // Map rows to transfer IDs
    });

    // Handle select all checkbox
    $('#selectAll').on('click', function() {
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('.row-checkbox', rows).prop('checked', this.checked);
        table.rows({ 'search': 'applied' }).select(this.checked);
    });

    // Update select all checkbox and row selection
    $('#transferHistoryTable tbody').on('change', '.row-checkbox', function() {
        var row = table.row($(this).closest('tr'));
        if (this.checked) {
            row.select();
        } else {
            row.deselect();
            $('#selectAll').prop('checked', false);
        }
    });

    // Handle bulk action form submission
    $('#bulkActionForm').on('submit', function(e) {
        e.preventDefault();
        
        var selectedIds = table.rows({ selected: true }).ids().toArray();
        if (selectedIds.length === 0) {
            alert('<?php echo $lang === 'am' ? 'እባክዎ ለመሰረዝ ቢያንስ አንድ ሽግግር ይምረጡ' : 'Please select at least one transfer to perform the action'; ?>');
            return;
        }
        
        $('#selectedTransfers').val(selectedIds.join(','));
        this.submit();
    });

    // Modal for enlarged photo
    $('#photoModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var photoSrc = button.data('src');
        var modal = $(this);
        modal.find('#modalPhoto').attr('src', photoSrc);
    });
});
</script>
</body>
</html>