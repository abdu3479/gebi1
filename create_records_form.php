<?php
// Ensure session is started with proper configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
session_start();

// Debugging: Log session and CSRF token details
error_log("Session ID: " . session_id());
error_log("Session CSRF Token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

require_once 'config/database.php';
require_once 'config/lang.php';
require_once 'classes/Owner.php';
require_once 'classes/Asset.php';
require_once 'classes/Lease.php';

// Handle language selection
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'am'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$lang = $_SESSION['lang'] ?? 'en';

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("Generated new CSRF token: " . $_SESSION['csrf_token']);
}

// Initialize database and classes
try {
    $db = new Database();
    $conn = $db->getConnection();
    $owner = new Owner($conn);
    $asset = new Asset($conn);
    $lease = new Lease($conn);
} catch (Exception $e) {
    error_log("Initialization failed: " . $e->getMessage());
    die($translations[$lang]['init_error'] ?? 'System initialization failed. Please contact the administrator.');
}

// Fetch property and construction types


try {
    // Fetch property types
    $property_types = $asset->getPropertyTypes();
    if (empty($property_types)) {
        throw new Exception($translations[$lang]['no_property_types_error'] ?? 'No property types available. Please add property types first.', 1001);
    }

    // Fetch construction types
    $construction_types = $asset->getConstructionTypes();
    // No exception for empty construction_types, as it's optional

} catch (Exception $e) {
    // Log the error with code
    error_log("Failed to fetch types: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");

    // Set arrays to empty to allow form rendering
    $property_types = [];
    $construction_types = [];

    // Store user-friendly error message in session
    $_SESSION['error_message'] = sprintf(
        $translations[$lang]['error_prefix'] ?? 'Error in %s: ',
        $translations[$lang]['asset_details'] ?? 'Asset Details'
    ) . ($translations[$lang][$e->getMessage()] ?? $e->getMessage());
}



