<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_messages'][] = translate('unauthorized_access');
    redirect('index.php');
}

// Handle language change
if (isset($_POST['lang']) && in_array($_POST['lang'], ['en', 'am'])) {
    $_SESSION['lang'] = $_POST['lang'];
    $redirectUrl = 'rooms.php';
    if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0) {
        $redirectUrl .= '?edit=' . (int)$_GET['edit'];
    } elseif (isset($_GET['building_id']) && is_numeric($_GET['building_id']) && $_GET['building_id'] > 0) {
        $redirectUrl .= '?building_id=' . (int)$_GET['building_id'];
        if (isset($_GET['is_occupied']) && in_array($_GET['is_occupied'], ['0', '1'])) {
            $redirectUrl .= '&is_occupied=' . (int)$_GET['is_occupied'];
        }
    }
    redirect($redirectUrl);
}

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_messages'][] = "Database connection failed: " . sanitize($e->getMessage());
    redirect('index.php');
}

// Set role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

// Fetch room data for editing
$editRoom = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE room_id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editRoom = $result->fetch_assoc();
        $stmt->close();
        if (!$editRoom) {
            $_SESSION['error_messages'][] = translate("Room not found.");
            redirect('rooms.php');
        }
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = "Error fetching room: " . sanitize($e->getMessage());
        redirect('rooms.php');
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
        $_SESSION['error_messages'][] = "Error fetching notifications: " . sanitize($e->getMessage());
    }
}

