<?php
session_start();
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Ensure user is authenticated
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_messages'] = [translate('unauthorized_access')];
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
    $_SESSION['error_messages'] = ["Database connection failed: " . sanitize($e->getMessage())];
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
        $stmt = $conn->prepare("SELECT r.*, t.id AS taxpayer_id, t.business_name, t.tin, t.taxpayer_category 
                                FROM rooms r 
                                LEFT JOIN taxpayers t ON r.taxpayer_id = t.id 
                                WHERE r.id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $editRoom = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$editRoom) {
            $_SESSION['error_messages'] = [translate('room_not_found')];
            redirect('rooms.php');
        }
    } catch (Exception $e) {
        $_SESSION['error_messages'] = ["Error fetching room: " . sanitize($e->getMessage())];
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
        $notificationsCount = (int)$stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'] = ["Error fetching notifications: " . sanitize($e->getMessage())];
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
        .main-header .navbar-nav .nav-item .nav-link { padding: 0.5rem 1rem; }
        .select2-container--default .select2-selection--single { height: calc(2.25rem + 2px); padding: 0.375rem 0.75rem; }
        body, input, select, textarea, .datatable { font-family: 'Noto Sans Ethiopic', 'Source Sans Pro', sans-serif; }
        .modal-lg { max-width: 800px; }
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
                            <a href="taxpayers.php" class="nav-link">
                                <i class="nav-icon fas fa-user-tie"></i>
                                <p><?php echo translate('taxpayers'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="goods.php" class="nav-link">
                                <i class="nav-icon fas fa-box"></i>
                                <p><?php echo translate('goods'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="transactions.php" class="nav-link">
                                <i class="nav-icon fas fa-exchange-alt"></i>
                                <p><?php echo translate('transactions'); ?></p>
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
                            <a href="tax_report.php" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p><?php echo translate('annual_tax_report'); ?></p>
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
                    <!-- Rooms Table -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_rooms'); ?></h3>
                                <div class="card-tools">
                                    <?php if (isAdmin() || isOfficer()): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#roomModal" data-mode="create">
                                            <i class="fas fa-plus"></i> <?php echo translate('add_room'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <form method="get" class="form-inline ml-2">
                                        <div class="input-group input-group-sm">
                                            <select class="form-control select2" name="building_id" onchange="this.form.submit()">
                                                <option value=""><?php echo translate('all_buildings'); ?></option>
                                                <?php
                                                try {
                                                    $stmt = $conn->prepare("SELECT b.id, b.name, b.location 
                                                                           FROM buildings b
                                                                           ORDER BY b.name");
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    $selectedBuildingId = (isset($_GET['building_id']) && is_numeric($_GET['building_id']) && $_GET['building_id'] > 0) ? (int)$_GET['building_id'] : '';
                                                    while ($row = $result->fetch_assoc()):
                                                        $selected = ($selectedBuildingId == $row['id']) ? 'selected' : '';
                                                ?>
                                                    <option value="<?php echo (int)$row['id']; ?>" <?php echo $selected; ?>>
                                                        <?php echo sanitize($row['name']); ?> - <?php echo sanitize($row['location']); ?>
                                                    </option>
                                                <?php endwhile; 
                                                    $stmt->close();
                                                } catch (Exception $e) {
                                                    $_SESSION['error_messages'] = ["Error fetching buildings: " . sanitize($e->getMessage())];
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
                                            <th><?php echo translate('building'); ?></th>
                                            <th><?php echo translate('taxpayer'); ?></th>
                                            <th><?php echo translate('floor'); ?></th>
                                            <th><?php echo translate('type'); ?></th>
                                            <th><?php echo translate('area'); ?></th>
                                            <th><?php echo translate('rent'); ?></th>
                                            <th><?php echo translate('tax'); ?></th>
                                            <th><?php echo translate('status'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $sql = "SELECT r.id, r.room_number, r.floor_number, r.area, r.monthly_rent, r.tax_rate, r.tax_amount, r.is_occupied, 
                                                    b.name AS building_name, rt.name AS type_name, 
                                                    t.business_name, t.tin, t.taxpayer_category
                                                    FROM rooms r
                                                    JOIN buildings b ON r.building_id = b.id
                                                    JOIN room_types rt ON r.type_id = rt.id
                                                    LEFT JOIN taxpayers t ON r.taxpayer_id = t.id
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
                                            
                                            $sql .= " ORDER BY b.name, r.floor_number, r.room_number";
                                            
                                            $stmt = $conn->prepare($sql);
                                            if (!empty($params)) {
                                                $stmt->bind_param($types, ...$params);
                                            }
                                            $stmt->execute()->get_result();
                                            
                                            while ($row = $result->fetch_assoc()):
                                                $statusClass = $row['is_occupied'] ? 'badge-danger' : 'badge-success';
                                                $statusText = $row['is_occupied'] ? translate('occupied') : translate('vacant');
                                                $category = $row['taxpayer_category'] ? ($lang === 'am' ? ($row['taxpayer_category'] === 'A' ? 'ሀ' : ($row['taxpayer_category'] === 'B' ? 'ለ' : 'ሐ')) : $row['taxpayer_category']) : '';
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['id']; ?></td>
                                                <td><?php echo sanitize($row['room_number']); ?></td>
                                                <td><?php echo sanitize($row['building_name']); ?></td>
                                                <td><?php echo $row['business_name'] ? sanitize($row['business_name']) . ' (' . sanitize($row['tin']) . ', ' . $category . ')' : '-'; ?></td>
                                                <td><?php echo (int)$row['floor_number']; ?></td>
                                                <td><?php echo sanitize($row['type_name']); ?></td>
                                                <td><?php echo number_format((float)$row['area'], 2); ?> m²</td>
                                                <td><?php echo formatCurrency($row['monthly_rent']); ?></td>
                                                <td><?php echo formatCurrency($row['tax_amount']); ?></td>
                                                <td><span class="badge <?php echo $statusClass; ?>">><?php echo $statusText; ?></span></td>
                                                <td>
                                                    <?php if (isAdmin() || isOfficer()): ?>
                                                        <button class="btn btn-sm btn-primary edit-room" 
                                                                data-mode="edit"
                                                                data-id="<?php echo (int)$row['id']; ?>"
                                                                data-building-id="<?php echo (int)$row['building_id']; ?>"
                                                                data-type-id="<?php echo (int)$row['type_id']; ?>"
                                                                data-taxpayer-id="<?php echo (int)$row['taxpayer_id'] ?? ''; ?>"
                                                                data-room-number="<?php echo htmlspecialchars($row['room_number']); ?>"
                                                                data-floor-number="<?php echo (int)$row['floor_number']; ?>"
                                                                data-area="<?php echo number_format((float)$row['area'], 2); ?>"
                                                                data-monthly-rent="<?php echo number_format((float)$row['monthly_rent'], 2); ?>"
                                                                data-tax-rate="<?php echo number_format((float)$row['tax_rate'], 2); ?>"
                                                                data-is-occupied="<?php echo (int)$row['is_occupied']; ?>"
                                                                data-toggle="modal"
                                                                data-target="#roomModal"
                                                                title="<?php echo translate('edit'); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger delete-room" 
                                                                data-id="<?php echo (int)$row['id']; ?>"
                                                                data-toggle="modal"
                                                                data-target="#deleteModal"
                                                                title="<?php echo translate('delete'); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($row['taxpayer_id']): ?>
                                                        <a href="transactions.php?room_id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-success" title="<?php echo translate('transactions'); ?>">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="rental_tax.php?room_id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo translate('tax_assessment'); ?>">
                                                        <i class="fas fa-file-invoice-dollar"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; 
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'] = ["Error fetching rooms: " . sanitize($e->getMessage())];
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Room Modal -->
                <div class="modal fade" id="roomModal" tabindex="-1" role="dialog" aria-labelledby="roomModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="roomModalLabel"><?php echo translate('add_room'); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo translate('close'); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <form method="post" action="process_rooms.php" id="roomForm">
                                <div class="modal-body">
                                    <input type="hidden" name="edit_id" id="edit_id">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="building_id"><?php echo translate('building'); ?>*</label>
                                                <select class="form-control select2-modal" id="building_id" name="building_id" required>
                                                    <option value=""><?php echo translate('select_building'); ?></option>
                                                    <?php
                                                    try {
                                                        $stmt = $conn->prepare("SELECT id, name FROM buildings ORDER BY name");
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while ($row = $result->fetch_assoc()):
                                                            $selected = ($editRoom && $editRoom['building_id'] == $row['id']) ? 'selected' : '';
                                                    ?>
                                                        <option value="<?php echo (int)$row['id']; ?>" <?php echo $selected; ?>>
                                                            <?php echo sanitize($row['name']); ?>
                                                        </option>
                                                    <?php endwhile; 
                                                        $stmt->close();
                                                    } catch (Exception $e) {
                                                        $_SESSION['error_messages'] = ["Error fetching buildings: " . sanitize($e->getMessage())];
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="type_id"><?php echo translate('room_type'); ?>*</label>
                                                <select class="form-control select2-modal" id="type_id" name="type_id" required>
                                                    <option value=""><?php echo translate('select_room_type'); ?></option>
                                                    <?php
                                                    try {
                                                        $stmt = $conn->prepare("SELECT id, name FROM room_types ORDER BY name");
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while ($row = $result->fetch_assoc()):
                                                            $selected = ($editRoom && $editRoom['type_id'] == $row['id']) ? 'selected' : '';
                                                    ?>
                                                        <option value="<?php echo (int)$row['id']; ?>" <?php echo $selected; ?>>
                                                            <?php echo sanitize($row['name']); ?>
                                                        </option>
                                                    <?php endwhile; 
                                                        $stmt->close();
                                                    } catch (Exception $e) {
                                                        $_SESSION['error_messages'] = ["Error fetching room types: " . sanitize($e->getMessage())];
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="taxpayer_id"><?php echo translate('taxpayer'); ?></label>
                                                <select class="form-control select2-modal" id="taxpayer_id" name="taxpayer_id">
                                                    <option value=""><?php echo translate('select_taxpayer'); ?></option>
                                                    <?php
                                                    try {
                                                        $stmt = $conn->prepare("SELECT id, business_name, tin, taxpayer_category FROM taxpayers ORDER BY business_name");
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while ($row = $result->fetch_assoc()):
                                                            $category = $lang === 'am' ? ($row['taxpayer_category'] === 'A' ? 'ሀ' : ($row['taxpayer_category'] === 'B' ? 'ለ' : 'ሐ')) : $row['taxpayer_category'];
                                                            $selected = ($editRoom && $editRoom['taxpayer_id'] == $row['id']) ? 'selected' : '';
                                                    ?>
                                                        <option value="<?php echo (int)$row['id']; ?>" <?php echo $selected; ?>>
                                                            <?php echo sanitize($row['business_name']) . ' (TIN: ' . sanitize($row['tin']) . ', ' . translate('category') . ': ' . $category . ')'; ?>
                                                        </option>
                                                    <?php endwhile; 
                                                        $stmt->close();
                                                    } catch (Exception $e) {
                                                        $_SESSION['error_messages'] = ["Error fetching taxpayers: " . sanitize($e->getMessage())];
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="room_number"><?php echo translate('room_number'); ?>*</label>
                                                <input type="text" class="form-control" id="room_number" name="room_number" required maxlength="50">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="floor_number"><?php echo translate('floor_number'); ?>*</label>
                                                <input type="number" class="form-control" id="floor_number" name="floor_number" required min="-10" max="100">
                                            </div>
                                            <div class="form-group">
                                                <label for="area"><?php echo translate('area'); ?> (m²)*</label>
                                                <input type="number" step="0.01" class="form-control" id="area" name="area" required min="0">
                                            </div>
                                            <div class="form-group">
                                                <label for="monthly_rent"><?php echo translate('monthly_rent'); ?>*</label>
                                                <input type="number" step="0.01" class="form-control" id="monthly_rent" name="monthly_rent" required min="0">
                                            </div>
                                            <div class="form-group">
                                                <label for="tax_rate"><?php echo translate('tax_rate'); ?> (%)*</label>
                                                <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" required min="0">
                                            </div>
                                            <div class="form-group">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="is_occupied" name="is_occupied">
                                                    <label class="custom-control-label" for="is_occupied"><?php echo translate('is_occupied'); ?></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('cancel'); ?></button>
                                    <button type="submit" class="btn btn-primary" id="submitRoom"><?php echo translate('submit'); ?></button>
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
                                <?php echo translate('confirm_delete_room'); ?>
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

    <!-- Footer -->
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
$(function() {
    // Initialize DataTable
    $('.datatable').DataTable({
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>Btr<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            <?php if ($lang === 'am'): ?>
                decimal: "",
                emptyTable: "ምንም ውሂብ የለም",
                info: "_TOTAL_ ከ _START_ እስከ _END_ ያሳይ",
                infoEmpty: "0 ከ 0 እስከ 0 ያሳይ",
                infoFiltered: "(_MAX_ ከሁሉ ተጣርቷል)",
                lengthMenu: "_MENU_ ግ�ቶችን አሳይ",
                loadingRecords: "በመጫን ላይ...",
                processing: "በማቀነባበር ላይ...",
                search: "ፈልግ:",
                zeroRecords: "ምንም ተዛማጅ መዝገቦች አልተገኙም",
                paginate: {
                    first: "መጀመሪያ",
                    last: "መጨረሻ",
                    next: "ቀጣይ",
                    previous: "ቀዳሚ"
                },
                buttons: {
                    copy: "ቅጂ",
                    csv: "CSV",
                    excel: "Excel",
                    pdf: "PDF",
                    print: "አትም"
                }
            <?php endif; ?>
        }
    }).buttons().container().appendTo('.card-tools');

    // Initialize Select2
    $('.select2').select2({ theme: 'bootstrap4' });
    function initializeModalSelect2() {
        $('.select2-modal').select2({
            theme: 'bootstrap4',
            dropdownParent: $('#roomModal')
        });
    }

    $('#roomModal').on('shown.bs.modal', initializeModalSelect2);

    // Reset modal form
    $('#roomModal').on('hidden.bs.modal', function() {
        $('#roomForm')[0].reset();
        $('.select2-modal').val('').trigger('change');
        $('#edit_id').val('');
        $('#is_occupied').prop('checked', false);
        $('#submitRoom').text('<?php echo translate('submit'); ?>');
        $('#roomModalLabel').text('<?php echo translate('add_room'); ?>');
    });

    // Handle modal show
    $('#roomModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var mode = button.data('mode');
        var modal = $(this);

        if (mode === 'create') {
            modal.find('.modal-title').text('<?php echo translate('add_room'); ?>');
            modal.find('#submitRoom').text('<?php echo translate('submit'); ?>');
            modal.find('#roomForm')[0].reset();
            modal.find('#building_id').val('').trigger('change');
            modal.find('#type_id').val('').trigger('change');
            modal.find('#taxpayer_id').val('').trigger('change');
            modal.find('#edit_id').val('');
            modal.find('#is_occupied').prop('checked', false);
            <?php if (isset($_GET['building_id']) && is_numeric($_GET['building_id']) && $_GET['building_id'] > 0): ?>
                modal.find('#building_id').val('<?php echo (int)$_GET['building_id']; ?>').trigger('change');
            <?php endif; ?>
        } else if (mode === 'edit') {
            modal.find('.modal-title').text('<?php echo translate('edit_room'); ?>');
            modal.find('#submitRoom').text('<?php echo translate('update'); ?>');
            var roomId = button.data('id');
            $('#edit_id').val(roomId);
            $('#building_id').val(button.data('building-id')).trigger('change');
            $('#type_id').val(button.data('type-id')).trigger('change');
            $('#taxpayer_id').val(button.data('taxpayer-id')).trigger('change');
            $('#room_number').val(button.data('room-number'));
            $('#floor_number').val(button.data('floor-number'));
            $('#area').val(button.data('area'));
            $('#monthly_rent').val(button.data('monthly-rent'));
            $('#tax_rate').val(button.data('tax-rate'));
            $('#is_occupied').prop('checked', button.data('is-occupied') == 1);
        }
    });

    // Handle delete
    $('.delete-room').on('click', function() {
        var roomId = $(this).data('id');
        $('#deleteConfirm').attr('href', 'process_rooms.php?delete=' + roomId + '&csrf_token=<?php echo $_SESSION['csrf_token']; ?>');
    });

    // Auto-open modal for edit
    <?php if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0): ?>
    $(document).ready(function() {
        $('#roomModal').modal('show');
        $('#roomModal').trigger('show.bs.modal', {
            relatedTarget: {
                dataset: {
                    mode: 'edit',
                    id: <?php echo (int)$_GET['edit']; ?>,
                    buildingId: <?php echo (int)$editRoom['building_id']; ?>,
                    typeId: <?php echo (int)$editRoom['type_id']; ?>,
                    taxpayerId: <?php echo (int)($editRoom['taxpayer_id'] ?? ''); ?>,
                    roomNumber: '<?php echo htmlspecialchars(sanitize($editRoom['room_number'])); ?>',
                    floorNumber: <?php echo (int)$editRoom['floor_number']; ?>,
                    area: <?php echo number_format((float)$editRoom['area'], 2, '.', ''); ?>,
                    monthlyRent: <?php echo number_format((float)$editRoom['monthly_rent'], 2, '.', ''); ?>,
                    taxRate: <?php echo number_format((float)$editRoom['tax_rate'], 2, '.', ''); ?>,
                    isOccupied: <?php echo (int)$editRoom['is_occupied']; ?>
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