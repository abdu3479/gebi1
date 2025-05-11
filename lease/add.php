<?php
include '../includes/nav.php'; 
require_once '../config/database.php';
require_once '../classes/Lease.php';
require_once '../classes/Asset.php';
require_once '../classes/Owner.php';

// Session variables for role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

// Redirect viewers to dashboard
if ($is_viewer) {
    header("Location: /dashboard.php");
    exit;
}

// Detect current page for active link
$current_page = basename($_SERVER['PHP_SELF']);

$db = new Database();
$conn = $db->getConnection();
$leaseObj = new Lease($conn);
$assetObj = new Asset($conn);
$ownerObj = new Owner($conn);

$errors = [];
$success = '';
$assets = $assetObj->getAll();
$owners = $ownerObj->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
            throw new Exception('User ID is not set or invalid');
        }
        $data = [
            'lease_number' => $leaseObj->generateLeaseNumber(),
            'asset_id' => (int)($_POST['asset_id'] ?? 0),
            'owner_id' => (int)($_POST['owner_id'] ?? 0),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date' => trim($_POST['end_date'] ?? ''),
            'yearly_rent_rate' => (float)($_POST['monthly_rent'] ?? 0) * 12,
            'payment_terms' => trim($_POST['payment_terms'] ?? 'monthly'),
            'status' => 'pending', // Default status since field is removed from form
            'terms_conditions' => trim($_POST['terms_conditions'] ?? ''),
            'lease_term_of_years' => (int)($_POST['lease_term_of_years'] ?? 1),
            'created_by' => (int)$_SESSION['user_id']
        ];
        // Debug: Log $data
        error_log("Form data: " . print_r($data, true));
        $lease_id = $leaseObj->create($data);
        // Debug: Log $lease_id
        error_log("Lease ID: " . $lease_id);
        $success = "Lease created successfully with ID: $lease_id";
        
        // Log to audit_logs using prepared statement
        $query = "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $user_id = (int)$_SESSION['user_id'];
        $action_type = 'create';
        $table_name = 'lease_agreements';
        $new_values = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        // Debug: Log query parameters
        error_log("Audit log params: user_id=$user_id, action_type=$action_type, table_name=$table_name, record_id=$lease_id, new_values=$new_values, ip_address=$ip_address, user_agent=$user_agent");
        $stmt->bind_param('ississs', $user_id, $action_type, $table_name, $lease_id, $new_values, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        // Debug: Log exception
        error_log("Exception in lease creation: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lease - Werabe Land Lease</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
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
                        <a href="/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
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
                                    <a href="add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ሊዝ ጨምር' : 'Add Lease'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
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
                                    <a href="../charges/list_charge.php" class="nav-link <?php echo $current_page === 'list_charges.php' ? 'active' : ''; ?>">
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
                                <a href="register.php" class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
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
                    <h1><?php echo $lang === 'am' ? 'አዲስ ሊዝ ጨምር' : 'Add New Lease'; ?></h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="asset_id"><?php echo $lang === 'am' ? 'ንብረት' : 'Asset'; ?></label>
                                    <select class="form-control" id="asset_id" name="asset_id" required>
                                        <option value=""><?php echo $lang === 'am' ? 'ንብረት ይምረጡ' : 'Select Asset'; ?></option>
                                        <?php foreach ($assets as $asset): ?>
                                            <option value="<?php echo $asset['id']; ?>" 
                                                    data-owner-id="<?php echo $asset['owner_id'] ?? ''; ?>">
                                                <?php echo htmlspecialchars("{$asset['asset_code']} - {$asset['property_name']}", ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="owner_id"><?php echo $lang === 'am' ? 'ባለቤት' : 'Owner'; ?></label>
                                    <select class="form-control" id="owner_id" name="owner_id" required>
                                        <option value=""><?php echo $lang === 'am' ? 'ባለቤት ይምረጡ' : 'Select Owner'; ?></option>
                                        <?php foreach ($owners as $owner): ?>
                                            <option value="<?php echo $owner['id']; ?>">
                                                <?php echo htmlspecialchars($owner['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date"><?php echo $lang === 'am' ? 'መጀመሪያ ቀን' : 'Start Date'; ?></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date"><?php echo $lang === 'am' ? 'መጨረሻ ቀን' : 'End Date'; ?></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="monthly_rent"><?php echo $lang === 'am' ? 'ወርሃዊ ኪራይ (ብር)' : 'Monthly Rent (ETB)'; ?></label>
                                    <input type="number" step="0.01" class="form-control" id="monthly_rent" name="monthly_rent" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_terms"><?php echo $lang === 'am' ? 'የክፍያ ውሎች' : 'Payment Terms'; ?></label>
                                    <select class="form-control" id="payment_terms" name="payment_terms">
                                        <option value="monthly"><?php echo $lang === 'am' ? 'ወርሃዊ' : 'Monthly'; ?></option>
                                        <option value="quarterly"><?php echo $lang === 'am' ? 'በየሩብ ዓመቱ' : 'Quarterly'; ?></option>
                                        <option value="annually"><?php echo $lang === 'am' ? 'ዓመታዊ' : 'Annually'; ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lease_term_of_years"><?php echo $lang === 'am' ? 'የሊዝ ጊዜ (ዓመታት)' : 'Lease Term (Years)'; ?></label>
                                    <input type="number" class="form-control" id="lease_term_of_years" name="lease_term_of_years" value="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="terms_conditions"><?php echo $lang === 'am' ? 'ውሎች እና ሁኔታዎች' : 'Terms & Conditions'; ?></label>
                            <textarea class="form-control" id="terms_conditions" name="terms_conditions"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo $lang === 'am' ? 'ሊዝ ጨምር' : 'Add Lease'; ?></button>
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
<script>
$(document).ready(function() {
    $('#asset_id').on('change', function() {
        var assetId = $(this).val();
        var ownerSelect = $('#owner_id');

        if (!assetId) {
            ownerSelect.val(''); // Reset owner dropdown
            return;
        }

        // Try to get owner_id from data attribute first
        var selectedOption = $(this).find('option:selected');
        var ownerId = selectedOption.data('owner-id');

        if (ownerId) {
            ownerSelect.val(ownerId);
        } else {
            // Fallback to AJAX if data attribute is not available
            $.ajax({
                url: 'get_owner_by_asset.php',
                type: 'GET',
                data: { asset_id: assetId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.owner_id) {
                        ownerSelect.val(response.owner_id);
                    } else {
                        ownerSelect.val('');
                        console.error('Error fetching owner: ' + (response.error || 'No owner found'));
                    }
                },
                error: function(xhr, status, error) {
                    ownerSelect.val('');
                    console.error('AJAX error: ' + error);
                }
            });
        }
    });
});
</script>
</body>
</html>