<?php
// Set the timezone to Ethiopia
date_default_timezone_set('Africa/Addis_Ababa');

include '../includes/nav.php';
require_once '../config/database.php';
require_once '../classes/Payment.php';
require_once '../classes/Lease.php';
require_once '../classes/Bill.php';

// Telebirr API configuration (replace with actual credentials)
$telebirr_config = [
    'app_id' => 'YOUR_TELEBIRR_APP_ID',
    'app_key' => 'YOUR_TELEBIRR_APP_KEY',
    'public_key' => 'YOUR_TELEBIRR_PUBLIC_KEY',
    'short_code' => 'YOUR_TELEBIRR_SHORT_CODE',
    'notify_url' => 'https://your-domain.com/notify.php',
    'return_url' => 'https://your-domain.com/add_payment.php',
    'api_url' => 'YOUR_TELEBIRR_WEBPAY_URL' // e.g., http://196.188.120.3:11443/ammwebpay
];

// CBE-Birr API configuration (replace with actual credentials)
$cbebirr_config = [
    'merchant_id' => 'YOUR_CBE_BIRR_MERCHANT_ID',
    'api_key' => 'YOUR_CBE_BIRR_API_KEY',
    'notify_url' => 'https://your-domain.com/notify.php',
    'return_url' => 'https://your-domain.com/add_payment.php',
    'api_url' => 'YOUR_CBE_BIRR_API_URL' // e.g., WeBirr or CBE-Birr gateway URL
];

