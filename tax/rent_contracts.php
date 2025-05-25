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
if (isset($_POST['lang'])) {
    $_SESSION['lang'] = ($_POST['lang'] === 'am') ? 'am' : 'en';
    $redirectUrl = 'rent_contracts.php';
    
    $params = [];
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $params['edit'] = (int)$_GET['edit'];
    }
    if (isset($_GET['room']) && is_numeric($_GET['room'])) {
        $params['room'] = (int)$_GET['room'];
    }
    if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'active', 'terminated', 'completed'])) {
        $params['status'] = $_GET['status'];
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
    redirect('dashboard.php');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Update contract statuses based on dates
    updateContractStatuses($conn);
} catch (Exception $e) {
    $_SESSION['error_message'] = translate('database_error') . ": " . sanitize($e->getMessage());
    redirect('dashboard.php');
}

// Set role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

// Fetch contract data for editing
$editContract = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT * FROM rent_contracts WHERE contract_id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editContract = $result->fetch_assoc();
        $stmt->close();
        
        if (!$editContract) {
            $_SESSION['error_message'] = translate('contract_not_found');
            redirect('rent_contracts.php');
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = translate('error_fetching_contract') . ": " . sanitize($e->getMessage());
        redirect('rent_contracts.php');
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

$pageTitle = translate('rent_contracts');
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
                            <a href="rent_contracts.php" class="nav-link active">
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
                        <h1><?php echo translate('rent_contract'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('rent_contracts'); ?></li>
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
                                <h3 class="card-title"><?php echo isset($_GET['edit']) ? translate('edit_contract') : translate('new_contract'); ?></h3>
                            </div>
                            <form method="post" action="process_rent_contract.php">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <?php if (isset($_GET['edit'])): ?>
                                <input type="hidden" name="edit_id" value="<?php echo (int)$_GET['edit']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="room_id"><?php echo translate('room'); ?>*</label>
                                        <select class="form-control select2" id="room_id" name="room_id" required>
                                            <option value=""><?php echo translate('select_room'); ?></option>
                                            <?php
                                            try {
                                                $sql = "SELECT r.room_id, r.room_number, b.building_name, p.property_name 
                                                        FROM rooms r
                                                        JOIN buildings b ON r.building_id = b.building_id
                                                        JOIN properties p ON b.property_id = p.property_id";
                                                if (!isset($_GET['edit'])) {
                                                    $sql .= " WHERE r.is_occupied = 0";
                                                } else {
                                                    $sql .= " WHERE r.is_occupied = 0 OR r.room_id = ?";
                                                }
                                                $sql .= " ORDER BY p.property_name, b.building_name, r.room_number";
                                                $stmt = $conn->prepare($sql);
                                                if (isset($_GET['edit'])) {
                                                    $stmt->bind_param("i", $editContract['room_id']);
                                                }
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                
                                                while ($row = $result->fetch_assoc()):
                                                    $selected = (isset($editContract['room_id']) && $editContract['room_id'] == $row['room_id']) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo (int)$row['room_id']; ?>" <?php echo $selected; ?>>
                                                        <?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?> - <?php echo translate('room'); ?> <?php echo sanitize($row['room_number']); ?>
                                                    </option>
                                                <?php endwhile; 
                                                $stmt->close();
                                            } catch (Exception $e) {
                                                $_SESSION['error_messages'][] = translate('error_fetching_rooms') . ": " . sanitize($e->getMessage());
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="tenant_name"><?php echo translate('tenant_name'); ?>*</label>
                                        <input type="text" class="form-control" id="tenant_name" name="tenant_name" required 
                                               value="<?php echo isset($editContract['tenant_name']) ? sanitize($editContract['tenant_name']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="tenant_phone"><?php echo translate('tenant_phone'); ?></label>
                                        <input type="text" class="form-control" id="tenant_phone" name="tenant_phone" 
                                               value="<?php echo isset($editContract['tenant_phone']) ? sanitize($editContract['tenant_phone']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="tenant_id_number"><?php echo translate('tenant_id_number'); ?></label>
                                        <input type="text" class="form-control" id="tenant_id_number" name="tenant_id_number" 
                                               value="<?php echo isset($editContract['tenant_id_number']) ? sanitize($editContract['tenant_id_number']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="start_date"><?php echo translate('start_date'); ?>*</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required 
                                               value="<?php echo isset($editContract['start_date']) ? sanitize($editContract['start_date']) : date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="end_date"><?php echo translate('end_date'); ?>*</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required 
                                               value="<?php echo isset($editContract['end_date']) ? sanitize($editContract['end_date']) : date('Y-m-d', strtotime('+1 year')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="monthly_rent"><?php echo translate('monthly_rent'); ?>*</label>
                                        <input type="number" step="0.01" class="form-control" id="monthly_rent" name="monthly_rent" required 
                                               value="<?php echo isset($editContract['monthly_rent']) ? number_format((float)$editContract['monthly_rent'], 2, '.', '') : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="deposit_amount"><?php echo translate('deposit_amount'); ?></label>
                                        <input type="number" step="0.01" class="form-control" id="deposit_amount" name="deposit_amount" 
                                               value="<?php echo isset($editContract['deposit_amount']) ? number_format((float)$editContract['deposit_amount'], 2, '.', '') : '0'; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="notes"><?php echo translate('notes'); ?></label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($editContract['notes']) ? sanitize($editContract['notes']) : ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? translate('update') : translate('submit'); ?></button>
                                    <?php if (isset($_GET['edit'])): ?>
                                        <a href="rent_contracts.php" class="btn btn-default"><?php echo translate('cancel'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_rent_contracts'); ?></h3>
                                <div class="card-tools">
                                    <form method="get" class="form-inline">
                                        <div class="input-group input-group-sm">
                                            <select class="form-control select2" name="room" onchange="this.form.submit()">
                                                <option value=""><?php echo translate('all_rooms'); ?></option>
                                                <?php
                                                try {
                                                    $stmt = $conn->prepare("SELECT r.room_id, r.room_number, b.building_name, p.property_name 
                                                                           FROM rooms r
                                                                           JOIN buildings b ON r.building_id = b.building_id
                                                                           JOIN properties p ON b.property_id = p.property_id
                                                                           ORDER BY p.property_name, b.building_name, r.room_number");
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    
                                                    while ($row = $result->fetch_assoc()):
                                                        $selected = ($_GET['room'] ?? '') == $row['room_id'] ? 'selected' : '';
                                                        ?>
                                                        <option value="<?php echo (int)$row['room_id']; ?>" <?php echo $selected; ?>>
                                                            <?php echo sanitize($row['property_name']); ?> - <?php echo sanitize($row['building_name']); ?> - <?php echo translate('room'); ?> <?php echo sanitize($row['room_number']); ?>
                                                        </option>
                                                    <?php endwhile; 
                                                    $stmt->close();
                                                } catch (Exception $e) {
                                                    $_SESSION['error_messages'][] = translate('error_fetching_rooms') . ": " . sanitize($e->getMessage());
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="input-group input-group-sm ml-2">
                                            <select class="form-control" name="status" onchange="this.form.submit()">
                                                <option value=""><?php echo translate('all_statuses'); ?></option>
                                                <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>><?php echo translate('pending'); ?></option>
                                                <option value="active" <?php echo ($_GET['status'] ?? '') == 'active' ? 'selected' : ''; ?>><?php echo translate('active'); ?></option>
                                                <option value="terminated" <?php echo ($_GET['status'] ?? '') == 'terminated' ? 'selected' : ''; ?>><?php echo translate('terminated'); ?></option>
                                                <option value="completed" <?php echo ($_GET['status'] ?? '') == 'completed' ? 'selected' : ''; ?>><?php echo translate('completed'); ?></option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('id'); ?></th>
                                            <th><?php echo translate('room'); ?></th>
                                            <th><?php echo translate('tenant'); ?></th>
                                            <th><?php echo translate('period'); ?></th>
                                            <th><?php echo translate('rent'); ?></th>
                                            <th><?php echo translate('deposit'); ?></th>
                                            <th><?php echo translate('status'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $sql = "SELECT c.*, r.room_number, b.building_name, p.property_name 
                                                    FROM rent_contracts c
                                                    JOIN rooms r ON c.room_id = r.room_id
                                                    JOIN buildings b ON r.building_id = b.building_id
                                                    JOIN properties p ON b.property_id = p.property_id
                                                    WHERE 1=1";
                                            $params = [];
                                            $types = '';
                                            
                                            if (!empty($_GET['room']) && is_numeric($_GET['room'])) {
                                                $sql .= " AND c.room_id = ?";
                                                $params[] = (int)$_GET['room'];
                                                $types .= 'i';
                                            }
                                            
                                            if (!empty($_GET['status'])) {
                                                $validStatuses = ['pending', 'active', 'terminated', 'completed'];
                                                $status = in_array($_GET['status'], $validStatuses) ? $_GET['status'] : 'active';
                                                $sql .= " AND c.status = ?";
                                                $params[] = $status;
                                                $types .= 's';
                                            }
                                            
                                            $sql .= " ORDER BY c.start_date DESC";
                                            
                                            $stmt = $conn->prepare($sql);
                                            if (!empty($params)) {
                                                $stmt->bind_param($types, ...$params);
                                            }
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            $today = date('Y-m-d');
                                            while ($row = $result->fetch_assoc()):
                                                $roomInfo = sanitize($row['property_name']) . " - " . sanitize($row['building_name']) . " - " . translate('room') . " " . sanitize($row['room_number']);
                                                $period = date('M d, Y', strtotime($row['start_date'])) . " - " . date('M d, Y', strtotime($row['end_date']));
                                                $is_future = $row['start_date'] > $today;
                                                $is_current = $row['start_date'] <= $today && $row['end_date'] >= $today;
                                                $is_past = $row['end_date'] < $today;
                                                ?>
                                                <tr>
                                                    <td><?php echo (int)$row['contract_id']; ?></td>
                                                    <td><?php echo $roomInfo; ?></td>
                                                    <td><?php echo sanitize($row['tenant_name']); ?></td>
                                                    <td><?php echo $period; ?></td>
                                                    <td><?php echo formatCurrency($row['monthly_rent']); ?></td>
                                                    <td><?php echo formatCurrency($row['deposit_amount']); ?></td>
                                                    <td>
                                                        <span class="badge <?php
                                                            $statusClass = match ($row['status']) {
                                                                'active' => 'badge-success',
                                                                'pending' => 'badge-warning',
                                                                'terminated' => 'badge-danger',
                                                                'completed' => 'badge-info',
                                                                default => 'badge-secondary'
                                                            };
                                                            echo $statusClass;
                                                        ?>">
                                                            <?php echo translate($row['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="rent_contracts.php?edit=<?php echo (int)$row['contract_id']; ?>" class="btn btn-sm btn-primary" title="<?php echo translate('edit'); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="process_rent_contract.php?delete=<?php echo (int)$row['contract_id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-danger" title="<?php echo translate('delete'); ?>" onclick="return confirm('<?php echo translate('confirm_delete_contract'); ?>');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <?php if ($row['status'] === 'pending' && $is_future): ?>
                                                            <a href="process_rent_contract.php?change_status=<?php echo (int)$row['contract_id']; ?>&new_status=active&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-success" title="<?php echo translate('approve'); ?>">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php elseif ($row['status'] === 'active' && $is_current): ?>
                                                            <a href="process_rent_contract.php?change_status=<?php echo (int)$row['contract_id']; ?>&new_status=terminated&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-warning" title="<?php echo translate('terminate'); ?>" onclick="return confirm('<?php echo translate('confirm_terminate_contract'); ?>');">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        <?php elseif ($row['status'] === 'terminated' && $is_current): ?>
                                                            <a href="process_rent_contract.php?change_status=<?php echo (int)$row['contract_id']; ?>&new_status=active&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-success" title="<?php echo translate('activate'); ?>">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="rental_payments.php?contract=<?php echo (int)$row['contract_id']; ?>" class="btn btn-sm btn-info" title="<?php echo translate('view_payments'); ?>">
                                                            <i class="fas fa-file-invoice-dollar"></i>
                                                        </a>
                                                        <a href="rental_tax.php?contract=<?php echo (int)$row['contract_id']; ?>" class="btn btn-sm btn-warning" title="<?php echo translate('tax_assessment'); ?>">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; 
                                            $stmt->close();
                                        } catch (Exception $e) {
                                            $_SESSION['error_messages'][] = translate('error_fetching_contracts') . ": " . sanitize($e->getMessage());
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

    $('form').on('submit', function() {
        return confirm('<?php echo $lang === 'am' ? 'ይህን ቅጽ ማስገባት ወይም ማዘመን እንደሚፈልጉ እርግጠኛ ነዎት?' : 'Are you sure you want to submit or update this form?'; ?>');
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>