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
    $redirectUrl = 'properties.php';
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $redirectUrl .= '?edit=' . (int)$_GET['edit'];
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

// Fetch property data for editing
$editProperty = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT p.*, pt.name AS type_name FROM properties p LEFT JOIN property_types pt ON p.type_id = pt.id WHERE p.property_id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editProperty = $result->fetch_assoc();
        $stmt->close();
        if (!$editProperty) {
            $_SESSION['error_messages'][] = translate("Property not found.");
            redirect('properties.php');
        }
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = "Error fetching property: " . sanitize($e->getMessage());
        redirect('properties.php');
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

$pageTitle = translate('properties');
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
                            <a href="properties.php" class="nav-link active">
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
                        <h1><?php echo translate('properties'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('properties'); ?></li>
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
                    <!-- Property Form -->
                    <div class="col-md-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo isset($_GET['edit']) ? translate('edit_property') : translate('add_new_property'); ?></h3>
                            </div>
                            <form method="post" action="process_property.php" id="propertyForm">
                                <?php if (isset($_GET['edit'])): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo (int)$_GET['edit']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="owner_id"><?php echo translate('owner'); ?>*</label>
                                        <select class="form-control select2" id="owner_id" name="owner_id" required>
                                            <option value=""><?php echo translate('select_owner'); ?></option>
                                            <?php
                                            try {
                                                $stmt = $conn->prepare("SELECT id, full_name FROM owners ORDER BY full_name");
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()):
                                                    $selected = (isset($editProperty['owner_id']) && $editProperty['owner_id'] == $row['id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo (int)$row['id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo sanitize($row['full_name']); ?>
                                                </option>
                                            <?php endwhile; 
                                                $stmt->close();
                                            } catch (Exception $e) {
                                                $_SESSION['error_messages'][] = "Error fetching owners: " . sanitize($e->getMessage());
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="type_id"><?php echo translate('property_type'); ?>*</label>
                                        <select class="form-control select2" id="type_id" name="type_id" required>
                                            <option value=""><?php echo translate('select_property_type'); ?></option>
                                            <?php
                                            try {
                                                $stmt = $conn->prepare("SELECT id, name FROM property_types ORDER BY name");
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()):
                                                    $selected = (isset($editProperty['type_id']) && $editProperty['type_id'] == $row['id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo (int)$row['id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo sanitize($row['name']); ?>
                                                </option>
                                            <?php endwhile; 
                                                $stmt->close();
                                            } catch (Exception $e) {
                                                $_SESSION['error_messages'][] = "Error fetching property types: " . sanitize($e->getMessage());
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="property_name"><?php echo translate('property_name'); ?>*</label>
                                        <input type="text" class="form-control" id="property_name" name="property_name" required maxlength="100"
                                               value="<?php echo isset($editProperty['property_name']) ? sanitize($editProperty['property_name']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="address"><?php echo translate('address'); ?>*</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($editProperty['address']) ? sanitize($editProperty['address']) : ''; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="area"><?php echo translate('area'); ?>*</label>
                                        <input type="number" step="0.01" class="form-control" id="area" name="area" required min="0"
                                               value="<?php echo isset($editProperty['area']) ? (float)$editProperty['area'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="registration_number"><?php echo translate('registration_number'); ?></label>
                                        <input type="text" class="form-control" id="registration_number" name="registration_number" maxlength="50"
                                               value="<?php echo isset($editProperty['registration_number']) ? sanitize($editProperty['registration_number']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="construction_year"><?php echo translate('construction_year'); ?></label>
                                        <input type="number" class="form-control" id="construction_year" name="construction_year" min="1900" max="<?php echo date('Y'); ?>"
                                               value="<?php echo isset($editProperty['construction_year']) ? (int)$editProperty['construction_year'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="valuation"><?php echo translate('valuation'); ?></label>
                                        <input type="number" step="0.01" class="form-control" id="valuation" name="valuation" min="0"
                                               value="<?php echo isset($editProperty['valuation']) ? (float)$editProperty['valuation'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="zoning"><?php echo translate('zoning'); ?></label>
                                        <input type="text" class="form-control" id="zoning" name="zoning" maxlength="100"
                                               value="<?php echo isset($editProperty['zoning']) ? sanitize($editProperty['zoning']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="registration_date"><?php echo translate('registration_date'); ?></label>
                                        <input type="date" class="form-control" id="registration_date" name="registration_date"
                                               value="<?php echo isset($editProperty['registration_date']) ? $editProperty['registration_date'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="tax_status"><?php echo translate('tax_status'); ?>*</label>
                                        <select class="form-control select2" id="tax_status" name="tax_status" required>
                                            <option value=""><?php echo translate('select_tax_status'); ?></option>
                                            <?php
                                            $tax_statuses = ['Active', 'Exempt', 'Delinquent'];
                                            foreach ($tax_statuses as $status):
                                                $selected = (isset($editProperty['tax_status']) && $editProperty['tax_status'] == $status) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $status; ?>" <?php echo $selected; ?>><?php echo translate($status); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? translate('update') : translate('submit'); ?></button>
                                    <?php if (isset($_GET['edit'])): ?>
                                        <a href="properties.php" class="btn btn-default"><?php echo translate('cancel'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Properties Table -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_properties'); ?></h3>
                                <?php if (isset($_GET['owner']) && is_numeric($_GET['owner']) && $_GET['owner'] > 0): ?>
                                    <div class="card-tools">
                                        <a href="properties.php" class="btn btn-sm btn-secondary"><?php echo translate('show_all_properties'); ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('id'); ?></th>
                                            <th><?php echo translate('property_name'); ?></th>
                                            <th><?php echo translate('owner'); ?></th>
                                            <th><?php echo translate('property_type'); ?></th>
                                            <th><?php echo translate('area'); ?></th>
                                            <th><?php echo translate('registration_number'); ?></th>
                                            <th><?php echo translate('construction_year'); ?></th>
                                            <th><?php echo translate('valuation'); ?></th>
                                            <th><?php echo translate('zoning'); ?></th>
                                            <th><?php echo translate('registration_date'); ?></th>
                                            <th><?php echo translate('tax_status'); ?></th>
                                            <th><?php echo translate('buildings'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $sql = "SELECT p.*, o.full_name, pt.name AS type_name, COUNT(b.building_id) AS building_count 
                                                    FROM properties p 
                                                    JOIN owners o ON p.owner_id = o.id 
                                                    LEFT JOIN property_types pt ON p.type_id = pt.id 
                                                    LEFT JOIN buildings b ON p.property_id = b.property_id";
                                            $params = [];
                                            $types = '';
                                            
                                            if (isset($_GET['owner']) && is_numeric($_GET['owner']) && $_GET['owner'] > 0) {
                                                $sql .= " WHERE p.owner_id = ?";
                                                $params[] = (int)$_GET['owner'];
                                                $types .= 'i';
                                            }
                                            
                                            $sql .= " GROUP BY p.property_id ORDER BY p.property_name";
                                            $stmt = $conn->prepare($sql);
                                            if (!empty($params)) {
                                                $stmt->bind_param($types, ...$params);
                                            }
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()):
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['property_id']; ?></td>
                                                <td><?php echo sanitize($row['property_name']); ?></td>
                                                <td><?php echo sanitize($row['full_name']); ?></td>
                                                <td><?php echo sanitize($row['type_name'] ?? '-'); ?></td>
                                                <td><?php echo number_format((float)$row['area'], 2); ?></td>
                                                <td><?php echo $row['registration_number'] ? sanitize($row['registration_number']) : '-'; ?></td>
                                                <td><?php echo $row['construction_year'] ? (int)$row['construction_year'] : '-'; ?></td>
                                                <td><?php echo $row['valuation'] !== null ? formatCurrency($row['valuation']) : '-'; ?></td>
                                                <td><?php echo $row['zoning'] ? sanitize($row['zoning']) : '-'; ?></td>
                                                <td><?php echo $row['registration_date'] ? $row['registration_date'] : '-'; ?></td>
                                                <td><?php echo translate($row['tax_status']); ?></td>
                                                <td><?php echo (int)$row['building_count']; ?></td>
                                                <td>
                                                    <a href="properties.php?edit=<?php echo (int)$row['property_id']; ?>" class="btn btn-sm btn-primary" title="<?php echo translate('edit'); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="process_property.php?delete=<?php echo (int)$row['property_id']; ?>" class="btn btn-sm btn-danger" title="<?php echo translate('delete'); ?>" onclick="return confirm('<?php echo translate('confirm_delete_property'); ?>');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="buildings.php?property=<?php echo (int)$row['property_id']; ?>" class="btn btn-sm btn-info" title="<?php echo translate('view_buildings'); ?>">
                                                        <i class="fas fa-building"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; 
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'][] = "Error fetching properties: " . sanitize($e->getMessage());
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
    $('#propertyForm').on('submit', function(e) {
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
<?php  ?>