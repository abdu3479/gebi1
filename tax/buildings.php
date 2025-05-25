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
    $_SESSION['error_messages'][] = translate('unauthorized_access');
    redirect('index.php');
}

// Handle language change
if (isset($_POST['lang']) && in_array($_POST['lang'], ['en', 'am'])) {
    $_SESSION['lang'] = $_POST['lang'];
    redirect('buildings.php' . (isset($_GET['edit']) ? '?edit=' . $_GET['edit'] : ''));
}

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

// Fetch building data for editing
$editBuilding = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT b.*, p.owner_id FROM buildings b JOIN properties p ON b.property_id = p.property_id WHERE b.building_id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $result = $stmt->get_result();
    $editBuilding = $result->fetch_assoc();
    $stmt->close();
    if (!$editBuilding) {
        $_SESSION['error_messages'][] = translate('building_not_found');
        redirect('buildings.php');
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

$pageTitle = translate('buildings');
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
        .modal-lg {
            max-width: 800px;
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
            <span class="brand-text font-weight-light"><?php echo $lang === 'am' ? 'የግብር አስተዳደር' : 'PMTS'; ?></span>
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
                            <a href="buildings.php" class="nav-link active">
                                <i class="nav-icon fas fa-building"></i>
                                <p><?php echo translate('buildings'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rooms.php" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
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

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo translate('buildings'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('buildings'); ?></li>
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
                                <h3 class="card-title"><?php echo translate('list_of_buildings'); ?></h3>
                                <div class="card-tools">
                                    <?php if (isAdmin() || isOfficer()): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#buildingModal" data-mode="create">
                                            <i class="fas fa-plus"></i> <?php echo translate('add_new_building'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (isset($_GET['property']) && is_numeric($_GET['property'])): ?>
                                        <a href="buildings.php" class="btn btn-sm btn-secondary"><?php echo translate('show_all_buildings'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('id'); ?></th>
                                            <th><?php echo translate('building_name'); ?></th>
                                            <th><?php echo translate('property'); ?></th>
                                            <th><?php echo translate('owner'); ?></th>
                                            <th><?php echo translate('floor_count'); ?></th>
                                            <th><?php echo translate('room_count'); ?></th>
                                            <th><?php echo translate('building_area'); ?></th>
                                            <th><?php echo translate('construction_type'); ?></th>
                                            <th><?php echo translate('construction_year'); ?></th>
                                            <th><?php echo translate('building_type'); ?></th>
                                            <th><?php echo translate('address'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $sql = "SELECT b.*, p.property_name, o.full_name 
                                                    FROM buildings b 
                                                    JOIN properties p ON b.property_id = p.property_id 
                                                    JOIN owners o ON p.owner_id = o.id";
                                            $params = [];
                                            $types = '';
                                            
                                            if (isset($_GET['property']) && is_numeric($_GET['property'])) {
                                                $sql .= " WHERE b.property_id = ?";
                                                $params[] = (int)$_GET['property'];
                                                $types .= 'i';
                                            }
                                            
                                            $sql .= " ORDER BY b.building_name";
                                            $stmt = $conn->prepare($sql);
                                            if (!empty($params)) {
                                                $stmt->bind_param($types, ...$params);
                                            }
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()):
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['building_id']; ?></td>
                                                <td><?php echo sanitize($row['building_name']); ?></td>
                                                <td><?php echo sanitize($row['property_name']); ?></td>
                                                <td><?php echo sanitize($row['full_name']); ?></td>
                                                <td><?php echo (int)$row['floor_count']; ?></td>
                                                <td><?php echo $row['room_count'] !== null ? (int)$row['room_count'] : '-'; ?></td>
                                                <td><?php echo number_format((float)$row['building_area'], 2); ?></td>
                                                <td><?php echo translate($row['construction_type']); ?></td>
                                                <td><?php echo $row['construction_year'] ? (int)$row['construction_year'] : '-'; ?></td>
                                                <td><?php echo translate($row['building_type']); ?></td>
                                                <td><?php echo $row['address'] ? sanitize($row['address']) : '-'; ?></td>
                                                <td>
                                                    <?php if (isAdmin() || isOfficer()): ?>
                                                        <button class="btn btn-sm btn-primary edit-building" 
                                                                data-mode="edit"
                                                                data-id="<?php echo (int)$row['building_id']; ?>"
                                                                data-property-id="<?php echo (int)$row['property_id']; ?>"
                                                                data-building-name="<?php echo htmlspecialchars(sanitize($row['building_name'])); ?>"
                                                                data-floor-count="<?php echo (int)$row['floor_count']; ?>"
                                                                data-room-count="<?php echo (int)$row['room_count']; ?>"
                                                                data-building-area="<?php echo (float)$row['building_area']; ?>"
                                                                data-construction-type="<?php echo sanitize($row['construction_type']); ?>"
                                                                data-construction-year="<?php echo (int)$row['construction_year']; ?>"
                                                                data-description="<?php echo htmlspecialchars(sanitize($row['description'])); ?>"
                                                                data-address="<?php echo htmlspecialchars(sanitize($row['address'])); ?>"
                                                                data-building-type="<?php echo sanitize($row['building_type']); ?>"
                                                                data-toggle="modal"
                                                                data-target="#buildingModal"
                                                                title="<?php echo translate('edit'); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger delete-building" 
                                                                data-id="<?php echo (int)$row['building_id']; ?>"
                                                                data-toggle="modal"
                                                                data-target="#deleteModal"
                                                                title="<?php echo translate('delete'); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <a href="rooms.php?building=<?php echo (int)$row['building_id']; ?>" 
                                                       class="btn btn-sm btn-info" 
                                                       title="<?php echo translate('view_rooms'); ?>">
                                                        <i class="fas fa-door-open"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; 
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'][] = "Error fetching buildings: " . sanitize($e->getMessage());
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Building Modal -->
                <div class="modal fade" id="buildingModal" tabindex="-1" role="dialog" aria-labelledby="buildingModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="buildingModalLabel"><?php echo translate('add_new_building'); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo translate('close'); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <form method="post" action="process_building.php" id="buildingForm">
                                <div class="modal-body">
                                    <input type="hidden" name="edit_id" id="edit_id">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="property_id"><?php echo translate('property'); ?>*</label>
                                                <select class="form-control select2-modal" id="property_id" name="property_id" required>
                                                    <option value=""><?php echo translate('select_property'); ?></option>
                                                    <?php
                                                    try {
                                                        $stmt = $conn->prepare("SELECT p.property_id, p.property_name, o.full_name 
                                                                               FROM properties p 
                                                                               JOIN owners o ON p.owner_id = o.id 
                                                                               ORDER BY p.property_name");
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while ($row = $result->fetch_assoc()):
                                                            $selected = (isset($editBuilding['property_id']) && $editBuilding['property_id'] == $row['property_id']) ? 'selected' : '';
                                                    ?>
                                                        <option value="<?php echo (int)$row['property_id']; ?>" <?php echo $selected; ?>>
                                                            <?php echo sanitize($row['property_name']); ?> (<?php echo translate('owner'); ?>: <?php echo sanitize($row['full_name']); ?>)
                                                        </option>
                                                    <?php endwhile; 
                                                        $stmt->close();
                                                    } catch (Exception $e) {
                                                        $_SESSION['error_messages'][] = "Error fetching properties: " . sanitize($e->getMessage());
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="building_name"><?php echo translate('building_name'); ?>*</label>
                                                <input type="text" class="form-control" id="building_name" name="building_name" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="floor_count"><?php echo translate('floor_count'); ?>*</label>
                                                <input type="number" class="form-control" id="floor_count" name="floor_count" required min="1">
                                            </div>
                                            <div class="form-group">
                                                <label for="room_count"><?php echo translate('room_count'); ?></label>
                                                <input type="number" class="form-control" id="room_count" name="room_count" min="0">
                                            </div>
                                            <div class="form-group">
                                                <label for="building_area"><?php echo translate('building_area'); ?>*</label>
                                                <input type="number" step="0.01" class="form-control" id="building_area" name="building_area" required min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="construction_type"><?php echo translate('construction_type'); ?>*</label>
                                                <select class="form-control select2-modal" id="construction_type" name="construction_type" required>
                                                    <option value=""><?php echo translate('select_construction_type'); ?></option>
                                                    <?php
                                                    $construction_types = ['wood', 'concrete', 'mixed'];
                                                    foreach ($construction_types as $type):
                                                    ?>
                                                        <option value="<?php echo $type; ?>"><?php echo translate($type); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="construction_year"><?php echo translate('construction_year'); ?></label>
                                                <input type="number" class="form-control" id="construction_year" name="construction_year" min="1900" max="<?php echo date('Y'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="description"><?php echo translate('description'); ?></label>
                                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="address"><?php echo translate('address'); ?></label>
                                                <input type="text" class="form-control" id="address" name="address">
                                            </div>
                                            <div class="form-group">
                                                <label for="building_type"><?php echo translate('building_type'); ?>*</label>
                                                <select class="form-control select2-modal" id="building_type" name="building_type" required>
                                                    <option value=""><?php echo translate('select_building_type'); ?></option>
                                                    <?php
                                                    $building_types = ['Residential', 'Commercial', 'Mixed', 'Other'];
                                                    foreach ($building_types as $type):
                                                    ?>
                                                        <option value="<?php echo $type; ?>"><?php echo translate($type); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('close'); ?></button>
                                    <button type="submit" class="btn btn-primary" id="submitBuilding"><?php echo translate('submit'); ?></button>
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
                                <?php echo translate('confirm_delete_building'); ?>
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
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
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

    // Initialize Select2 for modal
    function initializeModalSelect2() {
        $('.select2-modal').select2({
            theme: 'bootstrap4',
            dropdownParent: $('#buildingModal')
        });
    }

    // Call Select2 initialization when modal is shown
    $('#buildingModal').on('shown.bs.modal', function () {
        initializeModalSelect2();
    });

    // Reset form and Select2 when modal is hidden
    $('#buildingModal').on('hidden.bs.modal', function () {
        $('#buildingForm')[0].reset();
        $('.select2-modal').val('').trigger('change');
        $('#edit_id').val('');
        $('#submitBuilding').text('<?php echo translate('submit'); ?>');
        $('#buildingModalLabel').text('<?php echo translate('add_new_building'); ?>');
    });

    // Handle modal show for create/edit
    $('#buildingModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var mode = button.data('mode');
        var modal = $(this);

        if (mode === 'create') {
            modal.find('.modal-title').text('<?php echo translate('add_new_building'); ?>');
            modal.find('#submitBuilding').text('<?php echo translate('submit'); ?>');
            modal.find('#buildingForm')[0].reset();
            modal.find('#property_id').val('').trigger('change');
            modal.find('#construction_type').val('').trigger('change');
            modal.find('#building_type').val('').trigger('change');
            modal.find('#edit_id').val('');
            <?php if (isset($_GET['property']) && is_numeric($_GET['property'])): ?>
                modal.find('#property_id').val('<?php echo (int)$_GET['property']; ?>').trigger('change');
            <?php endif; ?>
        } else if (mode === 'edit') {
            modal.find('.modal-title').text('<?php echo translate('edit_building'); ?>');
            modal.find('#submitBuilding').text('<?php echo translate('update'); ?>');

            var buildingId = button.data('id');
            $('#edit_id').val(buildingId);
            $('#property_id').val(button.data('property-id')).trigger('change');
            $('#building_name').val(button.data('building-name'));
            $('#floor_count').val(button.data('floor-count'));
            $('#room_count').val(button.data('room-count'));
            $('#building_area').val(button.data('building-area'));
            $('#construction_type').val(button.data('construction-type')).trigger('change');
            $('#construction_year').val(button.data('construction-year'));
            $('#description').val(button.data('description'));
            $('#address').val(button.data('address'));
            $('#building_type').val(button.data('building-type')).trigger('change');
        }
    });

    // Handle delete button click
    $('.delete-building').on('click', function() {
        var buildingId = $(this).data('id');
        $('#deleteConfirm').attr('href', 'process_building.php?delete=' + buildingId + '&csrf_token=<?php echo $_SESSION['csrf_token']; ?>');
    });

    // Auto-open modal for edit mode on page load
    <?php if (isset($_GET['edit']) && is_numeric($_GET['edit'])): ?>
    $(document).ready(function() {
        $('#buildingModal').modal('show');
        $('#buildingModal').trigger('show.bs.modal', {
            relatedTarget: {
                dataset: {
                    mode: 'edit',
                    id: <?php echo (int)$_GET['edit']; ?>,
                    propertyId: <?php echo (int)$editBuilding['property_id']; ?>,
                    buildingName: '<?php echo htmlspecialchars(sanitize($editBuilding['building_name'])); ?>',
                    floorCount: <?php echo (int)$editBuilding['floor_count']; ?>,
                    roomCount: <?php echo (int)$editBuilding['room_count']; ?>,
                    buildingArea: <?php echo (float)$editBuilding['building_area']; ?>,
                    constructionType: '<?php echo sanitize($editBuilding['construction_type']); ?>',
                    constructionYear: <?php echo (int)$editBuilding['construction_year']; ?>,
                    description: '<?php echo htmlspecialchars(sanitize($editBuilding['description'])); ?>',
                    address: '<?php echo htmlspecialchars(sanitize($editBuilding['address'])); ?>',
                    buildingType: '<?php echo sanitize($editBuilding['building_type']); ?>'
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