<?php
session_start();
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Authentication and Authorization
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_messages'][] = translate('unauthorized_access');
    redirect('index.php');
}

// CSRF Protection
if (empty($_SESSION['csrf_token']) || (time() - ($_SESSION['csrf_token_time'] ?? 0)) > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// Initialize variables
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$isAdmin = isAdmin();
$isOfficer = isOfficer();

// Database Connection
try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Failed to connect to database");
    }
} catch (Exception $e) {
    logError($e);
    $_SESSION['error_messages'][] = translate('database_error');
    redirect('index.php');
}

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleVehicleTaxFormSubmission($conn);
    redirect('vehicle_tax.php');
}

// Fetch vehicle taxes and types
$vehicleTaxes = fetchVehicleTaxes($conn);
$vehicleTypes = fetchVehicleTypes($conn);

// HTML Template
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= translate('vehicle_tax_management') ?> | Tax System</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE 3 (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <!-- DataTables (CDN) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">
    <!-- Select2 (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed sidebar-collapse">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-dark">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="index.php" class="nav-link"><?= translate('home') ?></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> <?= translate('logout') ?>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-globe"></i> <?= strtoupper($lang) ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="?lang=en" class="dropdown-item">English</a>
                    <a href="?lang=fr" class="dropdown-item">Français</a>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="index.php" class="brand-link">
            <span class="brand-text font-weight-light">Tax System</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p><?= translate('dashboard') ?></p>
                        </a>
                    </li>
                    <?php if ($isAdmin || $isOfficer): ?>
                        <li class="nav-item">
                            <a href="taxpayers.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p><?= translate('taxpayers') ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="vehicle_tax.php" class="nav-link active">
                                <i class="nav-icon fas fa-car"></i>
                                <p><?= translate('vehicle_tax') ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="vehicle_types.php" class="nav-link">
                                <i class="nav-icon fas fa-list"></i>
                                <p><?= translate('vehicle_types') ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p><?= translate('reports') ?></p>
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a href="settings.php" class="nav-link">
                                <i class="nav-icon fas fa-cog"></i>
                                <p><?= translate('settings') ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?= translate('vehicle_tax_management') ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?= translate('home') ?></a></li>
                            <li class="breadcrumb-item active"><?= translate('vehicle_tax') ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <?php
                // Display success messages
                if (isset($_SESSION['success_messages']) && !empty($_SESSION['success_messages'])) {
                    foreach ($_SESSION['success_messages'] as $message) {
                        ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-check"></i> Success</h5>
                            <?= htmlspecialchars($message) ?>
                        </div>
                        <?php
                    }
                    unset($_SESSION['success_messages']);
                }

                // Display error messages
                if (isset($_SESSION['error_messages']) && !empty($_SESSION['error_messages'])) {
                    foreach ($_SESSION['error_messages'] as $message) {
                        ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Error</h5>
                            <?= htmlspecialchars($message) ?>
                        </div>
                        <?php
                    }
                    unset($_SESSION['error_messages']);
                }
                ?>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><?= translate('vehicle_taxes_list') ?></h3>
                                <div class="card-tools">
                                    <?php if ($isAdmin || $isOfficer): ?>
                                        <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#vehicleTaxModal" data-mode="create">
                                            <i class="fas fa-plus"></i> <?= translate('add_vehicle_tax') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="vehicleTaxesTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th><?= translate('plate_number') ?></th>
                                            <th><?= translate('owner_name') ?></th>
                                            <th><?= translate('vehicle_type') ?></th>
                                            <th><?= translate('tax_year') ?></th>
                                            <th><?= translate('tax_amount') ?></th>
                                            <th><?= translate('payment_status') ?></th>
                                            <th><?= translate('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vehicleTaxes as $tax): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($tax['plate_number']) ?></td>
                                                <td><?= htmlspecialchars($tax['owner_name']) ?></td>
                                                <td><?= htmlspecialchars($tax['vehicle_type_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($tax['tax_year']) ?></td>
                                                <td><?= htmlspecialchars(number_format($tax['tax_amount'], 2)) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $tax['payment_status'] === 'paid' ? 'success' : ($tax['payment_status'] === 'overdue' ? 'danger' : 'warning') ?>">
                                                        <?= translate($tax['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-primary edit-vehicle-tax" 
                                                                data-id="<?= htmlspecialchars($tax['id']) ?>"
                                                                data-vehicle-tax="<?= htmlspecialchars(json_encode($tax), ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger delete-vehicle-tax" 
                                                                data-id="<?= htmlspecialchars($tax['id']) ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Vehicle Tax Modal -->
    <div class="modal fade" id="vehicleTaxModal" tabindex="-1" role="dialog" aria-labelledby="vehicleTaxModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h4 class="modal-title" id="vehicleTaxModalLabel"><?= translate('add_vehicle_tax') ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="vehicleTaxForm" method="post" action="vehicle_tax.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="vehicleTaxId">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="plate_number"><?= translate('plate_number') ?>*</label>
                                    <input type="text" class="form-control" id="plate_number" name="plate_number" required>
                                </div>
                                <div class="form-group">
                                    <label for="owner_name"><?= translate('owner_name') ?>*</label>
                                    <input type="text" class="form-control" id="owner_name" name="owner_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_type_id"><?= translate('vehicle_type') ?>*</label>
                                    <select class="form-control select2" id="vehicle_type_id" name="vehicle_type_id" required>
                                        <option value=""><?= translate('select_vehicle_type') ?></option>
                                        <?php foreach ($vehicleTypes as $type): ?>
                                            <option value="<?= htmlspecialchars($type['id']) ?>"><?= htmlspecialchars($type['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_year"><?= translate('tax_year') ?>*</label>
                                    <input type="number" class="form-control" id="tax_year" name="tax_year" required>
                                </div>
                                <div class="form-group">
                                    <label for="tax_amount"><?= translate('tax_amount') ?>*</label>
                                    <input type="number" step="0.01" class="form-control" id="tax_amount" name="tax_amount" required>
                                </div>
                                <div class="form-group">
                                    <label for="payment_status"><?= translate('payment_status') ?>*</label>
                                    <select class="form-control select2" id="payment_status" name="payment_status" required>
                                        <option value="pending"><?= translate('pending') ?></option>
                                        <option value="paid"><?= translate('paid') ?></option>
                                        <option value="overdue"><?= translate('overdue') ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="engine_number"><?= translate('engine_number') ?></label>
                                    <input type="text" class="form-control" id="engine_number" name="engine_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="chassis_number"><?= translate('chassis_number') ?></label>
                                    <input type="text" class="form-control" id="chassis_number" name="chassis_number">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?= translate('close') ?></button>
                        <button type="submit" class="btn btn-primary"><?= translate('save_changes') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content bg-danger">
                <div class="modal-header">
                    <h4 class="modal-title"><?= translate('confirm_delete') ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?= translate('confirm_delete_vehicle_tax') ?></p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-light" data-dismiss="modal"><?= translate('cancel') ?></button>
                    <form id="deleteForm" method="post" action="vehicle_tax.php">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-outline-light"><?= translate('delete') ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>Copyright © 2025 Tax System.</strong> All rights reserved.
    </footer>
</div>

<!-- jQuery (CDN) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<!-- DataTables (CDN) -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<!-- Select2 (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Custom Script -->
<script>
$(function () {
    // Initialize DataTable
    $('#vehicleTaxesTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/<?= $lang ?>.json"
        }
    });

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Handle modal show for create/edit
    $('#vehicleTaxModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var mode = button.data('mode');
        var modal = $(this);
        
        if (mode === 'edit') {
            var vehicleTax = JSON.parse(button.data('vehicleTax'));
            modal.find('.modal-title').text('<?= translate('edit_vehicle_tax') ?>');
            modal.find('#formAction').val('update');
            modal.find('#vehicleTaxId').val(vehicleTax.id);
            
            // Populate form fields
            modal.find('#plate_number').val(vehicleTax.plate_number);
            modal.find('#owner_name').val(vehicleTax.owner_name);
            modal.find('#vehicle_type_id').val(vehicleTax.vehicle_type_id).trigger('change');
            modal.find('#tax_year').val(vehicleTax.tax_year);
            modal.find('#tax_amount').val(vehicleTax.tax_amount);
            modal.find('#payment_status').val(vehicleTax.payment_status).trigger('change');
            modal.find('#engine_number').val(vehicleTax.engine_number);
            modal.find('#chassis_number').val(vehicleTax.chassis_number);
        } else {
            modal.find('.modal-title').text('<?= translate('add_vehicle_tax') ?>');
            modal.find('#formAction').val('create');
            modal.find('#vehicleTaxForm')[0].reset();
            modal.find('.select2').trigger('change');
        }
    });

    // Handle delete button
    $('.delete-vehicle-tax').click(function() {
        var id = $(this).data('id');
        $('#deleteId').val(id);
        $('#deleteModal').modal('show');
    });

    // Form validation
    $('#vehicleTaxForm').submit(function(e) {
        if ($('#plate_number').val().length < 1) {
            e.preventDefault();
            alert('<?= translate('plate_number_required') ?>');
            return false;
        }
        if ($('#owner_name').val().length < 1) {
            e.preventDefault();
            alert('<?= translate('owner_name_required') ?>');
            return false;
        }
        return true;
    });
});
</script>
</body>
</html>

<?php
// Helper functions
function handleVehicleTaxFormSubmission($conn) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_messages'][] = translate('csrf_validation_failed');
        return;
    }

    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    try {
        $data = [
            'plate_number' => sanitize($_POST['plate_number'] ?? ''),
            'owner_name' => sanitize($_POST['owner_name'] ?? ''),
            'vehicle_type_id' => filter_input(INPUT_POST, 'vehicle_type_id', FILTER_VALIDATE_INT) ?: null,
            'tax_year' => filter_input(INPUT_POST, 'tax_year', FILTER_VALIDATE_INT),
            'tax_amount' => filter_input(INPUT_POST, 'tax_amount', FILTER_VALIDATE_FLOAT) ?: 0.00,
            'payment_status' => in_array($_POST['payment_status'], ['pending', 'paid', 'overdue']) ? $_POST['payment_status'] : 'pending',
            'engine_number' => sanitize($_POST['engine_number'] ?? ''),
            'chassis_number' => sanitize($_POST['chassis_number'] ?? ''),
            'created_by' => $_SESSION['user_id'] ?? 1 // Assuming user_id is stored in session
        ];

        if ($action === 'create') {
            $query = "INSERT INTO vehicle_taxes (plate_number, owner_name, vehicle_type_id, tax_year, tax_amount, payment_status, engine_number, chassis_number, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssisdsssi', $data['plate_number'], $data['owner_name'], $data['vehicle_type_id'], $data['tax_year'], $data['tax_amount'], $data['payment_status'], $data['engine_number'], $data['chassis_number'], $data['created_by']);
            $stmt->execute();
            $_SESSION['success_messages'][] = translate('vehicle_tax_created');
        } elseif ($action === 'update') {
            $query = "UPDATE vehicle_taxes SET plate_number = ?, owner_name = ?, vehicle_type_id = ?, tax_year = ?, tax_amount = ?, payment_status = ?, engine_number = ?, chassis_number = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssisdsssi', $data['plate_number'], $data['owner_name'], $data['vehicle_type_id'], $data['tax_year'], $data['tax_amount'], $data['payment_status'], $data['engine_number'], $data['chassis_number'], $id);
            $stmt->execute();
            $_SESSION['success_messages'][] = translate('vehicle_tax_updated');
        } elseif ($action === 'delete') {
            $query = "DELETE FROM vehicle_taxes WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['success_messages'][] = translate('vehicle_tax_deleted');
        }
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('operation_failed');
    }
}

function fetchVehicleTaxes($conn) {
    try {
        $query = "SELECT t.id, t.plate_number, t.owner_name, t.vehicle_type_id, t.tax_year, t.tax_amount, t.payment_status, t.engine_number, t.chassis_number, vt.name AS vehicle_type_name 
                  FROM vehicle_taxes t 
                  LEFT JOIN vehicle_types vt ON t.vehicle_type_id = vt.id 
                  ORDER BY t.plate_number";
        $result = $conn->query($query);
        $vehicleTaxes = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vehicleTaxes[] = $row;
            }
        }
        return $vehicleTaxes;
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('error_fetching_vehicle_taxes');
        return [];
    }
}

function fetchVehicleTypes($conn) {
    try {
        $query = "SELECT id, name FROM vehicle_types ORDER BY name";
        $result = $conn->query($query);
        $vehicleTypes = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vehicleTypes[] = $row;
            }
        }
        return $vehicleTypes;
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('error_fetching_vehicle_types');
        return [];
    }
}
?>