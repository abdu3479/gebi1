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

// Get edit assessment data
$editAssessment = null;
if (isset($_GET['edit'])) {
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt = $conn->prepare("SELECT a.*, c.monthly_rent 
                                FROM tax_assessments a 
                                JOIN rent_contracts c ON a.contract_id = c.contract_id 
                                WHERE a.assessment_id = ? AND a.is_rental = TRUE");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $editAssessment = $result->fetch_assoc();
        $stmt->close();
        if (!$editAssessment) {
            $_SESSION['error_message'] = translate('assessment_not_found');
            redirect('rental_tax.php');
        }
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

$pageTitle = translate('rental_tax_assessment');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap4-theme/1.5.4/select2-bootstrap4.min.css">
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
            <img src="../assets/images/logo.jpg" alt="Tax System Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">PMT</span>
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
                            <a href="rooms.php" class="nav-link">
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
                            <a href="assessments.php" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                <p><?php echo translate('tax_assessments'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rental_tax.php" class="nav-link active">
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
                        <h1><?php echo translate('rental_tax_assessment'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('rental_tax_assessment'); ?></li>
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
                                <h3 class="card-title"><?php echo $editAssessment ? translate('edit_assessment') : translate('new_assessment'); ?></h3>
                            </div>
                            <form method="post" action="process_rental_tax.php">
                                <?php if ($editAssessment): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo (int)$editAssessment['assessment_id']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="contract_id"><?php echo translate('rent_contract'); ?>*</label>
                                        <select class="form-control select2" id="contract_id" name="contract_id" required <?php echo isset($_GET['contract']) ? 'disabled' : ''; ?>>
                                            <option value=""><?php echo translate('select_contract'); ?></option>
                                            <?php
                                            $stmt = $conn->prepare("SELECT c.contract_id, r.room_number, b.building_name, p.property_name, c.tenant_name, rt.type_name 
                                                                    FROM rent_contracts c
                                                                    JOIN rooms r ON c.room_id = r.room_id
                                                                    JOIN room_types rt ON r.type_id = rt.type_id
                                                                    JOIN buildings b ON r.building_id = b.building_id
                                                                    JOIN properties p ON b.property_id = p.property_id
                                                                    WHERE c.status = 'active'
                                                                    ORDER BY p.property_name, b.building_name, r.room_number");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            while ($row = $result->fetch_assoc()):
                                                $selected = ($editAssessment && $editAssessment['contract_id'] == $row['contract_id']) || (isset($_GET['contract']) && (int)$_GET['contract'] == $row['contract_id']) ? 'selected' : '';
                                                ?>
                                                <option value="<?php echo (int)$row['contract_id']; ?>" <?php echo $selected; ?> data-room-type="<?php echo sanitize($row['type_name']); ?>">
                                                    <?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?> - Room <?php echo sanitize($row['room_number']); ?> (Tenant: <?php echo sanitize($row['tenant_name']); ?>, Type: <?php echo sanitize($row['type_name']); ?>)
                                                </option>
                                            <?php endwhile;
                                            $stmt->close();
                                            ?>
                                        </select>
                                        <?php if (isset($_GET['contract'])): ?>
                                            <input type="hidden" name="contract_id" value="<?php echo (int)$_GET['contract']; ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label for="rate_id"><?php echo translate('tax_rate'); ?>*</label>
                                        <select class="form-control select2" id="rate_id" name="rate_id" required>
                                            <option value=""><?php echo translate('select_tax_rate'); ?></option>
                                            <?php
                                            $stmt = $conn->prepare("SELECT * FROM tax_rates WHERE applicable_to = 'rental' AND (expiry_date IS NULL OR expiry_date >= CURDATE()) ORDER BY rate_name");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            while ($row = $result->fetch_assoc()):
                                                $selected = ($editAssessment && $editAssessment['rate_id'] == $row['rate_id']) ? 'selected' : '';
                                                ?>
                                                <option value="<?php echo (int)$row['rate_id']; ?>" data-percentage="<?php echo (float)$row['rate_percentage']; ?>" <?php echo $selected; ?>>
                                                    <?php echo sanitize($row['rate_name']); ?> (Rental Tax - <?php echo (float)$row['rate_percentage']; ?>%)
                                                </option>
                                            <?php endwhile;
                                            $stmt->close();
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="assessment_date"><?php echo translate('assessment_date'); ?>*</label>
                                        <input type="date" class="form-control" id="assessment_date" name="assessment_date" required 
                                               value="<?php echo $editAssessment ? sanitize($editAssessment['assessment_date']) : date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="period_month"><?php echo translate('period_month'); ?>*</label>
                                        <select class="form-control" id="period_month" name="period_month" required>
                                            <option value=""><?php echo translate('select_month'); ?></option>
                                            <?php
                                            $months = [
                                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                            ];
                                            foreach ($months as $num => $name):
                                                $selected = ($editAssessment && $editAssessment['period_month'] == $num) ? 'selected' : '';
                                                ?>
                                                <option value="<?php echo $num; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="fiscal_year"><?php echo translate('fiscal_year'); ?>*</label>
                                        <input type="number" class="form-control" id="fiscal_year" name="fiscal_year" required 
                                               value="<?php echo $editAssessment ? (int)$editAssessment['fiscal_year'] : date('Y'); ?>" min="2000" max="2100">
                                    </div>
                                    <div class="form-group">
                                        <label for="assessed_value"><?php echo translate('assessed_value'); ?> (<?php echo translate('rental_income'); ?>)*</label>
                                        <input type="number" step="0.01" class="form-control" id="assessed_value" name="assessed_value" required 
                                               value="<?php echo $editAssessment ? (float)$editAssessment['assessed_value'] : ($editAssessment['monthly_rent'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="tax_amount"><?php echo translate('calculated_tax_amount'); ?></label>
                                        <input type="number" step="0.01" class="form-control" id="tax_amount" name="tax_amount" readonly 
                                               value="<?php echo $editAssessment ? (float)$editAssessment['tax_amount'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="notes"><?php echo translate('notes'); ?></label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $editAssessment ? sanitize($editAssessment['notes']) : ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo $editAssessment ? translate('update') : translate('submit'); ?></button>
                                    <?php if ($editAssessment): ?>
                                        <a href="rental_tax.php?contract=<?php echo isset($_GET['contract']) ? (int)$_GET['contract'] : ''; ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('rental_tax_assessments'); ?></h3>
                                <div class="card-tools">
                                    <form method="get" class="form-inline">
                                        <?php if (isset($_GET['contract'])): ?>
                                            <input type="hidden" name="contract" value="<?php echo (int)$_GET['contract']; ?>">
                                        <?php endif; ?>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" name="fiscal_year" placeholder="<?php echo translate('fiscal_year'); ?>" 
                                                   value="<?php echo isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : date('Y'); ?>" 
                                                   min="2000" max="2100" onchange="this.form.submit()">
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_GET['contract'])): 
                                    $contract_id = (int)$_GET['contract'];
                                    $stmt = $conn->prepare("SELECT c.*, r.room_number, rt.type_name, b.building_name, p.property_name 
                                                            FROM rent_contracts c
                                                            JOIN rooms r ON c.room_id = r.room_id
                                                            JOIN room_types rt ON r.type_id = rt.type_id
                                                            JOIN buildings b ON r.building_id = b.building_id
                                                            JOIN properties p ON b.property_id = p.property_id
                                                            WHERE c.contract_id = ?");
                                    $stmt->bind_param("i", $contract_id);
                                    $stmt->execute();
                                    $contractResult = $stmt->get_result();
                                    $contractRow = $contractResult->fetch_assoc();
                                    $stmt->close();
                                    if ($contractRow):
                                ?>
                                    <div class="callout callout-info">
                                        <h5><?php echo translate('contract_details'); ?></h5>
                                        <p>
                                            <strong><?php echo translate('property'); ?>:</strong> <?php echo sanitize($contractRow['property_name']); ?><br>
                                            <strong><?php echo translate('building'); ?>:</strong> <?php echo sanitize($contractRow['building_name']); ?><br>
                                            <strong><?php echo translate('room'); ?>:</strong> <?php echo sanitize($contractRow['room_number']); ?> (<?php echo translate('type'); ?>: <?php echo sanitize($contractRow['type_name']); ?>)<br>
                                            <strong><?php echo translate('tenant'); ?>:</strong> <?php echo sanitize($contractRow['tenant_name']); ?><br>
                                            <strong><?php echo translate('monthly_rent'); ?>:</strong> <?php echo formatCurrency($contractRow['monthly_rent']); ?><br>
                                            <strong><?php echo translate('contract_period'); ?>:</strong> <?php echo date('M d, Y', strtotime($contractRow['start_date'])); ?> to <?php echo date('M d, Y', strtotime($contractRow['end_date'])); ?>
                                        </p>
                                    </div>
                                <?php endif; endif; ?>
                                
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('id'); ?></th>
                                            <?php if (!isset($_GET['contract'])): ?>
                                                <th><?php echo translate('room_tenant'); ?></th>
                                            <?php endif; ?>
                                            <th><?php echo translate('tax_rate'); ?></th>
                                            <th><?php echo translate('period'); ?></th>
                                            <th><?php echo translate('income'); ?></th>
                                            <th><?php echo translate('tax'); ?></th>
                                            <th><?php echo translate('status'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT a.*, r.rate_name, r.rate_percentage, rm.room_number, rt.type_name, 
                                                b.building_name, p.property_name, c.tenant_name, c.contract_id
                                                FROM tax_assessments a
                                                JOIN tax_rates r ON a.rate_id = r.rate_id
                                                JOIN rent_contracts c ON a.contract_id = c.contract_id
                                                JOIN rooms rm ON c.room_id = rm.room_id
                                                JOIN room_types rt ON rm.type_id = rt.type_id
                                                JOIN buildings b ON rm.building_id = b.building_id
                                                JOIN properties p ON b.property_id = p.property_id
                                                WHERE a.is_rental = TRUE";
                                        $params = [];
                                        $types = "";
                                        
                                        if (isset($_GET['contract'])) {
                                            $contract_id = (int)$_GET['contract'];
                                            $sql .= " AND c.contract_id = ?";
                                            $params[] = $contract_id;
                                            $types .= "i";
                                        }
                                        
                                        if (isset($_GET['fiscal_year'])) {
                                            $fiscal_year = (int)$_GET['fiscal_year'];
                                            $sql .= " AND a.fiscal_year = ?";
                                            $params[] = $fiscal_year;
                                            $types .= "i";
                                        } else {
                                            $sql .= " AND a.fiscal_year = ?";
                                            $params[] = date('Y');
                                            $types .= "i";
                                        }
                                        
                                        $sql .= " ORDER BY a.fiscal_year DESC, a.period_month DESC";
                                        $stmt = $conn->prepare($sql);
                                        if (!empty($params)) {
                                            $stmt->bind_param($types, ...$params);
                                        }
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        while ($row = $result->fetch_assoc()):
                                            $statusClass = $row['status'] == 'paid' ? 'badge-success' : ($row['status'] == 'overdue' ? 'badge-danger' : 'badge-warning');
                                            $monthName = date('F', mktime(0, 0, 0, $row['period_month'], 1));
                                            $rateInfo = sanitize($row['rate_name']) . " (" . (float)$row['rate_percentage'] . "%)";
                                            ?>
                                            <tr>
                                                <td><?php echo (int)$row['assessment_id']; ?></td>
                                                <?php if (!isset($_GET['contract'])): ?>
                                                    <td><?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?> - Room <?php echo sanitize($row['room_number']); ?> (<?php echo translate('type'); ?>: <?php echo sanitize($row['type_name']); ?>)<br><small><?php echo translate('tenant'); ?>: <?php echo sanitize($row['tenant_name']); ?></small></td>
                                                <?php endif; ?>
                                                <td><?php echo $rateInfo; ?></td>
                                                <td><?php echo $monthName . " " . (int)$row['fiscal_year']; ?></td>
                                                <td><?php echo formatCurrency($row['assessed_value']); ?></td>
                                                <td><?php echo formatCurrency($row['tax_amount']); ?></td>
                                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo sanitize($row['status']); ?></span></td>
                                                <td>
                                                    <a href="rental_tax.php?edit=<?php echo (int)$row['assessment_id']; ?>&contract=<?php echo (int)$row['contract_id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="<?php echo translate('edit'); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="process_rental_tax.php?delete=<?php echo (int)$row['assessment_id']; ?>&contract=<?php echo (int)$row['contract_id']; ?>" 
                                                       class="btn btn-sm btn-danger" title="<?php echo translate('delete'); ?>" 
                                                       onclick="return confirm('<?php echo translate('confirm_delete_assessment'); ?>');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php if ($row['status'] != 'paid'): ?>
                                                        <a href="process_rental_tax.php?change_status=<?php echo (int)$row['assessment_id']; ?>&new_status=paid&contract=<?php echo (int)$row['contract_id']; ?>" 
                                                           class="btn btn-sm btn-success" title="<?php echo translate('mark_as_paid'); ?>">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($row['status'] != 'overdue'): ?>
                                                        <a href="process_rental_tax.php?change_status=<?php echo (int)$row['assessment_id']; ?>&new_status=overdue&contract=<?php echo (int)$row['contract_id']; ?>" 
                                                           class="btn btn-sm btn-warning" title="<?php echo translate('mark_as_overdue'); ?>">
                                                            <i class="fas fa-exclamation"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="payments.php?assessment=<?php echo (int)$row['assessment_id']; ?>" 
                                                       class="btn btn-sm btn-info" title="<?php echo translate('view_payments'); ?>">
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
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>
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
    
    // Fetch monthly rent and filter tax rates by room type
    $('#contract_id').change(function() {
        var contractId = $(this).val();
        var roomType = $(this).find('option:selected').data('room-type');
        if (contractId) {
            $.ajax({
                url: 'ajax_get_contract.php',
                type: 'GET',
                data: { contract_id: contractId },
                success: function(data) {
                    try {
                        var contract = JSON.parse(data);
                        $('#assessed_value').val(parseFloat(contract.monthly_rent).toFixed(2));
                        calculateTax();
                    } catch (e) {
                        console.error('Error parsing contract data:', e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                }
            });
        }
        // Filter tax rates (optional: implement server-side filtering by room_type)
        $('#rate_id').trigger('change');
    });
    
    $('#rate_id, #assessed_value').on('change keyup', function() {
        calculateTax();
    });
    
    function calculateTax() {
        var ratePercentage = parseFloat($('#rate_id option:selected').data('percentage')) || 0;
        var assessedValue = parseFloat($('#assessed_value').val()) || 0;
        if (ratePercentage && assessedValue > 0) {
            var taxAmount = assessedValue * (ratePercentage / 100);
            $('#tax_amount').val(taxAmount.toFixed(2));
        } else {
            $('#tax_amount').val('');
        }
    }
    
    // Initialize for edit mode
    <?php if ($editAssessment): ?>
        calculateTax();
    <?php endif; ?>
});
</script>
</body>
</html>
<?php $conn->close(); ?>