$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST Data: " . json_encode($_POST));
    error_log("Submitted CSRF Token: " . ($_POST['csrf_token'] ?? 'Not set'));

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = $translations[$lang]['csrf_error'] ?? 'Invalid CSRF token';
        error_log("CSRF token validation failed. Submitted: " . ($_POST['csrf_token'] ?? 'Not set') . ", Expected: " . $_SESSION['csrf_token']);
    } else {
        $conn->autocommit(false); // Start transaction
        try {
            // Owner data
            $owner_data = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'id_number' => trim($_POST['id_number'] ?? ''),
                'status' => 'active',
                'is_active' => 1,
                'photo' => ''
            ];

            // Validate owner data lengths
            if (strlen($owner_data['address']) > 100) {
                throw new Exception($translations[$lang]['address_too_long'] ?? 'Address exceeds maximum length', 1001);
            }
            if (strlen($owner_data['id_number']) > 50) {
                throw new Exception($translations[$lang]['id_number_too_long'] ?? 'ID number exceeds maximum length', 1001);
            }

            // Handle owner photo upload
            if (!empty($_FILES['photo']['name'])) {
                if ($_FILES['photo']['size'] > 2 * 1024 * 1024) { // 2MB limit
                    throw new Exception($translations[$lang]['photo_too_large'] ?? 'Photo file size exceeds 2MB', 1001);
                }
                $allowed_types = ['image/jpeg', 'image/png'];
                if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                    throw new Exception($translations[$lang]['invalid_photo_type'] ?? 'Invalid photo file type. Allowed: JPEG, PNG', 1001);
                }
                $upload_dir = 'Uploads/owners/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $photo_path = $upload_dir . uniqid() . '_' . basename($_FILES['photo']['name']);
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    throw new Exception($translations[$lang]['photo_upload_failed'] ?? 'Failed to upload owner photo', 1001);
                }
                if (strlen($photo_path) > 255) {
                    throw new Exception($translations[$lang]['photo_path_too_long'] ?? 'Photo path exceeds maximum length', 1001);
                }
                $owner_data['photo'] = $photo_path;
            }

            // Validate owner data
            $owner_errors = [];
            if (empty($owner_data['first_name'])) {
                $owner_errors[] = $translations[$lang]['first_name_required'] ?? 'First name is required';
            }
            if (empty($owner_data['last_name'])) {
                $owner_errors[] = $translations[$lang]['last_name_required'] ?? 'Last name is required';
            }
            if (empty($owner_data['phone']) || !preg_match('/^\+251[79]\d{8}$/', $owner_data['phone'])) {
                $owner_errors[] = $translations[$lang]['phone_required'] ?? 'Valid Ethiopian phone number (+251...) is required';
            }
            if ($owner_errors) {
                throw new Exception(implode('; ', $owner_errors), 1001);
            }

            // Asset data
            $asset_data = [
    'asset_code' => trim($_POST['asset_code'] ?? ''),
    'property_name' => trim($_POST['property_name'] ?? ''),
    'block' => trim($_POST['block'] ?? ''),
    'kebele' => trim($_POST['kebele'] ?? ''),
    'lot_number' => trim($_POST['lot_number'] ?? ''),
    'property_type_id' => (int)($_POST['property_type_id'] ?? 0),
    'construction_type_id' => !empty($_POST['construction_type_id']) ? (int)$_POST['construction_type_id'] : null,
    'address' => trim($_POST['address_asset'] ?? ''),
    'size' => (float)($_POST['size'] ?? 0),
    'annual_lease_rate' => (float)($_POST['annual_lease_rate'] ?? 0),
    'registration_date' => trim($_POST['registration_date'] ?? ''),
    'status' => trim($_POST['asset_status'] ?? 'Active'),
    'registration_number' => trim($_POST['registration_number'] ?? ''),
    'tax_identification_number' => trim($_POST['tax_identification_number_asset'] ?? ''),
    'document_path' => '',
    'has_electricity' => isset($_POST['has_electricity']) ? 1 : 0,
    'has_water' => isset($_POST['has_water']) ? 1 : 0,
    'has_sewage' => isset($_POST['has_sewage']) ? 1 : 0,
];
            // Validate asset data lengths
            if (strlen($asset_data['asset_code']) > 50) {
                throw new Exception($translations[$lang]['asset_code_too_long'] ?? 'Asset code exceeds maximum length', 1002);
            }
            if (strlen($asset_data['property_name']) > 100) {
                throw new Exception($translations[$lang]['property_name_too_long'] ?? 'Property name exceeds maximum length', 1002);
            }
            if (strlen($asset_data['block']) > 50) {
                throw new Exception($translations[$lang]['block_too_long'] ?? 'Block exceeds maximum length', 1002);
            }
            if (strlen($asset_data['kebele']) > 50) {
                throw new Exception($translations[$lang]['kebele_too_long'] ?? 'Kebele exceeds maximum length', 1002);
            }
            if (strlen($asset_data['lot_number']) > 50) {
                throw new Exception($translations[$lang]['lot_number_too_long'] ?? 'Lot number exceeds maximum length', 1002);
            }
            if (strlen($asset_data['construction_type']) > 100) {
                throw new Exception($translations[$lang]['construction_type_too_long'] ?? 'Construction type exceeds maximum length', 1002);
            }
            if (strlen($asset_data['registration_number']) > 50) {
                throw new Exception($translations[$lang]['registration_number_too_long'] ?? 'Registration number exceeds maximum length', 1002);
            }
            if (strlen($asset_data['tax_identification_number']) > 50) {
                throw new Exception($translations[$lang]['tax_identification_number_too_long'] ?? 'Tax identification number exceeds maximum length', 1002);
            }

            // Handle asset document upload
            if (!empty($_FILES['document_path']['name'])) {
                if ($_FILES['document_path']['size'] > 5 * 1024 * 1024) { // 5MB limit
                    throw new Exception($translations[$lang]['document_too_large'] ?? 'Document file size exceeds 5MB', 1002);
                }
                $allowed_types = ['application/pdf', 'image/png', 'image/jpeg'];
                if (!in_array($_FILES['document_path']['type'], $allowed_types)) {
                    throw new Exception($translations[$lang]['invalid_document_type'] ?? 'Invalid document file type. Allowed: PDF, PNG, JPEG', 1002);
                }
                $upload_dir = 'Uploads/assets/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $doc_path = $upload_dir . uniqid() . '_' . basename($_FILES['document_path']['name']);
                if (!move_uploaded_file($_FILES['document_path']['tmp_name'], $doc_path)) {
                    throw new Exception($translations[$lang]['document_upload_failed'] ?? 'Failed to upload asset document', 1002);
                }
                if (strlen($doc_path) > 255) {
                    throw new Exception($translations[$lang]['document_path_too_long'] ?? 'Document path exceeds maximum length', 1002);
                }
                $asset_data['document_path'] = $doc_path;
            }

            // Validate asset data
            $asset_errors = [];
            if (empty($asset_data['asset_code'])) {
                $asset_errors[] = $translations[$lang]['asset_code_required'] ?? 'Asset code is required';
            }
            if ($asset_data['property_type_id'] < 1) {
                $asset_errors[] = $translations[$lang]['invalid_property_type'] ?? 'Invalid property type';
            }
            if ($asset_data['size'] <= 0) {
                $asset_errors[] = $translations[$lang]['size_invalid'] ?? 'Size must be greater than 0';
            }
            if ($asset_data['annual_lease_rate'] <= 0) {
                $asset_errors[] = $translations[$lang]['lease_rate_invalid'] ?? 'Annual lease rate must be greater than 0';
            }
            if (empty($asset_data['registration_date'])) {
                $asset_errors[] = $translations[$lang]['registration_date_invalid'] ?? 'Valid registration date is required';
            }
            if (!in_array($asset_data['status'], ['Active', 'Inactive', 'Under Maintenance', 'Leased', 'Sold', 'Confiscated'])) {
                $asset_errors[] = $translations[$lang]['invalid_status'] ?? 'Invalid asset status';
            }
            if ($asset_errors) {
                throw new Exception(implode('; ', $asset_errors), 1002);
            }

            // Lease data
            $lease_data = [
                'lease_number' => $lease->generateLeaseNumber(),
                'start_date' => trim($_POST['start_date'] ?? ''),
                'end_date' => trim($_POST['end_date'] ?? ''),
                'monthly_rent' => (float)($_POST['monthly_rent'] ?? 0),
                'yearly_rent_rate' => (float)($_POST['yearly_rent_rate'] ?? 0),
                'lease_term_of_years' => (int)($_POST['lease_term_of_years'] ?? 1),
                'payment_terms' => trim($_POST['payment_terms'] ?? 'monthly'),
                'terms_conditions' => strip_tags(trim($_POST['terms_conditions'] ?? '')),
                'created_by' => $_SESSION['user_id'] ?? 1,
                'status' => 'active'
            ];

            // Validate lease data lengths
            if (strlen($lease_data['terms_conditions']) > 65535) {
                throw new Exception($translations[$lang]['terms_conditions_too_long'] ?? 'Terms and conditions exceed maximum length', 1003);
            }

            // Validate lease data
            $lease_errors = [];
            if (empty($lease_data['start_date'])) {
                $lease_errors[] = $translations[$lang]['start_date_invalid'] ?? 'Valid start date is required';
            }
            if ($lease_data['end_date'] && strtotime($lease_data['end_date']) <= strtotime($lease_data['start_date'])) {
                $lease_errors[] = $translations[$lang]['date_validation'] ?? 'End date must be after start date';
            }
            if ($lease_data['monthly_rent'] <= 0) {
                $lease_errors[] = $translations[$lang]['rent_rate_invalid'] ?? 'Monthly rent must be greater than 0';
            }
            if ($lease_data['lease_term_of_years'] < 1) {
                $lease_errors[] = $translations[$lang]['lease_term_invalid'] ?? 'Lease term must be at least 1 year';
            }
            if (!in_array($lease_data['payment_terms'], ['monthly', 'quarterly', 'annually'])) {
                $lease_errors[] = $translations[$lang]['invalid_payment_terms'] ?? 'Invalid payment terms';
            }
            if ($lease_errors) {
                throw new Exception(implode('; ', $lease_errors), 1003);
            }

            // Create records
            error_log("Creating owner: " . json_encode($owner_data));
            $owner_id = $owner->create($owner_data);
            error_log("Owner created, ID: $owner_id");

            $asset_data['owner_id'] = $owner_id;
            error_log("Creating asset: " . json_encode($asset_data));
            $asset_id = $asset->create($asset_data);
            error_log("Asset created, ID: $asset_id");

            $lease_data['owner_id'] = $owner_id;
            $lease_data['asset_id'] = $asset_id;
            error_log("Creating lease: " . json_encode($lease_data));
            $lease_id = $lease->create($lease_data);
            error_log("Lease created, ID: $lease_id");

            $conn->commit();
            $success = sprintf(
                $translations[$lang]['success_message'] ?? 'Owner ID %d, Asset ID %d, and Lease %s created successfully!',
                $owner_id,
                $asset_id,
                $lease_data['lease_number']
            );

            // Refresh CSRF token only on successful submission
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            error_log("Refreshed CSRF token after success: " . $_SESSION['csrf_token']);
        } catch (Exception $e) {
            $conn->rollback();
            $section = match ($e->getCode()) {
                1001 => 'Owner',
                1002 => 'Asset',
                1003 => 'Lease',
                default => 'General'
            };
            $errors[] = ($translations[$lang]['error_prefix'] ?? "Error in $section: ") . $e->getMessage();
            error_log("Create records failed: {$e->getMessage()} | Section: $section | Data: " . json_encode($_POST));
        }
        $conn->autocommit(true); // Restore autocommit
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($translations[$lang]['page_title'] ?? 'Create Records'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap@3.0.1/icheck-bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Abyssinica+SIL&display=swap" rel="stylesheet">
    <style>
        .amharic { font-family: 'Abyssinica SIL', sans-serif; }
        .error { color: red; font-size: 0.9em; }
        .select2-container--default .select2-selection--single { 
            height: calc(2.25rem + 2px); 
            padding: .375rem .75rem; 
            border: 1px solid #ced4da; 
            border-radius: .25rem; 
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered { 
            line-height: 1.5; 
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { 
            height: calc(2.25rem + 2px); 
        }
        .form-group label { font-weight: 500; }
        .card-header { background-color: #007bff; color: white; }
        .icheck-primary input[type="checkbox"] + label::before { margin-right: 10px; }
        .is-invalid .select2-container--default .select2-selection--single {
            border-color: #dc3545;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="profile.php" class="dropdown-item"><?php echo htmlspecialchars($translations[$lang]['profile'] ?? 'Profile'); ?></a>
                    <a href="logout.php" class="dropdown-item"><?php echo htmlspecialchars($translations[$lang]['logout'] ?? 'Logout'); ?></a>
                </div>
            </li>
            <li class="nav-item">
                <a href="?lang=en" class="nav-link <?php echo $lang === 'en' ? 'active' : ''; ?>">EN</a>
            </li>
            <li class="nav-item">
                <a href="?lang=am" class="nav-link <?php echo $lang === 'am' ? 'active' : ''; ?>">አማ</a>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="dashboard.php" class="brand-link">
            <img src="assets/images/logo.jpg" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light amharic"><?php echo htmlspecialchars($translations[$lang]['app_name'] ?? 'Gebi System'); ?></span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="assets/images/logo.jpg" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="profile.php" class="d-block"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></a>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['dashboard'] ?? 'Dashboard'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="create_records_form.php" class="nav-link active">
                            <i class="nav-icon fas fa-plus"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['create_records'] ?? 'Create Records'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="lease/list.php" class="nav-link">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['lease_agreements'] ?? 'Lease Agreements'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="owner/list.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['owners'] ?? 'Owners'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="asset/list.php" class="nav-link">
                            <i class="nav-icon fas fa-building"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['assets'] ?? 'Assets'); ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p class="amharic"><?php echo htmlspecialchars($translations[$lang]['logout'] ?? 'Logout'); ?></p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 amharic"><?php echo htmlspecialchars($translations[$lang]['page_heading'] ?? 'Create Owner, Asset, and Lease'); ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php"><?php echo htmlspecialchars($translations[$lang]['home'] ?? 'Home'); ?></a></li>
                            <li class="breadcrumb-item active amharic"><?php echo htmlspecialchars($translations[$lang]['create_records'] ?? 'Create Records'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title amharic"><?php echo htmlspecialchars($translations[$lang]['card_title'] ?? 'Create New Records'); ?></h3>
                            </div>
                            <div class="card-body">
                                <?php if ($success): ?>
                                    <div class="alert alert-success alert-dismissible">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                        <h5><i class="icon fas fa-check"></i> <?php echo htmlspecialchars($translations[$lang]['success'] ?? 'Success!'); ?></h5>
                                        <?php echo htmlspecialchars($success); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($errors): ?>
                                    <div class="alert alert-danger alert-dismissible">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                        <h5><i class="icon fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($translations[$lang]['error'] ?? 'Error!'); ?></h5>
                                        <ul>
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" id="createForm" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                                    <!-- Owner Section -->
                                    <div class="card card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title amharic"><?php echo htmlspecialchars($translations[$lang]['owner_details'] ?? 'Owner Details'); ?></h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="first_name" class="amharic"><?php echo htmlspecialchars($translations[$lang]['first_name'] ?? 'First Name'); ?> <span class="text-danger">*</span></label>
                                                        <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="last_name" class="amharic"><?php echo htmlspecialchars($translations[$lang]['last_name'] ?? 'Last Name'); ?> <span class="text-danger">*</span></label>
                                                        <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="phone" class="amharic"><?php echo htmlspecialchars($translations[$lang]['phone'] ?? 'Phone'); ?> <span class="text-danger">*</span></label>
                                                        <input type="text" name="phone" id="phone" class="form-control" pattern="\+251[79]\d{8}" placeholder="+251912345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="address" class="amharic"><?php echo htmlspecialchars($translations[$lang]['address'] ?? 'Address'); ?></label>
                                                        <input type="text" name="address" id="address" class="form-control" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="id_number" class="amharic"><?php echo htmlspecialchars($translations[$lang]['id_number'] ?? 'ID Number'); ?></label>
                                                        <input type="text" name="id_number" id="id_number" class="form-control" value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="photo" class="amharic"><?php echo htmlspecialchars($translations[$lang]['photo'] ?? 'Photo'); ?></label>
                                                        <input type="file" name="photo" id="photo" class="form-control-file" accept="image/jpeg,image/png">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Asset Section -->
                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title amharic"><?php echo htmlspecialchars($translations[$lang]['asset_details'] ?? 'Asset Details'); ?></h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="asset_code" class="amharic"><?php echo htmlspecialchars($translations[$lang]['asset_code'] ?? 'Asset Code'); ?> <span class="text-danger">*</span></label>
                                                        <input type="text" name="asset_code" id="asset_code" class="form-control" value="<?php echo htmlspecialchars($_POST['asset_code'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="property_name" class="amharic"><?php echo htmlspecialchars($translations[$lang]['property_name'] ?? 'Property Name'); ?></label>
                                                        <input type="text" name="property_name" id="property_name" class="form-control" value="<?php echo htmlspecialchars($_POST['property_name'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="block" class="amharic"><?php echo htmlspecialchars($translations[$lang]['block'] ?? 'Block'); ?></label>
                                                        <input type="text" name="block" id="block" class="form-control" value="<?php echo htmlspecialchars($_POST['block'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="kebele" class="amharic"><?php echo htmlspecialchars($translations[$lang]['kebele'] ?? 'Kebele'); ?></label>
                                                        <input type="text" name="kebele" id="kebele" class="form-control" value="<?php echo htmlspecialchars($_POST['kebele'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="lot_number" class="amharic"><?php echo htmlspecialchars($translations[$lang]['lot_number'] ?? 'Lot Number'); ?></label>
                                                        <input type="text" name="lot_number" id="lot_number" class="form-control" value="<?php echo htmlspecialchars($_POST['lot_number'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                               <div class="col-md-3">
    <!-- Property Type Dropdown -->
<div class="form-group">
    <label for="property_type_id" class="amharic"><?php echo htmlspecialchars($translations[$lang]['property_type'] ?? 'Property Type'); ?></label>
    <select name="property_type_id" id="property_type_id" class="form-control select2" required>
        <?php if (empty($property_types)): ?>
            <option value=""><?php echo htmlspecialchars($translations[$lang]['no_property_types'] ?? 'No property types available'); ?></option>
        <?php else: ?>
            <option value=""><?php echo htmlspecialchars($translations[$lang]['select_property_type'] ?? 'Select Property Type'); ?></option>
            <?php foreach ($property_types as $type): ?>
                <option value="<?php echo htmlspecialchars($type['id']); ?>" <?php echo (isset($asset_data['property_type_id']) && $asset_data['property_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type['name']); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
    <?php if (empty($property_types)): ?>
        <small class="form-text text-danger amharic"><?php echo htmlspecialchars($translations[$lang]['no_property_types_error'] ?? 'Please add property types in the system first'); ?></small>
    <?php endif; ?>
</div>
</div>
<!-- Construction Type Dropdown -->
 <div class="col-md-3">
<div class="form-group">
    <label for="construction_type_id" class="amharic"><?php echo htmlspecialchars($translations[$lang]['construction_type'] ?? 'Construction Type'); ?> <span class="text-muted"><?php echo htmlspecialchars($translations[$lang]['optional'] ?? '(Optional)'); ?></span></label>
    <select name="construction_type_id" id="construction_type_id" class="form-control select2">
        <?php if (empty($construction_types)): ?>
            <option value=""><?php echo htmlspecialchars($translations[$lang]['no_construction_types'] ?? 'No construction types available'); ?></option>
        <?php else: ?>
            <option value=""><?php echo htmlspecialchars($translations[$lang]['select_construction_type'] ?? 'Select Construction Type'); ?></option>
            <?php foreach ($construction_types as $type): ?>
                <option value="<?php echo htmlspecialchars($type['id']); ?>" <?php echo (isset($asset_data['construction_type_id']) && $asset_data['construction_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type['name']); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
</div>
  </div> 
<div class="col-md-3">
                                                <div class="form-group">
                                                        <label for="address_asset" class="amharic"><?php echo htmlspecialchars($translations[$lang]['address'] ?? 'Address'); ?></label>
                                                        <input type="text" name="address_asset" id="address_asset" class="form-control" value="<?php echo htmlspecialchars($_POST['address_asset'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="size" class="amharic"><?php echo htmlspecialchars($translations[$lang]['size'] ?? 'Size (m²)'); ?> <span class="text-danger">*</span></label>
                                                        <input type="number" name="size" id="size" class="form-control" step="0.01" min="0.01" value="<?php echo htmlspecialchars($_POST['size'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="annual_lease_rate" class="amharic"><?php echo htmlspecialchars($translations[$lang]['annual_lease_rate'] ?? 'Annual Lease Rate (ETB)'); ?> <span class="text-danger">*</span></label>
                                                        <input type="number" name="annual_lease_rate" id="annual_lease_rate" class="form-control" step="0.01" min="0.01" value="<?php echo htmlspecialchars($_POST['annual_lease_rate'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="registration_date" class="amharic"><?php echo htmlspecialchars($translations[$lang]['registration_date'] ?? 'Registration Date'); ?> <span class="text-danger">*</span></label>
                                                        <input type="date" name="registration_date" id="registration_date" class="form-control" value="<?php echo htmlspecialchars($_POST['registration_date'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="asset_status" class="amharic"><?php echo htmlspecialchars($translations[$lang]['status'] ?? 'Status'); ?> <span class="text-danger">*</span></label>
                                                        <select name="asset_status" id="asset_status" class="form-control" required>
                                                            <option value="Active" <?php echo ($_POST['asset_status'] ?? 'Active') == 'Active' ? 'selected' : ''; ?>><?php echo htmlspecialchars($translations[$lang]['active'] ?? 'Active'); ?></option>
                                                            <option value="Inactive" <?php echo ($_POST['asset_status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>><?php echo htmlspecialchars($translations[$lang]['inactive'] ?? 'Inactive'); ?></option>
                                                            <option value="Under Maintenance" <?php echo ($_POST['asset_status'] ?? '') == 'Under Maintenance' ? 'selected' : ''; ?>><?php echo htmlspecialchars($translations[$lang]['under_maintenance'] ?? 'Under Maintenance'); ?></option>
                                                            <option value="Leased" <?php echo ($_POST['asset_status'] ?? '') == 'Leased' ? 'selected' : ''; ?>><?php echo htmlspecialchars($translations[$lang]['leased'] ?? 'Leased'); ?></option>
                                                            <option value="Sold" <?php echo ($_POST['asset_status'] ?? '') == 'Sold' ? 'selected' : ''; ?>><?php echo htmlspecialchars($translations[$lang]['sold'] ?? 'Sold'); ?></option>
                                                            <option value="Confiscated" <?php echo ($_POST['asset_status'] ?? '') == 'Confiscated' ? 'selected' : ''; ?>><?php echo htmlspecialchars($translations[$lang]['confiscated'] ?? 'Confiscated'); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="registration_number" class="amharic"><?php echo htmlspecialchars($translations[$lang]['registration_number'] ?? 'Registration Number'); ?></label>
                                                        <input type="text" name="registration_number" id="registration_number" class="form-control" value="<?php echo htmlspecialchars($_POST['registration_number'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="tax_identification_number_asset" class="amharic"><?php echo htmlspecialchars($translations[$lang]['tax_identification_number'] ?? 'Tax Identification Number'); ?></label>
                                                        <input type="text" name="tax_identification_number_asset" id="tax_identification_number_asset" class="form-control" value="<?php echo htmlspecialchars($_POST['tax_identification_number_asset'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="document_path" class="amharic"><?php echo htmlspecialchars($translations[$lang]['document'] ?? 'Document'); ?></label>
                                                        <input type="file" name="document_path" id="document_path" class="form-control-file" accept=".pdf,.png,.jpg,.jpeg">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="amharic"><?php echo htmlspecialchars($translations[$lang]['utilities'] ?? 'Utilities'); ?></label><br>
                                                        <div class="icheck-primary d-inline">
                                                            <input type="checkbox" name="has_electricity" id="has_electricity" <?php echo isset($_POST['has_electricity']) ? 'checked' : ''; ?>>
                                                            <label for="has_electricity" class="amharic"><?php echo htmlspecialchars($translations[$lang]['has_electricity'] ?? 'Has Electricity'); ?></label>
                                                        </div>
                                                        <div class="icheck-primary d-inline ml-3">
                                                            <input type="checkbox" name="has_water" id="has_water" <?php echo isset($_POST['has_water']) ? 'checked' : ''; ?>>
                                                            <label for="has_water" class="amharic"><?php echo htmlspecialchars($translations[$lang]['has_water'] ?? 'Has Water'); ?></label>
                                                        </div>
                                                        <div class="icheck-primary d-inline ml-3">
                                                            <input type="checkbox" name="has_sewage" id="has_sewage" <?php echo isset($_POST['has_sewage']) ? 'checked' : ''; ?>>
                                                            <label for="has_sewage" class="amharic"><?php echo htmlspecialchars($translations[$lang]['has_sewage'] ?? 'Has Sewage'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Lease Section -->
                                    <div class="card card-success">
                                        <div class="card-header">
                                            <h3 class="card-title amharic"><?php echo htmlspecialchars($translations[$lang]['lease_details'] ?? 'Lease Details'); ?></h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="start_date" class="amharic"><?php echo htmlspecialchars($translations[$lang]['start_date'] ?? 'Start Date'); ?> <span class="text-danger">*</span></label>
                                                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="end_date" class="amharic"><?php echo htmlspecialchars($translations[$lang]['end_date'] ?? 'End Date'); ?></label>
                                                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="monthly_rent" class="amharic"><?php echo htmlspecialchars($translations[$lang]['monthly_rent'] ?? 'Monthly Rent (ETB)'); ?> <span class="text-danger">*</span></label>
                                                        <input type="number" name="monthly_rent" id="monthly_rent" class="form-control" step="0.01" min="0.01" value="<?php echo htmlspecialchars($_POST['monthly_rent'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="yearly_rent_rate" class="amharic"><?php echo htmlspecialchars($translations[$lang]['yearly_rent_rate'] ?? 'Yearly Rent Rate (ETB)'); ?></label>
                                                        <input type="number" name="yearly_rent_rate" id="yearly_rent_rate" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['yearly_rent_rate'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="lease_term_of_years" class="amharic"><?php echo htmlspecialchars($translations[$lang]['lease_term'] ?? 'Lease Term (Years)'); ?> <span class="text-danger">*</span></label>
                                                        <input type="number" name="lease_term_of_years" id="lease_term_of_years" class="form-control" min="1" value="<?php echo htmlspecialchars($_POST['lease_term_of_years'] ?? '1'); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="payment_terms" class="amharic"><?php echo htmlspecialchars($translations[$lang]['payment_terms'] ?? 'Payment Terms'); ?> <span class="text-danger">*</span></label>
                                                        <select name="payment_terms" id="payment_terms" class="form-control" required>
                                                            <option value="monthly" <?php echo ($_POST['payment_terms'] ?? 'monthly') == 'monthly' ? 'selected' : ''; ?>><?php echo htmlspecialchars($translations[$lang]['monthly'] ?? 'Monthly'); ?></option>
                                                            <option value="quarterly" <?php echo ($_POST['payment_terms'] ?? '') == 'quarterly' ? 'selected' : ''; ?>><?php echo htmlspecialchars($translations[$lang]['quarterly'] ?? 'Quarterly'); ?></option>
                                                            <option value="annually" <?php echo ($_POST['payment_terms'] ?? '') == 'annually' ? 'selected' : ''; ?>><?php echo htmlspecialchars($translations[$lang]['annually'] ?? 'Annually'); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="terms_conditions" class="amharic"><?php echo htmlspecialchars($translations[$lang]['terms_conditions'] ?? 'Terms and Conditions'); ?></label>
                                                        <textarea name="terms_conditions" id="terms_conditions" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['terms_conditions'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary amharic">
                                            <i class="fas fa-save"></i> <?php echo htmlspecialchars($translations[$lang]['submit'] ?? 'Create Records'); ?>
                                        </button>
                                        <a href="dashboard.php" class="btn btn-secondary amharic">
                                            <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars($translations[$lang]['cancel'] ?? 'Cancel'); ?>
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong><?php echo htmlspecialchars($translations[$lang]['copyright'] ?? 'Copyright'); ?> © 2025 <a href="#">Gebi System</a>.</strong>
        <?php echo htmlspecialchars($translations[$lang]['all_rights_reserved'] ?? 'All rights reserved'); ?>.
        <div class="float-right d-none d-sm-inline-block">
            <b><?php echo htmlspecialchars($translations[$lang]['version'] ?? 'Version'); ?></b> 1.0.0
        </div>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: "<?php echo htmlspecialchars($translations[$lang]['select_option'] ?? 'Select an option'); ?>",
        allowClear: true
    });

    // Client-side form validation
 $('#createForm').on('submit', function(e) {
    let errors = [];
    const lang = '<?php echo $lang; ?>';
    const translations = {
        en: {
            first_name_required: 'First name is required',
            last_name_required: 'Last name is required',
            phone_required: 'Valid Ethiopian phone number (+251...) is required',
            asset_code_required: 'Asset code is required',
            property_type_required: 'Property type is required',
            size_invalid: 'Size must be greater than 0',
            lease_rate_invalid: 'Annual lease rate must be greater than 0',
            registration_date_invalid: 'Valid registration date is required',
            start_date_invalid: 'Valid start date is required',
            rent_rate_invalid: 'Monthly rent must be greater than 0',
            lease_term_invalid: 'Lease term must be at least 1 year',
            date_validation: 'End date must be after start date'
        },
        am: {
            first_name_required: 'የመጀመሪያ ስም ያስፈልጋል',
            last_name_required: 'የአባት ስም ያስፈልጋል',
            phone_required: 'ትክክለኛ የኢትዮጵያ ስልክ ቁጥር (+251...) ያስፈልጋል',
            asset_code_required: 'የንብረት ኮድ ያስፈልጋል',
            property_type_required: 'የንብረት አይነት ያስፈልጋል',
            size_invalid: 'መጠን ከ0 በላይ መሆን አለበት',
            lease_rate_invalid: 'ዓመታዊ የኪራይ ተመን ከ0 በላይ መሆን አለበት',
            registration_date_invalid: 'ትክክለኛ የምዝገባ ቀን ያስፈልጋል',
            start_date_invalid: 'ትክክለኛ የመጀመሪያ ቀን ያስፈልጋል',
            rent_rate_invalid: 'ወርሃዊ ኪራይ ከ0 በላይ መሆን አለበት',
            lease_term_invalid: 'የኪራይ ጊዜ ቢያንስ 1 ዓመት መሆን አለበት',
            date_validation: 'የማብቂያ ቀን ከመጀመሪያ ቀን በኋላ መሆን አለበት'
        }
    };

    // Owner validation
    if (!$('#first_name').val().trim()) {
        errors.push(translations[lang].first_name_required);
        $('#first_name').addClass('is-invalid');
    } else {
        $('#first_name').removeClass('is-invalid');
    }
    if (!$('#last_name').val().trim()) {
        errors.push(translations[lang].last_name_required);
        $('#last_name').addClass('is-invalid');
    } else {
        $('#last_name').removeClass('is-invalid');
    }
    if (!$('#phone').val().match(/^\+251[79]\d{8}$/)) {
        errors.push(translations[lang].phone_required);
        $('#phone').addClass('is-invalid');
    } else {
        $('#phone').removeClass('is-invalid');
    }

    // Asset validation
    if (!$('#asset_code').val().trim()) {
        errors.push(translations[lang].asset_code_required);
        $('#asset_code').addClass('is-invalid');
    } else {
        $('#asset_code').removeClass('is-invalid');
    }
    if (!$('#property_type_id').val()) {
        errors.push(translations[lang].property_type_required);
        $('#property_type_id').next('.select2-container').addClass('is-invalid');
    } else {
        $('#property_type_id').next('.select2-container').removeClass('is-invalid');
    }
    if (isNaN(parseFloat($('#size').val())) || parseFloat($('#size').val()) <= 0) {
        errors.push(translations[lang].size_invalid);
        $('#size').addClass('is-invalid');
    } else {
        $('#size').removeClass('is-invalid');
    }
    if (isNaN(parseFloat($('#annual_lease_rate').val())) || parseFloat($('#annual_lease_rate').val()) <= 0) {
        errors.push(translations[lang].lease_rate_invalid);
        $('#annual_lease_rate').addClass('is-invalid');
    } else {
        $('#annual_lease_rate').removeClass('is-invalid');
    }
    if (!$('#registration_date').val()) {
        errors.push(translations[lang].registration_date_invalid);
        $('#registration_date').addClass('is-invalid');
    } else {
        $('#registration_date').removeClass('is-invalid');
    }

    // Lease validation
    if (!$('#start_date').val()) {
        errors.push(translations[lang].start_date_invalid);
        $('#start_date').addClass('is-invalid');
    } else {
        $('#start_date').removeClass('is-invalid');
    }
    if (isNaN(parseFloat($('#monthly_rent').val())) || parseFloat($('#monthly_rent').val()) <= 0) {
        errors.push(translations[lang].rent_rate_invalid);
        $('#monthly_rent').addClass('is-invalid');
    } else {
        $('#monthly_rent').removeClass('is-invalid');
    }
    if (isNaN(parseInt($('#lease_term_of_years').val())) || parseInt($('#lease_term_of_years').val()) < 1) {
        errors.push(translations[lang].lease_term_invalid);
        $('#lease_term_of_years').addClass('is-invalid');
    } else {
        $('#lease_term_of_years').removeClass('is-invalid');
    }
    if ($('#end_date').val() && new Date($('#end_date').val()) <= new Date($('#start_date').val())) {
        errors.push(translations[lang].date_validation);
        $('#end_date').addClass('is-invalid');
    } else {
        $('#end_date').removeClass('is-invalid');
    }

    if (errors.length > 0) {
        e.preventDefault();
        alert(errors.join('\n'));
    }
});
</script>
</body>
</html>