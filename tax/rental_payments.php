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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract_id = filter_input(INPUT_POST, 'contract_id', FILTER_VALIDATE_INT);
    $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $period_month = filter_input(INPUT_POST, 'period_month', FILTER_VALIDATE_INT);
    $period_year = filter_input(INPUT_POST, 'period_year', FILTER_VALIDATE_INT);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $receipt_number = filter_input(INPUT_POST, 'receipt_number', FILTER_SANITIZE_STRING);
    $received_by = filter_input(INPUT_POST, 'received_by', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

    // Validate inputs
    $valid_methods = ['cash', 'bank', 'mobile'];
    if (!$contract_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $payment_date) || $amount <= 0 || 
        $period_month < 1 || $period_month > 12 || $period_year < 2000 || $period_year > date('Y') + 1 || 
        !in_array($payment_method, $valid_methods) || empty($receipt_number) || empty($received_by)) {
        $_SESSION['error_message'] = translate('invalid_form_data');
        redirect('rental_payments.php?contract=' . ($contract_id ?: ''));
    }

    // Verify contract exists
    $stmt = $conn->prepare("SELECT room_id FROM rent_contracts WHERE contract_id = ? AND status = 'active'");
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contract_row = $result->fetch_assoc();
    $stmt->close();
    if (!$contract_row) {
        $_SESSION['error_message'] = translate('invalid_contract');
        redirect('rental_payments.php');
    }
    $room_id = $contract_row['room_id'];

    // Check for duplicate payment (except when editing)
    if (!$edit_id) {
        $stmt = $conn->prepare("SELECT payment_id FROM rental_payments WHERE contract_id = ? AND period_month = ? AND period_year = ?");
        $stmt->bind_param("iii", $contract_id, $period_month, $period_year);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            $_SESSION['error_message'] = translate('duplicate_payment_period');
            $stmt->close();
            redirect('rental_payments.php?contract=' . $contract_id);
        }
        $stmt->close();
    }

    // Begin transaction
    $conn->begin_transaction();
    try {
        if ($edit_id) {
            // Update existing payment
            $stmt = $conn->prepare("UPDATE rental_payments SET 
                                    contract_id = ?, payment_date = ?, amount = ?, 
                                    period_month = ?, period_year = ?, payment_method = ?, 
                                    receipt_number = ?, received_by = ?, notes = ?
                                    WHERE payment_id = ?");
            $stmt->bind_param("isdiissssi", $contract_id, $payment_date, $amount, 
                            $period_month, $period_year, $payment_method, 
                            $receipt_number, $received_by, $notes, $edit_id);
            $message = translate('payment_updated');
        } else {
            // Add new payment
            $stmt = $conn->prepare("INSERT INTO rental_payments 
                                    (contract_id, payment_date, amount, period_month, 
                                     period_year, payment_method, receipt_number, received_by, notes) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdiissss", $contract_id, $payment_date, $amount, 
                            $period_month, $period_year, $payment_method, 
                            $receipt_number, $received_by, $notes);
            $message = translate('payment_added');
        }

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $stmt->close();

        // Update room vacancy status
        $stmt = $conn->prepare("UPDATE rooms SET is_occupied = 1, days_vacant = 0 WHERE room_id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['success_message'] = $message;
        redirect('rental_payments.php?contract=' . $contract_id);
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = translate('error_processing_payment') . ': ' . sanitize($e->getMessage());
        redirect('rental_payments.php?contract=' . ($contract_id ?: ''));
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $delete_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    $contract_id = filter_input(INPUT_GET, 'contract', FILTER_VALIDATE_INT);

    if (!$delete_id || !$contract_id) {
        $_SESSION['error_message'] = translate('invalid_delete_request');
        redirect('rental_payments.php?contract=' . ($contract_id ?: ''));
    }

    // Verify payment exists and get room_id
    $stmt = $conn->prepare("SELECT p.contract_id, c.room_id 
                            FROM rental_payments p 
                            JOIN rent_contracts c ON p.contract_id = c.contract_id 
                            WHERE p.payment_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment_row = $result->fetch_assoc();
    $stmt->close();

    if (!$payment_row || $payment_row['contract_id'] != $contract_id) {
        $_SESSION['error_message'] = translate('payment_not_found');
        redirect('rental_payments.php?contract=' . $contract_id);
    }
    $room_id = $payment_row['room_id'];

    // Begin transaction
    $conn->begin_transaction();
    try {
        // Delete payment
        $stmt = $conn->prepare("DELETE FROM rental_payments WHERE payment_id = ?");
        $stmt->bind_param("i", $delete_id);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $stmt->close();

        // Check if other payments exist for the contract
        $stmt = $conn->prepare("SELECT COUNT(*) AS payment_count FROM rental_payments WHERE contract_id = ?");
        $stmt->bind_param("i", $contract_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment_count = $result->fetch_assoc()['payment_count'];
        $stmt->close();

        // If no other payments, update room to vacant
        if ($payment_count == 0) {
            $stmt = $conn->prepare("UPDATE rooms SET is_occupied = 0, days_vacant = DATEDIFF(CURDATE(), c.end_date), 
                                    reporting_status = 'pending' 
                                    FROM rent_contracts c 
                                    WHERE c.contract_id = ? AND rooms.room_id = c.room_id");
            $stmt->bind_param("i", $contract_id);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        $_SESSION['success_message'] = translate('payment_deleted');
        redirect('rental_payments.php?contract=' . $contract_id);
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = translate('error_deleting_payment') . ': ' . sanitize($e->getMessage());
        redirect('rental_payments.php?contract=' . $contract_id);
    }
}

// Get payment data for editing
$editPayment = null;
if (isset($_GET['edit'])) {
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt = $conn->prepare("SELECT * FROM rental_payments WHERE payment_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $editPayment = $result->fetch_assoc();
        $stmt->close();
    }
}

// Filter variables
$contract_id_filter = filter_input(INPUT_GET, 'contract', FILTER_VALIDATE_INT, ['options' => ['default' => '']]);
$period_year = filter_input(INPUT_GET, 'period_year', FILTER_VALIDATE_INT, ['options' => ['default' => (int)date('Y')]]);
if ($period_year < 2000 || $period_year > date('Y') + 1) {
    $period_year = (int)date('Y');
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

$pageTitle = translate('rental_payments');
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
            <img src="https://via.placeholder.com/50x50" alt="Tax System Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Tax Management</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="https://via.placeholder.com/160x160" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="profile.php" class="d-block"><?php echo sanitize($_SESSION['full_name']); ?></a>
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
                            <a href="rental_payments.php" class="nav-link active">
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
                        <h1><?php echo translate('rental_payments'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('rental_payments'); ?></li>
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
                                <h3 class="card-title"><?php echo $editPayment ? translate('edit_payment') : translate('new_payment'); ?></h3>
                            </div>
                            <form method="post">
                                <?php if ($editPayment): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo (int)$editPayment['payment_id']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="contract_id"><?php echo translate('rent_contract'); ?></label>
                                        <select class="form-control select2" id="contract_id" name="contract_id" required <?php echo $contract_id_filter ? 'disabled' : ''; ?>>
                                            <option value=""><?php echo translate('select_contract'); ?></option>
                                            <?php
                                            $stmt = $conn->prepare("SELECT c.contract_id, r.room_number, b.building_name, p.property_name, c.tenant_name 
                                                                    FROM rent_contracts c
                                                                    JOIN rooms r ON c.room_id = r.room_id
                                                                    JOIN buildings b ON r.building_id = b.building_id
                                                                    JOIN properties p ON b.property_id = p.property_id
                                                                    WHERE c.status = 'active'
                                                                    ORDER BY p.property_name, b.building_name, r.room_number");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            while ($row = $result->fetch_assoc()) {
                                                $selected = ($editPayment && $editPayment['contract_id'] == $row['contract_id']) || $contract_id_filter == $row['contract_id'] ? 'selected' : '';
                                                echo "<option value='{$row['contract_id']}' $selected>" . 
                                                     sanitize($row['property_name']) . " - " . 
                                                     sanitize($row['building_name']) . " - Room " . 
                                                     sanitize($row['room_number']) . " (Tenant: " . 
                                                     sanitize($row['tenant_name']) . ")</option>";
                                            }
                                            $stmt->close();
                                            ?>
                                        </select>
                                        <?php if ($contract_id_filter): ?>
                                            <input type="hidden" name="contract_id" value="<?php echo (int)$contract_id_filter; ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label for="payment_date"><?php echo translate('payment_date'); ?></label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" required 
                                               value="<?php echo $editPayment ? sanitize($editPayment['payment_date']) : date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="period_month"><?php echo translate('period_month'); ?></label>
                                        <select class="form-control select2" id="period_month" name="period_month" required>
                                            <option value=""><?php echo translate('select_month'); ?></option>
                                            <?php
                                            $months = [
                                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                            ];
                                            foreach ($months as $num => $name) {
                                                $selected = ($editPayment && $editPayment['period_month'] == $num) ? 'selected' : '';
                                                echo "<option value='$num' $selected>" . translate(strtolower($name)) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="period_year"><?php echo translate('period_year'); ?></label>
                                        <input type="number" class="form-control" id="period_year" name="period_year" required 
                                               value="<?php echo $editPayment ? (int)$editPayment['period_year'] : date('Y'); ?>" 
                                               min="2000" max="<?php echo date('Y') + 1; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="amount"><?php echo translate('amount'); ?></label>
                                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required 
                                               value="<?php echo $editPayment ? number_format($editPayment['amount'], 2, '.', '') : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="payment_method"><?php echo translate('payment_method'); ?></label>
                                        <select class="form-control select2" id="payment_method" name="payment_method" required>
                                            <option value=""><?php echo translate('select_method'); ?></option>
                                            <option value="cash" <?php echo $editPayment && $editPayment['payment_method'] == 'cash' ? 'selected' : ''; ?>><?php echo translate('cash'); ?></option>
                                            <option value="bank" <?php echo $editPayment && $editPayment['payment_method'] == 'bank' ? 'selected' : ''; ?>><?php echo translate('bank_transfer'); ?></option>
                                            <option value="mobile" <?php echo $editPayment && $editPayment['payment_method'] == 'mobile' ? 'selected' : ''; ?>><?php echo translate('mobile_payment'); ?></option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="receipt_number"><?php echo translate('receipt_number'); ?></label>
                                        <input type="text" class="form-control" id="receipt_number" name="receipt_number" required 
                                               value="<?php echo $editPayment ? sanitize($editPayment['receipt_number']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="received_by"><?php echo translate('received_by'); ?></label>
                                        <input type="text" class="form-control" id="received_by" name="received_by" required 
                                               value="<?php echo $editPayment ? sanitize($editPayment['received_by']) : sanitize($_SESSION['full_name']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="notes"><?php echo translate('notes'); ?></label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $editPayment ? sanitize($editPayment['notes']) : ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo $editPayment ? translate('update') : translate('submit'); ?></button>
                                    <?php if ($editPayment): ?>
                                        <a href="rental_payments.php?contract=<?php echo (int)$contract_id_filter; ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('rental_payments'); ?></h3>
                                <div class="card-tools">
                                    <form method="get" class="form-inline">
                                        <?php if ($contract_id_filter): ?>
                                            <input type="hidden" name="contract" value="<?php echo (int)$contract_id_filter; ?>">
                                        <?php endif; ?>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" name="period_year" placeholder="<?php echo translate('year'); ?>" 
                                                   value="<?php echo (int)$period_year; ?>" onchange="this.form.submit()" min="2000" max="<?php echo date('Y') + 1; ?>">
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($contract_id_filter): ?>
                                    <?php
                                    $stmt = $conn->prepare("SELECT c.*, r.room_number, b.building_name, p.property_name, c.tenant_name 
                                                            FROM rent_contracts c
                                                            JOIN rooms r ON c.room_id = r.room_id
                                                            JOIN buildings b ON r.building_id = b.building_id
                                                            JOIN properties p ON b.property_id = p.property_id
                                                            WHERE c.contract_id = ?");
                                    $stmt->bind_param("i", $contract_id_filter);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $contractRow = $result->fetch_assoc();
                                    $stmt->close();
                                    if ($contractRow):
                                    ?>
                                        <div class="callout callout-info">
                                            <h5><?php echo translate('contract_details'); ?></h5>
                                            <p>
                                                <strong><?php echo translate('property'); ?>:</strong> <?php echo sanitize($contractRow['property_name']); ?><br>
                                                <strong><?php echo translate('building'); ?>:</strong> <?php echo sanitize($contractRow['building_name']); ?><br>
                                                <strong><?php echo translate('room'); ?>:</strong> <?php echo sanitize($contractRow['room_number']); ?><br>
                                                <strong><?php echo translate('tenant'); ?>:</strong> <?php echo sanitize($contractRow['tenant_name']); ?><br>
                                                <strong><?php echo translate('monthly_rent'); ?>:</strong> <?php echo formatCurrency($contractRow['monthly_rent']); ?><br>
                                                <strong><?php echo translate('contract_period'); ?>:</strong> 
                                                <?php echo date('M d, Y', strtotime($contractRow['start_date'])); ?> to 
                                                <?php echo date('M d, Y', strtotime($contractRow['end_date'])); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('id'); ?></th>
                                            <?php if (!$contract_id_filter): ?>
                                                <th><?php echo translate('room_tenant'); ?></th>
                                            <?php endif; ?>
                                            <th><?php echo translate('date'); ?></th>
                                            <th><?php echo translate('period'); ?></th>
                                            <th><?php echo translate('amount'); ?></th>
                                            <th><?php echo translate('method'); ?></th>
                                            <th><?php echo translate('receipt'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT p.*, r.room_number, b.building_name, pr.property_name, c.tenant_name, c.contract_id
                                                FROM rental_payments p
                                                JOIN rent_contracts c ON p.contract_id = c.contract_id
                                                JOIN rooms r ON c.room_id = r.room_id
                                                JOIN buildings b ON r.building_id = b.building_id
                                                JOIN properties pr ON b.property_id = pr.property_id
                                                WHERE 1=1";
                                        $params = [];
                                        $types = "";
                                        
                                        if ($contract_id_filter) {
                                            $sql .= " AND p.contract_id = ?";
                                            $params[] = $contract_id_filter;
                                            $types .= "i";
                                        }
                                        if ($period_year) {
                                            $sql .= " AND p.period_year = ?";
                                            $params[] = $period_year;
                                            $types .= "i";
                                        }
                                        $sql .= " ORDER BY p.period_year DESC, p.period_month DESC, p.payment_date DESC";
                                        
                                        $stmt = $conn->prepare($sql);
                                        if (!empty($params)) {
                                            $stmt->bind_param($types, ...$params);
                                        }
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        while ($row = $result->fetch_assoc()) {
                                            $monthName = date('F', mktime(0, 0, 0, $row['period_month'], 1));
                                            ?>
                                            <tr>
                                                <td><?php echo (int)$row['payment_id']; ?></td>
                                                <?php if (!$contract_id_filter): ?>
                                                    <td><?php echo sanitize($row['property_name']) . ' - ' . 
                                                               sanitize($row['building_name']) . ' - Room ' . 
                                                               sanitize($row['room_number']); ?><br>
                                                        <small><?php echo translate('tenant'); ?>: <?php echo sanitize($row['tenant_name']); ?></small>
                                                    </td>
                                                <?php endif; ?>
                                                <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                                                <td><?php echo translate(strtolower($monthName)) . ' ' . (int)$row['period_year']; ?></td>
                                                <td><?php echo formatCurrency($row['amount']); ?></td>
                                                <td><?php echo translate($row['payment_method']); ?></td>
                                                <td><?php echo sanitize($row['receipt_number']); ?></td>
                                                <td>
                                                    <a href="rental_payments.php?edit=<?php echo (int)$row['payment_id']; ?>&contract=<?php echo (int)$row['contract_id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="<?php echo translate('edit'); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="rental_payments.php?delete=<?php echo (int)$row['payment_id']; ?>&contract=<?php echo (int)$row['contract_id']; ?>" 
                                                       class="btn btn-sm btn-danger" title="<?php echo translate('delete'); ?>" 
                                                       onclick="return confirm('<?php echo translate('confirm_delete_payment'); ?>');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="receipt.php?payment_id=<?php echo (int)$row['payment_id']; ?>" 
                                                       class="btn btn-sm btn-info" title="<?php echo translate('print_receipt'); ?>" target="_blank">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php }
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

    // Fetch monthly rent via AJAX
    $('#contract_id').change(function() {
        var contractId = $(this).val();
        if (contractId) {
            $.get('ajax_get_rent.php?contract_id=' + contractId, function(data) {
                if (data.success) {
                    $('#amount').val(data.monthly_rent);
                } else {
                    alert(data.error);
                    $('#amount').val('');
                }
            }, 'json').fail(function() {
                alert('<?php echo translate('error_fetching_rent'); ?>');
                $('#amount').val('');
            });
        } else {
            $('#amount').val('');
        }
    });
});
</script>
</body>
</html>