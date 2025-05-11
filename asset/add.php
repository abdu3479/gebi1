<?php
include '../includes/nav.php';
require_once '../config/database.php';
require_once '../classes/Asset.php';

//session_start(); // Ensure session is started
$lang = $_SESSION['lang'] ?? 'en';
$role = $_SESSION['role'] ?? 'viewer';

// Redirect viewers to dashboard
if ($role === 'viewer') {
    header("Location: ../dashboard.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$assetObj = new Asset($conn);
$owners = $assetObj->getOwners();
$property_types = $assetObj->getPropertyTypes();
$construction_types = $assetObj->getConstructionTypes();

$errors = [];
$success = '';
$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'asset_code' => trim($_POST['asset_code'] ?? ''),
        'property_name' => trim($_POST['property_name'] ?? ''),
        'block' => trim($_POST['block'] ?? ''),
        'kebele' => trim($_POST['kebele'] ?? ''),
        'lot_number' => trim($_POST['lot_number'] ?? ''),
        'owner_id' => !empty($_POST['owner_id']) ? (int)$_POST['owner_id'] : 0,
        'owner_name' => trim($_POST['owner_name'] ?? ''),
        'property_type_id' => !empty($_POST['property_type_id']) ? (int)$_POST['property_type_id'] : null,
        'construction_type_id' => !empty($_POST['construction_type_id']) ? (int)$_POST['construction_type_id'] : 0,
        'address' => trim($_POST['address'] ?? ''),
        'size' => !empty($_POST['size']) ? (float)$_POST['size'] : null,
        'annual_lease_rate' => !empty($_POST['annual_lease_rate']) ? (float)$_POST['annual_lease_rate'] : null,
        'status' => trim($_POST['status'] ?? 'Active'),
        'registration_date' => trim($_POST['registration_date'] ?? date('Y-m-d')),
        'construction_type' => trim($_POST['construction_type'] ?? ''),
        'registration_number' => trim($_POST['registration_number'] ?? ''),
        'tax_identification_number' => trim($_POST['tax_identification_number'] ?? ''),
        'has_electricity' => isset($_POST['has_electricity']) ? 1 : 0,
        'has_water' => isset($_POST['has_water']) ? 1 : 0,
        'has_sewage' => isset($_POST['has_sewage']) ? 1 : 0,
        'document_path' => ''
    ];

    // Handle file upload
    if (isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['document'];
        
        // Detailed error messages
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => $lang === 'am' ? 'ፋይሉ ከፒኤችፒ የላይኛው የፋይል መጠን ገደብ ይበልጣል።' : 'File exceeds PHP upload limit.',
            UPLOAD_ERR_FORM_SIZE => $lang === 'am' ? 'ፋይሉ ከቅጹ የላይኛው የፋይል መጠን ገደብ ይበልጣል።' : 'File exceeds form upload limit.',
            UPLOAD_ERR_PARTIAL => $lang === 'am' ? 'ፋይሉ በከፊል ብቻ ተጫነ።' : 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => $lang === 'am' ? 'ምንም ፋይል አልተጫነም።' : 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => $lang === 'am' ? 'የጊዜያዊ ፋይል ማህደር ጠፍቷል።' : 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => $lang === 'am' ? 'ፋይሉን ወደ ዲስክ መጻፍ አልተቻለም።' : 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => $lang === 'am' ? 'ፋይል መጫን በፒኤችፒ ምርቃት ተቋርጧል።' : 'File upload stopped by PHP extension.'
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_msg = $upload_errors[$file['error'] ?? ($lang === 'am' ? 'ያልታወቀ የፋይል መጫን ስህተት።' : 'Unknown file upload error.')];
            $errors[] = $error_msg . ' (' . $file['error'] . ')';
            error_log("File upload error: " . $error_msg . " Code: " . $file['error']);
        } else {
            $allowed_types = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB

            // Get the actual file type (more secure than trusting client-provided type)
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $file['tmp_name']);
            finfo_close($file_info);

            if (!in_array($mime_type, $allowed_types)) {
                $errors[] = $lang === 'am' ? 'የፋይል አይነት ተፈቅዷል አይደለም። ፒዲኤፍ፣ ፒኤንጂ፣ ጄፒጂ ብቻ ይፈቀዳል።' : 'Invalid file type. Only PDF, PNG, JPG allowed.';
                error_log("Invalid file type: " . $mime_type);
            } elseif ($file['size'] > $max_size) {
                $errors[] = $lang === 'am' ? 'የፋይል መጠን ከ5 ሜጋ ባይት መብለጥ አይችልም።' : 'File size exceeds 5MB limit.';
                error_log("File size too large: " . $file['size']);
            } else {
                // Generate a safe filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'asset_' . uniqid() . '.' . strtolower($ext);
                $upload_dir = '../Uploads/assets/';
                $destination = $upload_dir . $filename;

                // Ensure upload directory exists and is writable
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $errors[] = $lang === 'am' ? 'የፋይል ማህደር መፍጠር አልተቻለም።' : 'Failed to create upload directory.';
                        error_log("Failed to create directory: " . $upload_dir);
                    }
                }

                if (!is_writable($upload_dir)) {
                    $errors[] = $lang === 'am' ? 'ወደ ፋይል ማህደር መጻፍ አይቻልም።' : 'Upload directory is not writable.';
                    error_log("Directory not writable: " . $upload_dir);
                }

                if (empty($errors)) {
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $data['document_path'] = $filename;
                        error_log("File uploaded successfully to: " . $destination);
                    } else {
                        $errors[] = $lang === 'am' ? 'ፋይል መጫን አልተሳካም።' : 'Failed to upload file.';
                        error_log("Failed to move uploaded file. Temp: " . $file['tmp_name'] . " Dest: " . $destination);
                        
                        // Additional debug info
                        error_log("Is uploaded file: " . (is_uploaded_file($file['tmp_name']) ? 'Yes' : 'No'));
                        error_log("Is writable: " . (is_writable($upload_dir) ? 'Yes' : 'No'));
                    }
                }
            }
        }
    }

    // Debug: Log form data
    error_log("Add asset form data: " . json_encode($data));

    if (empty($form_errors) && empty($errors)) {
        try {
            $asset_id = $assetObj->create($data);
            $success = $lang === 'am' ? "ንብረት በተሳካ ሁኔታ ተፈጥሯል በመለያ ቁጥር: $asset_id" : "Asset created successfully with ID: $asset_id";
            $data = array_fill_keys(array_keys($data), '');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            // Clean up uploaded file if creation fails
            if (!empty($data['document_path']) && file_exists('../Uploads/assets/' . $data['document_path'])) {
                unlink('../Uploads/assets/' . $data['document_path']);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'ንብረት ጨምር - ወራቤ መሬት ሊዝ' : 'Add Asset - Werabe Land Lease'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <?php if ($lang === 'am'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&display=swap">
        <style>
            body { font-family: 'Noto Sans Ethiopic', Arial, sans-serif; }
            .content-wrapper, .card, form { text-align: right; }
            .form-group label { float: right; width: 100%; }
            .form-control, .btn { direction: rtl; }
        </style>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Integrated Sidebar -->
      <?php
    $is_viewer = $role === 'viewer';
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="../dashboard.php" class="brand-link">
            <img src="/dist/img/AdminLTELogo.png" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo $lang === 'am' ? 'ወራቤ መሬት ሊዝ' : 'Werabe Land Lease'; ?></span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="../dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p><?php echo $lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard'; ?></p>
                        </a>
                    </li>
                    <?php if (!$is_viewer): ?>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php']) && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ባለቤቶች' : 'Owners'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../owner/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ባለቤት ጨምር' : 'Add Owner'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../owner/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ባለቤቶች' : 'List Owners'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php', 'history.php', 'transfer.php']) && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ንብረቶች' : 'Assets'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../asset/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ንብረት ጨምር' : 'Add Asset'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../asset/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ንብረቶች' : 'List Assets'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../asset/history.php" class="nav-link <?php echo $current_page === 'history.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'የሽግግር ታሪክ' : 'Transfer History'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-file-contract"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ሊዞች' : 'Leases'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../lease/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ሊዝ ጨምር' : 'Add Lease'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../lease/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር �ዝዎች' : 'List Leases'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin', 'billing_officer', 'clerk'])): ?>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ቢሎች' : 'Bills'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../bills/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ቢል ጨምር' : 'Add Bill'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../bills/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ቢሎች' : 'List Bills'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-money-check-alt"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ክፍያዎች' : 'Payments'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../payments/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Payment'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../payments/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Payments'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item <?php echo in_array($current_page, ['register.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], '/user') ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-user-cog"></i>
                                <p>
                                    <?php echo $lang === 'am' ? 'ተጠቃሚዎች' : 'Users'; ?>
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="../register.php" class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ተጠቃሚ ጨምር' : 'Add User'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="../users/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'users/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ዝርዝር ተጠቃሚዎች' : 'List Users'; ?></p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo $lang === 'am' ? 'ንብረት ጨምር' : 'Add New Asset'; ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_FILES['document'])): ?>
                            <?php if ($_FILES['document']['error'] !== UPLOAD_ERR_OK && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE): ?>
                                <div class="alert alert-danger">
                                    <?php echo $lang === 'am' ? 'የፋይል መጫን ስህተት:' : 'File upload error:' ?> 
                                    <?php 
                                    $upload_errors = [
                                        UPLOAD_ERR_INI_SIZE => $lang === 'am' ? 'ፋይሉ በጣም ትልቅ ነው።' : 'File is too large.',
                                        UPLOAD_ERR_FORM_SIZE => $lang === 'am' ? 'ፋይሉ ከቅጹ የመጠን ገደብ ይበልጣል።' : 'File exceeds form size limit.',
                                        UPLOAD_ERR_PARTIAL => $lang === 'am' ? 'ፋይሉ በከፊል ብቻ ተጫነ።' : 'File was only partially uploaded.',
                                        UPLOAD_ERR_NO_FILE => $lang === 'am' ? 'ምንም ፋይል አልተጫነም።' : 'No file was uploaded.',
                                        UPLOAD_ERR_NO_TMP_DIR => $lang === 'am' ? 'የጊዜያዊ ፋይል ማህደር ጠፍቷል።' : 'Missing temporary folder.',
                                        UPLOAD_ERR_CANT_WRITE => $lang === 'am' ? 'ፋይሉን ወደ ዲስክ መጻፍ አልተቻለም።' : 'Failed to write file to disk.',
                                        UPLOAD_ERR_EXTENSION => $lang === 'am' ? 'ፋይል መጫን በፒኤችፒ ምርቃት ተቋርጧል።' : 'File upload stopped by PHP extension.'
                                    ];
                                    echo $upload_errors[$_FILES['document']['error']] ?? $_FILES['document']['error'];
                                    ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($owners)): ?>
                            <div class="alert alert-warning">
                                <?php echo $lang === 'am' ? 'ምንም ባለቤቶች አልተገኙም። እባክዎ ባለቤት ይጨምሩ።' : 'No owners found. Please add an owner first.'; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($construction_types)): ?>
                            <div class="alert alert-warning">
                                <?php echo $lang === 'am' ? 'ምንም የግንባታ አይነቶች አልተገኙም። እባክዎ የግንባታ አይነት ይጨምሩ።' : 'No construction types found. Please add a construction type first.'; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($property_types)): ?>
                            <div class="alert alert-warning">
                                <?php echo $lang === 'am' ? 'ምንም የንብረት አይነቶች አልተገኙም። እባክዎ የንብረት አይነት ይጨምሩ።' : 'No property types found. Please add a property type first.'; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="assetForm" enctype="multipart/form-data">
                            <div class="row">
                                <!-- First Column -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="asset_code"><?php echo $lang === 'am' ? 'ንብረት ኮድ' : 'Asset Code'; ?> <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="asset_code" name="asset_code" required value="<?php echo htmlspecialchars($data['asset_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="property_name"><?php echo $lang === 'am' ? 'የንብረት ስም' : 'Property Name'; ?></label>
                                        <input type="text" class="form-control" id="property_name" name="property_name" value="<?php echo htmlspecialchars($data['property_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="block"><?php echo $lang === 'am' ? 'ብሎክ' : 'Block'; ?></label>
                                                <input type="text" class="form-control" id="block" name="block" value="<?php echo htmlspecialchars($data['block'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="kebele"><?php echo $lang === 'am' ? 'ቀበሌ' : 'Kebele'; ?></label>
                                                <input type="text" class="form-control" id="kebele" name="kebele" value="<?php echo htmlspecialchars($data['kebele'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="lot_number"><?php echo $lang === 'am' ? 'የሎት ቁጥር' : 'Lot Number'; ?></label>
                                        <input type="text" class="form-control" id="lot_number" name="lot_number" value="<?php echo htmlspecialchars($data['lot_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="owner_id"><?php echo $lang === 'am' ? 'ባለቤት' : 'Owner'; ?></label>
                                        <select class="form-control" id="owner_id" name="owner_id">
                                            <option value=""><?php echo $lang === 'am' ? 'ባለቤት ይምረጡ' : 'Select Owner'; ?></option>
                                            <?php foreach ($owners as $owner): ?>
                                                <option value="<?php echo $owner['id']; ?>" <?php echo ($data['owner_id'] ?? '') == $owner['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($owner['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="owner_name"><?php echo $lang === 'am' ? 'የባለቤት ስም' : 'Owner Name'; ?></label>
                                        <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($data['owner_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="property_type_id"><?php echo $lang === 'am' ? 'የንብረት አይነት' : 'Property Type'; ?> <span class="text-danger">*</span></label>
                                        <select class="form-control" id="property_type_id" name="property_type_id" required>
                                            <option value=""><?php echo $lang === 'am' ? 'አይነት ይምረጡ' : 'Select Type'; ?></option>
                                            <?php foreach ($property_types as $type): ?>
                                                <option value="<?php echo $type['id']; ?>" <?php echo ($data['property_type_id'] ?? '') == $type['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Second Column -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="construction_type_id"><?php echo $lang === 'am' ? 'የግንባታ አይነት' : 'Construction Type'; ?></label>
                                        <select class="form-control" id="construction_type_id" name="construction_type_id">
                                            <option value=""><?php echo $lang === 'am' ? 'የግንባታ አይነት ይምረጡ' : 'Select Construction Type'; ?></option>
                                            <?php foreach ($construction_types as $type): ?>
                                                <option value="<?php echo $type['id']; ?>" <?php echo ($data['construction_type_id'] ?? '') == $type['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($type['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="address"><?php echo $lang === 'am' ? 'አድራሻ' : 'Address'; ?></label>
                                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($data['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="size"><?php echo $lang === 'am' ? 'መጠን (ስኩዌር ሜትር)' : 'Size (sqm)'; ?> <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0.01" class="form-control" id="size" name="size" required value="<?php echo htmlspecialchars($data['size'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="annual_lease_rate"><?php echo $lang === 'am' ? 'ዓመታዊ የሊዝ ተመን' : 'Annual Lease Rate'; ?> <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="annual_lease_rate" name="annual_lease_rate" required value="<?php echo htmlspecialchars($data['annual_lease_rate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><?php echo $lang === 'am' ? 'ሁኔታ' : 'Status'; ?> <span class="text-danger">*</span></label>
                                        <select class="form-control" id="status" name="status" required>
                                            <option value="Active" <?php echo ($data['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'ንቁ' : 'Active'; ?></option>
                                            <option value="Inactive" <?php echo ($data['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'እንቅስቃሴ የለም' : 'Inactive'; ?></option>
                                            <option value="Under Maintenance" <?php echo ($data['status'] ?? '') === 'Under Maintenance' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'በጥገና ላይ' : 'Under Maintenance'; ?></option>
                                            <option value="Leased" <?php echo ($data['status'] ?? '') === 'Leased' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'ተከራይቷል' : 'Leased'; ?></option>
                                            <option value="Sold" <?php echo ($data['status'] ?? '') === 'Sold' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'ተሽጧል' : 'Sold'; ?></option>
                                            <option value="Confiscated" <?php echo ($data['status'] ?? '') === 'Confiscated' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'ተወስዷል' : 'Confiscated'; ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="registration_date"><?php echo $lang === 'am' ? 'የመመዝገቢያ ቀን' : 'Registration Date'; ?> <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="registration_date" name="registration_date" required value="<?php echo htmlspecialchars($data['registration_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="construction_type"><?php echo $lang === 'am' ? 'የግንባታ አይነት መግለጫ' : 'Construction Type Description'; ?></label>
                                        <input type="text" class="form-control" id="construction_type" name="construction_type" value="<?php echo htmlspecialchars($data['construction_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Full width fields -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="registration_number"><?php echo $lang === 'am' ? 'የመመዝገቢያ ቁጥር' : 'Registration Number'; ?></label>
                                        <input type="text" class="form-control" id="registration_number" name="registration_number" value="<?php echo htmlspecialchars($data['registration_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tax_identification_number"><?php echo $lang === 'am' ? 'የግብር መለያ ቁጥር' : 'Tax Identification Number'; ?></label>
                                        <input type="text" class="form-control" id="tax_identification_number" name="tax_identification_number" value="<?php echo htmlspecialchars($data['tax_identification_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="document"><?php echo $lang === 'am' ? 'ሰነድ ወይም ፎቶ ጫን' : 'Upload Document or Photo'; ?></label>
                                <input type="file" class="form-control-file" id="document" name="document" accept=".pdf,.png,.jpg,.jpeg">
                                <small class="form-text text-muted"><?php echo $lang === 'am' ? 'ፒዲኤፍ፣ ፒኤንጂ፣ ጄፒጂ ብቻ፣ ከፍተኛ 5 ሜጋ ባይት' : 'PDF, PNG, JPG only, max 5MB'; ?></small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="has_electricity" value="1" id="has_electricity" <?php echo ($data['has_electricity'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="has_electricity"><?php echo $lang === 'am' ? 'ኤሌክትሪክ አለው' : 'Has Electricity'; ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="has_water" value="1" id="has_water" <?php echo ($data['has_water'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="has_water"><?php echo $lang === 'am' ? 'ውሃ አለው' : 'Has Water'; ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="has_sewage" value="1" id="has_sewage" <?php echo ($data['has_sewage'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="has_sewage"><?php echo $lang === 'am' ? 'ፍሳሽ አለው' : 'Has Sewage'; ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><?php echo $lang === 'am' ? 'ንብረት ጨምር' : 'Add Asset'; ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    $('#assetForm').on('submit', function(e) {
        $(this).find('input[type=text], select, textarea').each(function() {
            $(this).val($(this).val().trim());
        });

        <?php if (!empty($owners)): ?>
        // Optional: Warn if no owner selected
        if (!$('#owner_id').val()) {
            if (!confirm('<?php echo $lang === 'am' ? 'ባለቤት አልተመረጠም። ያለ ባለቤት መቀጠል ይፈልጋሉ?' : 'No owner selected. Proceed without an owner?'; ?>')) {
                e.preventDefault();
            }
        }
        <?php endif; ?>

        // Validate file type and size client-side
        const fileInput = $('#document')[0];
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const allowedTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (!allowedTypes.includes(file.type)) {
                alert('<?php echo $lang === 'am' ? 'የፋይል አይነት ተፈቅዷል አይደለም። ፒዲኤፍ፣ ፒኤንጂ፣ ጄፒጂ ብቻ ይፈቀዳል።' : 'Invalid file type. Only PDF, PNG, JPG allowed.'; ?>');
                e.preventDefault();
            } else if (file.size > maxSize) {
                alert('<?php echo $lang === 'am' ? 'የፋይል መጠን ከ5 ሜጋ ባይት መብለጥ አይችልም።' : 'File size exceeds 5MB limit.'; ?>');
                e.preventDefault();
            }
        }
    });
});
</script>
</body>
</html>