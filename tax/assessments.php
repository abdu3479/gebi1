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
    $_SESSION['error_message'] = translate('unauthorized_access');
    redirect('index.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Error: " . sanitize($e->getMessage()));
}

/**
 * Update assessment statuses to overdue based on due date
 */
function updateAssessmentStatuses($conn) {
    $today = date('Y-m-d');
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT assessment_id, due_date FROM tax_assessments WHERE status = 'pending' AND due_date IS NOT NULL");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($assessment = $result->fetch_assoc()) {
            if ($assessment['due_date'] < $today) {
                $new_status = 'overdue';
                $update_stmt = $conn->prepare("UPDATE tax_assessments SET status = ? WHERE assessment_id = ?");
                $update_stmt->bind_param("si", $new_status, $assessment['assessment_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                logAction($conn, $_SESSION['user_id'] ?? 0, "Auto-updated assessment status to $new_status", $assessment['assessment_id'], 'tax_assessments');
            }
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating assessment statuses: " . $e->getMessage());
    }
}

/**
 * Update assessment statuses to paid based on payments
 */
function updatePaidStatuses($conn) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            SELECT ta.assessment_id, ta.tax_amount, COALESCE(SUM(p.amount), 0) AS total_paid
            FROM tax_assessments ta
            LEFT JOIN payment p ON ta.assessment_id = p.assessment_id
            WHERE ta.status IN ('pending', 'overdue')
            GROUP BY ta.assessment_id, ta.tax_amount
            HAVING total_paid >= ta.tax_amount
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($assessment = $result->fetch_assoc()) {
            $new_status = 'paid';
            $update_stmt = $conn->prepare("UPDATE tax_assessments SET status = ? WHERE assessment_id = ?");
            $update_stmt->bind_param("si", $new_status, $assessment['assessment_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            logAction($conn, $_SESSION['user_id'] ?? 0, "Auto-updated assessment status to $new_status based on payments", $assessment['assessment_id'], 'tax_assessments');
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating paid statuses: " . $e->getMessage());
    }
}

// Update assessment statuses
updateAssessmentStatuses($conn);
updatePaidStatuses($conn);

// Set role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <style>
        .main-header .navbar-nav .nav-item .nav-link {
            padding: 0.5rem 1rem;
        }
        .select2-container--default .select2-selection--single {
            height: calc(2.25rem + 2px);
            padding: 0.375rem 0.75rem;
        }
        .badge-overdue {
            background-color: #dc3545;
        }
        .badge-pending {
            background-color: #ffc107;
        }
        .badge-paid {
            background-color: #28a745;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Navigation Bar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="<?php echo translate('toggle_sidebar'); ?>">
                    <i class="fas fa-bars"></i>
                </a>
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

    <!-- Sidebar Navigation -->
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
                                <p><?php echo translate('rental_tax'); ?></p>
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

    <!-- Content Wrapper -->
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
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_assessments'); ?></h3>
                                <div class="card-tools">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#assessmentModal">
                                            <i class="fas fa-plus"></i> <?php echo translate('new_assessment'); ?>
                                        </button>
                                        <?php if (isAdmin()): ?>
                                            <button type="button" class="btn btn-sm btn-secondary ml-2" data-toggle="modal" data-target="#signatureModal">
                                                <i class="fas fa-signature"></i> <?php echo translate('upload_signature'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <form method="get" class="form-inline">
                                            <div class="form-group mr-2">
                                                <select class="form-control" name="status" onchange="this.form.submit()">
                                                    <option value=""><?php echo translate('all_statuses'); ?></option>
                                                    <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>><?php echo translate('pending'); ?></option>
                                                    <option value="paid" <?php echo ($_GET['status'] ?? '') == 'paid' ? 'selected' : ''; ?>><?php echo translate('paid'); ?></option>
                                                    <option value="overdue" <?php echo ($_GET['status'] ?? '') == 'overdue' ? 'selected' : ''; ?>><?php echo translate('overdue'); ?></option>
                                                </select>
                                            </div>
                                            <div class="form-group mr-2">
                                                <select class="form-control select2" name="property_id" style="width: 250px;" onchange="this.form.submit()">
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
                                            <div class="form-group">
                                                <input type="number" class="form-control" name="fiscal_year" placeholder="<?php echo translate('fiscal_year'); ?>" 
                                                       value="<?php echo $_GET['fiscal_year'] ?? date('Y'); ?>" onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th><?php echo translate('property_building'); ?></th>
                                            <th><?php echo translate('tax_rate'); ?></th>
                                            <th><?php echo translate('value'); ?></th>
                                            <th><?php echo translate('tax'); ?></th>
                                            <th><?php echo translate('remaining_balance'); ?></th>
                                            <th><?php echo translate('year'); ?></th>
                                            <th><?php echo translate('status'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT a.*, p.property_name, b.building_name, r.rate_name, r.rate_percentage, r.applicable_to,
        COALESCE(SUM(pay.amount), 0) AS total_paid,
        (a.tax_amount - COALESCE(SUM(pay.amount), 0)) AS remaining_balance
        FROM tax_assessments a
        JOIN properties p ON a.property_id = p.property_id
        LEFT JOIN buildings b ON a.building_id = b.building_id
        JOIN tax_rates r ON a.rate_id = r.rate_id
        LEFT JOIN payment pay ON a.assessment_id = pay.assessment_id
        GROUP BY a.assessment_id, p.property_name, b.building_name, r.rate_name, r.rate_percentage, r.applicable_to";
                                        
                                        $params = [];
                                        $types = '';
                                        
                                        if (!empty($_GET['status'])) {
                                            $sql .= " HAVING a.status = ?";
                                            $params[] = $_GET['status'];
                                            $types .= 's';
                                        }
                                        
                                        if (!empty($_GET['property_id'])) {
                                            $sql .= " AND a.property_id = ?";
                                            $params[] = (int)$_GET['property_id'];
                                            $types .= 'i';
                                        }
                                        
                                        if (!empty($_GET['fiscal_year'])) {
                                            $sql .= " AND a.fiscal_year = ?";
                                            $params[] = (int)$_GET['fiscal_year'];
                                            $types .= 'i';
                                        }
                                        
                                        $sql .= " ORDER BY a.assessment_date DESC";
                                        
                                        $stmt = $conn->prepare($sql);
                                        if (!empty($params)) {
                                            $stmt->bind_param($types, $params);
                                        }
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        while ($row = $result->fetch_assoc()):
                                            $statusClass = $row['status'] == 'paid' ? 'badge-paid' : ($row['status'] == 'overdue' ? 'badge-overdue' : 'badge-pending');
                                            $targetName = $row['building_name'] ? sanitize($row['building_name']) . " (Building)" : sanitize($row['property_name']) . " (Property)";
                                            $rateInfo = sanitize($row['rate_name']) . " (" . (float)$row['rate_percentage'] . "%)";
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['assessment_id']; ?></td>
                                                <td><?php echo $targetName; ?></td>
                                                <td><?php echo $rateInfo; ?></td>
                                                <td><?php echo formatCurrency($row['assessed_value']); ?></td>
                                                <td><?php echo formatCurrency($row['tax_amount']); ?></td>
                                                <td><?php echo formatCurrency($row['remaining_balance']); ?></td>
                                                <td><?php echo (int)$row['fiscal_year']; ?></td>
                                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo translate($row['status']); ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary edit-assessment" 
                                                            data-id="<?php echo (int)$row['assessment_id']; ?>"
                                                            data-property-id="<?php echo (int)$row['property_id']; ?>"
                                                            data-building-id="<?php echo (int)$row['building_id']; ?>"
                                                            data-rate-id="<?php echo (int)$row['rate_id']; ?>"
                                                            data-assessment-date="<?php echo sanitize($row['assessment_date']); ?>"
                                                            data-due-date="<?php echo sanitize($row['due_date']); ?>"
                                                            data-assessed-value="<?php echo number_format($row['assessed_value'], 2, '.', ''); ?>"
                                                            data-tax-amount="<?php echo number_format($row['tax_amount'], 2, '.', ''); ?>"
                                                            data-fiscal-year="<?php echo (int)$row['fiscal_year']; ?>"
                                                            data-notes="<?php echo sanitize($row['notes']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="process_assessment.php?delete=<?php echo (int)$row['assessment_id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       title="<?php echo translate('delete'); ?>" 
                                                       onclick="return confirm('<?php echo translate('confirm_delete_assessment'); ?>');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="payments.php?assessment=<?php echo (int)$row['assessment_id']; ?>" 
                                                       class="btn btn-sm btn-info" 
                                                       title="<?php echo translate('view_payments'); ?>">
                                                        <i class="fas fa-file-invoice-dollar"></i>
                                                    </a>
                                                    <?php if ($row['status'] == 'paid'): ?>
                                                        <a href="generate_invoice.php?assessment_id=<?php echo (int)$row['assessment_id']; ?>" 
                                                           class="btn btn-sm btn-secondary" 
                                                           title="<?php echo translate('generate_invoice'); ?>">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </a>
                                                    <?php endif; ?>
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

    <!-- Assessment Modal -->
    <div class="modal fade" id="assessmentModal" tabindex="-1" role="dialog" aria-labelledby="assessmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assessmentModalLabel"><?php echo translate('new_assessment'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="process_assessment.php">
                    <input type="hidden" name="edit_id" id="edit_id" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_property_id"><?php echo translate('property'); ?>*</label>
                                    <select class="form-control select2" id="modal_property_id" name="property_id" required>
                                        <option value=""><?php echo translate('select_property'); ?></option>
                                        <?php
                                        $stmt = $conn->prepare("SELECT p.property_id, p.property_name, o.full_name 
                                                               FROM properties p 
                                                               JOIN owners o ON p.owner_id = o.id 
                                                               ORDER BY p.property_name");
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo (int)$row['property_id']; ?>">
                                                <?php echo sanitize($row['property_name']); ?> (Owner: <?php echo sanitize($row['full_name']); ?>)
                                            </option>
                                        <?php endwhile; 
                                        $stmt->close();
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="modal_building_id"><?php echo translate('building'); ?></label>
                                    <select class="form-control select2" id="modal_building_id" name="building_id">
                                        <option value=""><?php echo translate('select_building'); ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="modal_rate_id"><?php echo translate('tax_rate'); ?>*</label>
                                    <select class="form-control select2" id="modal_rate_id" name="rate_id" required>
                                        <option value=""><?php echo translate('select_tax_rate'); ?></option>
                                        <?php
                                        $stmt = $conn->prepare("SELECT * FROM tax_rates WHERE expiry_date IS NULL OR expiry_date >= CURDATE() ORDER BY rate_name");
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()):
                                            $type = $row['applicable_to'] == 'property' ? translate('property_tax') : translate('building_tax');
                                        ?>
                                            <option value="<?php echo (int)$row['rate_id']; ?>">
                                                <?php echo sanitize($row['rate_name']); ?> (<?php echo $type; ?> - <?php echo (float)$row['rate_percentage']; ?>%)
                                            </option>
                                        <?php endwhile; 
                                        $stmt->close();
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="modal_assessment_date"><?php echo translate('assessment_date'); ?>*</label>
                                    <input type="date" class="form-control" id="modal_assessment_date" name="assessment_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal_due_date"><?php echo translate('due_date'); ?>*</label>
                                    <input type="date" class="form-control" id="modal_due_date" name="due_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="modal_assessed_value"><?php echo translate('assessed_value'); ?>*</label>
                                    <input type="number" step="0.01" class="form-control" id="modal_assessed_value" name="assessed_value" required>
                                </div>
                                <div class="form-group">
                                    <label for="modal_tax_amount"><?php echo translate('tax'); ?>*</label>
                                    <input type="number" step="0.01" class="form-control" id="modal_tax_amount" name="tax_amount" required readonly>
                                </div>
                                <div class="form-group">
                                    <label for="modal_fiscal_year"><?php echo translate('fiscal_year'); ?>*</label>
                                    <input type="number" class="form-control" id="modal_fiscal_year" name="fiscal_year" required>
                                </div>
                                <div class="form-group">
                                    <label for="modal_notes"><?php echo translate('notes'); ?></label>
                                    <textarea class="form-control" id="modal_notes" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('close'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo translate('submit'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Signature/Stamp Modal (Admin Only) -->
    <?php if (isAdmin()): ?>
    <div class="modal fade" id="signatureModal" tabindex="-1" role="dialog" aria-labelledby="signatureModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="signatureModalLabel"><?php echo translate('upload_stamp_signature'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="upload_signature_stamp.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <label for="stamp"><?php echo translate('official_stamp'); ?> (PNG, Max 1MB)</label>
                            <input type="file" class="form-control-file" id="stamp" name="stamp" accept="image/png">
                        </div>
                        <div class="form-group">
                            <label for="signature"><?php echo translate('authorized_signature'); ?> (PNG, Max 1MB)</label>
                            <input type="file" class="form-control-file" id="signature" name="signature" accept="image/png">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('close'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo translate('upload'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
        "responsive": true
    });
    
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Update buildings dropdown when property changes
    $('#modal_property_id').change(function() {
        var propertyId = $(this).val();
        $('#modal_building_id').html('<option value=""><?php echo translate('select_building'); ?></option>');
        
        if (propertyId) {
            $.ajax({
                url: 'ajax_get_buildings.php',
                type: 'GET',
                data: { property_id: propertyId },
                dataType: 'json',
                success: function(data) {
                    $.each(data, function(index, building) {
                        $('#modal_building_id').append('<option value="' + building.building_id + '">' + building.building_name + '</option>');
                    });
                    $('#modal_building_id').trigger('change');
                },
                error: function() {
                    alert('<?php echo translate('failed_to_load_buildings'); ?>');
                }
            });
        }
    });

    // Calculate tax amount when rate or assessed value changes
    function calculateTax() {
        var rateId = $('#modal_rate_id').val();
        var assessedValue = parseFloat($('#modal_assessed_value').val()) || 0;
        
        if (rateId && assessedValue > 0) {
            $.ajax({
                url: 'ajax_get_rate.php',
                type: 'GET',
                data: { rate_id: rateId },
                dataType: 'json',
                success: function(data) {
                    var rate = parseFloat(data.rate_percentage);
                    var taxAmount = assessedValue * (rate / 100);
                    $('#modal_tax_amount').val(taxAmount.toFixed(2));
                },
                error: function() {
                    alert('<?php echo translate('failed_to_load_tax_rate'); ?>');
                }
            });
        } else {
            $('#modal_tax_amount').val('');
        }
    }
    
    $('#modal_rate_id').change(calculateTax);
    $('#modal_assessed_value').keyup(calculateTax);

    // Set default due date to 30 days after assessment date
    $('#modal_assessment_date').change(function() {
        var assessmentDate = $(this).val();
        if (assessmentDate) {
            var dueDate = new Date(assessmentDate);
            dueDate.setDate(dueDate.getDate() + 30);
            var formattedDueDate = dueDate.toISOString().split('T')[0];
            $('#modal_due_date').val(formattedDueDate);
        }
    });

    // Set default fiscal year to current year
    $('#modal_fiscal_year').val(new Date().getFullYear());

    // Handle edit button click
    $('.edit-assessment').click(function() {
        var assessmentId = $(this).data('id');
        $('#edit_id').val(assessmentId);
        $('#assessmentModalLabel').text('<?php echo translate('edit_assessment'); ?>');
        
        // Fill form with assessment data
        $('#modal_property_id').val($(this).data('property-id')).trigger('change');
        $('#modal_building_id').val($(this).data('building-id'));
        $('#modal_rate_id').val($(this).data('rate-id'));
        $('#modal_assessment_date').val($(this).data('assessment-date'));
        $('#modal_due_date').val($(this).data('due-date'));
        $('#modal_assessed_value').val($(this).data('assessed-value'));
        $('#modal_tax_amount').val($(this).data('tax-amount'));
        $('#modal_fiscal_year').val($(this).data('fiscal-year'));
        $('#modal_notes').val($(this).data('notes'));
        
        // Show modal
        $('#assessmentModal').modal('show');
    });

    // Reset form when modal is closed
    $('#assessmentModal').on('hidden.bs.modal', function () {
        $('#edit_id').val('');
        $('#assessmentModalLabel').text('<?php echo translate('new_assessment'); ?>');
        $(this).find('form')[0].reset();
        $('#modal_property_id').val('').trigger('change');
        $('#modal_fiscal_year').val(new Date().getFullYear());
    });
});
</script>
</body>
</html>