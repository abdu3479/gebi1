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
    handleTaxpayerFormSubmission($conn);
    redirect('taxpayers.php');
}

// Fetch taxpayers data
$taxpayers = fetchTaxpayers($conn);

// HTML Template
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= translate('taxpayer_management') ?> | Tax System</title>
    
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
                            <a href="taxpayers.php" class="nav-link active">
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
                        <h1><?= translate('taxpayer_management') ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?= translate('home') ?></a></li>
                            <li class="breadcrumb-item active"><?= translate('taxpayers') ?></li>
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
                                <h3 class="card-title"><?= translate('taxpayers_list') ?></h3>
                                <div class="card-tools">
                                    <?php if ($isAdmin || $isOfficer): ?>
                                        <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#taxpayerModal" data-mode="create">
                                            <i class="fas fa-plus"></i> <?= translate('add_taxpayer') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="taxpayersTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>TIN</th>
                                            <th><?= translate('business_name') ?></th>
                                            <th><?= translate('owner') ?></th>
                                            <th><?= translate('category') ?></th>
                                            <th><?= translate('type') ?></th>
                                            <th><?= translate('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($taxpayers as $taxpayer): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($taxpayer['tin']) ?></td>
                                                <td><?= htmlspecialchars($taxpayer['business_name']) ?></td>
                                                <td><?= htmlspecialchars($taxpayer['owner_name']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $taxpayer['taxpayer_category'] === 'A' ? 'danger' : ($taxpayer['taxpayer_category'] === 'B' ? 'warning' : 'success') ?>">
                                                        <?= $taxpayer['taxpayer_category'] ?>
                                                    </span>
                                                </td>
                                                <td><?= translate($taxpayer['registration_type']) ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-primary edit-taxpayer" 
                                                            data-id="<?= $taxpayer['id'] ?>"
                                                            data-taxpayer="<?= htmlspecialchars(json_encode($taxpayer), ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger delete-taxpayer" 
                                                            data-id="<?= $taxpayer['id'] ?>">
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

    <!-- Taxpayer Modal -->
    <div class="modal fade" id="taxpayerModal" tabindex="-1" role="dialog" aria-labelledby="taxpayerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h4 class="modal-title" id="taxpayerModalLabel"><?= translate('add_taxpayer') ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="taxpayerForm" method="post" action="taxpayers.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="taxpayerId">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tin"><?= translate('tin') ?>*</label>
                                    <input type="text" class="form-control" id="tin" name="tin" required>
                                </div>
                                <div class="form-group">
                                    <label for="business_name"><?= translate('business_name') ?>*</label>
                                    <input type="text" class="form-control" id="business_name" name="business_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="owner_name"><?= translate('owner_name') ?></label>
                                    <input type="text" class="form-control" id="owner_name" name="owner_name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_type"><?= translate('registration_type') ?>*</label>
                                    <select class="form-control select2" id="registration_type" name="registration_type" required>
                                        <option value="sole_proprietorship"><?= translate('sole_proprietorship') ?></option>
                                        <option value="partnership"><?= translate('partnership') ?></option>
                                        <option value="plc"><?= translate('plc') ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="taxpayer_category"><?= translate('taxpayer_category') ?>*</label>
                                    <select class="form-control select2" id="taxpayer_category" name="taxpayer_category" required>
                                        <option value="A">A (15%)</option>
                                        <option value="B">B (0%)</option>
                                        <option value="C">C (0%)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone"><?= translate('phone') ?></label>
                                    <input type="text" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email"><?= translate('email') ?></label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="address"><?= translate('address') ?></label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
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
                    <p><?= translate('confirm_delete_taxpayer') ?></p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-light" data-dismiss="modal"><?= translate('cancel') ?></button>
                    <form id="deleteForm" method="post" action="taxpayers.php">
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
    <?php include 'includes/footer.php'; ?>
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
    $('#taxpayersTable').DataTable({
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
    $('#taxpayerModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var mode = button.data('mode');
        var modal = $(this);
        
        if (mode === 'edit') {
            var taxpayer = JSON.parse(button.data('taxpayer'));
            modal.find('.modal-title').text('<?= translate('edit_taxpayer') ?>');
            modal.find('#formAction').val('update');
            modal.find('#taxpayerId').val(taxpayer.id);
            
            // Populate form fields
            Object.keys(taxpayer).forEach(key => {
                if (key !== 'id') {
                    modal.find(`#${key}`).val(taxpayer[key]).trigger('change');
                }
            });
        } else {
            modal.find('.modal-title').text('<?= translate('add_taxpayer') ?>');
            modal.find('#formAction').val('create');
            modal.find('#taxpayerForm')[0].reset();
            modal.find('.select2').trigger('change');
        }
    });

    // Handle delete button
    $('.delete-taxpayer').click(function() {
        var taxpayerId = $(this).data('id');
        $('#deleteId').val(taxpayerId);
        $('#deleteModal').modal('show');
    });

    // Form validation
    $('#taxpayerForm').submit(function(e) {
        if ($('#tin').val().length < 3) {
            e.preventDefault();
            alert('<?= translate('tin_too_short') ?>');
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
function handleTaxpayerFormSubmission($conn) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_messages'][] = translate('csrf_validation_failed');
        return;
    }

    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    try {
        $data = [
            'tin' => sanitize($_POST['tin'] ?? ''),
            'business_name' => sanitize($_POST['business_name'] ?? ''),
            'owner_name' => sanitize($_POST['owner_name'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'email' => filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: null,
            'registration_type' => in_array($_POST['registration_type'], ['sole_proprietorship', 'partnership', 'plc']) 
                ? $_POST['registration_type'] 
                : 'sole_proprietorship',
            'taxpayer_category' => in_array($_POST['taxpayer_category'], ['A', 'B', 'C']) 
                ? $_POST['taxpayer_category'] 
                : 'C'
        ];

        if ($action === 'create') {
            $query = "INSERT INTO taxpayers 
                (tin, business_name, owner_name, address, phone, email, registration_type, taxpayer_category) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                'ssssssss',
                $data['tin'],
                $data['business_name'],
                $data['owner_name'],
                $data['address'],
                $data['phone'],
                $data['email'],
                $data['registration_type'],
                $data['taxpayer_category']
            );
            $stmt->execute();
            $_SESSION['success_messages'][] = translate('taxpayer_created');
        } elseif ($action === 'update') {
            $query = "UPDATE taxpayers SET 
                tin = ?, business_name = ?, owner_name = ?, address = ?, phone = ?, email = ?, 
                registration_type = ?, taxpayer_category = ? 
                WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                'ssssssssi',
                $data['tin'],
                $data['business_name'],
                $data['owner_name'],
                $data['address'],
                $data['phone'],
                $data['email'],
                $data['registration_type'],
                $data['taxpayer_category'],
                $id
            );
            $stmt->execute();
            $_SESSION['success_messages'][] = translate('taxpayer_updated');
        } elseif ($action === 'delete') {
            $query = "DELETE FROM taxpayers WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['success_messages'][] = translate('taxpayer_deleted');
        }
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('operation_failed');
    }
}

function fetchTaxpayers($conn) {
    try {
        $query = "SELECT * FROM taxpayers ORDER BY business_name";
        $result = $conn->query($query);
        $taxpayers = [];
        while ($row = $result->fetch_assoc()) {
            $taxpayers[] = $row;
        }
        return $taxpayers;
    } catch (Exception $e) {
        logError($e);
        $_SESSION['error_messages'][] = translate('error_fetching_taxpayers');
        return [];
    }
}
?>