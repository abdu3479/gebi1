<?php
require_once '../config/database.php';
require_once 'includes/helpers.php';

// Ensure user is authenticated and has required role
ensureAuthenticated();
if (!isAdmin() && !isOfficer()) {
    $_SESSION['error_message'] = "Unauthorized access.";
    redirect('index.php');
}

// Handle language change
if (isset($_POST['lang']) && in_array($_POST['lang'], ['en', 'am'])) {
    $_SESSION['lang'] = $_POST['lang'];
    redirect('buildings.php' . (isset($_GET['edit']) ? '?edit=' . $_GET['edit'] : ''));
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

// Fetch building data for editing
$editBuilding = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT b.*, p.owner_id FROM buildings b JOIN properties p ON b.property_id = p.property_id WHERE b.building_id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $result = $stmt->get_result();
    $editBuilding = $result->fetch_assoc();
    $stmt->close();
    if (!$editBuilding) {
        $_SESSION['error_message'] = "Building not found.";
        redirect('buildings.php');
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

$pageTitle = translate('buildings');
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
                <a class="nav-link" data-toggle="dropdown" href="#" aria-label="Language">
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
        <a href="index.php" class="brand-link">
            <img src="../assets/images/logo.jpg" alt="Tax System Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo $lang === 'am' ? 'የግብር አስተዳደር' : 'PMTS'; ?></span>
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
                            <a href="buildings.php" class="nav-link active">
                                <i class="nav-icon fas fa-building"></i>
                                <p><?php echo translate('buildings'); ?></p>
                            </a>
                        </li>
                         <li class="nav-item">
                            <a href="rooms.php" class="nav-link active">
                                <i class="nav-icon fas fa-building"></i>
                                <p><?php echo translate('rooms'); ?></p>
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
                        <h1><?php echo translate('buildings'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo translate('dashboard'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo translate('buildings'); ?></li>
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
                                <h3 class="card-title"><?php echo isset($_GET['edit']) ? translate('edit_building') : translate('add_new_building'); ?></h3>
                            </div>
                            <form method="post" action="process_building.php">
                                <?php if (isset($_GET['edit'])): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo (int)$_GET['edit']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="form-group">
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
                                                $selected = (isset($editBuilding) && $editBuilding['property_id'] == $row['property_id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo (int)$row['property_id']; ?>" <?php echo $selected; ?>>
                                                    <?php echo sanitize($row['property_name']); ?> (<?php echo translate('owner'); ?>: <?php echo sanitize($row['full_name']); ?>)
                                                </option>
                                            <?php endwhile; 
                                            $stmt->close();
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="building_name"><?php echo translate('building_name'); ?>*</label>
                                        <input type="text" class="form-control" id="building_name" name="building_name" required 
                                               value="<?php echo isset($editBuilding['building_name']) ? sanitize($editBuilding['building_name']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="floor_count"><?php echo translate('floor_count'); ?>*</label>
                                        <input type="number" class="form-control" id="floor_count" name="floor_count" required min="1" 
                                               value="<?php echo isset($editBuilding['floor_count']) ? (int)$editBuilding['floor_count'] : '1'; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="room_count"><?php echo translate('room_count'); ?></label>
                                        <input type="number" class="form-control" id="room_count" name="room_count" min="0" 
                                               value="<?php echo isset($editBuilding['room_count']) ? (int)$editBuilding['room_count'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="building_area"><?php echo translate('building_area'); ?>*</label>
                                        <input type="number" step="0.01" class="form-control" id="building_area" name="building_area" required min="0" 
                                               value="<?php echo isset($editBuilding['building_area']) ? (float)$editBuilding['building_area'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="construction_type"><?php echo translate('construction_type'); ?>*</label>
                                        <select class="form-control select2" id="construction_type" name="construction_type" required>
                                            <option value=""><?php echo translate('select_construction_type'); ?></option>
                                            <?php
                                            $construction_types = ['wood', 'concrete', 'mixed'];
                                            foreach ($construction_types as $type):
                                                $selected = (isset($editBuilding['construction_type']) && $editBuilding['construction_type'] == $type) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $type; ?>" <?php echo $selected; ?>><?php echo translate($type); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="construction_year"><?php echo translate('construction_year'); ?></label>
                                        <input type="number" class="form-control" id="construction_year" name="construction_year" min="1900" max="<?php echo date('Y'); ?>" 
                                               value="<?php echo isset($editBuilding['construction_year']) ? (int)$editBuilding['construction_year'] : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="description"><?php echo translate('description'); ?></label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($editBuilding['description']) ? sanitize($editBuilding['description']) : ''; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="address"><?php echo translate('address'); ?></label>
                                        <input type="text" class="form-control" id="address" name="address" 
                                               value="<?php echo isset($editBuilding['address']) ? sanitize($editBuilding['address']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="building_type"><?php echo translate('building_type'); ?>*</label>
                                        <select class="form-control select2" id="building_type" name="building_type" required>
                                            <option value=""><?php echo translate('select_building_type'); ?></option>
                                            <?php
                                            $building_types = ['Residential', 'Commercial', 'Mixed', 'Other'];
                                            foreach ($building_types as $type):
                                                $selected = (isset($editBuilding['building_type']) && $editBuilding['building_type'] == $type) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $type; ?>" <?php echo $selected; ?>><?php echo translate($type); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? translate('update') : translate('submit'); ?></button>
                                    <?php if (isset($_GET['edit'])): ?>
                                        <a href="buildings.php" class="btn btn-default"><?php echo translate('cancel'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo translate('list_of_buildings'); ?></h3>
                                <?php if (isset($_GET['property']) && is_numeric($_GET['property'])): ?>
                                    <div class="card-tools">
                                        <a href="buildings.php" class="btn btn-sm btn-secondary"><?php echo translate('show_all_buildings'); ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('id'); ?></th>
                                            <th><?php echo translate('building_name'); ?></th>
                                            <th><?php echo translate('property'); ?></th>
                                            <th><?php echo translate('owner'); ?></th>
                                            <th><?php echo translate('floor_count'); ?></th>
                                            <th><?php echo translate('room_count'); ?></th>
                                            <th><?php echo translate('building_area'); ?></th>
                                            <th><?php echo translate('construction_type'); ?></th>
                                            <th><?php echo translate('construction_year'); ?></th>
                                            <th><?php echo translate('building_type'); ?></th>
                                            <th><?php echo translate('address'); ?></th>
                                            <th><?php echo translate('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT b.*, p.property_name, o.full_name 
                                                FROM buildings b 
                                                JOIN properties p ON b.property_id = p.property_id 
                                                JOIN owners o ON p.owner_id = o.id";
                                        $params = [];
                                        $types = '';
                                        
                                        if (isset($_GET['property']) && is_numeric($_GET['property'])) {
                                            $sql .= " WHERE b.property_id = ?";
                                            $params[] = (int)$_GET['property'];
                                            $types .= 'i';
                                        }
                                        
                                        $sql .= " ORDER BY b.building_name";
                                        $stmt = $conn->prepare($sql);
                                        if (!empty($params)) {
                                            $stmt->bind_param($types, ...$params);
                                        }
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()):
                                        ?>
                                            <tr>
                                                <td><?php echo (int)$row['building_id']; ?></td>
                                                <td><?php echo sanitize($row['building_name']); ?></td>
                                                <td><?php echo sanitize($row['property_name']); ?></td>
                                                <td><?php echo sanitize($row['full_name']); ?></td>
                                                <td><?php echo (int)$row['floor_count']; ?></td>
                                                <td><?php echo $row['room_count'] !== null ? (int)$row['room_count'] : '-'; ?></td>
                                                <td><?php echo number_format((float)$row['building_area'], 2); ?></td>
                                                <td><?php echo translate($row['construction_type']); ?></td>
                                                <td><?php echo $row['construction_year'] ? (int)$row['construction_year'] : '-'; ?></td>
                                                <td><?php echo translate($row['building_type']); ?></td>
                                                <td><?php echo $row['address'] ? sanitize($row['address']) : '-'; ?></td>
                                                <td>
                                                    <a href="buildings.php?edit=<?php echo (int)$row['building_id']; ?>" class="btn btn-sm btn-primary" title="<?php echo translate('edit'); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="process_building.php?delete=<?php echo (int)$row['building_id']; ?>" class="btn btn-sm btn-danger" title="<?php echo translate('delete'); ?>" onclick="return confirm('<?php echo translate('delete'); ?>?');">
                                                        <i class="fas fa-trash"></i>
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
        "responsive": true,
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
                }
            <?php endif; ?>
        }
    });
    
    $('.select2').select2({
        theme: 'bootstrap4'
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>