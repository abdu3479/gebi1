<?php
include '../includes/nav.php';
require_once '../config/database.php';
require_once '../config/lang.php';
require_once '../classes/Lease.php';
require_once '../classes/Asset.php';
require_once '../classes/Owner.php';

// Session variables for role and language
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';

// Initialize database and classes
try {
    $db = new Database();
    $conn = $db->getConnection();
    $leaseObj = new Lease($conn);
    $assetObj = new Asset($conn);
    $ownerObj = new Owner($conn);
} catch (Exception $e) {
    error_log("Initialization failed: " . $e->getMessage());
    die($translations[$lang]['init_error'] ?? 'System initialization failed.');
}

// Get lease ID from query string
$lease_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lease = null;
$errors = [];
$payment_history = [];
$audit_logs = [];
$lease_term_warning = null;

if ($lease_id > 0) {
    try {
        // Fetch lease details
        $lease = $leaseObj->getById($lease_id);
        if (!$lease) {
            $errors[] = $translations[$lang]['lease_not_found'] ?? ($lang === 'am' ? 'ሊዝ አልተገኘም' : 'Lease not found');
        } else {
            // Fetch owner and asset details
            $owner = $ownerObj->getById($lease['owner_id']);
            $asset = $assetObj->getById($lease['asset_id']);
            $lease['owner_name'] = $owner ? $owner['first_name'] . ' ' . $owner['last_name'] : ($lang === 'am' ? 'ያልታወቀ' : 'Unknown');
            $lease['asset_code'] = $asset['asset_code'] ?? ($lang === 'am' ? 'ያልተገለፀ' : 'Not specified');
            $lease['lot_number'] = $asset['lot_number'] ?? null;
            $lease['kebele'] = $asset['kebele'] ?? null;
            $lease['size'] = $asset['size'] ?? null;
            $lease['terms_conditions'] = $lease['terms_conditions'] ?? null;

            // Calculate total lease cost and paid/unpaid amounts
            $lease['total_lease_cost'] = $lease['yearly_rent_rate'] * $lease['lease_term_of_years'];
            $paid_amount = 0;
            foreach ($payment_history as $payment) {
                if ($payment['status'] === 'completed') {
                    $paid_amount += $payment['amount'];
                }
            }
            $lease['paid_amount'] = $paid_amount;
            $lease['left_unpaid_amount'] = $lease['total_lease_cost'] - $paid_amount;

            // Check lease term validity
            $start = DateTime::createFromFormat('Y-m-d', $lease['start_date']);
            $end = $lease['end_date'] ? DateTime::createFromFormat('Y-m-d', $lease['end_date']) : null;
            if ($start && $end) {
                $interval = $start->diff($end);
                $years = $interval->y + ($interval->m / 12);
                if (abs($years - $lease['lease_term_of_years']) > 0.5) {
                    $lease_term_warning = $translations[$lang]['lease_term_warning'] ?? ($lang === 'am'
                        ? "ማስጠንቀቂያ: የሊዝ ጊዜ ({$lease['lease_term_of_years']} ዓመታት) ከቀኖች ክልል ({$lease['start_date']} እስከ {$lease['end_date']}) ጋር አይዛመድም።"
                        : "Warning: Lease term ({$lease['lease_term_of_years']} years) does not match date range ({$lease['start_date']} to {$lease['end_date']}).");
                }
            }

            // Fetch payment history
            $query = "SELECT p.id, p.payment_date, p.amount, p.status, p.reference_number 
                      FROM payments p 
                      WHERE p.lease_id = ? 
                      ORDER BY p.payment_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $lease_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $payment_history[] = $row;
            }
            $stmt->close();

            // Fetch audit logs
            $query = "SELECT al.action_type, al.old_values, al.new_values, al.created_at, u.username 
                      FROM audit_logs al 
                      LEFT JOIN users u ON u.id = al.user_id 
                      WHERE al.table_name = 'lease_agreements' AND al.record_id = ? 
                      ORDER BY al.created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $lease_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $audit_logs[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $errors[] = $translations[$lang]['error_fetching_lease'] ?? ($lang === 'am' ? 'ስህተት ተከስቷል: ' . $e->getMessage() : 'An error occurred: ' . $e->getMessage());
        error_log("Error fetching lease details: " . $e->getMessage());
    }
} else {
    $errors[] = $translations[$lang]['invalid_lease_id'] ?? ($lang === 'am' ? 'የሊዝ መለያ ተገቢ አይደለም' : 'Invalid lease ID');
}

// Translations for bilingual support
$translations = array_merge($translations ?? [], [
    'lease_details' => ['en' => 'Lease Details', 'am' => 'የሊዝ ዝርዝሮች'],
    'lease_number' => ['en' => 'Lease Number', 'am' => 'የሊዝ ቁጥር'],
    'asset' => ['en' => 'Asset Code', 'am' => 'የንብረት ኮድ'],
    'lot_number' => ['en' => 'Lot Number', 'am' => 'የሎት ቁጥር'],
    'kebele' => ['en' => 'Kebele', 'am' => 'ቀበሌ'],
    'size' => ['en' => 'Size (sqm)', 'am' => 'መጠን (ካሬ ሜትር)'],
    'owner' => ['en' => 'Owner', 'am' => 'ባለቤት'],
    'start_date' => ['en' => 'Start Date', 'am' => 'መጀመሪያ ቀን'],
    'end_date' => ['en' => 'End Date', 'am' => 'መጨረሻ ቀን'],
    'annual_rent' => ['en' => 'Annual Rent (ETB)', 'am' => 'ዓመታዊ ኪራይ (ብር)'],
    'payment_terms' => ['en' => 'Payment Terms', 'am' => 'የክፍያ ውሎች'],
    'lease_term' => ['en' => 'Lease Term (Years)', 'am' => 'የሊዝ ጊዜ (ዓመታት)'],
    'status' => ['en' => 'Status', 'am' => 'ሁኔታ'],
    'total_cost' => ['en' => 'Total Lease Cost', 'am' => 'ጠቅላላ የሊዝ ወጪ'],
    'paid_amount' => ['en' => 'Paid Amount', 'am' => 'የተከፈለ መጠን'],
    'unpaid_amount' => ['en' => 'Unpaid Amount', 'am' => 'ያልተከፈለ መጠን'],
    'terms_conditions' => ['en' => 'Terms & Conditions', 'am' => 'ውሎች እና ሁኔታዎች'],
    'created_by' => ['en' => 'Created By', 'am' => 'ተፈጥሯል በ'],
    'payment_history' => ['en' => 'Payment History', 'am' => 'የክፍያ ታሪክ'],
    'payment_date' => ['en' => 'Payment Date', 'am' => 'የክፍያ ቀን'],
    'amount' => ['en' => 'Amount', 'am' => 'መጠን'],
    'reference_number' => ['en' => 'Reference Number', 'am' => 'የማጣቀሻ ቁጥር'],
    'audit_logs' => ['en' => 'Audit Logs', 'am' => 'የኦዲት መዝገቦች'],
    'action_type' => ['en' => 'Action Type', 'am' => 'የድርጊት አይነት'],
    'user' => ['en' => 'User', 'am' => 'ተጠቃሚ'],
    'old_values' => ['en' => 'Old Values', 'am' => 'የቆዩ እሴቶች'],
    'new_values' => ['en' => 'New Values', 'am' => 'አዲስ እሴቶች'],
    'created_at' => ['en' => 'Created At', 'am' => 'የተፈጠረበት ጊዜ'],
    'no_payments' => ['en' => 'No payments found', 'am' => 'ምንም ክፍያዎች አልተገኙም'],
    'no_audit_logs' => ['en' => 'No audit logs found', 'am' => 'ምንም የኦዲት መዝገቦች አልተገኙም'],
    'back_to_list' => ['en' => 'Back to Lease List', 'am' => 'ወደ ሊዝ ዝርዝር ተመለስ'],
    'lease_not_found' => ['en' => 'Lease not found', 'am' => 'ሊዝ አልተገኘም'],
    'invalid_lease_id' => ['en' => 'Invalid lease ID', 'am' => 'የሊዝ መለያ ተገቢ አይደለም'],
    'error_fetching_lease' => ['en' => 'An error occurred', 'am' => 'ስህተት ተከስቷል'],
    'lease_term_warning' => ['en' => 'Warning: Lease term does not match date range', 'am' => 'ማስጠንቀቂያ: የሊዝ ጊዜ ከቀኖች ክልል ጋር አይዛመድም']
]);

$statusTranslations = [
    'active' => ['en' => 'Active', 'am' => 'ንቁ'],
    'pending' => ['en' => 'Pending', 'am' => 'በመጠባበቅ ላይ'],
    'expired' => ['en' => 'Expired', 'am' => 'ጊዜው አልፏል'],
    'terminated' => ['en' => 'Terminated', 'am' => 'ተቋርጧል']
];
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['lease_details'][$lang] . ' - Gebi System'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Abyssinica+SIL&display=swap" rel="stylesheet">
    <style>
        .amharic { font-family: 'Abyssinica SIL', sans-serif; }
        .table-responsive { overflow-x: auto; }
        .card-header h3 { margin: 0; }
        .badge { font-size: 0.9rem; }
        .terms-conditions { word-wrap: break-word; max-width: 500px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 0.85rem; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="amharic"><?php echo $translations['lease_details'][$lang]; ?></h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> <?php echo $translations['back_to_list'][$lang]; ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($lease): ?>
                    <?php if ($lease_term_warning): ?>
                        <div class="alert alert-warning">
                            <?php echo htmlspecialchars($lease_term_warning, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Lease Details -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title amharic"><?php echo $translations['lease_details'][$lang]; ?></h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['lease_number'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars($lease['lease_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['asset'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars($lease['asset_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['lot_number'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars($lease['lot_number'] ?: ($lang === 'am' ? 'ያልተገለፀ' : 'Not specified'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['kebele'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars($lease['kebele'] ?: ($lang === 'am' ? 'ያልተገለፀ' : 'Not specified'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['size'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars($lease['size'] ? number_format($lease['size'], 2) . ' sqm' : ($lang === 'am' ? 'ያልተገለፀ' : 'Not specified'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['owner'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars($lease['owner_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['start_date'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars($lease['start_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['end_date'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars($lease['end_date'] ?: ($lang === 'am' ? 'ያልተገለፀ' : 'Not specified'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['annual_rent'][$lang]; ?></th>
                                        <td><?php echo number_format($lease['yearly_rent_rate'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['payment_terms'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars(ucfirst($lease['payment_terms'] ?: ($lang === 'am' ? 'ያልተገለፀ' : 'Not specified')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['lease_term'][$lang]; ?></th>
                                        <td><?php echo htmlspecialchars($lease['lease_term_of_years'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['status'][$lang]; ?></th>
                                        <td>
                                            <span class="badge <?php
                                                switch ($lease['status']) {
                                                    case 'active': echo 'badge-success'; break;
                                                    case 'pending': echo 'badge-warning'; break;
                                                    case 'expired': echo 'badge-secondary'; break;
                                                    case 'terminated': echo 'badge-danger'; break;
                                                    default: echo 'badge-info';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($statusTranslations[$lease['status']][$lang] ?? ucfirst($lease['status']), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['total_cost'][$lang]; ?></th>
                                        <td><?php echo number_format($lease['total_lease_cost'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['paid_amount'][$lang]; ?></th>
                                        <td><?php echo number_format($lease['paid_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['unpaid_amount'][$lang]; ?></th>
                                        <td><?php echo number_format($lease['left_unpaid_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['terms_conditions'][$lang]; ?></th>
                                        <td class="terms-conditions"><?php echo htmlspecialchars($lease['terms_conditions'] ?: ($lang === 'am' ? 'ምንም ውሎች የሉም' : 'No terms specified'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="amharic"><?php echo $translations['created_by'][$lang]; ?></th>
                                        <td><?php
                                            $query = "SELECT username FROM users WHERE id = ?";
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param("i", $lease['created_by']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $user = $result->fetch_assoc();
                                            $stmt->close();
                                            echo htmlspecialchars($user['username'] ?? ($lang === 'am' ? 'ያልታወቀ' : 'Unknown'), ENT_QUOTES, 'UTF-8');
                                        ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title amharic"><?php echo $translations['payment_history'][$lang]; ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th class="amharic"><?php echo $translations['payment_date'][$lang]; ?></th>
                                            <th class="amharic"><?php echo $translations['amount'][$lang]; ?></th>
                                            <th class="amharic"><?php echo $translations['status'][$lang]; ?></th>
                                            <th class="amharic"><?php echo $translations['reference_number'][$lang]; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payment_history)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center amharic"><?php echo $translations['no_payments'][$lang]; ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($payment_history as $payment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($payment['payment_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo number_format($payment['amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $payment['status'] === 'completed' ? 'badge-success' : 'badge-warning'; ?>">
                                                            <?php echo htmlspecialchars(ucfirst($payment['status']), ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($payment['reference_number'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Logs -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title amharic"><?php echo $translations['audit_logs'][$lang]; ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th class="amharic"><?php echo $translations['action_type'][$lang]; ?></th>
                                            <th class="amharic"><?php echo $translations['user'][$lang]; ?></th>
                                            <th class="amharic"><?php echo $translations['old_values'][$lang]; ?></th>
                                            <th class="amharic"><?php echo $translations['new_values'][$lang]; ?></th>
                                            <th class="amharic"><?php echo $translations['created_at'][$lang]; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($audit_logs)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center amharic"><?php echo $translations['no_audit_logs'][$lang]; ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($audit_logs as $log): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(ucfirst($log['action_type']), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($log['username'] ?: 'System', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><pre><?php echo htmlspecialchars($log['old_values'] ? json_encode(json_decode($log['old_values'], true), JSON_PRETTY_PRINT) : '-', ENT_QUOTES, 'UTF-8'); ?></pre></td>
                                                    <td><pre><?php echo htmlspecialchars($log['new_values'] ? json_encode(json_decode($log['new_values'], true), JSON_PRETTY_PRINT) : '-', ENT_QUOTES, 'UTF-8'); ?></pre></td>
                                                    <td><?php echo htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>