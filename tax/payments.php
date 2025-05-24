<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('index.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Error: " . sanitize($e->getMessage()));
}

// Set role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

// Fetch payment data for editing
$editPayment = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT p.*, a.property_id FROM payments p JOIN tax_assessments a ON p.assessment_id = a.assessment_id WHERE p.payment_id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $result = $stmt->get_result();
    $editPayment = $result->fetch_assoc();
    $stmt->close();
    if (!$editPayment) {
        $_SESSION['error_message'] = "Payment not found.";
        redirect('payments.php');
    }
}

// Get notifications count
$notificationsCount = 0;
if (!$is_viewer) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM tax_assessments WHERE status = ?");
    $status = 'pending';
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $notificationsCount = $row['count'];
    $stmt->close();
}

$pageTitle = translate('payments');
?>
<!DOCTYPE html>
<html lang="<?php echo sanitize($lang); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tax Management System - <?php echo sanitize($pageTitle); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <style>
        .main-header .navbar-nav .nav-item .nav-link {
            padding: 0.5rem 1rem;
        }
        .select2-container--default .select2-selection--single {
            height: calc(2.25rem + 2px);
            padding: 0.375rem 0.75rem;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" aria-label="<?php echo translate('notifications'); ?>">
                    <i class="far fa-bell"></i>
                    <?php if ($notificationsCount > 0): ?>
                        <span class="badge badge-warning navbar-badge"><?php echo $notificationsCount; ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header"><?php echo $notificationsCount; ?> <?php echo translate('notifications'); ?></span>
                    <div class="dropdown-divider"></div>
                    <a href="assessments.php?status=pending" class="dropdown-item">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> <?php echo $notificationsCount; ?> <?php echo translate('pending_tax_assessments'); ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="assessments.php" class="dropdown-item dropdown-footer"><?php echo translate('see_all_notifications'); ?></a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" aria-label="<?php echo translate('user_menu'); ?>">
                    <i class="far fa-user"></i>
                    <span class="hidden-xs"><?php echo sanitize($_SESSION['username']); ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header"><?php echo translate('user_menu'); ?></span>
                    <div class="dropdown-divider"></div>
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> <?php echo translate('profile'); ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> <?php echo translate('logout'); ?>
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="index.php" class="brand-link">
            <img src="../assets/images/logo.jpg" alt="Tax System Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Tax Management</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="../assets/images/logo.jpg" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="profile.php" class="d-block"><?php echo sanitize($_SESSION['username']); ?></a>
                    <small><?php echo ucfirst(sanitize($_SESSION['role'])); ?></small>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p><?php echo translate('dashboard'); ?></p>
                        </a>
                    </li>
                    <?php if (isAdmin() || isOfficer()): ?>
                        <li class="nav-item">
                            <a href="owners.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p><?php echo translate('property_owners'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rooms.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p><?php echo translate('rooms'); ?></p>
                            </a>
                        </li>
                           <li class="nav-item">
                            <a href=" rental_payments.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p><?php echo translate(' rental payments'); ?></p>
                            </a>
                        </li>
                       
                        <li class="nav-item">
                            <a href="properties.php" class="nav-link">
                                <i class="nav-icon fas fa-home"></i>
                                <p><?php echo translate('properties'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="buildings.php" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p><?php echo translate('buildings'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="assessments.php" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                <p><?php echo translate('tax_assessments'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="payments.php" class="nav-link active">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p><?php echo translate('payments'); ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a href="users.php" class="nav-link">
                                <i class="nav-icon fas fa-user-cog"></i>
                                <p><?php echo translate('system_users'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports.php" class="nav-link">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p><?php echo translate('reports'); ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
                            <i class="nav-icon fas fa-user"></i>
                            <p><?php echo translate('profile'); ?></p>
                        </a>
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
                        <h1><?php echo translate('payments'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('payments'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php displayMessages(); ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo isset($_GET['edit']) ? translate('edit_payment') : translate('add_new_payment'); ?></h3>
                            </div>
                            <form method="post" action="process_payment.php">
                                <?php if (isset($_GET['edit'])): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo (int)$_GET['edit']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="assessment_id"><?php echo translate('assessment'); ?>*</label>
                                        <select class="form-control select2" id="assessment_id" name="assessment_id" required>
                                            <option value=""><?php echo translate('select_assessment'); ?></option>
                                            <?php
                                            $stmt = $conn->prepare("SELECT a.assessment_id, a.fiscal_year, p.property_name, o.full_name, b.building_name 
                                                                   FROM tax_assessments a 
                                                                   JOIN properties p ON a.property_id = p.property_id 
                                                                   JOIN owners o ON p.owner_id = o.id 
                                                                   LEFT JOIN buildings b ON a.building_id = b.building_id 
                                                                   WHERE a.status != 'paid'
                                                                   ORDER BY a.fiscal_year DESC");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()):
                                                $selected = (isset($editPayment) && $editPayment['assessment_id'] == $row['assessment_id']) ? 'selected' : '';
                                                $target = $row['building_name'] ? sanitize($row['building_name']) . " (Building)" : sanitize($row['property_name']) . " (Property)";
                                            ?>
                                                <option value="<?php echo (int)$row['assessment_id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo $target; ?> - Year: <?php echo (int)$row['fiscal_year']; ?> (Owner: <?php echo sanitize($row['full_name']); ?>)
                                                </option>
                                            <?php endwhile; 
                                            $stmt->close();
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="amount"><?php echo translate('amount'); ?>*</label>
                                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required 
                                               value="<?php echo isset($editPayment['amount']) ? number_format($editPayment['amount'], 2, '.', '') : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="payment_date"><?php echo translate('payment_date'); ?>*</label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" required 
                                               value="<?php echo isset($editPayment['payment_date']) ? sanitize($editPayment['payment_date']) : date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="payment_method"><?php echo translate('payment_method'); ?>*</label>
                                        <select class="form-control" id="payment_method" name="payment_method" required>
                                            <option value=""><?php echo translate('select_payment_method'); ?></option>
                                            <option value="cash" <?php echo isset($editPayment['payment_method']) && $editPayment['payment_method'] == 'cash' ? 'selected' : ''; ?>><?php echo translate('cash'); ?></option>
                                            <option value="credit_card" <?php echo isset($editPayment['payment_method']) && $editPayment['payment_method'] == 'credit_card' ? 'selected' : ''; ?>><?php echo translate('credit_card'); ?></option>
                                            <option value="bank_transfer" <?php echo isset($editPayment['payment_method']) && $editPayment['payment_method'] == 'bank_transfer' ? 'selected' : ''; ?>><?php echo translate('bank_transfer'); ?></option>
                                            <option value="mobile_payment" <?php echo isset($editPayment['payment_method']) && $editPayment['payment_method'] == 'mobile_payment' ? 'selected' : ''; ?>><?php echo translate('mobile_payment'); ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="notes"><?php echo translate('notes'); ?></label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($editPayment['notes']) ? sanitize($editPayment['notes']) : ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? translate('update') : translate('submit'); ?></button>
                                    <?php if (isset($_GET['edit'])): ?>
                                        <a href="payments.php" class="btn btn-default"><?php echo translate('cancel'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_payments'); ?></h3>
                                <?php if (isset($_GET['assessment']) && is_numeric($_GET['assessment'])): ?>
                                    <div class="card-tools">
                                        <a href="payments.php" class="btn btn-sm btn-secondary"><?php echo translate('show_all_payments'); ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th><?php echo translate('assessment'); ?></th>
                                            <th><?php echo translate('amount'); ?></th>
                                            <th><?php echo translate('payment_date'); ?></th>
                                            <th><?php echo translate('payment_method'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT p.*, a.fiscal_year, a.tax_amount, pr.property_name, o.full_name, b.building_name 
                                                FROM payment p 
                                                JOIN tax_assessments a ON p.tax_assessment_id = a.assessment_id 
                                                JOIN properties pr ON a.property_id = pr.property_id 
                                                JOIN owners o ON pr.owner_id = o.id 
                                                LEFT JOIN buildings b ON a.building_id = b.building_id";
                                        $params = [];
                                        $types = '';
                                        
                                        if (isset($_GET['assessment']) && is_numeric($_GET['assessment'])) {
                                            $sql .= " WHERE p.tax_assessment_id = ?";
                                            $params[] = (int)$_GET['assessment'];
                                            $types .= 'i';
                                        }
                                        
                                        $sql .= " ORDER BY p.payment_date DESC";
                                        $stmt = $conn->prepare($sql);
                                        if (!empty($params)) {
                                            $stmt->bind_param($types, ...$params);
                                        }
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()):
                                            $target = $row['building_name'] ? sanitize($row['building_name']) . " (Building)" : sanitize($row['property_name']) . " (Property)";
                                            $assessment_info = $target . " - Year: " . (int)$row['fiscal_year'] . " (Tax: " . formatCurrency($row['tax_amount']) . ")";
                                            $method = translate($row['payment_method']);
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['payment_id']; ?></td>
                                                <td><?php echo $assessment_info; ?></td>
                                                <td><?php echo formatCurrency($row['amount']); ?></td>
                                                <td><?php echo sanitize($row['payment_date']); ?></td>
                                                <td><?php echo $method; ?></td>
                                                <td>
                                                    <a href="payments.php?edit=<?php echo (int)$row['payment_id']; ?>" class="btn btn-sm btn-primary" title="<?php echo translate('edit'); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="process_payment.php?delete=<?php echo (int)$row['payment_id']; ?>" class="btn btn-sm btn-danger" title="<?php echo translate('delete'); ?>" onclick="return confirm('Are you sure you want to delete this payment?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; 
                                        $stmt->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>Copyright © <?php echo date('Y'); ?> Tax Management System.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(function () {
    $('.datatable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true
    });
    
    $('.select2').select2({
        theme: 'bootstrap4'
    });
});
</script>
</body>
</html>