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

// Handle language change
if (isset($_POST['lang']) && in_array($_POST['lang'], ['en', 'am'])) {
    $_SESSION['lang'] = $_POST['lang'];
    $redirectUrl = 'room_types.php';
    if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0) {
        $redirectUrl .= '?edit=' . (int)$_GET['edit'];
    }
    redirect($redirectUrl);
}

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_messages'][] = translate('unauthorized_access');
    redirect('dashboard.php');
}

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_messages'][] = translate('database_error') . ": " . sanitize($e->getMessage());
    redirect('dashboard.php');
}

// Set role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_messages'][] = translate('invalid_csrf_token');
        redirect('room_types.php' . (isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? '?edit=' . (int)$_POST['edit_id'] : ''));
    }

    $type_name = filter_input(INPUT_POST, 'type_name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?: null;
    $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    // Validate inputs
    if (empty($type_name) || strlen(trim($type_name)) > 100) {
        $_SESSION['error_messages'][] = translate('invalid_type_name');
        redirect('room_types.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    // Check for duplicate type_name (except when editing the same type)
    try {
        $stmt = $conn->prepare("SELECT type_id FROM room_types WHERE type_name = ? AND type_id != ?");
        $stmt->bind_param("si", $type_name, $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            $_SESSION['error_messages'][] = translate('duplicate_type_name');
            $stmt->close();
            redirect('room_types.php' . ($edit_id ? '?edit=' . $edit_id : ''));
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_checking_duplicate') . ': ' . sanitize($e->getMessage());
        redirect('room_types.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }

    try {
        if ($edit_id) {
            // Update existing type
            $stmt = $conn->prepare("UPDATE room_types SET type_name = ?, description = ? WHERE type_id = ?");
            $stmt->bind_param("ssi", $type_name, $description, $edit_id);
            $message = translate('type_updated');
            $action = "Updated room type ID: $edit_id";
        } else {
            // Add new type
            $stmt = $conn->prepare("INSERT INTO room_types (type_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $type_name, $description);
            $message = translate('type_added');
            $action = "Created room type: $type_name";
        }

        if ($stmt->execute()) {
            $new_id = $edit_id ?: $conn->insert_id;
            logAction($conn, $_SESSION['user_id'], $action, $new_id, 'room_types');
            $_SESSION['success_messages'][] = $message;
            $stmt->close();
            redirect('room_types.php');
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_processing_type') . ': ' . sanitize($e->getMessage());
        redirect('room_types.php' . ($edit_id ? '?edit=' . $edit_id : ''));
    }
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $delete_id = (int)$_GET['delete'];
    if (!$delete_id) {
        $_SESSION['error_messages'][] = translate('invalid_delete_request');
        redirect('room_types.php');
    }

    // Check if type is used in rooms
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM rooms WHERE type_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['count'] > 0) {
            $_SESSION['error_messages'][] = translate('type_has_rooms');
            redirect('room_types.php');
        }

        // Delete room type
        $stmt = $conn->prepare("DELETE FROM room_types WHERE type_id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            logAction($conn, $_SESSION['user_id'], "Deleted room type ID: $delete_id", $delete_id, 'room_types');
            $_SESSION['success_messages'][] = translate('type_deleted');
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
        redirect('room_types.php');
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_deleting_type') . ': ' . sanitize($e->getMessage());
        redirect('room_types.php');
    }
}

// Get type data for editing
$editType = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT * FROM room_types WHERE type_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $editType = $result->fetch_assoc();
        $stmt->close();
        if (!$editType) {
            $_SESSION['error_messages'][] = translate('type_not_found');
            redirect('room_types.php');
        }
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_fetching_type') . ': ' . sanitize($e->getMessage());
        redirect('room_types.php');
    }
}

// Get notifications count
$notificationsCount = 0;
if (!$is_viewer) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM tax_assessments WHERE status = ?");
        $status = 'pending';
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $notificationsCount = (int)$row['count'];
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_fetching_notifications') . ': ' . sanitize($e->getMessage());
    }
}

$pageTitle = translate('room_types');
?>
<!DOCTYPE html>
<html lang="<?php echo sanitize($lang); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo translate('tax_management_system') . ' - ' . sanitize($pageTitle); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <style>
        .main-header .navbar-nav .nav-item .nav-link {
            padding: 0.5rem 1rem;
        }
        body, input, textarea, .datatable {
            font-family: 'Noto Sans Ethiopic', 'Source Sans Pro', sans-serif;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .badge {
            font-size: 85%;
            font-weight: 600;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Main Header -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="<?php echo translate('toggle_sidebar'); ?>">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <!-- Language Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" aria-label="<?php echo translate('language'); ?>">
                    <i class="fas fa-globe"></i> <?php echo $lang === 'am' ? 'አማርኛ' : 'English'; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <form method="post" action="">
                        <button type="submit" name="lang" value="en" class="dropdown-item">English</button>
                        <button type="submit" name="lang" value="am" class="dropdown-item">አማርኛ</button>
                    </form>
                </div>
            </li>
            <!-- Notifications Dropdown -->
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
            <!-- User Menu Dropdown -->
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

    <!-- Main Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="dashboard.php" class="brand-link">
            <img src="../assets/images/logo.jpg" alt="<?php echo translate('tax_system_logo'); ?>" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo $lang === 'am' ? 'የግብር አስተዳደር' : 'Tax Management'; ?></span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="../assets/images/logo.jpg" class="img-circle elevation-2" alt="<?php echo translate('user_image'); ?>">
                </div>
                <div class="info">
                    <a href="profile.php" class="d-block"><?php echo sanitize($_SESSION['username']); ?></a>
                    <small><?php echo ucfirst(translate($_SESSION['role'])); ?></small>
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
                            <a href="vacancy_reports.php" class="nav-link">
                                <i class="nav-icon fas fa-chart-pie"></i>
                                <p><?php echo translate('vacancy_reports'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="assessments.php" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                <p><?php echo translate('tax_assessments'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rental_tax.php" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p><?php echo translate('rental_tax_assessment'); ?></p>
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

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo translate('room_types'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('room_types'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <!-- Display Session Messages -->
                <?php displayMessages(); ?>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_room_types'); ?></h3>
                                <div class="card-tools">
                                    <?php if (isAdmin() || isOfficer()): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#typeModal" data-mode="create">
                                            <i class="fas fa-plus"></i> <?php echo translate('add_new_room_type'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
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
                                        try {
                                            $stmt = $conn->prepare("SELECT rt.*, COUNT(r.room_id) AS room_count 
                                                                    FROM room_types rt
                                                                    LEFT JOIN rooms r ON rt.type_id = r.type_id
                                                                    GROUP BY rt.type_id
                                                                    ORDER BY rt.type_name");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo (int)$row['type_id']; ?></td>
                                                    <td><?php echo sanitize($row['type_name']); ?></td>
                                                    <td><?php echo sanitize($row['description'] ?? ''); ?></td>
                                                    <td><?php echo (int)$row['room_count']; ?></td>
                                                    <td>
                                                        <?php if (isAdmin() || isOfficer()): ?>
                                                            <button class="btn btn-sm btn-primary edit-type" 
                                                                    data-mode="edit"
                                                                    data-id="<?php echo (int)$row['type_id']; ?>"
                                                                    data-type-name="<?php echo htmlspecialchars(sanitize($row['type_name'])); ?>"
                                                                    data-description="<?php echo htmlspecialchars(sanitize($row['description'] ?? '')); ?>"
                                                                    data-toggle="modal"
                                                                    data-target="#typeModal"
                                                                    title="<?php echo translate('edit'); ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-type" 
                                                                    data-id="<?php echo (int)$row['type_id']; ?>"
                                                                    data-toggle="modal"
                                                                    data-target="#deleteModal"
                                                                    title="<?php echo translate('delete'); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile;
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'][] = translate('error_fetching_types') . ': ' . sanitize($e->getMessage());
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Room Type Modal -->
                <div class="modal fade" id="typeModal" tabindex="-1" role="dialog" aria-labelledby="typeModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="typeModalLabel"><?php echo translate('add_new_room_type'); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo translate('close'); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <form method="post" id="typeForm">
                                <div class="modal-body">
                                    <input type="hidden" name="edit_id" id="edit_id">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="form-group">
                                        <label for="type_name"><?php echo translate('type_name'); ?>*</label>
                                        <input type="text" class="form-control" id="type_name" name="type_name" required maxlength="100">
                                    </div>
                                    <div class="form-group">
                                        <label for="description"><?php echo translate('description'); ?></label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('close'); ?></button>
                                    <button type="submit" class="btn btn-primary" id="submitType"><?php echo translate('submit'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel"><?php echo translate('confirm_delete'); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo translate('close'); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <?php echo translate('confirm_delete_type'); ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('cancel'); ?></button>
                                <a href="#" id="deleteConfirm" class="btn btn-danger"><?php echo translate('delete'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong><?php echo $lang === 'am' ? 'የቅጂ መብት © ' . date('Y') . ' የግብር አስተዳደር ስርዓት' : 'Copyright © ' . date('Y') . ' Tax Management System'; ?>.</strong>
        <?php echo $lang === 'am' ? 'ሁሉም መብቶች የተጠበቁ ናቸው።' : 'All rights reserved.'; ?>
        <div class="float-right d-none d-sm-inline-block">
            <b><?php echo $lang === 'am' ? 'እትም' : 'Version'; ?></b> 1.0.0
        </div>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(function () {
    // Initialize DataTable with export buttons
    $('.datatable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "buttons": ['copy', 'csv', 'excel', 'pdf', 'print'],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "language": {
            <?php if ($lang === 'am'): ?>
                "decimal": "",
                "emptyTable": "ምንም ውሂብ የለም",
                "info": "_TOTAL_ ከ _START_ እስከ _END_ ያሳያል",
                "infoEmpty": "0 ከ 0 እስከ 0 ያሳያል",
                "infoFiltered": "(_MAX_ ከጠቅላላ ተጣርቷል)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "_MENU_ ግቤቶችን አሳይ",
                "loadingRecords": "በመጫን ላይ...",
                "processing": "በማቀነባበር ላይ...",
                "search": "ፈልግ:",
                "zeroRecords": "ምንም ተዛማጅ መዝገቦች አልተገኙም",
                "paginate": {
                    "first": "መጀመሪያ",
                    "last": "መጨረሻ",
                    "next": "ቀጣይ",
                    "previous": "ቀዳሚ"
                },
                "buttons": {
                    "copy": "ቅጂ",
                    "csv": "CSV",
                    "excel": "Excel",
                    "pdf": "PDF",
                    "print": "አትም"
                }
            <?php endif; ?>
        }
    }).buttons().container().appendTo('.card-tools');

    // Reset form when modal is hidden
    $('#typeModal').on('hidden.bs.modal', function () {
        $('#typeForm')[0].reset();
        $('#edit_id').val('');
        $('#submitType').text('<?php echo translate('submit'); ?>');
        $('#typeModalLabel').text('<?php echo translate('add_new_room_type'); ?>');
    });

    // Handle modal show for create/edit
    $('#typeModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var mode = button.data('mode');
        var modal = $(this);

        if (mode === 'create') {
            modal.find('.modal-title').text('<?php echo translate('add_new_room_type'); ?>');
            modal.find('#submitType').text('<?php echo translate('submit'); ?>');
            modal.find('#typeForm')[0].reset();
            modal.find('#edit_id').val('');
        } else if (mode === 'edit') {
            modal.find('.modal-title').text('<?php echo translate('edit_room_type'); ?>');
            modal.find('#submitType').text('<?php echo translate('update'); ?>');
            modal.find('#edit_id').val(button.data('id'));
            modal.find('#type_name').val(button.data('type-name'));
            modal.find('#description').val(button.data('description'));
        }
    });

    // Handle delete button click
    $('.delete-type').on('click', function() {
        var typeId = $(this).data('id');
        $('#deleteConfirm').attr('href', 'room_types.php?delete=' + typeId + '&csrf_token=<?php echo $_SESSION['csrf_token']; ?>');
    });

    // Client-side form validation
    $('#typeForm').on('submit', function(e) {
        var typeName = $('#type_name').val().trim();
        if (!typeName || typeName.length > 100) {
            e.preventDefault();
            alert('<?php echo translate('invalid_type_name'); ?>');
            $('#type_name').focus();
            return false;
        }
        return true;
    });

    // Auto-open modal for edit mode on page load
    <?php if (isset($_GET['edit']) && $editType): ?>
    $(document).ready(function() {
        $('#typeModal').modal('show');
        $('#typeModal').trigger('show.bs.modal', {
            relatedTarget: {
                dataset: {
                    mode: 'edit',
                    id: <?php echo (int)$editType['type_id']; ?>,
                    typeName: '<?php echo htmlspecialchars(sanitize($editType['type_name'])); ?>',
                    description: '<?php echo htmlspecialchars(sanitize($editType['description'] ?? '')); ?>'
                }
            }
        });
    });
    <?php endif; ?>
});
</script>
</body>
</html>
<?php $conn->close(); ?>