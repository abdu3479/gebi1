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
if (!isAdmin() && !isOfficer() && !isViewer()) {
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('index.php');
}

// Validate assessment ID
if (!isset($_GET['assessment_id']) || !is_numeric($_GET['assessment_id'])) {
    $_SESSION['error_message'] = "Invalid assessment ID.";
    redirect('assessments.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . sanitize($e->getMessage());
    redirect('assessments.php');
}

// Fetch assessment details
$assessment_id = (int)$_GET['assessment_id'];
$stmt = $conn->prepare("
    SELECT a.*, p.property_name, o.full_name, b.building_name, r.rate_name, r.rate_percentage
    FROM tax_assessments a
    JOIN properties p ON a.property_id = p.property_id
    JOIN owners o ON p.owner_id = o.id
    LEFT JOIN buildings b ON a.building_id = b.building_id
    JOIN tax_rates r ON a.rate_id = r.rate_id
    WHERE a.assessment_id = ? AND a.status = 'paid'
");
$stmt->bind_param("i", $assessment_id);
$stmt->execute();
$result = $stmt->get_result();
$assessment = $result->fetch_assoc();
$stmt->close();

if (!$assessment) {
    $_SESSION['error_message'] = "Assessment not found or not marked as paid.";
    redirect('assessments.php');
}

// Fetch stamp and signature
$stamp = null;
$signature = null;
$stmt = $conn->prepare("SELECT image, file_name FROM signatures_stamps WHERE type = ? ORDER BY upload_date DESC LIMIT 1");
$types = ['stamp', 'signature'];
foreach ($types as $type) {
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($type === 'stamp') {
            $stamp = $row;
        } else {
            $signature = $row;
        }
    }
}
$stmt->close();

// Set language and page title
$lang = $_SESSION['lang'] ?? 'en';
$pageTitle = translate('invoice') . " - Assessment ID: " . $assessment_id;

?>
<!DOCTYPE html>
<html lang="<?php echo sanitize($lang); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tax Management System - <?php echo sanitize($pageTitle); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Source Sans Pro', sans-serif;
            color: #555;
        }
        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }
        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }
        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }
        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }
        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }
        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.item.last td {
            border-bottom: none;
        }
        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
        .stamp-signature {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stamp-signature img {
            max-width: 150px;
            height: 300px;
        }
        @media print {
            .no-print {
                display: none;
            }
            .invoice-box {
                box-shadow: none;
                border: none;
            }
            .stamp-signature img {
                max-width: 150px;
                height: auto;
            }
        }
        img {
            border-radius: 50% 50%;
        }
        h1 {
            text-align: center;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link no-print" href="javascript:window.print()" aria-label="<?php echo translate('print_invoice'); ?>">
                    <i class="fas fa-print"></i> <?php echo translate('print'); ?>
                </a>
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

    <!-- Main Sidebar -->
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
                        <h1><?php echo translate('invoice'); ?> - Assessment ID: <?php echo $assessment_id; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item"><a href="assessments.php"><?php echo translate('tax_assessments'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('invoice'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php displayMessages(); ?>
                <div class="invoice-box">
                    <table cellpadding="0" cellspacing="0">
                        <tr class="top">
                            <td colspan="2">
                                <table>
                                    <tr>
                                        <td class="title">
                                            <img src="../assets/images/logo.jpg" style="width: 100%; max-width: 100px;" alt="Tax System Logo">
                                        </td>
                                        <td>
                                            <strong><?php echo translate('invoice'); ?> #<?php echo (int)$assessment['assessment_id']; ?></strong><br>
                                            <?php echo translate('issue_date'); ?>: <?php echo date('Y-m-d'); ?><br>
                                            <?php echo translate('status'); ?>: <span class="badge badge-success"><?php echo sanitize($assessment['status']); ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="information">
                            <td colspan="2">
                                <table>
                                    <tr>
                                        <td>
                                            <strong>PTMS</strong><br>
                                            Werabe City<br>
                                            Country, ZIP Code<br>
                                            Email: info@taxsystem.com
                                        </td>
                                        <td>
                                            <strong><?php echo translate('billed_to'); ?>:</strong><br>
                                            <?php echo sanitize($assessment['full_name']); ?><br>
                                            <?php echo translate('property'); ?>: <?php echo sanitize($assessment['property_name']); ?><br>
                                            <?php if ($assessment['building_name']): ?>
                                                <?php echo translate('building'); ?>: <?php echo sanitize($assessment['building_name']); ?><br>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="heading">
                            <td><?php echo translate('assessment_details'); ?></td>
                            <td></td>
                        </tr>
                        <tr class="item">
                            <td><?php echo translate('assessment_id'); ?></td>
                            <td><?php echo (int)$assessment['assessment_id']; ?></td>
                        </tr>
                        <tr class="item">
                            <td><?php echo translate('assessment_date'); ?></td>
                            <td><?php echo sanitize($assessment['assessment_date']); ?></td>
                        </tr>
                        <tr class="item">
                            <td><?php echo translate('fiscal_year'); ?></td>
                            <td><?php echo (int)$assessment['fiscal_year']; ?></td>
                        </tr>
                        <tr class="item">
                            <td><?php echo translate('tax_rate'); ?></td>
                            <td><?php echo sanitize($assessment['rate_name']); ?> (<?php echo (float)$assessment['rate_percentage']; ?>%)</td>
                        </tr>
                        <tr class="item">
                            <td><?php echo translate('assessed_value'); ?></td>
                            <td><?php echo formatCurrency($assessment['assessed_value']); ?></td>
                        </tr>
                        <tr class="total">
                            <td><strong><?php echo translate('tax_amount'); ?></strong></td>
                            <td><strong><?php echo formatCurrency($assessment['tax_amount']); ?></strong></td>
                        </tr>
                        <?php if ($assessment['notes']): ?>
                            <tr class="item last">
                                <td><?php echo translate('notes'); ?></td>
                                <td><?php echo sanitize($assessment['notes']); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                    <div class="stamp-signature">
                        <div>
                            <p><strong><?php echo translate('official_stamp'); ?></strong></p>
                            <?php if ($stamp): ?>
                                <img src="data:image/jpg;base64,<?php echo base64_encode($stamp['image']); ?>" alt="Official Stamp" title="Official Stamp">
                            <?php else: ?>
                                <p>[<?php echo translate('no_stamp_available'); ?>]</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p><strong><?php echo translate('authorized_signature'); ?></strong></p>
                            <?php if ($signature): ?>
                                <img src="data:image/jpg;base64,<?php echo base64_encode($signature['image']); ?>" alt="Authorized Signature" title="Authorized Signature">
                            <?php else: ?>
                                <p>[<?php echo translate('no_signature_available'); ?>]</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-4 no-print">
                        <a href="assessments.php" class="btn btn-default"><?php echo translate('back_to_assessments'); ?></a>
                        <button onclick="window.print()" class="btn btn-primary"><?php echo translate('print_invoice'); ?></button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>Copyright © <?php echo date('Y'); ?> PTMS.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
</body>
</html>