$pageTitle = translate('rooms');
?>
<!DOCTYPE html>
<html lang="<?php echo sanitize($lang); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tax Management System - <?php echo sanitize($pageTitle); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css">
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
        body, input, select, textarea, .datatable {
            font-family: 'Noto Sans Ethiopic', 'Source Sans Pro', sans-serif;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Main Header -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="<?php echo translate('toggle_sidebar'); ?>"><i class="fas fa-bars"></i></a>
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
        <a href="index.php" class="brand-link">
            <img src="../assets/images/logo.jpg" alt="Tax System Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo $lang === 'am' ? 'የግብር አስተዳደር' : 'Tax Management'; ?></span>
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
                            <a href="rooms.php" class="nav-link active">
                                <i class="nav-icon fas fa-door-open"></i>
                                <p><?php echo translate('rooms'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="room_types.php" class="nav-link">
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
                            <a href="payments.php" class="nav-link">
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

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo translate('room_management'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('rooms'); ?></li>
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
                    <!-- Room Form -->
                    <div class="col-md-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo isset($_GET['edit']) ? translate('edit_room') : translate('add_new_room'); ?></h3>
                            </div>
                            <form method="post" action="process_room.php" id="roomForm">
                                <?php if (isset($_GET['edit'])): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo (int)$_GET['edit']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="building_id"><?php echo translate('building'); ?>*</label>
                                        <select class="form-control select2" id="building_id" name="building_id" required>
                                            <option value=""><?php echo translate('select_building'); ?></option>
                                            <?php
                                            try {
                                                $stmt = $conn->prepare("SELECT b.building_id, b.building_name, p.property_name 
                                                                       FROM buildings b
                                                                       JOIN properties p ON b.property_id = p.property_id
                                                                       ORDER BY p.property_name, b.building_name");
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()):
                                                    $selected = (isset($editRoom['building_id']) && $editRoom['building_id'] == $row['building_id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo (int)$row['building_id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?>
                                                </option>
                                            <?php endwhile; 
                                                $stmt->close();
                                            } catch (Exception $e) {
                                                $_SESSION['error_messages'][] = "Error fetching buildings: " . sanitize($e->getMessage());
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="type_id"><?php echo translate('room_type'); ?>*</label>
                                        <select class="form-control select2" id="type_id" name="type_id" required>
                                            <option value=""><?php echo translate('select_type'); ?></option>
                                            <?php
                                            try {
                                                $stmt = $conn->prepare("SELECT type_id, type_name FROM room_types ORDER BY type_name");
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()):
                                                    $selected = (isset($editRoom['type_id']) && $editRoom['type_id'] == $row['type_id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo (int)$row['type_id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo sanitize($row['type_name']); ?>
                                                </option>
                                            <?php endwhile; 
                                                $stmt->close();
                                            } catch (Exception $e) {
                                                $_SESSION['error_messages'][] = "Error fetching room types: " . sanitize($e->getMessage());
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="room_number"><?php echo translate('room_number'); ?>*</label>
                                        <input type="text" class="form-control" id="room_number" name="room_number" required maxlength="50"
                                               value="<?php echo isset($editRoom['room_number']) ? sanitize($editRoom['room_number']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="floor_number"><?php echo translate('floor_number'); ?>*</label>
                                        <input type="number" class="form-control" id="floor_number" name="floor_number" required min="-10" max="100"
                                               value="<?php echo isset($editRoom['floor_number']) ? (int)$editRoom['floor_number'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="area"><?php echo translate('area'); ?> (sqm)*</label>
                                        <input type="number" step="0.01" class="form-control" id="area" name="area" required min="0"
                                               value="<?php echo isset($editRoom['area']) ? number_format((float)$editRoom['area'], 2, '.', '') : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="monthly_rent"><?php echo translate('monthly_rent'); ?>*</label>
                                        <input type="number" step="0.01" class="form-control" id="monthly_rent" name="monthly_rent" required min="0"
                                               value="<?php echo isset($editRoom['monthly_rent']) ? number_format((float)$editRoom['monthly_rent'], 2, '.', '') : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="is_occupied" name="is_occupied" 
                                                   <?php echo isset($editRoom['is_occupied']) && $editRoom['is_occupied'] ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="is_occupied"><?php echo translate('currently_occupied'); ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? translate('update') : translate('submit'); ?></button>
                                    <?php if (isset($_GET['edit'])): ?>
                                        <a href="rooms.php" class="btn btn-default"><?php echo translate('cancel'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Rooms Table -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_rooms'); ?></h3>
                                <div class="card-tools">
                                    <form method="get" class="form-inline">
                                        <div class="input-group input-group-sm">
                                            <select class="form-control select2" name="building_id" onchange="this.form.submit()">
                                                <option value=""><?php echo translate('all_buildings'); ?></option>
                                                <?php
                                                try {
                                                    $stmt = $conn->prepare("SELECT b.building_id, b.building_name, p.property_name 
                                                                           FROM buildings b
                                                                           JOIN properties p ON b.property_id = p.property_id
                                                                           ORDER BY p.property_name, b.building_name");
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    $selectedBuildingId = (isset($_GET['building_id']) && is_numeric($_GET['building_id']) && $_GET['building_id'] > 0) ? (int)$_GET['building_id'] : '';
                                                    while ($row = $result->fetch_assoc()):
                                                        $selected = ($selectedBuildingId == $row['building_id']) ? 'selected' : '';
                                                ?>
                                                    <option value="<?php echo (int)$row['building_id']; ?>" <?php echo $selected; ?>>
                                                        <?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?>
                                                    </option>
                                                <?php endwhile; 
                                                    $stmt->close();
                                                } catch (Exception $e) {
                                                    $_SESSION['error_messages'][] = "Error fetching buildings: " . sanitize($e->getMessage());
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="input-group input-group-sm ml-2">
                                            <select class="form-control" name="is_occupied" onchange="this.form.submit()">
                                                <option value=""><?php echo translate('all_rooms'); ?></option>
                                                <option value="1" <?php echo (isset($_GET['is_occupied']) && $_GET['is_occupied'] === '1') ? 'selected' : ''; ?>><?php echo translate('occupied'); ?></option>
                                                <option value="0" <?php echo (isset($_GET['is_occupied']) && $_GET['is_occupied'] === '0') ? 'selected' : ''; ?>><?php echo translate('vacant'); ?></option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('id'); ?></th>
                                            <th><?php echo translate('room_number'); ?></th>
                                            <th><?php echo translate('building_property'); ?></th>
                                            <th><?php echo translate('floor'); ?></th>
                                            <th><?php echo translate('type'); ?></th>
                                            <th><?php echo translate('area'); ?></th>
                                            <th><?php echo translate('rent'); ?></th>
                                            <th><?php echo translate('status'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $sql = "SELECT r.*, rt.type_name, b.building_name, p.property_name 
                                                    FROM rooms r
                                                    JOIN room_types rt ON r.type_id = rt.type_id
                                                    JOIN buildings b ON r.building_id = b.building_id
                                                    JOIN properties p ON b.property_id = p.property_id
                                                    WHERE 1=1";
                                            $params = [];
                                            $types = '';
                                            
                                            if (isset($_GET['building_id']) && is_numeric($_GET['building_id']) && $_GET['building_id'] > 0) {
                                                $sql .= " AND r.building_id = ?";
                                                $params[] = (int)$_GET['building_id'];
                                                $types .= 'i';
                                            }
                                            
                                            if (isset($_GET['is_occupied']) && in_array($_GET['is_occupied'], ['0', '1'])) {
                                                $sql .= " AND r.is_occupied = ?";
                                                $params[] = (int)$_GET['is_occupied'];
                                                $types .= 'i';
                                            }
                                            
                                            $sql .= " ORDER BY p.property_name, b.building_name, r.floor_number, r.room_number";
                                            
                                            $stmt = $conn->prepare($sql);
                                            if (!empty($params)) {
                                                $stmt->bind_param($types, ...$params);
                                            }
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            while ($row = $result->fetch_assoc()):
                                                $statusClass = $row['is_occupied'] ? 'badge-danger' : 'badge-success';
                                                $statusText = $row['is_occupied'] ? translate('occupied') : translate('vacant');
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['room_id']; ?></td>
                                                <td><?php echo sanitize($row['room_number']); ?></td>
                                                <td><?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?></td>
                                                <td><?php echo (int)$row['floor_number']; ?></td>
                                                <td><?php echo sanitize($row['type_name']); ?></td>
                                                <td><?php echo number_format((float)$row['area'], 2); ?> sqm</td>
                                                <td><?php echo formatCurrency($row['monthly_rent']); ?></td>
                                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                <td>
                                                    <a href="rooms.php?edit=<?php echo (int)$row['room_id']; ?>" class="btn btn-sm btn-primary" title="<?php echo translate('edit'); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="process_room.php?delete=<?php echo (int)$row['room_id']; ?>" class="btn btn-sm btn-danger" title="<?php echo translate('delete'); ?>" onclick="return confirm('<?php echo translate('confirm_delete_room'); ?>');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="rent_contracts.php?room=<?php echo (int)$row['room_id']; ?>" class="btn btn-sm btn-info" title="<?php echo translate('view_contracts'); ?>">
                                                        <i class="fas fa-file-contract"></i>
                                                    </a>
                                                    <a href="rental_tax.php?room=<?php echo (int)$row['room_id']; ?>" class="btn btn-sm btn-warning" title="<?php echo translate('tax_assessment'); ?>">
                                                        <i class="fas fa-file-invoice-dollar"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; 
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'][] = "Error fetching rooms: " . sanitize($e->getMessage());
                                        }
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
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
        "dom": 'Bfrtip',
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        "language": {
            <?php if ($lang === 'am'): ?>
                "lengthMenu": "በገፅ ላይ _MENU_ መዝገቦችን አሳይ",
                "zeroRecords": "ምንም መዝገቦች አልተገኙም",
                "info": "ከ _TOTAL_ መዝገቦች ውስጥ _START_ እስከ _END_ ታይቷል",
                "infoEmpty": "ምንም መዝገቦች የሉም",
                "infoFiltered": "(ከ _MAX_ መዝገቦች ተጣርቷል)",
                "search": "ፈልግ:",
                "paginate": {
                    "first": "መጀመሪያ",
                    "last": "መጨረሻ",
                    "next": "ቀጣይ",
                    "previous": "ቀዳሚ"
                },
                "buttons": {
                    "copy": "ቅዳ",
                    "csv": "CSV አውርድ",
                    "excel": "Excel አውርድ",
                    "pdf": "PDF አውርድ",
                    "print": "አትም"
                }
            <?php endif; ?>
        }
    });
    
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Form submission confirmation
    $('#roomForm').on('submit', function(e) {
        <?php if ($lang === 'am'): ?>
            return confirm('ይህን ቅጽ ማስገባት ወይም ማዘመን እንደሚፈልጉ እርግጠኛ ነዎት?');
        <?php else: ?>
            return confirm('Are you sure you want to submit or update this form?');
        <?php endif; ?>
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>