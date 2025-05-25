<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = translate('unauthorized_access');
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
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT p.*, a.property_id FROM payment p JOIN tax_assessments a ON p.assessment_id = a.assessment_id WHERE p.payment_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editPayment = $result->fetch_assoc();
    $stmt->close();
    
    if (!$editPayment) {
        $_SESSION['error_message'] = translate('payment_not_found');
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
    <!-- Navigation and header code remains the same as your original -->
 <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="<?php echo translate('toggle_sidebar'); ?>"><i class="fas fa-bars"></i></a>
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
                            <a href="rental_tax.php" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p><?php echo translate('rental_tax'); ?></p>
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
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_payments'); ?></h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#paymentModal">
                                        <i class="fas fa-plus"></i> <?php echo translate('add_payment'); ?>
                                    </button>
                                </div>
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
                                            <th><?php echo translate('receipt_number'); ?></th>
                                            <th><?php echo translate('received_by'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT p.*, a.fiscal_year, a.tax_amount, a.status, pr.property_name, o.full_name, b.building_name,
                                                COALESCE(SUM(p2.amount), 0) AS total_paid
                                                FROM payment p 
                                                JOIN tax_assessments a ON p.assessment_id = a.assessment_id 
                                                JOIN properties pr ON a.property_id = pr.property_id 
                                                JOIN owners o ON pr.owner_id = o.id 
                                                LEFT JOIN buildings b ON a.building_id = b.building_id
                                                LEFT JOIN payment p2 ON a.assessment_id = p2.assessment_id
                                                GROUP BY p.payment_id, a.assessment_id, a.fiscal_year, a.tax_amount, a.status, pr.property_name, o.full_name, b.building_name
                                                ORDER BY p.payment_date DESC";
                                        
                                        $stmt = $conn->prepare($sql);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()):
                                            $target = $row['building_name'] ? sanitize($row['building_name']) . " (Building)" : sanitize($row['property_name']) . " (Property)";
                                            $remaining = $row['tax_amount'] - $row['total_paid'];
                                            $assessment_info = $target . " - Year: " . (int)$row['fiscal_year'] . " (Tax: " . formatCurrency($row['tax_amount']) . ", Status: " . translate($row['status']) . ", Remaining: " . formatCurrency($remaining) . ")";
                                            $method = translate($row['payment_method']);
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['payment_id']; ?></td>
                                                <td><?php echo $assessment_info; ?></td>
                                                <td><?php echo formatCurrency($row['amount']); ?></td>
                                                <td><?php echo sanitize($row['payment_date']); ?></td>
                                                <td><?php echo $method; ?></td>
                                                <td><?php echo sanitize($row['receipt_number']); ?></td>
                                                <td><?php echo sanitize($row['received_by']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary edit-payment" 
                                                            data-id="<?php echo (int)$row['payment_id']; ?>"
                                                            data-assessment-id="<?php echo (int)$row['assessment_id']; ?>"
                                                            data-tax-assessment-id="<?php echo sanitize($row['tax_assessment_id']); ?>"
                                                            data-amount="<?php echo number_format($row['amount'], 2, '.', ''); ?>"
                                                            data-payment-date="<?php echo sanitize($row['payment_date']); ?>"
                                                            data-payment-method="<?php echo sanitize($row['payment_method']); ?>"
                                                            data-receipt-number="<?php echo sanitize($row['receipt_number']); ?>"
                                                            data-received-by="<?php echo sanitize($row['received_by']); ?>"
                                                            data-notes="<?php echo sanitize($row['notes']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="process_payment.php?delete=<?php echo (int)$row['payment_id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       title="<?php echo translate('delete'); ?>" 
                                                       onclick="return confirm('<?php echo translate('confirm_delete_payment'); ?>');">
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

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel"><?php echo isset($_GET['edit']) ? translate('edit_payment') : translate('add_new_payment'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="process_payment.php">
                    <input type="hidden" name="edit_id" id="edit_id" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_assessment_id"><?php echo translate('assessment'); ?>*</label>
                                    <select class="form-control select2" id="modal_assessment_id" name="assessment_id" required>
                                        <option value=""><?php echo translate('select_assessment'); ?></option>
                                        <?php
                                        $stmt = $conn->prepare("
                                            SELECT a.assessment_id, a.fiscal_year, a.tax_amount, p.property_name, o.full_name, b.building_name,
                                                   (a.tax_amount - COALESCE(SUM(pay.amount), 0)) AS remaining_balance
                                            FROM tax_assessments a 
                                            JOIN properties p ON a.property_id = p.property_id 
                                            JOIN owners o ON p.owner_id = o.id 
                                            LEFT JOIN buildings b ON a.building_id = b.building_id 
                                            LEFT JOIN payment pay ON a.assessment_id = pay.assessment_id
                                            WHERE a.status IN ('pending', 'overdue')
                                            GROUP BY a.assessment_id, a.fiscal_year, a.tax_amount, p.property_name, o.full_name, b.building_name
                                            ORDER BY a.fiscal_year DESC
                                        ");
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()):
                                            $selected = (isset($editPayment) && $editPayment['assessment_id'] == $row['assessment_id']) ? 'selected' : '';
                                            $target = $row['building_name'] ? sanitize($row['building_name']) . " (Building)" : sanitize($row['property_name']) . " (Property)";
                                        ?>
                                            <option value="<?php echo (int)$row['assessment_id']; ?>" 
                                                    <?php echo $selected; ?> 
                                                    data-tax-amount="<?php echo (float)$row['tax_amount']; ?>" 
                                                    data-remaining-balance="<?php echo (float)$row['remaining_balance']; ?>">
                                                <?php echo $target; ?> - Year: <?php echo (int)$row['fiscal_year']; ?> (Owner: <?php echo sanitize($row['full_name']); ?>, Remaining: <?php echo formatCurrency($row['remaining_balance']); ?>)
                                            </option>
                                        <?php endwhile; 
                                        $stmt->close();
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="modal_tax_assessment_id"><?php echo translate('tax_assessment_id'); ?></label>
                                    <input type="text" class="form-control" id="modal_tax_assessment_id" name="tax_assessment_id">
                                </div>
                                <div class="form-group">
                                    <label for="modal_amount"><?php echo translate('amount'); ?>*</label>
                                    <input type="number" step="0.01" class="form-control" id="modal_amount" name="amount" required>
                                </div>
                                <div class="form-group">
                                    <label for="modal_payment_date"><?php echo translate('payment_date'); ?>*</label>
                                    <input type="date" class="form-control" id="modal_payment_date" name="payment_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_payment_method"><?php echo translate('payment_method'); ?>*</label>
                                    <select class="form-control" id="modal_payment_method" name="payment_method" required>
                                        <option value=""><?php echo translate('select_payment_method'); ?></option>
                                        <option value="cash"><?php echo translate('cash'); ?></option>
                                        <option value="bank"><?php echo translate('bank'); ?></option>
                                        <option value="mobile"><?php echo translate('mobile'); ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="modal_receipt_number"><?php echo translate('receipt_number'); ?>*</label>
                                    <input type="text" class="form-control" id="modal_receipt_number" name="receipt_number" required>
                                </div>
                                <div class="form-group">
                                    <label for="modal_received_by"><?php echo translate('received_by'); ?>*</label>
                                    <input type="text" class="form-control" id="modal_received_by" name="received_by" required>
                                </div>
                                <div class="form-group">
                                    <label for="modal_notes"><?php echo translate('notes'); ?></label>
                                    <textarea class="form-control" id="modal_notes" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('close'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo translate('submit'); ?></button>
                    </div>
                </form>
            </div>
        </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(function () {
    // Initialize DataTable
    $('.datatable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true
    });
    
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Auto-fill form fields when assessment is selected
    $('#modal_assessment_id').change(function() {
        var assessmentId = $(this).val();
        var selectedOption = $(this).find('option:selected');
        var remainingBalance = selectedOption.data('remaining-balance') || 0;

        // Reset fields
        $('#modal_tax_assessment_id').val('');
        $('#modal_amount').val('');
        $('#modal_receipt_number').val('');

        if (assessmentId) {
            // Auto-fill fields
            $('#modal_tax_assessment_id').val('TAX-' + assessmentId);
            $('#modal_amount').val(remainingBalance.toFixed(2));
            $('#modal_payment_date').val('<?php echo date('Y-m-d'); ?>');
            $('#modal_receipt_number').val('REC-' + assessmentId + '-' + Date.now());
            $('#modal_received_by').val('<?php echo sanitize($_SESSION['username']); ?>');
        }
    });

    // Handle edit button click
    $('.edit-payment').click(function() {
        var paymentId = $(this).data('id');
        $('#edit_id').val(paymentId);
        $('#paymentModalLabel').text('<?php echo translate('edit_payment'); ?>');
        
        // Fill form with payment data
        $('#modal_assessment_id').val($(this).data('assessment-id')).trigger('change');
        $('#modal_tax_assessment_id').val($(this).data('tax-assessment-id'));
        $('#modal_amount').val($(this).data('amount'));
        $('#modal_payment_date').val($(this).data('payment-date'));
        $('#modal_payment_method').val($(this).data('payment-method'));
        $('#modal_receipt_number').val($(this).data('receipt-number'));
        $('#modal_received_by').val($(this).data('received-by'));
        $('#modal_notes').val($(this).data('notes'));
        
        // Show modal
        $('#paymentModal').modal('show');
    });

    // Reset form when modal is closed
    $('#paymentModal').on('hidden.bs.modal', function () {
        $('#edit_id').val('');
        $('#paymentModalLabel').text('<?php echo translate('add_new_payment'); ?>');
        $(this).find('form')[0].reset();
        $('#modal_assessment_id').val('').trigger('change');
    });

    // Trigger change event on page load if editing
    <?php if (isset($editPayment['assessment_id'])): ?>
        $('#modal_assessment_id').val('<?php echo $editPayment['assessment_id']; ?>').trigger('change');
        $('#modal_tax_assessment_id').val('<?php echo sanitize($editPayment['tax_assessment_id']); ?>');
        $('#modal_amount').val('<?php echo number_format($editPayment['amount'], 2, '.', ''); ?>');
        $('#modal_payment_date').val('<?php echo sanitize($editPayment['payment_date']); ?>');
        $('#modal_payment_method').val('<?php echo sanitize($editPayment['payment_method']); ?>');
        $('#modal_receipt_number').val('<?php echo sanitize($editPayment['receipt_number']); ?>');
        $('#modal_received_by').val('<?php echo sanitize($editPayment['received_by']); ?>');
        $('#modal_notes').val('<?php echo sanitize($editPayment['notes']); ?>');
        $('#paymentModal').modal('show');
    <?php endif; ?>
});
</script>
</body>
</html>