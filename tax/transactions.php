<?php
session_start();
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_messages'][] = translate('unauthorized_access');
    redirect('index.php');
}

// Generate CSRF token if not set or expired
if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    $_SESSION['csrf_token_time'] = time();
}

// Handle language change
if (isset($_POST['lang']) && in_array($_POST['lang'], ['en', 'am'])) {
    $_SESSION['lang'] = $_POST['lang'];
    $redirectUrl = 'transactions.php';
    if (isset($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT)) {
        $redirectUrl .= '?edit=' . (int)$_GET['edit'];
    }
    redirect($redirectUrl);
}

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $_SESSION['error_messages'][] = translate('database_error') . ": " . sanitize($e->getMessage());
}

// Set role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

// Fetch transaction data for editing
$editTransaction = [];
if (isset($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT)) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT t.id, t.taxpayer_id, t.good_id, t.transaction_type, t.quantity, t.total_amount, t.tax_amount, t.transaction_date, 
                               tp.business_name, tp.taxpayer_category 
                               FROM transactions t
                               LEFT JOIN taxpayers tp ON t.taxpayer_id = tp.id
                               WHERE t.id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $editTransaction = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$editTransaction) {
            $_SESSION['error_messages'][] = translate('transaction_not_found');
            redirect('transactions.php');
        }
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_fetching_transaction') . ": " . sanitize($e->getMessage());
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
        $notificationsCount = (int)$stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_messages'][] = translate('error_fetching_notifications') . ": " . sanitize($e->getMessage());
    }
}

