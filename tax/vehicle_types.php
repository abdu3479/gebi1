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
    handleVehicleTypeFormSubmission($conn);
    redirect('vehicle_types.php');
}

// Fetch vehicle types
$vehicleTypes = fetchVehicleTypes($conn);

// HTML Template
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= translate('vehicle_types_management') ?> | Tax System</title>
    
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
                            <a href="vehicle_tax.php" class="nav-link">
                                <i class="nav-icon fas fa-car"></i>
                                <p><?= translate('vehicle_tax') ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="vehicle_types.php" class="nav-link active">
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
                        <h1><?= translate('vehicle_types_management') ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?= translate('home') ?></a></li>
                            <li class="breadcrumb-item active"><?= translate('vehicle_types') ?></li>
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
                                <h3 class="card-title"><?= translate('vehicle_types_list') ?></h3>
                                <div class="card-tools">
                                    <?php if ($isAdmin || $isOfficer): ?>
                                        <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#vehicleTypeModal" data-mode="create">
                                            <i class="fas fa-plus"></i> <?= translate('add_vehicle_type') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="vehicleTypesTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th><?= translate('id') ?></th>
                                            <th><?= translate('name') ?></th>
                                            <th><?= translate('description') ?></th>
                                            <th><?= translate('tax_rate_id') ?></th>
                                            <th><?= translate('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vehicleTypes as $type): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($type['id']) ?></td>
                                                <td><?= htmlspecialchars($type['name']) ?></td>
                                                <td><?= htmlspecialchars($type['description'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($type['tax_rate_id'] ?? 'N/A') ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-primary edit-vehicle-type" 
                                                                data-id="<?= htmlspecialchars($type['id']) ?>"
                                                                data-vehicle-type="<?= htmlspecialchars(json_encode($type), ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger delete-vehicle-type" 
                                                                data-id="<?= htmlspecialchars($type['id']) ?>">
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

    <!-- Vehicle Type Modal -->
    <div class="modal fade" id="vehicleTypeModal" tabindex="-1" role="dialog" aria-labelledby="vehicleTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h4 class="modal-title" id="vehicleTypeModalLabel"><?= translate('add_vehicle_type') ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="vehicleTypeForm" method="post" action="vehicle_types.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="vehicleTypeId">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name"><?= translate('name') ?>*</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="description"><?= translate('description') ?></label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_rate_id"><?= translate('tax_rate_id') ?></label>
                                    <input type="number" class="form-control" id="tax_rate_id" name="tax_rate_id">
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
                    <p><?= translate('confirm_delete_vehicle_type') ?></p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-light" data-dismiss="modal"><?= translate('cancel') ?></button>
                    <form id="deleteForm" method="post" action="vehicle_types.php">
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
    $('#vehicleTypesTable').DataTable({
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

    // Initialize Select2 (if any)
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Handle modal show for create/edit
    $('#vehicleTypeModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var mode = button.data('mode');
        var modal = $(this);
        
        if (mode === 'edit') {
            var vehicleType = JSON.parse(button.data('vehicleType'));
            modal.find('.modal-title').text('<?= translate('edit_vehicle_type') ?>');
            modal.find('#formAction').val('update');
            modal.find('#vehicleTypeId').val(vehicleType.id);
            
            // Populate form fields
            modal.find('#name').val(vehicleType.name);
            modal.find('#description').val(vehicleType.description);
            modal.find('#tax_rate_id').val(vehicleType.tax_rate_id);
        } else {
            modal.find('.modal-title').text('<?= translate('add_vehicle_type') ?>');
            modal.find('#formAction').val('create');
            modal.find('#vehicleTypeForm')[0].reset();
        }
    });

    // Handle delete button
    $('.delete-vehicle-type').click(function() {
        var id = $(this).data('id');
        $('#deleteId').val(id);
        $('#deleteModal').modal('show');
    });

    // Form validation
    $('#vehicleTypeForm').submit(function(e) {
        if ($('#name').val().length < 1) {
            e.preventDefault();
            alert('<?= translate('name_required') ?>');
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
function handleVehicleTypeFormSubmission($conn) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_messages'][] = translate('csrf_validation_failed');
        return;
    }

    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    try {
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'tax_rate_id' => filter_input(INPUT_POST, 'tax_rate_id', FILTER_VALIDATE_INT) ?: null
        ];

        if ($action === 'create') {
            $query = "INSERT INTO vehicle_types (name, description, tax_rate_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssi', $data['name'], $data['description'], $data['tax_rate_id']);
            $stmt->execute();
            $_SESSION['success_messages'][] = translate('vehicle_type_created');
        } elseif ($action === 'update') {
            $query = "UPDATE vehicle_types SET name = ?, description = ?, tax_rate_id = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssii', $data['name'], $data['description'], $data['tax_rate_id'], $id);
            $stmt->execute();
            $_SESSION['success_messages'][] = translate('vehicle_type_updated');
        } elseif ($action === 'delete') {
            $query = "DELETE FROM vehicle_types WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['success_messages'][] = translate('vehicle_type_deleted');
        }
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('operation_failed') . ': ' . $e->getMessage();
    }
}

function fetchVehicleTypes($conn) {
    try {
        // Simple query without join, using tax_rate_id directly
        $query = "SELECT id, name, description, tax_rate_id FROM vehicle_types ORDER BY name";
        $result = $conn->query($query);
        $vehicleTypes = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vehicleTypes[] = $row;
            }
        } else {
            throw new Exception("Query failed: " . $conn->error);
        }
        return $vehicleTypes;
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('error_fetching_vehicle_types') . ': ' . $e->getMessage();
        return [];
    }
}
?>