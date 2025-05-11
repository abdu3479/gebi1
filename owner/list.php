<?php

include '../includes/nav.php';
require_once '../config/database.php';
require_once '../classes/Owner.php';

// Handle language switch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang'])) {
    $new_lang = in_array($_POST['lang'], ['en', 'am']) ? $_POST['lang'] : 'en';
    $_SESSION['lang'] = $new_lang;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Set session variables
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = ($_SESSION['role'] ?? '') === 'viewer';
$current_page = basename($_SERVER['PHP_SELF']);

// Restrict access to authorized roles
if (!in_array($_SESSION['role'] ?? '', ['admin', 'billing_officer', 'clerk', 'viewer'])) {
    $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ ይህን ገፅ ለመድረስ ፍቃድ የለዎትም።' : 'Error: You do not have permission to access this page.';
    header('Location: ../dashboard.php');
    exit;
}

// Initialize database connection and fetch owners
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $ownerObj = new Owner($conn);
    $owners = $ownerObj->getAll();
} catch (Exception $e) {
    error_log("Error fetching owners: " . $e->getMessage());
    $_SESSION['error'] = $lang === 'am' ? 'ስህተት፡ ባለቤቶችን መጫን አልተሳካም። (' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ')' : 'Error: Failed to load owners. (' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ')';
    $owners = [];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'ዝርዝር ባለቤቶች - ወራቤ መሬት ሊዝ' : 'List Owners - Werabe Land Lease'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">
    <?php if ($lang === 'am'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&display=swap">
        <style>
            body { font-family: 'Noto Sans Ethiopic', Arial, sans-serif; }
            .content-wrapper, .card, table { text-align: left; }
            .dataTables_wrapper { direction: ltr; }
            .lang-switcher { text-align: left; }
        </style>
    <?php else: ?>
        <style>
            .lang-switcher { text-align: right; }
        </style>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo $lang === 'am' ? 'ዝርዝር ባለቤቶች' : 'List Owners'; ?></h1>
                    </div>
                    <div class="col-sm-6 lang-switcher">
                        <form method="POST" id="lang-form">
                            <select name="lang" id="lang-switcher" class="form-control custom-select" style="width: 150px; float: <?php echo $lang === 'am' ? 'left' : 'right'; ?>;">
                                <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="am" <?php echo $lang === 'am' ? 'selected' : ''; ?>>አማርኛ</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $lang === 'am' ? 'ባለቤቶች' : 'Owners'; ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        <table id="ownersTable" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                   
                                    <th><?php echo $lang === 'am' ? 'ስም' : 'First Name'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የአባት ስም' : 'Last Name'; ?></th>
                            
                                    <th><?php echo $lang === 'am' ? 'ስልክ' : 'Phone'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የመታወቂያ ቁጥር' : 'ID Number'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'አድራሻ' : 'Address'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ሁኔታ' : 'Status'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'ንቁ ነው' : 'Is Active'; ?></th>
                                    <th><?php echo $lang === 'am' ? 'የተፈጠረበት ጊዜ' : 'Created At'; ?></th>
                                    <?php if (!$is_viewer): ?>
                                        <th><?php echo $lang === 'am' ? 'ተግባራት' : 'Actions'; ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($owners as $owner): ?>
                                    <tr>
                                        
                                        <td><?php echo htmlspecialchars($owner['first_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($owner['last_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                    
                                        <td><?php echo htmlspecialchars($owner['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($owner['id_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($owner['address'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($owner['status'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo $owner['is_active'] ? ($lang === 'am' ? 'አዎ' : 'Yes') : ($lang === 'am' ? 'አይ' : 'No'); ?></td>
                                        <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($owner['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php if (!$is_viewer): ?>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton_<?php echo $owner['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <?php echo $lang === 'am' ? 'ተግባራት' : 'Actions'; ?>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton_<?php echo $owner['id']; ?>">
                                                        <a class="dropdown-item" href="edit.php?id=<?php echo $owner['id']; ?>">
                                                            <i class="fas fa-edit mr-2"></i><?php echo $lang === 'am' ? 'አስተካክል' : 'Edit'; ?>
                                                        </a>
                                                        <a class="dropdown-item" href="bill_history.php?owner_id=<?php echo $owner['id']; ?>">
                                                            <i class="fas fa-file-invoice-dollar mr-2"></i><?php echo $lang === 'am' ? 'የቢል ታሪክ' : 'Bill History'; ?>
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger" href="delete.php?id=<?php echo $owner['id']; ?>" onclick="return confirm('<?php echo $lang === 'am' ? 'እርግጠኛ ነዎት ይህን ባለቤት መሰረዝ ይፈልጋሉ?' : 'Are you sure you want to delete this owner?'; ?>')">
                                                            <i class="fas fa-trash mr-2"></i><?php echo $lang === 'am' ? 'ሰርዝ' : 'Delete'; ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#ownersTable').DataTable({
        "dom": '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        "buttons": [
            <?php if (!$is_viewer): ?>
            'copy', 'csv', 'excel', 'pdf', 'print'
            <?php endif; ?>
        ],
        "paging": true,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "<?php echo $lang === 'am' ? '//cdn.datatables.net/plug-ins/1.13.7/i18n/am.json' : '//cdn.datatables.net/plug-ins/1.13.7/i18n/en-GB.json'; ?>"
        }
    });

    // Auto-submit language switcher form on change
    $('#lang-switcher').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>
</body>
</html>