if (!in_array($_SESSION['role'] ?? '', ['admin', 'billing_officer', 'clerk'])) {
    header("Location: /dashboard.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$paymentObj = new Payment($conn);
$leaseObj = new Lease($conn);
$billObj = new Bill($conn);

$errors = [];
$success = '';
$leases = $leaseObj->getAll(['status' => 'active']); // Only active leases
$bills = []; // Initially empty; populated via AJAX

// Handle language selection
$lang = $_SESSION['lang'] ?? 'en';
$translations = [
    'en' => [
        'add_payment' => 'Add New Payment',
        'select_lease' => 'Select Lease',
        'select_bill' => 'Select Bill',
        'no_bills' => 'No unpaid bills available',
        'loading' => 'Loading...',
        'amount' => 'Amount (ETB)',
        'payment_method' => 'Payment Method',
        'payment_date' => 'Payment Date',
        'notes' => 'Notes',
        'receipt' => 'Receipt (Optional)',
        'submit' => 'Add Payment',
        'success' => 'Payment created successfully with ID: %s',
        'telebirr_redirect' => ' Redirecting to Telebirr...',
        'cbe_birr_redirect' => ' Redirecting to CBE-Birr...'
    ],
    'am' => [
        'add_payment' => 'አዲስ ክፍያ ጨምር',
        'select_lease' => 'ሊዝ ይምረጡ',
        'select_bill' => 'ቢል ይምረጡ',
        'no_bills' => 'ምንም ያልተከፈሉ ቢል የለም',
        'loading' => 'በመጫን ላይ...',
        'amount' => 'መጠን (ብር)',
        'payment_method' => 'የክፍያ ዘዴ',
        'payment_date' => 'የክፍያ ቀን',
        'notes' => 'ማስታወሻዎች',
        'receipt' => 'ደረሰኝ (አማራጭ)',
        'submit' => 'ክፍያ ጨምር',
        'success' => 'ክፍያ በተሳካ ሁኔታ ተፈጥሯል በID: %s',
        'telebirr_redirect' => ' ወደ ቴሌብር በመቀየር ላይ...',
        'cbe_birr_redirect' => ' ወደ ሲቢኢ-ብር በመቀየር ላዯ...'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'lease_id' => (int)($_POST['lease_id'] ?? 0),
            'bill_id' => (int)($_POST['bill_id'] ?? 0),
            'amount' => (float)($_POST['amount'] ?? 0),
            'payment_method' => trim($_POST['payment_method'] ?? ''),
            'payment_date' => trim($_POST['payment_date'] ?? ''),
            'status' => in_array($_POST['payment_method'], ['telebirr', 'cbe_birr']) ? 'pending' : 'completed',
            'notes' => trim($_POST['notes'] ?? ''),
            'received_by' => $_SESSION['user_id'] ?? 1,
            'out_trade_no' => 'PAY_' . time() . '_' . rand(1000, 9999)
        ];
        if (!empty($_FILES['receipt']) && $_FILES['receipt']['error'] !== UPLOAD_ERR_NO_FILE) {
            $data['receipt'] = $_FILES['receipt'];
        }

        if ($data['payment_method'] === 'telebirr') {
            // Telebirr payment request
            $telebirr_data = [
                'appId' => $telebirr_config['app_id'],
                'shortCode' => $telebirr_config['short_code'],
                'notifyUrl' => $telebirr_config['notify_url'],
                'returnUrl' => $telebirr_config['return_url'],
                'receiveName' => 'Werabe Land Lease',
                'totalAmount' => number_format($data['amount'], 2, '.', ''),
                'subject' => 'Land Lease Payment',
                'timeoutExpress' => '30',
                'nonce' => uniqid(),
                'outTradeNo' => $data['out_trade_no'],
                'timestamp' => time()
            ];

            // Generate Telebirr signature
            ksort($telebirr_data);
            $string_to_sign = '';
            foreach ($telebirr_data as $key => $value) {
                $string_to_sign .= "$key=$value&";
            }
            $string_to_sign = rtrim($string_to_sign, '&');
            $telebirr_data['sign'] = hash_hmac('sha256', $string_to_sign, $telebirr_config['app_key']);

            // Call Telebirr API
            $ch = curl_init($telebirr_config['api_url']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($telebirr_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $response_data = json_decode($response, true);
            if (isset($response_data['pay_url'])) {
                $data['telebirr_url'] = $response_data['pay_url'];
            } else {
                throw new Exception('Failed to initiate Telebirr payment: ' . ($response_data['message'] ?? 'Unknown error'));
            }
        } elseif ($data['payment_method'] === 'cbe_birr') {
            // CBE-Birr payment request
            $cbebirr_data = [
                'merchant_id' => $cbebirr_config['merchant_id'],
                'api_key' => $cbebirr_config['api_key'],
                'notify_url' => $cbebirr_config['notify_url'],
                'return_url' => $cbebirr_config['return_url'],
                'amount' => number_format($data['amount'], 2, '.', ''),
                'description' => 'Land Lease Payment',
                'payment_code' => $data['out_trade_no'],
                'timestamp' => time()
            ];

            // Generate CBE-Birr signature
            ksort($cbebirr_data);
            $string_to_sign = http_build_query($cbebirr_data);
            $cbebirr_data['sign'] = hash_hmac('sha256', $string_to_sign, $cbebirr_config['api_key']);

            // Call CBE-Birr API
            $ch = curl_init($cbebirr_config['api_url']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($cbebirr_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $response_data = json_decode($response, true);
            if (isset($response_data['pay_url'])) {
                $data['cbe_birr_url'] = $response_data['pay_url'];
            } else {
                throw new Exception('Failed to initiate CBE-Birr payment: ' . ($response_data['message'] ?? 'Unknown error'));
            }
        }

        $response = $paymentObj->create($data);
        $success = sprintf($translations[$lang]['success'], $response['payment_id']);
        
        // Redirect for Telebirr or CBE-Birr
        if (!empty($response['telebirr_url'])) {
            $success .= $translations[$lang]['telebirr_redirect'];
            header("Refresh: 2; URL={$response['telebirr_url']}");
        } elseif (!empty($response['cbe_birr_url'])) {
            $success .= $translations[$lang]['cbe_birr_redirect'];
            header("Refresh: 2; URL={$response['cbe_birr_url']}");
        }
        
        // Log to audit_logs
        $conn->query(
            "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent, created_at) 
             VALUES ({$_SESSION['user_id']}, 'create', 'payments', {$response['payment_id']}, '" . json_encode($data, JSON_UNESCAPED_UNICODE) . "', 
                     '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())"
        );
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations[$lang]['add_payment']; ?> - Werabe Land Lease</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <style>
        .loading::after {
            content: '<?php echo $translations[$lang]['loading']; ?>';
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
     <?php
    $is_viewer = $role === 'viewer';
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="../dashboard.php" class="brand-link">
            <img src="../assets/images/logo.jpg" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
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
                                    <a href="add_payment.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Payment'; ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
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
                        <h1><?php echo $translations[$lang]['add_payment']; ?></h1>
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
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lease_id"><?php echo $translations[$lang]['select_lease']; ?></label>
                                        <select class="form-control" id="lease_id" name="lease_id" required>
                                            <option value=""><?php echo $translations[$lang]['select_lease']; ?></option>
                                            <?php foreach ($leases as $lease): ?>
                                                <option value="<?php echo $lease['id']; ?>">
                                                    <?php echo htmlspecialchars("{$lease['lease_number']} - {$lease['lot_number']} ({$lease['owner_name']})", ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bill_id"><?php echo $translations[$lang]['select_bill']; ?></label>
                                        <select class="form-control" id="bill_id" name="bill_id" required disabled>
                                            <option value=""><?php echo $translations[$lang]['select_lease']; ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="amount"><?php echo $translations[$lang]['amount']; ?></label>
                                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_date"><?php echo $translations[$lang]['payment_date']; ?></label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date_display" value="<?php echo date('Y-m-d'); ?>" disabled required>
                                        <input type="hidden" name="payment_date" id="payment_date_hidden" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_method"><?php echo $translations[$lang]['payment_method']; ?></label>
                                        <select class="form-control" id="payment_method" name="payment_method" required>
                                            <option value="cash"><?php echo $lang === 'am' ? 'ጥሬ ገንዘብ' : 'Cash'; ?></option>
                                            <option value="bank_transfer"><?php echo $lang === 'am' ? 'የባንክ ማስተላለፍ' : 'Bank Transfer'; ?></option>
                                            <option value="check"><?php echo $lang === 'am' ? 'ቼክ' : 'Check'; ?></option>
                                            <option value="mobile_money"><?php echo $lang === 'am' ? 'ሞባይል ገንዘብ' : 'Mobile Money'; ?></option>
                                            <option value="telebirr"><?php echo $lang === 'am' ? 'ቴሌብር' : 'Telebirr'; ?></option>
                                            <option value="cbe_birr"><?php echo $lang === 'am' ? 'ሲቢኢ-ብር' : 'CBE-Birr'; ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="receipt"><?php echo $translations[$lang]['receipt']; ?></label>
                                        <input type="file" class="form-control-file" id="receipt" name="receipt" accept="image/*,application/pdf">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes"><?php echo $translations[$lang]['notes']; ?></label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><?php echo $translations[$lang]['submit']; ?></button>
                            </div>
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
    const billSelect = $('#bill_id');
    const amountInput = $('#amount');
    const translations = {
        'en': {
            'select_bill': '<?php echo $translations['en']['select_bill']; ?>',
            'no_bills': '<?php echo $translations['en']['no_bills']; ?>',
            'loading': '<?php echo $translations['en']['loading']; ?>'
        },
        'am': {
            'select_bill': '<?php echo $translations['am']['select_bill']; ?>',
            'no_bills': '<?php echo $translations['am']['no_bills']; ?>',
            'loading': '<?php echo $translations['am']['loading']; ?>'
        }
    };
    const lang = '<?php echo $lang; ?>';

    // Set local date in the payment_date field
    const today = new Date();
    const localDate = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
    $('#payment_date').val(localDate);
    $('#payment_date_hidden').val(localDate);

    // Disable bill select initially
    billSelect.prop('disabled', true);

    // Handle lease_id change
    $('#lease_id').on('change', function() {
        const leaseId = $(this).val();
        billSelect.prop('disabled', true).addClass('loading');
        amountInput.val(''); // Clear amount

        if (!leaseId) {
            billSelect.html(`<option value="">${translations[lang]['select_bill']}</option>`);
            return;
        }

        // Fetch bills via AJAX
        $.ajax({
            url: 'get_bills_by_lease.php',
            method: 'GET',
            data: { lease_id: leaseId },
            dataType: 'json',
            success: function(response) {
                billSelect.removeClass('loading').prop('disabled', false);
                billSelect.html(`<option value="">${translations[lang]['select_bill']}</option>`);
                
                if (response.length === 0) {
                    billSelect.append(`<option value="" disabled>${translations[lang]['no_bills']}</option>`);
                    billSelect.prop('disabled', true);
                } else {
                    response.forEach(function(bill) {
                        billSelect.append(
                            `<option value="${bill.id}" data-amount="${bill.total_amount}">
                                ${bill.bill_number} - ${parseFloat(bill.total_amount).toFixed(2)} ETB
                            </option>`
                        );
                    });
                }
            },
            error: function() {
                billSelect.removeClass('loading').prop('disabled', false);
                billSelect.html(`<option value="">${translations[lang]['select_bill']}</option>`);
                alert('Failed to load bills. Please try again.');
            }
        });
    });

    // Auto-fill amount when bill is selected
    billSelect.on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const amount = selectedOption.data('amount');
        if (amount) {
            amountInput.val(parseFloat(amount).toFixed(2));
        } else {
            amountInput.val('');
        }
    });
});
</script>
</body>
</html>