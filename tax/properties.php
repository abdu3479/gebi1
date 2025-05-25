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
    redirect('properties.php');
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
                <?php displayMessages(); ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_properties'); ?></h3>
                                <div class="card-tools">
                                    <?php if (isAdmin() || isOfficer()): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#propertyModal" data-mode="create">
                                            <i class="fas fa-plus"></i> <?php echo translate('add_new_property'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
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
                                                    LEFT JOIN buildings b ON p.property_id = b.property_id
                                                    GROUP BY p.property_id, o.full_name, pt.name
                                                    ORDER BY p.property_name";
                                            
                                            $stmt = $conn->prepare($sql);
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
                                                <td><?php echo translate($row['tax_status']); ?></td>
                                                <td><?php echo (int)$row['building_count']; ?></td>
                                                <td>
                                                    <?php if (isAdmin() || isOfficer()): ?>
                                                        <button class="btn btn-sm btn-primary edit-property" 
                                                                data-mode="edit"
                                                                data-id="<?php echo (int)$row['property_id']; ?>"
                                                                data-owner-id="<?php echo (int)$row['owner_id']; ?>"
                                                                data-type-id="<?php echo (int)$row['type_id']; ?>"
                                                                data-property-name="<?php echo htmlspecialchars(sanitize($row['property_name'])); ?>"
                                                                data-address="<?php echo htmlspecialchars(sanitize($row['address'])); ?>"
                                                                data-area="<?php echo (float)$row['area']; ?>"
                                                                data-registration-number="<?php echo htmlspecialchars(sanitize($row['registration_number'])); ?>"
                                                                data-construction-year="<?php echo (int)$row['construction_year']; ?>"
                                                                data-valuation="<?php echo (float)$row['valuation']; ?>"
                                                                data-zoning="<?php echo htmlspecialchars(sanitize($row['zoning'])); ?>"
                                                                data-registration-date="<?php echo $row['registration_date']; ?>"
                                                                data-tax-status="<?php echo sanitize($row['tax_status']); ?>"
                                                                data-toggle="modal"
                                                                data-target="#propertyModal"
                                                                title="<?php echo translate('edit'); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger delete-property" 
                                                                data-id="<?php echo (int)$row['property_id']; ?>"
                                                                data-toggle="modal"
                                                                data-target="#deleteModal"
                                                                title="<?php echo translate('delete'); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <a href="buildings.php?property=<?php echo (int)$row['property_id']; ?>" 
                                                       class="btn btn-sm btn-info" 
                                                       title="<?php echo translate('view_buildings'); ?>">
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

                <!-- Property Modal -->
                <div class="modal fade" id="propertyModal" tabindex="-1" role="dialog" aria-labelledby="propertyModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="propertyModalLabel"><?php echo translate('add_new_property'); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo translate('close'); ?>">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="post" action="process_property.php" id="propertyForm">
                                <div class="modal-body">
                                    <input type="hidden" name="edit_id" id="edit_id">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="modal_owner_id"><?php echo translate('owner'); ?>*</label>
                                                <select class="form-control select2-modal" id="modal_owner_id" name="owner_id" required>
                                                    <option value=""><?php echo translate('select_owner'); ?></option>
                                                    <?php
                                                    try {
                                                        $stmt = $conn->prepare("SELECT id, full_name FROM owners ORDER BY full_name");
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while ($row = $result->fetch_assoc()):
                                                    ?>
                                                        <option value="<?php echo (int)$row['id']; ?>">
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
                                                <label for="modal_type_id"><?php echo translate('property_type'); ?>*</label>
                                                <select class="form-control select2-modal" id="modal_type_id" name="type_id" required>
                                                    <option value=""><?php echo translate('select_property_type'); ?></option>
                                                    <?php
                                                    try {
                                                        $stmt = $conn->prepare("SELECT id, name FROM property_types ORDER BY name");
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while ($row = $result->fetch_assoc()):
                                                    ?>
                                                        <option value="<?php echo (int)$row['id']; ?>">
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
                                                <label for="modal_property_name"><?php echo translate('property_name'); ?>*</label>
                                                <input type="text" class="form-control" id="modal_property_name" name="property_name" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="modal_address"><?php echo translate('address'); ?>*</label>
                                                <textarea class="form-control" id="modal_address" name="address" rows="3" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="modal_area"><?php echo translate('area'); ?>*</label>
                                                <input type="number" step="0.01" class="form-control" id="modal_area" name="area" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="modal_registration_number"><?php echo translate('registration_number'); ?></label>
                                                <input type="text" class="form-control" id="modal_registration_number" name="registration_number">
                                            </div>
                                            <div class="form-group">
                                                <label for="modal_construction_year"><?php echo translate('construction_year'); ?></label>
                                                <input type="number" class="form-control" id="modal_construction_year" name="construction_year">
                                            </div>
                                            <div class="form-group">
                                                <label for="modal_valuation"><?php echo translate('valuation'); ?></label>
                                                <input type="number" step="0.01" class="form-control" id="modal_valuation" name="valuation">
                                            </div>
                                            <div class="form-group">
                                                <label for="modal_zoning"><?php echo translate('zoning'); ?></label>
                                                <input type="text" class="form-control" id="modal_zoning" name="zoning">
                                            </div>
                                            <div class="form-group">
                                                <label for="modal_registration_date"><?php echo translate('registration_date'); ?></label>
                                                <input type="date" class="form-control" id="modal_registration_date" name="registration_date">
                                            </div>
                                            <div class="form-group">
                                                <label for="modal_tax_status"><?php echo translate('tax_status'); ?>*</label>
                                                <select class="form-control select2-modal" id="modal_tax_status" name="tax_status" required>
                                                    <option value=""><?php echo translate('select_tax_status'); ?></option>
                                                    <option value="Active"><?php echo translate('Active'); ?></option>
                                                    <option value="Exempt"><?php echo translate('Exempt'); ?></option>
                                                    <option value="Delinquent"><?php echo translate('Delinquent'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('close'); ?></button>
                                    <button type="submit" class="btn btn-primary" id="submitProperty"><?php echo translate('submit'); ?></button>
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
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <?php echo translate('confirm_delete_property'); ?>
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
        <strong>Copyright &copy; <?php echo date('Y'); ?> Tax Management System.</strong>
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
            dropdownParent: $('#propertyModal')
        });
    }

    // Call Select2 initialization when modal is shown
    $('#propertyModal').on('shown.bs.modal', function () {
        initializeModalSelect2();
    });

    // Reset form and Select2 when modal is hidden
    $('#propertyModal').on('hidden.bs.modal', function () {
        $('#propertyForm')[0].reset();
        $('.select2-modal').val('').trigger('change');
        $('#edit_id').val('');
        $('#submitProperty').text('<?php echo translate('submit'); ?>');
        $('#propertyModalLabel').text('<?php echo translate('add_new_property'); ?>');
    });

    // Handle modal show for create/edit
    $('#propertyModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var mode = button.data('mode');
        var modal = $(this);

        if (mode === 'create') {
            modal.find('.modal-title').text('<?php echo translate('add_new_property'); ?>');
            modal.find('#submitProperty').text('<?php echo translate('submit'); ?>');
            modal.find('#propertyForm')[0].reset();
            modal.find('#modal_owner_id').val('').trigger('change');
            modal.find('#modal_type_id').val('').trigger('change');
            modal.find('#modal_tax_status').val('').trigger('change');
            modal.find('#edit_id').val('');
        } else if (mode === 'edit') {
            modal.find('.modal-title').text('<?php echo translate('edit_property'); ?>');
            modal.find('#submitProperty').text('<?php echo translate('update'); ?>');

            var propertyId = button.data('id');
            $('#edit_id').val(propertyId);
            $('#modal_owner_id').val(button.data('owner-id')).trigger('change');
            $('#modal_type_id').val(button.data('type-id')).trigger('change');
            $('#modal_property_name').val(button.data('property-name'));
            $('#modal_address').val(button.data('address'));
            $('#modal_area').val(button.data('area'));
            $('#modal_registration_number').val(button.data('registration-number'));
            $('#modal_construction_year').val(button.data('construction-year'));
            $('#modal_valuation').val(button.data('valuation'));
            $('#modal_zoning').val(button.data('zoning'));
            $('#modal_registration_date').val(button.data('registration-date'));
            $('#modal_tax_status').val(button.data('tax-status')).trigger('change');
        }
    });

    // Handle delete button click
    $('.delete-property').on('click', function() {
        var propertyId = $(this).data('id');
        $('#deleteConfirm').attr('href', 'process_property.php?delete=' + propertyId + '&csrf_token=<?php echo $_SESSION['csrf_token']; ?>');
    });
});
</script>
</body>
</html>