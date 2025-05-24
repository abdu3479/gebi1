<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = "Unauthorized access.";
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

// Fetch assessment data for editing
$editAssessment = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM tax_assessments WHERE assessment_id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $result = $stmt->get_result();
    $editAssessment = $result->fetch_assoc();
    $stmt->close();
    if (!$editAssessment) {
        $_SESSION['error_message'] = "Assessment not found.";
        redirect('assessments.php');
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

$pageTitle = translate('tax_assessments');
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
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></a>
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
                                <i class="nav-icon fas fa-building"></i>
                                <p><?php echo translate('rooms'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rental_tax.php" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p><?php echo translate('rental tax'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="assessments.php" class="nav-link active">
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
                        <h1><?php echo translate('tax_assessments'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('tax_assessments'); ?></li>
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
                                <h3 class="card-title"><?php echo isset($_GET['edit']) ? translate('edit_assessment') : translate('new_assessment'); ?></h3>
                            </div>
                            <form method="post" action="process_assessment.php">
                                <?php if (isset($_GET['edit'])): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo (int)$_GET['edit']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="form-group">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <label for="property_id"><?php echo translate('property'); ?>*</label>
                                        <select class="form-control select2" id="property_id" name="property_id" required>
                                            <option value=""><?php echo translate('select_property'); ?></option>
                                            <?php
                                            $stmt = $conn->prepare("SELECT p.property_id, p.property_name, o.full_name 
                                                                   FROM properties p 
                                                                   JOIN owners o ON p.owner_id = o.id 
                                                                   ORDER BY p.property_name");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()):
                                                $selected = (isset($editAssessment) && $editAssessment['property_id'] == $row['property_id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo (int)$row['property_id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo sanitize($row['property_name']); ?> (Owner: <?php echo sanitize($row['full_name']); ?>)
                                                </option>
                                            <?php endwhile; 
                                            $stmt->close();
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="building_id"><?php echo translate('building'); ?> (Optional)</label>
                                        <select class="form-control select2" id="building_id" name="building_id">
                                            <option value=""><?php echo translate('select_building'); ?></option>
                                            <?php
                                            if (isset($editAssessment) && $editAssessment['property_id']):
                                                $stmt = $conn->prepare("SELECT building_id, building_name FROM buildings WHERE property_id = ?");
                                                $stmt->bind_param("i", $editAssessment['property_id']);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()):
                                                    $selected = ($editAssessment['building_id'] == $row['building_id']) ? 'selected' : '';
                                            ?>
                                                    <option value="<?php echo (int)$row['building_id']; ?>" <?php echo $selected; ?>>
                                                        <?php echo sanitize($row['building_name']); ?>
                                                    </option>
                                            <?php endwhile; 
                                                $stmt->close();
                                            endif;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="rate_id"><?php echo translate('tax_rate'); ?>*</label>
                                        <select class="form-control select2" id="rate_id" name="rate_id" required>
                                            <option value=""><?php echo translate('select_tax_rate'); ?></option>
                                            <?php
                                            $stmt = $conn->prepare("SELECT * FROM tax_rates WHERE expiry_date IS NULL OR expiry_date >= CURDATE() ORDER BY rate_name");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()):
                                                $selected = (isset($editAssessment) && $editAssessment['rate_id'] == $row['rate_id']) ? 'selected' : '';
                                                $type = $row['applicable_to'] == 'property' ? translate('property_tax') : translate('building_tax');
                                            ?>
                                                <option value="<?php echo (int)$row['rate_id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo sanitize($row['rate_name']); ?> (<?php echo $type; ?> - <?php echo (float)$row['rate_percentage']; ?>%)
                                                </option>
                                            <?php endwhile; 
                                            $stmt->close();
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="assessment_date"><?php echo translate('assessment_date'); ?>*</label>
                                        <input type="date" class="form-control" id="assessment_date" name="assessment_date" required 
                                               value="<?php echo isset($editAssessment['assessment_date']) ? sanitize($editAssessment['assessment_date']) : date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="assessed_value"><?php echo translate('assessed_value'); ?>*</label>
                                        <input type="number" step="0.01" class="form-control" id="assessed_value" name="assessed_value" required 
                                               value="<?php echo isset($editAssessment['assessed_value']) ? number_format($editAssessment['assessed_value'], 2, '.', '') : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="tax_amount"><?php echo translate('tax'); ?>*</label>
                                        <input type="number" step="0.01" class="form-control" id="tax_amount" name="tax_amount" required readonly 
                                               value="<?php echo isset($editAssessment['tax_amount']) ? number_format($editAssessment['tax_amount'], 2, '.', '') : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="fiscal_year"><?php echo translate('fiscal_year'); ?>*</label>
                                        <input type="number" class="form-control" id="fiscal_year" name="fiscal_year" required 
                                               value="<?php echo isset($editAssessment['fiscal_year']) ? (int)$editAssessment['fiscal_year'] : date('Y'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="notes"><?php echo translate('notes'); ?></label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($editAssessment['notes']) ? sanitize($editAssessment['notes']) : ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? translate('update') : translate('submit'); ?></button>
                                    <?php if (isset($_GET['edit'])): ?>
                                        <a href="assessments.php" class="btn btn-default"><?php echo translate('cancel'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_assessments'); ?></h3>
                                <div class="card-tools">
                                    <form method="get" class="form-inline">
                                        <div class="input-group input-group-sm">
                                            <select class="form-control" name="status" onchange="this.form.submit()">
                                                <option value=""><?php echo translate('all_statuses'); ?></option>
                                                <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>><?php echo translate('pending'); ?></option>
                                                <option value="paid" <?php echo ($_GET['status'] ?? '') == 'paid' ? 'selected' : ''; ?>><?php echo translate('paid'); ?></option>
                                                <option value="overdue" <?php echo ($_GET['status'] ?? '') == 'overdue' ? 'selected' : ''; ?>><?php echo translate('overdue'); ?></option>
                                            </select>
                                        </div>
                                        <div class="input-group input-group-sm ml-2">
                                            <select class="form-control select2" name="property_id" onchange="this.form.submit()">
                                                <option value=""><?php echo translate('all_properties'); ?></option>
                                                <?php
                                                $stmt = $conn->prepare("SELECT p.property_id, p.property_name, o.full_name 
                                                                       FROM properties p 
                                                                       JOIN owners o ON p.owner_id = o.id 
                                                                       ORDER BY p.property_name");
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()):
                                                    $selected = ($_GET['property_id'] ?? '') == $row['property_id'] ? 'selected' : '';
                                                ?>
                                                    <option value="<?php echo (int)$row['property_id']; ?>" <?php echo $selected; ?>>
                                                        <?php echo sanitize($row['property_name']); ?> (Owner: <?php echo sanitize($row['full_name']); ?>)
                                                    </option>
                                                <?php endwhile; 
                                                $stmt->close();
                                                ?>
                                            </select>
                                        </div>
                                        <div class="input-group input-group-sm ml-2">
                                            <input type="number" class="form-control" name="fiscal_year" placeholder="<?php echo translate('fiscal_year'); ?>" 
                                                   value="<?php echo $_GET['fiscal_year'] ?? date('Y'); ?>" onchange="this.form.submit()">
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th><?php echo translate('property_building'); ?></th>
                                            <th><?php echo translate('tax_rate'); ?></th>
                                            <th><?php echo translate('value'); ?></th>
                                            <th><?php echo translate('tax'); ?></th>
                                            <th><?php echo translate('year'); ?></th>
                                            <th><?php echo translate('status'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT a.*, p.property_name, b.building_name, r.rate_name, r.rate_percentage, r.applicable_to
                                                FROM tax_assessments a
                                                JOIN properties p ON a.property_id = p.property_id
                                                LEFT JOIN buildings b ON a.building_id = b.building_id
                                                JOIN tax_rates r ON a.rate_id = r.rate_id
                                                WHERE 1=1";
                                        $params = [];
                                        $types = '';

                                        if (!empty($_GET['status'])) {
                                            $sql .= " AND a.status = ?";
                                            $params[] = $_GET['status'];
                                            $types .= 's';
                                        }
                                        if (!empty($_GET['property_id']) && is_numeric($_GET['property_id'])) {
                                            $sql .= " AND a.property_id = ?";
                                            $params[] = (int)$_GET['property_id'];
                                            $types .= 'i';
                                        }
                                        if (!empty($_GET['fiscal_year']) && is_numeric($_GET['fiscal_year'])) {
                                            $sql .= " AND a.fiscal_year = ?";
                                            $params[] = (int)$_GET['fiscal_year'];
                                            $types .= 'i';
                                        }
                                        $sql .= " ORDER BY a.assessment_date DESC";

                                        $stmt = $conn->prepare($sql);
                                        if (!empty($params)) {
                                            $stmt->bind_param($types, ...$params);
                                        }
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()):
                                            $statusClass = $row['status'] == 'paid' ? 'badge-success' : ($row['status'] == 'overdue' ? 'badge-danger' : 'badge-warning');
                                            $targetName = $row['building_name'] ? sanitize($row['building_name']) . " (Building)" : sanitize($row['property_name']) . " (Property)";
                                            $rateInfo = sanitize($row['rate_name']) . " (" . (float)$row['rate_percentage'] . "%)";
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['assessment_id']; ?></td>
                                                <td><?php echo $targetName; ?></td>
                                                <td><?php echo $rateInfo; ?></td>
                                                <td><?php echo formatCurrency($row['assessed_value']); ?></td>
                                                <td><?php echo formatCurrency($row['tax_amount']); ?></td>
                                                <td><?php echo (int)$row['fiscal_year']; ?></td>
                                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo sanitize($row['status']); ?></span></td>
                                                <td>
                                                    <a href="assessments.php?edit=<?php echo (int)$row['assessment_id']; ?>" class="btn btn-sm btn-primary" title="<?php echo translate('edit'); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="process_assessment.php?delete=<?php echo (int)$row['assessment_id']; ?>" class="btn btn-sm btn-danger" title="<?php echo translate('delete'); ?>" onclick="return confirm('Are you sure you want to delete this assessment?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="process_assessment.php?action=delete&id=123&csrf_token=<?php echo $_SESSION['csrf_token']; ?>">
                                                             Delete
                                                            </a>
                                                    <?php if ($row['status'] != 'paid'): ?>
                                                        <a href="process_assessment.php?change_status=<?php echo (int)$row['assessment_id']; ?>&new_status=paid" class="btn btn-sm btn-success" title="<?php echo translate('mark_as_paid'); ?>">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($row['status'] != 'overdue'): ?>
                                                        <a href="process_assessment.php?change_status=<?php echo (int)$row['assessment_id']; ?>&new_status=overdue" class="btn btn-sm btn-warning" title="<?php echo translate('mark_as_overdue'); ?>">
                                                            <i class="fas fa-exclamation"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="payments.php?assessment=<?php echo (int)$row['assessment_id']; ?>" class="btn btn-sm btn-info" title="<?php echo translate('view_payments'); ?>">
                                                        <i class="fas fa-file-invoice-dollar"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; 
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
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
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
    
    $('.select2').select2({
        theme: 'bootstrap4'
    });
    
    // Update buildings dropdown when property changes
    $('#property_id').change(function() {
        var propertyId = $(this).val();
        $('#building_id').html('<option value=""><?php echo translate('select_building'); ?></option>');
        if (propertyId) {
            $.ajax({
                url: 'ajax_get_buildings.php',
                type: 'GET',
                data: { property_id: propertyId },
                dataType: 'json',
                success: function(data) {
                    $.each(data, function(index, building) {
                        $('#building_id').append('<option value="' + building.building_id + '">' + building.building_name + '</option>');
                    });
                },
                error: function() {
                    alert('Failed to load buildings.');
                }
            });
        }
    });
    
    // Calculate tax amount when rate or assessed value changes
    function calculateTax() {
        var rateId = $('#rate_id').val();
        var assessedValue = parseFloat($('#assessed_value').val()) || 0;
        
        if (rateId && assessedValue > 0) {
            $.ajax({
                url: 'ajax_get_rate.php',
                type: 'GET',
                data: { rate_id: rateId },
                dataType: 'json',
                success: function(data) {
                    var rate = parseFloat(data.rate_percentage);
                    var taxAmount = assessedValue * (rate / 100);
                    $('#tax_amount').val(taxAmount.toFixed(2));
                },
                error: function() {
                    alert('Failed to load tax rate.');
                }
            });
        } else {
            $('#tax_amount').val('');
        }
    }
    
    $('#rate_id').change(calculateTax);
    $('#assessed_value').keyup(calculateTax);
});
</script>
</body>
</html>