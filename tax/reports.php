<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has admin role
ensureAuthenticated();
if (!isAdmin()) {
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

$pageTitle = translate('reports');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.css">
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
            <img src="https://via.placeholder.com/50x50" alt="Tax System Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Tax Management</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="https://via.placeholder.com/160x160" class="img-circle elevation-2" alt="User Image">
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
                            <a href="reports.php" class="nav-link active">
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
                        <h1><?php echo translate('reports'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('reports'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php displayMessages(); ?>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('report_options'); ?></h3>
                            </div>
                            <div class="card-body">
                                <form method="get">
                                    <div class="form-group">
                                        <label><?php echo translate('report_type'); ?></label>
                                        <select name="report" class="form-control" onchange="this.form.submit()">
                                            <option value="tax_collection" <?php echo ($_GET['report'] ?? '') == 'tax_collection' ? 'selected' : ''; ?>><?php echo translate('tax_collection'); ?></option>
                                            <option value="property_taxes" <?php echo ($_GET['report'] ?? '') == 'property_taxes' ? 'selected' : ''; ?>><?php echo translate('property_taxes'); ?></option>
                                            <option value="building_taxes" <?php echo ($_GET['report'] ?? '') == 'building_taxes' ? 'selected' : ''; ?>><?php echo translate('building_taxes'); ?></option>
                                            <option value="delinquent_taxes" <?php echo ($_GET['report'] ?? '') == 'delinquent_taxes' ? 'selected' : ''; ?>><?php echo translate('delinquent_taxes'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <?php if (($_GET['report'] ?? '') == 'tax_collection'): ?>
                                    <div class="form-group">
                                        <label><?php echo translate('date_range'); ?></label>
                                        <input type="date" name="start_date" class="form-control" value="<?php echo sanitize($_GET['start_date'] ?? date('Y-m-01')); ?>">
                                        <small class="form-text text-muted"><?php echo translate('start_date'); ?></small>
                                    </div>
                                    <div class="form-group">
                                        <input type="date" name="end_date" class="form-control" value="<?php echo sanitize($_GET['end_date'] ?? date('Y-m-t')); ?>">
                                        <small class="form-text text-muted"><?php echo translate('end_date'); ?></small>
                                    </div>
                                    <?php else: ?>
                                    <div class="form-group">
                                        <label><?php echo translate('fiscal_year'); ?></label>
                                        <input type="number" name="fiscal_year" class="form-control" value="<?php echo sanitize($_GET['fiscal_year'] ?? date('Y')); ?>">
                                    </div>
                                    <?php endif; ?>
                                    
                                    <button type="submit" class="btn btn-primary btn-block"><?php echo translate('generate_report'); ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-9">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <?php 
                                    $reportTitles = [
                                        'tax_collection' => translate('tax_collection_report'),
                                        'property_taxes' => translate('property_tax_report'),
                                        'building_taxes' => translate('building_tax_report'),
                                        'delinquent_taxes' => translate('delinquent_tax_report')
                                    ];
                                    echo sanitize($reportTitles[$_GET['report'] ?? 'tax_collection']);
                                    ?>
                                </h3>
                                <div class="card-tools">
                                    <button class="btn btn-sm btn-secondary" onclick="window.print()">
                                        <i class="fas fa-print"></i> <?php echo translate('print'); ?>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="exportToExcel()">
                                        <i class="fas fa-file-excel"></i> <?php echo translate('export'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php 
                                $reportType = $_GET['report'] ?? 'tax_collection';
                                $startDate = $_GET['start_date'] ?? date('Y-m-01');
                                $endDate = $_GET['end_date'] ?? date('Y-m-t');
                                $fiscalYear = (int)($_GET['fiscal_year'] ?? date('Y'));
                                
                                if ($reportType == 'tax_collection'): ?>
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h4><?php echo translate('tax_collection_summary'); ?> (<?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?>)</h4>
                                        <canvas id="collectionChart" height="100"></canvas>
                                    </div>
                                </div>
                                
                                <table class="table table-bordered datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th><?php echo translate('date'); ?></th>
                                            <th><?php echo translate('property_building'); ?></th>
                                            <th><?php echo translate('assessment'); ?></th>
                                            <th><?php echo translate('amount'); ?></th>
                                            <th><?php echo translate('payment_method'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT p.*, a.assessment_id, a.tax_amount, a.fiscal_year,
                                                CONCAT(IFNULL(b.building_name, pr.property_name), ' (', IFNULL(b.building_id, pr.property_id), ')') AS target_name
                                                FROM payment p
                                                JOIN tax_assessments a ON p.tax_assessment_id = a.assessment_id
                                                JOIN properties pr ON a.property_id = pr.property_id
                                                LEFT JOIN buildings b ON a.building_id = b.building_id
                                                WHERE p.payment_date BETWEEN ? AND ?
                                                ORDER BY p.payment_date DESC";
                                        
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("ss", $startDate, $endDate);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $total = 0;
                                        $chartData = [];
                                        $chartLabels = [];
                                        
                                        while ($row = $result->fetch_assoc()):
                                            $total += $row['amount'];
                                            $date = date('Y-m-d', strtotime($row['payment_date']));
                                            if (!isset($chartData[$date])) {
                                                $chartData[$date] = 0;
                                                $chartLabels[] = $date;
                                            }
                                            $chartData[$date] += $row['amount'];
                                            ?>
                                            <tr>
                                                <td><?php echo sanitize($row['payment_date']); ?></td>
                                                <td><?php echo sanitize($row['target_name']); ?></td>
                                                <td><?php echo (int)$row['assessment_id']; ?> (<?php echo translate('year'); ?>: <?php echo (int)$row['fiscal_year']; ?>)</td>
                                                <td><?php echo formatCurrency($row['amount']); ?></td>
                                                <td><?php echo translate($row['payment_method']); ?></td>
                                            </tr>
                                        <?php endwhile; 
                                        $stmt->close();
                                        ?>
                                        <tr class="table-active">
                                            <td colspan="3" class="text-right"><strong><?php echo translate('total_collected'); ?>:</strong></td>
                                            <td colspan="2"><strong><?php echo formatCurrency($total); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <?php elseif ($reportType == 'property_taxes'): ?>
                                <h4><?php echo translate('property_tax_report'); ?> <?php echo translate('for_fiscal_year'); ?> <?php echo $fiscalYear; ?></h4>
                                <table class="table table-bordered datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th><?php echo translate('property_id'); ?></th>
                                            <th><?php echo translate('property_name'); ?></th>
                                            <th><?php echo translate('owner'); ?></th>
                                            <th><?php echo translate('assessments'); ?></th>
                                            <th><?php echo translate('total_tax'); ?></th>
                                            <th><?php echo translate('paid'); ?></th>
                                            <th><?php echo translate('balance'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT p.property_id, p.property_name, o.full_name AS owner_name,
                                                COUNT(a.assessment_id) AS assessment_count,
                                                SUM(a.tax_amount) AS total_tax,
                                                SUM(CASE WHEN a.status = 'paid' THEN a.tax_amount ELSE 0 END) AS paid_amount
                                                FROM properties p
                                                JOIN owners o ON p.owner_id = o.owner_id
                                                LEFT JOIN tax_assessments a ON p.property_id = a.property_id AND a.building_id IS NULL AND a.fiscal_year = ?
                                                GROUP BY p.property_id
                                                ORDER BY p.property_name";
                                        
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("i", $fiscalYear);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $grandTotal = 0;
                                        $grandPaid = 0;
                                        
                                        while ($row = $result->fetch_assoc()):
                                            $balance = $row['total_tax'] - $row['paid_amount'];
                                            $grandTotal += $row['total_tax'];
                                            $grandPaid += $row['paid_amount'];
                                            ?>
                                            <tr>
                                                <td><?php echo (int)$row['property_id']; ?></td>
                                                <td><?php echo sanitize($row['property_name']); ?></td>
                                                <td><?php echo sanitize($row['owner_name']); ?></td>
                                                <td><?php echo (int)$row['assessment_count']; ?></td>
                                                <td><?php echo formatCurrency($row['total_tax']); ?></td>
                                                <td><?php echo formatCurrency($row['paid_amount']); ?></td>
                                                <td><?php echo formatCurrency($balance); ?></td>
                                            </tr>
                                        <?php endwhile; 
                                        $stmt->close();
                                        ?>
                                        <tr class="table-active">
                                            <td colspan="4" class="text-right"><strong><?php echo translate('grand_total'); ?>:</strong></td>
                                            <td><strong><?php echo formatCurrency($grandTotal); ?></strong></td>
                                            <td><strong><?php echo formatCurrency($grandPaid); ?></strong></td>
                                            <td><strong><?php echo formatCurrency($grandTotal - $grandPaid); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <?php elseif ($reportType == 'building_taxes'): ?>
                                <h4><?php echo translate('building_tax_report'); ?> <?php echo translate('for_fiscal_year'); ?> <?php echo $fiscalYear; ?></h4>
                                <table class="table table-bordered datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th><?php echo translate('building_id'); ?></th>
                                            <th><?php echo translate('building_name'); ?></th>
                                            <th><?php echo translate('property'); ?></th>
                                            <th><?php echo translate('owner'); ?></th>
                                            <th><?php echo translate('assessments'); ?></th>
                                            <th><?php echo translate('total_tax'); ?></th>
                                            <th><?php echo translate('paid'); ?></th>
                                            <th><?php echo translate('balance'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT b.building_id, b.building_name, p.property_name, o.full_name AS owner_name,
                                                COUNT(a.assessment_id) AS assessment_count,
                                                SUM(a.tax_amount) AS total_tax,
                                                SUM(CASE WHEN a.status = 'paid' THEN a.tax_amount ELSE 0 END) AS paid_amount
                                                FROM buildings b
                                                JOIN properties p ON b.property_id = p.property_id
                                                JOIN owners o ON p.owner_id = o.owner_id
                                                LEFT JOIN tax_assessments a ON b.building_id = a.building_id AND a.fiscal_year = ?
                                                GROUP BY b.building_id
                                                ORDER BY p.property_name, b.building_name";
                                        
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("i", $fiscalYear);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $grandTotal = 0;
                                        $grandPaid = 0;
                                        
                                        while ($row = $result->fetch_assoc()):
                                            $balance = $row['total_tax'] - $row['paid_amount'];
                                            $grandTotal += $row['total_tax'];
                                            $grandPaid += $row['paid_amount'];
                                            ?>
                                            <tr>
                                                <td><?php echo (int)$row['building_id']; ?></td>
                                                <td><?php echo sanitize($row['building_name']); ?></td>
                                                <td><?php echo sanitize($row['property_name']); ?></td>
                                                <td><?php echo sanitize($row['owner_name']); ?></td>
                                                <td><?php echo (int)$row['assessment_count']; ?></td>
                                                <td><?php echo formatCurrency($row['total_tax']); ?></td>
                                                <td><?php echo formatCurrency($row['paid_amount']); ?></td>
                                                <td><?php echo formatCurrency($balance); ?></td>
                                            </tr>
                                        <?php endwhile; 
                                        $stmt->close();
                                        ?>
                                        <tr class="table-active">
                                            <td colspan="5" class="text-right"><strong><?php echo translate('grand_total'); ?>:</strong></td>
                                            <td><strong><?php echo formatCurrency($grandTotal); ?></strong></td>
                                            <td><strong><?php echo formatCurrency($grandPaid); ?></strong></td>
                                            <td><strong><?php echo formatCurrency($grandTotal - $grandPaid); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <?php elseif ($reportType == 'delinquent_taxes'): ?>
                                <h4><?php echo translate('delinquent_tax_report'); ?> <?php echo translate('for_fiscal_year'); ?> <?php echo $fiscalYear; ?></h4>
                                <table class="table table-bordered datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th><?php echo translate('assessment_id'); ?></th>
                                            <th><?php echo translate('property_building'); ?></th>
                                            <th><?php echo translate('owner'); ?></th>
                                            <th><?php echo translate('tax_amount'); ?></th>
                                            <th><?php echo translate('due_date'); ?></th>
                                            <th><?php echo translate('days_overdue'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT a.assessment_id, a.tax_amount, a.assessment_date,
                                                CONCAT(IFNULL(b.building_name, p.property_name), ' (', IFNULL(b.building_id, p.property_id), ')') AS target_name,
                                                o.full_name AS owner_name
                                                FROM tax_assessments a
                                                JOIN properties p ON a.property_id = p.property_id
                                                JOIN owners o ON p.owner_id = o.owner_id
                                                LEFT JOIN buildings b ON a.building_id = b.building_id
                                                WHERE a.status != 'paid' AND a.fiscal_year = ?
                                                ORDER BY a.assessment_date";
                                        
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("i", $fiscalYear);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $total = 0;
                                        
                                        while ($row = $result->fetch_assoc()):
                                            $total += $row['tax_amount'];
                                            $dueDate = date('Y-m-d', strtotime($row['assessment_date'] . ' + 30 days'));
                                            $daysOverdue = max(0, (strtotime(date('Y-m-d')) - strtotime($dueDate)) / (60 * 60 * 24));
                                            ?>
                                            <tr>
                                                <td><?php echo (int)$row['assessment_id']; ?></td>
                                                <td><?php echo sanitize($row['target_name']); ?></td>
                                                <td><?php echo sanitize($row['owner_name']); ?></td>
                                                <td><?php echo formatCurrency($row['tax_amount']); ?></td>
                                                <td><?php echo sanitize($dueDate); ?></td>
                                                <td><?php echo floor($daysOverdue); ?></td>
                                            </tr>
                                        <?php endwhile; 
                                        $stmt->close();
                                        ?>
                                        <tr class="table-active">
                                            <td colspan="3" class="text-right"><strong><?php echo translate('total_delinquent_taxes'); ?>:</strong></td>
                                            <td colspan="3"><strong><?php echo formatCurrency($total); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <?php endif; ?>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
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

    <?php if ($reportType == 'tax_collection'): ?>
    // Chart.js for Tax Collection
    const ctx = document.getElementById('collectionChart').getContext('2d');
    const chartData = <?php echo json_encode(array_values($chartData)); ?>;
    const chartLabels = <?php echo json_encode($chartLabels); ?>;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: '<?php echo translate('tax_collected'); ?>',
                data: chartData,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '<?php echo translate('amount'); ?>'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: '<?php echo translate('date'); ?>'
                    }
                }
            }
        }
    });
    <?php endif; ?>
});

function exportToExcel() {
    const table = document.querySelector('.datatable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (const row of rows) {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        for (const col of cols) {
            rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
        }
        csv.push(rowData.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const reportType = '<?php echo $reportType; ?>';
    const fileName = reportType + '_report_' + new Date().toISOString().slice(0,10) + '.csv';
    link.setAttribute('href', URL.createObjectURL(blob));
    link.setAttribute('download', fileName);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
</body>
</html>