$pageTitle = translate('transactions');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tax Management System - <?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
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
        .main-header .navbar-nav .nav-item .nav-link { padding: 0.5rem 1rem; }
        .select2-container--default .select2-selection--single { height: calc(2.25rem + 2px); padding: 0.375rem 0.75rem; }
        body, input, select, textarea, .datatable { font-family: 'Noto Sans Ethiopic', 'Source Sans Pro', sans-serif; }
        .modal-lg { max-width: 800px; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Main Header -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
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
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <?php if ($notificationsCount > 0): ?>
                        <span class="badge badge-warning"><?php echo $notificationsCount; ?></span>
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
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                    <span class="hidden-xs"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
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
                    <a href="profile.php" class="d-block"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <small><?php echo ucfirst(htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8')); ?></small>
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
                            <a href="taxpayers.php" class="nav-link">
                                <i class="nav-icon fas fa-user-tie"></i>
                                <p><?php echo translate('taxpayers'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="goods.php" class="nav-link">
                                <i class="nav-icon fas fa-box"></i>
                                <p><?php echo translate('goods'); ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="transactions.php" class="nav-link active">
                                <i class="nav-icon fas fa-exchange-alt"></i>
                                <p><?php echo translate('transactions'); ?></p>
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
                            <a href="tax_report.php" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p><?php echo translate('annual_tax_report'); ?></p>
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
                        <h1><?php echo translate('transaction_management'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('transactions'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <!-- Display Session Messages -->
    
                
                <div class="row">
                    <!-- Transactions Table -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_transactions'); ?></h3>
                                <div class="card-tools">
                                    <?php if (isAdmin() || isOfficer()): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#transactionModal" data-mode="create">
                                            <i class="fas fa-plus"></i> <?php echo translate('add_transaction'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('id'); ?></th>
                                            <th><?php echo translate('taxpayer'); ?></th>
                                            <th><?php echo translate('good'); ?></th>
                                            <th><?php echo translate('type'); ?></th>
                                            <th><?php echo translate('quantity'); ?></th>
                                            <th><?php echo translate('total_amount'); ?></th>
                                            <th><?php echo translate('tax_amount'); ?></th>
                                            <th><?php echo translate('date'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $sql = "SELECT t.id, t.taxpayer_id, t.good_id, t.transaction_type, t.quantity, t.total_amount, t.tax_amount, t.transaction_date, 
                                                    tp.business_name, tp.taxpayer_category, g.name AS good_name
                                                    FROM transactions t
                                                    LEFT JOIN taxpayers tp ON t.taxpayer_id = tp.id
                                                    LEFT JOIN goods g ON t.good_id = g.id";
                                            
                                            $sql .= " ORDER BY t.transaction_date DESC";
                                            
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            while ($row = $result->fetch_assoc()):
                                                $category = $row['taxpayer_category'] ? ($lang === 'am' ? ($row['taxpayer_category'] === 'A' ? 'ሀ' : ($row['taxpayer_category'] === 'B' ? 'ለ' : 'ሐ')) : $row['taxpayer_category']) : '';
                                                $unit_price = $row['quantity'] > 0 ? $row['total_amount'] / $row['quantity'] : 0;
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['id']; ?></td>
                                                <td><?php echo $row['business_name'] ? htmlspecialchars($row['business_name'], ENT_QUOTES, 'UTF-8') . ' (' . $category . ')' : '-'; ?></td>
                                                <td><?php echo htmlspecialchars($row['good_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo ucfirst(htmlspecialchars($row['transaction_type'], ENT_QUOTES, 'UTF-8')); ?></td>
                                                <td><?php echo number_format((float)$row['quantity'], 2); ?></td>
                                                <td><?php echo formatCurrency($row['total_amount']); ?></td>
                                                <td><?php echo formatCurrency($row['tax_amount']); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($row['transaction_date'])); ?></td>
                                                <td>
                                                    <?php if (isAdmin() || isOfficer()): ?>
                                                        <button class="btn btn-sm btn-primary edit-transaction" 
                                                                data-mode="edit"
                                                                data-id="<?php echo (int)$row['id']; ?>"
                                                                data-taxpayer-id="<?php echo (int)($row['taxpayer_id'] ?? ''); ?>"
                                                                data-good-id="<?php echo (int)$row['good_id']; ?>"
                                                                data-transaction-type="<?php echo htmlspecialchars($row['transaction_type'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-quantity="<?php echo number_format((float)$row['quantity'], 2); ?>"
                                                                data-unit-price="<?php echo number_format((float)$unit_price, 2); ?>"
                                                                data-transaction-date="<?php echo htmlspecialchars($row['transaction_date'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-toggle="modal"
                                                                data-target="#transactionModal"
                                                                title="<?php echo translate('edit'); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger delete-transaction" 
                                                                data-id="<?php echo (int)$row['id']; ?>"
                                                                data-toggle="modal"
                                                                data-target="#deleteModal"
                                                                title="<?php echo translate('delete'); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; 
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'][] = translate('error_fetching_transactions') . ": " . sanitize($e->getMessage());
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Modal -->
                <div class="modal fade" id="transactionModal" tabindex="-1" role="dialog" aria-labelledby="transactionModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="transactionModalLabel"><?php echo translate('add_transaction'); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo translate('close'); ?>">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <form method="post" action="process_transactions.php" id="transactionForm">
                                <div class="modal-body">
                                    <input type="hidden" name="edit_id" id="edit_id">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="taxpayer_id"><?php echo translate('taxpayer'); ?>*</label>
                                                <select class="form-control select2-modal" id="taxpayer_id" name="taxpayer_id" required>
                                                    <option value=""><?php echo translate('select_taxpayer'); ?></option>
                                                    <?php
                                                    try {
                                                        $stmt = $conn->prepare("SELECT id, business_name, tin, taxpayer_category FROM taxpayers ORDER BY business_name");
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while ($row = $result->fetch_assoc()):
                                                            $category = $lang === 'am' ? ($row['taxpayer_category'] === 'A' ? 'ሀ' : ($row['taxpayer_category'] === 'B' ? 'ለ' : 'ሐ')) : $row['taxpayer_category'];
                                                            $selected = ($editTransaction && $editTransaction['taxpayer_id'] == $row['id']) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo (int)$row['id']; ?>" <?php echo $selected; ?> data-category="<?php echo htmlspecialchars($row['taxpayer_category'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo htmlspecialchars($row['business_name'], ENT_QUOTES, 'UTF-8') . ' (TIN: ' . htmlspecialchars($row['tin'], ENT_QUOTES, 'UTF-8') . ', ' . translate('category') . ': ' . $category . ')'; ?>
                                                    </option>
                                                    <?php endwhile; 
                                                        $stmt->close();
                                                    } catch (Exception $e) {
                                                        $_SESSION['error_messages'][] = translate('error_fetching_taxpayers') . ": " . sanitize($e->getMessage());
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="good_id"><?php echo translate('good'); ?>*</label>
                                                <select class="form-control select2-modal" id="good_id" name="good_id" required>
                                                    <option value=""><?php echo translate('select_good'); ?></option>
                                                    <?php
                                                    try {
                                                        $stmt = $conn->prepare("SELECT id, name, tot_rate, is_vat_exempt FROM goods ORDER BY name");
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while ($row = $result->fetch_assoc()):
                                                            $selected = ($editTransaction && $editTransaction['good_id'] == $row['id']) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo (int)$row['id']; ?>" <?php echo $selected; ?> 
                                                            data-tot-rate="<?php echo (float)$row['tot_rate']; ?>" 
                                                            data-is-vat-exempt="<?php echo (int)$row['is_vat_exempt']; ?>">
                                                        <?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                    <?php endwhile; 
                                                        $stmt->close();
                                                    } catch (Exception $e) {
                                                        $_SESSION['error_messages'][] = translate('error_fetching_goods') . ": " . sanitize($e->getMessage());
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="transaction_type"><?php echo translate('transaction_type'); ?>*</label>
                                                <select class="form-control" id="transaction_type" name="transaction_type" required>
                                                    <option value="sale"><?php echo translate('sale'); ?></option>
                                                    <option value="purchase"><?php echo translate('purchase'); ?></option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="quantity"><?php echo translate('quantity'); ?>*</label>
                                                <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required min="0">
                                            </div>
                                            <div class="form-group">
                                                <label for="unit_price"><?php echo translate('unit_price'); ?>*</label>
                                                <input type="number" step="0.01" class="form-control" id="unit_price" name="unit_price" required min="0">
                                            </div>
                                            <div class="form-group">
                                                <label for="transaction_date"><?php echo translate('transaction_date'); ?>*</label>
                                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="tax_amount"><?php echo translate('tax_amount'); ?></label>
                                                <input type="text" class="form-control" id="tax_amount" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('close'); ?></button>
                                    <?php if (isAdmin() || isOfficer()): ?>
                                        <button type="submit" class="btn btn-primary" id="submitTransaction"><?php echo translate('submit'); ?></button>
                                    <?php endif; ?>
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
                                <?php echo translate('confirm_delete_transaction'); ?>
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

    <!-- Footer -->
    <footer class="main-footer">
        <strong><?php echo $lang === 'am' ? 'የቅጂ መቤት © ' . date('Y') . ' የግብር አስተደር ስርዓት' : 'Copyright © ' . date('Y') . ' Tax Management System'; ?>.</strong>
        <?php echo $lang === 'am' ? 'ሁሉም መቤቶቤች የተጠበቁ ናቸው።' : 'All rights reserved.'; ?>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
$(function() {
    var taxRates = <?php echo json_encode(['A' => 0.15, 'B' => 0.0, 'C' => 0.0]); ?>;
    
    // Initialize DataTable
    $('.datatable').DataTable({
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-md-6"f>>Btr<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: <?php
            $langConfig = [
                'en' => [],
                'am' => [
                    'decimal' => '',
                    'emptyTable' => translate('no_data_available'),
                    'info' => translate('showing_entries'),
                    'infoEmpty' => translate('showing_zero_entries'),
                    'infoFiltered' => translate('filtered_from_max'),
                    'lengthMenu' => translate('show_menu_entries'),
                    'loadingRecords' => translate('loading'),
                    'processing' => translate('processing'),
                    'search' => translate('search') . ': ',
                    'zeroRecords' => translate('no_matching_records'),
                    'paginate' => [
                        'first' => translate('first'),
                        'last' => translate('last'),
                        'next' => translate('next'),
                        'previous' => translate('previous')
                    ],
                    'buttons' => [
                        'copy' => translate('copy'),
                        'csv' => translate('csv'),
                        'excel' => translate('excel'),
                        'pdf' => translate('pdf'),
                        'print' => translate('print')
                    ]
                ]
            ];
            echo json_encode($langConfig[$lang] ?? [], JSON_UNESCAPED_UNICODE);
        ?>
    }).buttons().container().appendTo('.card-tools');

    // Initialize Select2
    $('.select2').select2({ theme: 'bootstrap4' });
    function initializeModalSelect2() {
        $('.select2-modal').select2({
            theme: 'bootstrap4',
            dropdownParent: $('#transactionModal')
        });
    }

    $('#transactionModal').on('shown.bs.modal', initializeModalSelect2);

    // Reset modal form
    $('#transactionModal').on('hidden.bs.modal', function() {
        $('#transactionForm')[0].reset();
        $('.select2-modal').val('').trigger('change');
        $('#edit_id').val('');
        $('#tax_amount').val('');
        $('#submitTransaction').text('<?php echo translate('submit'); ?>');
        $('#transactionModalLabel').text('<?php echo translate('add_transaction'); ?>');
    });

    // Form validation
    $('#transactionForm').on('submit', function(e) {
        var quantity = parseFloat($('#quantity').val()) || 0;
        var unitPrice = parseFloat($('#unit_price').val()) || 0;
        if (quantity <= 0) {
            e.preventDefault();
            alert('<?php echo translate('quantity_must_be_positive'); ?>');
            return false;
        }
        if (unitPrice <= 0) {
            e.preventDefault();
            alert('<?php echo translate('unit_price_must_be_positive'); ?>');
            return false;
        }
    });

    // Calculate tax dynamically
    $('#taxpayer_id, #good_id, #quantity, #unit_price').on('change input', function() {
        var taxpayerId = $('#taxpayer_id').val();
        var goodId = $('#good_id').val();
        var quantity = parseFloat($('#quantity').val()) || 0;
        var unitPrice = parseFloat($('#unit_price').val()) || 0;
        var totalAmount = quantity * unitPrice;
        var taxAmount = 0;

        if (taxpayerId && goodId && totalAmount > 0) {
            var category = $('#taxpayer_id option:selected').data('category');
            var totRate = parseFloat($('#good_id option:selected').data('tot-rate')) || 0;
            var isVatExempt = parseInt($('#good_id option:selected').data('is-vat-exempt')) || 0;

            if (category === 'A' && !isVatExempt) {
                taxAmount = totalAmount * taxRates['A'];
            } else if (category === 'B' || category === 'C') {
                taxAmount = totalAmount * (totRate / 100);
            }
        }

        $('#tax_amount').val(taxAmount.toFixed(2));
    });

    // Handle modal show
    $('#transactionModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var mode = button.data('mode');
        var modal = $(this);

        if (mode === 'create') {
            modal.find('.modal-title').text('<?php echo translate('add_transaction'); ?>');
            modal.find('#submitTransaction').text('<?php echo translate('submit'); ?>');
            modal.find('#transactionForm')[0].reset();
            modal.find('#taxpayer_id').val('').trigger('change');
            modal.find('#good_id').val('').trigger('change');
            modal.find('#edit_id').val('');
            modal.find('#tax_amount').val('');
        } else if (mode === 'edit') {
            modal.find('.modal-title').text('<?php echo translate('edit_transaction'); ?>');
            modal.find('#submitTransaction').text('<?php echo translate('update'); ?>');
            var transactionId = button.data('id');
            $('#edit_id').val(transactionId);
            $('#taxpayer_id').val(button.data('taxpayer-id')).trigger('change');
            $('#good_id').val(button.data('good-id')).trigger('change');
            $('#transaction_type').val(button.data('transaction-type'));
            $('#quantity').val(button.data('quantity'));
            $('#unit_price').val(button.data('unit-price').toFixed(2));
            $('#transaction_date').val(button.data('transaction-date'));
            $('#taxpayer_id').trigger('change');
        }
    });

    // Handle delete
    $('.delete-transaction').on('click', function() {
        var transactionId = $(this).data('id');
        $('#deleteConfirm').attr('href', 'process_transactions.php?delete=' + transactionId + '&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>');
    });

    // Auto-open modal for edit
    <?php if (isset($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT) && $editTransaction): ?>
    $(document).ready(function() {
        var button = $('<button>').attr({
            'data-toggle': 'modal',
            'data-target': '#transactionModal',
            'data-mode': 'edit',
            'data-id': <?php echo (int)$_GET['edit']; ?>,
            'data-taxpayer-id': <?php echo (int)($editTransaction['taxpayer_id'] ?? ''); ?>,
            'data-good-id': <?php echo (int)($editTransaction['good_id'] ?? 0); ?>,
            'data-transaction-type': '<?php echo htmlspecialchars($editTransaction['transaction_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
            'data-quantity': <?php echo number_format((float)($editTransaction['quantity'] ?? 0), 2, '.', ''); ?>,
            'data-unit-price': <?php echo number_format((float)(($editTransaction['quantity'] ?? 0) > 0 ? ($editTransaction['total_amount'] ?? 0) / $editTransaction['quantity'] : 0), 2, '.', ''); ?>,
            'data-transaction-date': '<?php echo htmlspecialchars($editTransaction['transaction_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>'
        });
        $('#transactionModal').modal('show');
        $('#transactionModal').trigger('show.bs.modal', { relatedTarget: button[0] });
    });
    <?php endif; ?>
});
</script>
</body>
</html>
<?php $conn->close(); ?>