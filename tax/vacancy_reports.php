<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language change
if (isset($_POST['lang'])) {
    $_SESSION['lang'] = ($_POST['lang'] === 'am') ? 'am' : 'en';
    $redirectUrl = 'vacancy_reports.php';
    
    // Preserve any existing URL parameters
    $params = [];
    if (!empty($_GET)) {
        $params = array_filter($_GET, function($key) {
            return in_array($key, ['edit', 'view', 'status']);
        }, ARRAY_FILTER_USE_KEY);
    }
    
    if (!empty($params)) {
        $redirectUrl .= '?' . http_build_query($params);
    }
    
    redirect($redirectUrl);
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
    $_SESSION['error_message'] = translate('database_error') . ": " . sanitize($e->getMessage());
    redirect('index.php');
}

// Set role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $period_month = filter_input(INPUT_POST, 'period_month', FILTER_VALIDATE_INT);
    $period_year = filter_input(INPUT_POST, 'period_year', FILTER_VALIDATE_INT);
    $submission_method = filter_input(INPUT_POST, 'submission_method', FILTER_SANITIZE_STRING);
    $valid_methods = ['manual', 'api', 'email'];

    // Validate inputs
    if (!$period_month || $period_month < 1 || $period_month > 12 || 
        !$period_year || $period_year < 2000 || $period_year > date('Y') + 1 || 
        !in_array($submission_method, $valid_methods)) {
        $_SESSION['error_message'] = translate('invalid_report_params');
        redirect('vacancy_reports.php');
    }

    // Check for duplicate report
    try {
        $stmt = $conn->prepare("SELECT report_id FROM government_reports WHERE period_month = ? AND period_year = ?");
        $stmt->bind_param("ii", $period_month, $period_year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->fetch_assoc()) {
            $_SESSION['error_message'] = translate('duplicate_report');
            redirect('vacancy_reports.php');
        }
        $stmt->close();

        // Get vacant room data
        $stmt = $conn->prepare("SELECT r.room_id, r.room_number, r.days_vacant, 
                                       b.building_name, p.property_name, p.address,
                                       o.full_name AS owner_name, o.id_number AS owner_id
                                FROM rooms r
                                JOIN buildings b ON r.building_id = b.building_id
                                JOIN properties p ON b.property_id = p.property_id
                                JOIN owners o ON p.owner_id = o.id
                                WHERE r.is_occupied = 0 AND r.days_vacant > 30");
        $stmt->execute();
        $result = $stmt->get_result();
        $report_data = [];
        
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        $stmt->close();

        if (empty($report_data)) {
            $_SESSION['error_message'] = translate('no_vacant_rooms');
            redirect('vacancy_reports.php');
        }

        // Save report
        $json_data = json_encode($report_data);
        $user_id = (int)$_SESSION['user_id'];
        $reference_number = sprintf("VR-%04d%02d-%04d", $period_year, $period_month, rand(1, 9999));

        $stmt = $conn->prepare("INSERT INTO government_reports (report_date, period_month, period_year, submitted_by, submission_method, report_data, reference_number) VALUES (CURDATE(), ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisss", $period_month, $period_year, $user_id, $submission_method, $json_data, $reference_number);
        
        $conn->begin_transaction();
        try {
            if ($stmt->execute()) {
                // Update room reporting status
                $update_stmt = $conn->prepare("UPDATE rooms SET reporting_status = 'reported', last_reported_date = CURDATE() WHERE is_occupied = 0 AND days_vacant > 30");
                $update_stmt->execute();
                $update_stmt->close();
                
                $conn->commit();
                $_SESSION['success_message'] = translate('report_submitted');
            } else {
                throw new Exception(translate('report_submit_failed'));
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = translate('report_error') . ": " . sanitize($e->getMessage());
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_message'] = translate('database_error') . ": " . sanitize($e->getMessage());
    }
    
    redirect('vacancy_reports.php');
}

// Get current month/year
$current_month = date('n');
$current_year = date('Y');

// Get quick stats
$vacant_count = 0;
$overdue_count = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM rooms WHERE is_occupied = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $vacant_count = $result->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM rooms WHERE is_occupied = 0 AND days_vacant > 30 AND reporting_status != 'reported'");
    $stmt->execute();
    $result = $stmt->get_result();
    $overdue_count = $result->fetch_assoc()['total'];
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error_messages'][] = translate('stats_error') . ": " . sanitize($e->getMessage());
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
        $notificationsCount = $row['count'];
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('notifications_error') . ": " . sanitize($e->getMessage());
    }
}

