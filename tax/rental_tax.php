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
    $redirectUrl = 'rental_tax.php';
    
    $params = [];
    if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0) {
        $params['edit'] = (int)$_GET['edit'];
    }
    if (isset($_GET['contract']) && is_numeric($_GET['contract']) && $_GET['contract'] > 0) {
        $params['contract'] = (int)$_GET['contract'];
    }
    if (isset($_GET['fiscal_year']) && is_numeric($_GET['fiscal_year'])) {
        $params['fiscal_year'] = (int)$_GET['fiscal_year'];
    }
    
    if (!empty($params)) {
        $redirectUrl .= '?' . http_build_query($params);
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

// Get edit assessment data
$editAssessment = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0) {
    $edit_id = (int)$_GET['edit'];
    try {
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
            $_SESSION['error_messages'][] = translate('assessment_not_found');
            redirect('rental_tax.php');
        }
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_fetching_assessment') . ": " . sanitize($e->getMessage());
        redirect('rental_tax.php');
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
        $_SESSION['error_messages'][] = translate('error_fetching_notifications') . ": " . sanitize($e->getMessage());
    }
}

$pageTitle = translate('rental_tax_assessment');
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
                        <h1><?php echo translate('rental_tax_assessment'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('rental_tax_assessment'); ?></li>
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
                    <!-- Assessments Table -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('rental_tax_assessments'); ?></h3>
                                <div class="card-tools">
                                    <?php if (isAdmin() || isOfficer()): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#assessmentModal" data-mode="create">
                                            <i class="fas fa-plus"></i> <?php echo translate('new_assessment'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <form method="get" class="form-inline ml-2">
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
                                    try {
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
                                <?php 
                                        endif;
                                    } catch (Exception $e) {
                                        $_SESSION['error_messages'][] = translate('error_fetching_contract') . ": " . sanitize($e->getMessage());
                                    }
                                endif; ?>
                                
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
                                        try {
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
                                            
                                            if (isset($_GET['contract']) && is_numeric($_GET['contract'])) {
                                                $contract_id = (int)$_GET['contract'];
                                                $sql .= " AND c.contract_id = ?";
                                                $params[] = $contract_id;
                                                $types .= "i";
                                            }
                                            
                                            if (isset($_GET['fiscal_year']) && is_numeric($_GET['fiscal_year'])) {
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
                                                $statusClass = match ($row['status']) {
                                                    'paid' => 'badge-success',
                                                    'overdue' => 'badge-danger',
                                                    default => 'badge-warning'
                                                };
                                                $monthName = date('F', mktime(0, 0, 0, $row['period_month'], 1));
                                                $rateInfo = sanitize($row['rate_name']) . " (" . (float)$row['rate_percentage'] . "%)";
                                                ?>
                                                <tr>
                                                    <td><?php echo (int)$row['assessment_id']; ?></td>
                                                    <?php if (!isset($_GET['contract'])): ?>
                                                        <td><?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?> - <?php echo translate('room'); ?> <?php echo sanitize($row['room_number']); ?> (<?php echo translate('type'); ?>: <?php echo sanitize($row['type_name']); ?>)<br><small><?php echo translate('tenant'); ?>: <?php echo sanitize($row['tenant_name']); ?></small></td>
                                                    <?php endif; ?>
                                                    <td><?php echo $rateInfo; ?></td>
                                                    <td><?php echo $monthName . " " . (int)$row['fiscal_year']; ?></td>
                                                    <td><?php echo formatCurrency($row['assessed_value']); ?></td>
                                                    <td><?php echo formatCurrency($row['tax_amount']); ?></td>
                                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo translate($row['status']); ?></span></td>
                                                    <td>
                                                        <?php if (isAdmin() || isOfficer()): ?>
                                                            <button class="btn btn-sm btn-primary edit-assessment" 
                                                                    data-mode="edit"
                                                                    data-id="<?php echo (int)$row['assessment_id']; ?>"
                                                                    data-contract-id="<?php echo (int)$row['contract_id']; ?>"
                                                                    data-rate-id="<?php echo (int)$row['rate_id']; ?>"
                                                                    data-assessment-date="<?php echo htmlspecialchars(sanitize($row['assessment_date'])); ?>"
                                                                    data-period-month="<?php echo (int)$row['period_month']; ?>"
                                                                    data-fiscal-year="<?php echo (int)$row['fiscal_year']; ?>"
                                                                    data-assessed-value="<?php echo number_format((float)$row['assessed_value'], 2, '.', ''); ?>"
                                                                    data-tax-amount="<?php echo number_format((float)$row['tax_amount'], 2, '.', ''); ?>"
                                                                    data-notes="<?php echo htmlspecialchars(sanitize($row['notes'] ?? '')); ?>"
                                                                    data-toggle="modal"
                                                                    data-target="#assessmentModal"
                                                                    title="<?php echo translate('edit'); ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-assessment" 
                                                                    data-id="<?php echo (int)$row['assessment_id']; ?>"
                                                                    data-contract-id="<?php echo (int)$row['contract_id']; ?>"
                                                                    data-toggle="modal"
                                                                    data-target="#deleteModal"
                                                                    title="<?php echo translate('delete'); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <?php if ($row['status'] !== 'paid'): ?>
                                                                <a href="process_rental_tax.php?change_status=<?php echo (int)$row['assessment_id']; ?>&new_status=paid&contract=<?php echo (int)$row['contract_id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                                   class="btn btn-sm btn-success" title="<?php echo translate('mark_as_paid'); ?>">
                                                                    <i class="fas fa-check"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($row['status'] !== 'overdue'): ?>
                                                                <a href="process_rental_tax.php?change_status=<?php echo (int)$row['assessment_id']; ?>&new_status=overdue&contract=<?php echo (int)$row['contract_id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="<?php echo translate('mark_as_overdue'); ?>">
                                                                    <i class="fas fa-exclamation"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <a href="payments.php?assessment=<?php echo (int)$row['assessment_id']; ?>" 
                                                           class="btn btn-sm btn-info" title="<?php echo translate('view_payments'); ?>">
                                                            <i class="fas fa-file-invoice-dollar"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile;
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'][] = translate('error_fetching_assessments') . ": " . sanitize($e->getMessage());
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assessment Modal -->
                <div class="modal fade" id="assessmentModal" tabindex="-1" role="dialog" aria-labelledby="assessmentModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="assessmentModalLabel"><?php echo translate('new_assessment'); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo translate('close'); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <form method="post" action="process_rental_tax.php" id="assessmentForm">
                                <div class="modal-body">
                                    <input type="hidden" name="edit_id" id="edit_id">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="contract_id"><?php echo translate('rent_contract'); ?>*</label>
                                                <select class="form-control select2" id="contract_id" name="contract_id" required <?php echo isset($_GET['contract']) ? 'disabled' : ''; ?>>
                                                    <option value=""><?php echo translate('select_contract'); ?></option>
                                                    <?php
                                                    try {
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
                                                            $selected = (isset($_GET['contract']) && (int)$_GET['contract'] == $row['contract_id']) ? 'selected' : '';
                                                            ?>
                                                            <option value="<?php echo (int)$row['contract_id']; ?>" data-room-type="<?php echo sanitize($row['type_name']); ?>" <?php echo $selected; ?>>
                                                                <?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?> - <?php echo translate('room'); ?> <?php echo sanitize($row['room_number']); ?> (<?php echo translate('tenant'); ?>: <?php echo sanitize($row['tenant_name']); ?>, <?php echo translate('type'); ?>: <?php echo sanitize($row['type_name']); ?>)
                                                            </option>
                                                        <?php endwhile;
                                                        $stmt->close();
                                                    } catch (Exception $e) {
                                                        $_SESSION['error_messages'][] = translate('error_fetching_contracts') . ": " . sanitize($e->getMessage());
                                                    }
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
                                                    try {
                                                        $stmt = $conn->prepare("SELECT * FROM tax_rates WHERE applicable_to = 'rental' AND (expiry_date IS NULL OR expiry_date >= CURDATE()) ORDER BY rate_name");
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while ($row = $result->fetch_assoc()):
                                                            ?>
                                                            <option value="<?php echo (int)$row['rate_id']; ?>" data-percentage="<?php echo (float)$row['rate_percentage']; ?>">
                                                                <?php echo sanitize($row['rate_name']); ?> (<?php echo translate('rental_tax'); ?> - <?php echo (float)$row['rate_percentage']; ?>%)
                                                            </option>
                                                        <?php endwhile;
                                                        $stmt->close();
                                                    } catch (Exception $e) {
                                                        $_SESSION['error_messages'][] = translate('error_fetching_tax_rates') . ": " . sanitize($e->getMessage());
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="assessment_date"><?php echo translate('assessment_date'); ?>*</label>
                                                <input type="date" class="form-control" id="assessment_date" name="assessment_date" required value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
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
                                                        ?>
                                                        <option value="<?php echo $num; ?>"><?php echo $name; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="fiscal_year"><?php echo translate('fiscal_year'); ?>*</label>
                                                <input type="number" class="form-control" id="fiscal_year" name="fiscal_year" required 
                                                       value="<?php echo date('Y'); ?>" min="2000" max="2100">
                                            </div>
                                            <div class="form-group">
                                                <label for="assessed_value"><?php echo translate('assessed_value'); ?> (<?php echo translate('rental_income'); ?>)*</label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="assessed_value" name="assessed_value" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="tax_amount"><?php echo translate('calculated_tax_amount'); ?></label>
                                                <input type="number" step="0.01" class="form-control" id="tax_amount" name="tax_amount" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="notes"><?php echo translate('notes'); ?></label>
                                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('close'); ?></button>
                                    <button type="submit" class="btn btn-primary" id="submitAssessment"><?php echo translate('submit'); ?></button>
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
                                <?php echo translate('confirm_delete_assessment'); ?>
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

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Reset form when modal is hidden
    $('#assessmentModal').on('hidden.bs.modal', function () {
        $('#assessmentForm')[0].reset();
        $('#edit_id').val('');
        $('#assessment_date').val('<?php echo date('Y-m-d'); ?>');
        $('#fiscal_year').val('<?php echo date('Y'); ?>');
        $('#tax_amount').val('');
        $('#submitAssessment').text('<?php echo translate('submit'); ?>');
        $('#assessmentModalLabel').text('<?php echo translate('new_assessment'); ?>');
        $('#contract_id').val('').trigger('change');
        $('#rate_id').val('').trigger('change');
        $('#period_month').val('');
        <?php if (isset($_GET['contract'])): ?>
        $('#contract_id').val('<?php echo (int)$_GET['contract']; ?>').trigger('change');
        <?php endif; ?>
    });

    // Handle modal show for create/edit
    $('#assessmentModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var mode = button.data('mode');
        var modal = $(this);

        if (mode === 'create') {
            modal.find('.modal-title').text('<?php echo translate('new_assessment'); ?>');
            modal.find('#submitAssessment').text('<?php echo translate('submit'); ?>');
            modal.find('#assessmentForm')[0].reset();
            modal.find('#edit_id').val('');
            modal.find('#assessment_date').val('<?php echo date('Y-m-d'); ?>');
            modal.find('#fiscal_year').val('<?php echo date('Y'); ?>');
            modal.find('#tax_amount').val('');
            modal.find('#contract_id').val('').trigger('change');
            modal.find('#rate_id').val('').trigger('change');
            modal.find('#period_month').val('');
            <?php if (isset($_GET['contract'])): ?>
            modal.find('#contract_id').val('<?php echo (int)$_GET['contract']; ?>').trigger('change');
            <?php endif; ?>
        } else if (mode === 'edit') {
            modal.find('.modal-title').text('<?php echo translate('edit_assessment'); ?>');
            modal.find('#submitAssessment').text('<?php echo translate('update'); ?>');

            var assessmentId = button.data('id');
            $('#edit_id').val(assessmentId);
            $('#contract_id').val(button.data('contract-id')).trigger('change');
            $('#rate_id').val(button.data('rate-id')).trigger('change');
            $('#assessment_date').val(button.data('assessment-date'));
            $('#period_month').val(button.data('period-month'));
            $('#fiscal_year').val(button.data('fiscal-year'));
            $('#assessed_value').val(button.data('assessed-value'));
            $('#tax_amount').val(button.data('tax-amount'));
            $('#notes').val(button.data('notes'));
        }
    });

    // Handle delete button click
    $('.delete-assessment').on('click', function() {
        var assessmentId = $(this).data('id');
        var contractId = $(this).data('contract-id');
        $('#deleteConfirm').attr('href', 'process_rental_tax.php?delete=' + assessmentId + '&contract=' + contractId + '&csrf_token=<?php echo $_SESSION['csrf_token']; ?>');
    });

    // Fetch monthly rent and filter tax rates
    $('#contract_id').on('change', function() {
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
                        alert('<?php echo translate('error_fetching_contract_data'); ?>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    alert('<?php echo translate('error_fetching_contract_data'); ?>');
                }
            });
        } else {
            $('#assessed_value').val('');
            $('#tax_amount').val('');
        }
    });

    // Calculate tax amount
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

    // Client-side form validation
    $('#assessmentForm').on('submit', function(e) {
        var fiscalYear = parseInt($('#fiscal_year').val()) || 0;
        var assessedValue = parseFloat($('#assessed_value').val()) || 0;
        var taxAmount = parseFloat($('#tax_amount').val()) || 0;

        if (fiscalYear < 2000 || fiscalYear > 2100) {
            e.preventDefault();
            alert('<?php echo translate('fiscal_year_invalid'); ?>');
            $('#fiscal_year').focus();
            return false;
        }

        if (assessedValue <= 0) {
            e.preventDefault();
            alert('<?php echo translate('assessed_value_must_be_positive'); ?>');
            $('#assessed_value').focus();
            return false;
        }

        if (taxAmount <= 0) {
            e.preventDefault();
            alert('<?php echo translate('tax_amount_must_be_positive'); ?>');
            $('#tax_amount').focus();
            return false;
        }

        return true;
    });

    // Auto-open modal for edit mode on page load
    <?php if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $_GET['edit'] > 0): ?>
    $(document).ready(function() {
        $('#assessmentModal').modal('show');
        $('#assessmentModal').trigger('show.bs.modal', {
            relatedTarget: {
                dataset: {
                    mode: 'edit',
                    id: <?php echo (int)$editAssessment['assessment_id']; ?>,
                    contractId: <?php echo (int)$editAssessment['contract_id']; ?>,
                    rateId: <?php echo (int)$editAssessment['rate_id']; ?>,
                    assessmentDate: '<?php echo htmlspecialchars(sanitize($editAssessment['assessment_date'])); ?>',
                    periodMonth: <?php echo (int)$editAssessment['period_month']; ?>,
                    fiscalYear: <?php echo (int)$editAssessment['fiscal_year']; ?>,
                    assessedValue: '<?php echo number_format((float)$editAssessment['assessed_value'], 2, '.', ''); ?>',
                    taxAmount: '<?php echo number_format((float)$editAssessment['tax_amount'], 2, '.', ''); ?>',
                    notes: '<?php echo htmlspecialchars(sanitize($editAssessment['notes'] ?? '')); ?>'
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