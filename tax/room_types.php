<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_name = filter_input(INPUT_POST, 'type_name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    // Validate inputs
    if (empty($type_name) || strlen($type_name) > 100) {
        $_SESSION['error_message'] = translate('invalid_type_name');
        redirect('room_types.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Check for duplicate type_name (except when editing the same type)
    $stmt = $conn->prepare("SELECT type_id FROM room_types WHERE type_name = ? AND type_id != ?");
    $stmt->bind_param("si", $type_name, $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        $_SESSION['error_message'] = translate('duplicate_type_name');
        $stmt->close();
        redirect('room_types.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
    $stmt->close();

    try {
        if ($edit_id) {
            // Update existing type
            $stmt = $conn->prepare("UPDATE room_types SET type_name = ?, description = ? WHERE type_id = ?");
            $stmt->bind_param("ssi", $type_name, $description, $edit_id);
            $message = translate('type_updated');
        } else {
            // Add new type
            $stmt = $conn->prepare("INSERT INTO room_types (type_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $type_name, $description);
            $message = translate('type_added');
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = $message;
            $stmt->close();
            redirect('room_types.php');
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = translate('error_processing_type') . ': ' . sanitize($e->getMessage());
        redirect('room_types.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $delete_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if (!$delete_id) {
        $_SESSION['error_message'] = translate('invalid_delete_request');
        redirect('room_types.php');
    }

    // Check if type is used in rooms
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM rooms WHERE type_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row['count'] > 0) {
        $_SESSION['error_message'] = translate('type_has_rooms');
        redirect('room_types.php');
    }

    try {
        $stmt = $conn->prepare("DELETE FROM room_types WHERE type_id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = translate('type_deleted');
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
        redirect('room_types.php');
    } catch (Exception $e) {
        $_SESSION['error_message'] = translate('error_deleting_type') . ': ' . sanitize($e->getMessage());
        redirect('room_types.php');
    }
}

// Get type data for editing
$editType = null;
if (isset($_GET['edit'])) {
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt = $conn->prepare("SELECT * FROM room_types WHERE type_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $editType = $result->fetch_assoc();
        $stmt->close();
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

$pageTitle = translate('room_types');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <style>
        .main-header .navbar-nav .nav-item .nav-link {
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
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
            <img src="https://via.placeholder.com/50x50" alt="Tax System Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Tax Management</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="https://via.placeholder.com/160x160" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="profile.php" class="d-block"><?php echo sanitize($_SESSION['full_name']); ?></a>
                    <small><?php echo ucfirst(sanitize($_SESSION['role'])); ?></small>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
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
                            <a href="rooms.php" class="nav-link">
                                <i class="nav-icon fas fa-door-open"></i>
                                <p><?php echo translate('rooms'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="room_types.php" class="nav-link active">
                                <i class="nav-icon fas fa-tags"></i>
                                <p><?php echo translate('room_types'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rent_contracts.php" class="nav-link">
                                <i class="nav-icon fas fa-file-contract"></i>
                                <p><?php echo translate('rent_contracts'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="assessments.php" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                <p><?php echo translate('tax_assessments'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="payments.php" class="nav-link">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p><?php echo translate('payments'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rental_payments.php" class="nav-link">
                                <i class="nav-icon fas fa-money-check-alt"></i>
                                <p><?php echo translate('rental_payments'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="vacancy_reports.php" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p><?php echo translate('vacancy_reports'); ?></p>
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
                        <h1><?php echo translate('room_types'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('room_types'); ?></li>
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
                                <h3 class="card-title"><?php echo $editType ? translate('edit_room_type') : translate('add_new_room_type'); ?></h3>
                            </div>
                            <form method="post">
                                <?php if ($editType): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo (int)$editType['type_id']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="type_name"><?php echo translate('type_name'); ?></label>
                                        <input type="text" class="form-control" id="type_name" name="type_name" required 
                                               value="<?php echo $editType ? sanitize($editType['type_name']) : ''; ?>" maxlength="100">
                                    </div>
                                    <div class="form-group">
                                        <label for="description"><?php echo translate('description'); ?></label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $editType ? sanitize($editType['description']) : ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo $editType ? translate('update') : translate('submit'); ?></button>
                                    <?php if ($editType): ?>
                                        <a href="room_types.php" class="btn btn-default"><?php echo translate('cancel'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_room_types'); ?></h3>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('id'); ?></th>
                                            <th><?php echo translate('type_name'); ?></th>
                                            <th><?php echo translate('description'); ?></th>
                                            <th><?php echo translate('rooms'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $conn->prepare("SELECT rt.*, COUNT(r.room_id) AS room_count 
                                                                FROM room_types rt
                                                                LEFT JOIN rooms r ON rt.type_id = r.type_id
                                                                GROUP BY rt.type_id
                                                                ORDER BY rt.type_name");
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        while ($row = $result->fetch_assoc()) {
                                            ?>
                                            <tr>
                                                <td><?php echo (int)$row['type_id']; ?></td>
                                                <td><?php echo sanitize($row['type_name']); ?></td>
                                                <td><?php echo sanitize($row['description']); ?></td>
                                                <td><?php echo (int)$row['room_count']; ?></td>
                                                <td>
                                                    <a href="room_types.php?edit=<?php echo (int)$row['type_id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="<?php echo translate('edit'); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="room_types.php?delete=<?php echo (int)$row['type_id']; ?>" 
                                                       class="btn btn-sm btn-danger" title="<?php echo translate('delete'); ?>" 
                                                       onclick="return confirm('<?php echo translate('confirm_delete_type'); ?>');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php }
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
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>
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
});
</script>
</body>
</html>
<?php $conn->close(); ?>