$pageTitle = translate('vacancy_reports');
?>
<!DOCTYPE html>
<html lang="<?php echo sanitize($lang); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo translate('tax_management_system') . ' - ' . sanitize($pageTitle); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
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
        body {
            font-family: 'Source Sans Pro', sans-serif;
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
        .small-box {
            border-radius: 0.25rem;
            position: relative;
            display: block;
            margin-bottom: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .small-box > .inner {
            padding: 10px;
        }
        .small-box h3 {
            font-size: 38px;
            font-weight: bold;
            margin: 0 0 10px 0;
            white-space: nowrap;
            padding: 0;
        }
        .small-box p {
            font-size: 15px;
        }
        .small-box .icon {
            transition: all 0.3s linear;
            position: absolute;
            top: -10px;
            right: 10px;
            z-index: 0;
            font-size: 90px;
            color: rgba(0,0,0,0.15);
        }
        .small-box:hover .icon {
            font-size: 95px;
        }
        .bg-gray {
            background-color: #6c757d !important;
            color: white !important;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="<?php echo translate('toggle_sidebar'); ?>">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>
        
        <ul class="navbar-nav ml-auto">
            <!-- Language Switcher -->
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
            
            <!-- Notifications -->
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
            
            <!-- User Menu -->
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
        <a href="dashboard.php" class="brand-link">
            <img src="../assets/images/logo.jpg" alt="<?php echo translate('tax_system_logo'); ?>" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo translate('PMTS'); ?></span>
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
                            <a href="payments.php" class="nav-link">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p><?php echo translate('payments'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="vacancy_reports.php" class="nav-link active">
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
                        <h1><?php echo translate('vacancy_report_management'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('vacancy_reports'); ?></li>
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
                                <h3 class="card-title"><?php echo translate('generate_vacancy_report'); ?></h3>
                            </div>
                            <form method="post">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label><?php echo translate('reporting_period'); ?></label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <select class="form-control select2" name="period_month" required>
                                                    <?php
                                                    $months = [
                                                        1 => translate('january'), 
                                                        2 => translate('february'), 
                                                        3 => translate('march'), 
                                                        4 => translate('april'), 
                                                        5 => translate('may'), 
                                                        6 => translate('june'),
                                                        7 => translate('july'), 
                                                        8 => translate('august'), 
                                                        9 => translate('september'),
                                                        10 => translate('october'), 
                                                        11 => translate('november'), 
                                                        12 => translate('december')
                                                    ];
                                                    foreach ($months as $num => $name) {
                                                        $selected = ($num == $current_month) ? 'selected' : '';
                                                        echo "<option value='$num' $selected>$name</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="number" class="form-control" name="period_year" value="<?php echo $current_year; ?>" required min="2000" max="<?php echo $current_year + 1; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label><?php echo translate('submission_method'); ?></label>
                                        <select class="form-control select2" name="submission_method" required>
                                            <option value="manual"><?php echo translate('manual_submission'); ?></option>
                                            <option value="api"><?php echo translate('api_submission'); ?></option>
                                            <option value="email"><?php echo translate('email_submission'); ?></option>
                                        </select>
                                    </div>
                                    <div class="alert alert-info">
                                        <h5><i class="icon fas fa-info"></i> <?php echo translate('report_contents'); ?></h5>
                                        <?php echo translate('vacancy_report_info'); ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo translate('generate_submit_report'); ?></button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('quick_stats'); ?></h3>
                            </div>
                            <div class="card-body">
                                <div class="small-box bg-gray">
                                    <div class="inner">
                                        <h3><?php echo $vacant_count; ?></h3>
                                        <p><?php echo translate('currently_vacant_rooms'); ?></p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-door-open"></i>
                                    </div>
                                </div>
                                
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3><?php echo $overdue_count; ?></h3>
                                        <p><?php echo translate('unreported_vacancies'); ?></p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('vacant_rooms_requiring_reporting'); ?></h3>
                                <div class="card-tools">
                                    <button class="btn btn-sm btn-secondary" onclick="window.print()">
                                        <i class="fas fa-print"></i> <?php echo translate('print'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('room'); ?></th>
                                            <th><?php echo translate('property'); ?></th>
                                            <th><?php echo translate('owner'); ?></th>
                                            <th><?php echo translate('days_vacant'); ?></th>
                                            <th><?php echo translate('last_reported'); ?></th>
                                            <th><?php echo translate('status'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $stmt = $conn->prepare("SELECT r.room_number, r.days_vacant, r.last_reported_date, r.reporting_status,
                                                                   b.building_name, p.property_name, p.address,
                                                                   o.full_name AS owner_name
                                                            FROM rooms r
                                                            JOIN buildings b ON r.building_id = b.building_id
                                                            JOIN properties p ON b.property_id = p.property_id
                                                            JOIN owners o ON p.owner_id = o.id
                                                            WHERE r.is_occupied = 0 AND r.days_vacant > 30
                                                            ORDER BY r.days_vacant DESC");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            while ($row = $result->fetch_assoc()):
                                                $statusClass = $row['reporting_status'] == 'reported' ? 'badge-success' : 
                                                             ($row['reporting_status'] == 'non-compliant' ? 'badge-danger' : 'badge-warning');
                                                $statusText = translate($row['reporting_status']);
                                                ?>
                                                <tr>
                                                    <td><?php echo sanitize($row['room_number']); ?></td>
                                                    <td><?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?></td>
                                                    <td><?php echo sanitize($row['owner_name']); ?></td>
                                                    <td><?php echo (int)$row['days_vacant']; ?></td>
                                                    <td><?php echo $row['last_reported_date'] ? date('M d, Y', strtotime($row['last_reported_date'])) : translate('never'); ?></td>
                                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                </tr>
                                            <?php endwhile; 
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'][] = translate('error_fetching_vacancies') . ": " . sanitize($e->getMessage());
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('submitted_reports_history'); ?></h3>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('report_id'); ?></th>
                                            <th><?php echo translate('period'); ?></th>
                                            <th><?php echo translate('submitted_on'); ?></th>
                                            <th><?php echo translate('method'); ?></th>
                                            <th><?php echo translate('vacancies_reported'); ?></th>
                                            <th><?php echo translate('reference'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $stmt = $conn->prepare("SELECT report_id, period_month, period_year, report_date, 
                                                                   submission_method, reference_number, 
                                                                   JSON_LENGTH(report_data) AS vacancy_count
                                                            FROM government_reports
                                                            ORDER BY report_date DESC
                                                            LIMIT 10");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            while ($row = $result->fetch_assoc()):
                                                $monthName = translate(strtolower(date('F', mktime(0, 0, 0, $row['period_month'], 1))));
                                                ?>
                                                <tr>
                                                    <td><?php echo (int)$row['report_id']; ?></td>
                                                    <td><?php echo sanitize($monthName); ?> <?php echo (int)$row['period_year']; ?></td>
                                                    <td><?php echo date('M d, Y H:i', strtotime($row['report_date'])); ?></td>
                                                    <td><?php echo translate($row['submission_method'] . '_submission'); ?></td>
                                                    <td><?php echo (int)$row['vacancy_count']; ?></td>
                                                    <td><?php echo sanitize($row['reference_number']) ?: 'N/A'; ?></td>
                                                </tr>
                                            <?php endwhile; 
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'][] = translate('error_fetching_reports') . ": " . sanitize($e->getMessage());
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
    
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Confirm before submitting report
    $('form').on('submit', function() {
        return confirm('<?php echo $lang === 'am' ? 'ይህን የባዶ ክፍሎች ሪፖርት መላክ መፈለግዎን እርግጠኛ ነዎት?' : 'Are you sure you want to submit this vacancy report?'; ?>');
    });
});
</script>
</body>
</html>
<?php  ?>