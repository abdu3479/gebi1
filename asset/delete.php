<?php
require_once '../config/database.php';
require_once '../classes/Asset.php';

session_start();
$lang = $_SESSION['lang'] ?? 'en';
if (($_SESSION['role'] ?? '') === 'viewer') {
    header("Location: ../dashboard.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$assetObj = new Asset($conn);

$errors = [];
$success = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errors[] = $lang === 'am' ? 'ተገቢ ያልሆነ መለያ ቁጥር' : 'Invalid asset ID';
} else {
    try {
        $asset_id = (int)$_GET['id'];
        $deleted = $assetObj->delete($asset_id);
        if ($deleted) {
            $success = $lang === 'am' ? 'ንብረት በተሳካ ሁኔታ ተሰርዟል' : 'Asset deleted successfully';
            header("Location: list.php");
            exit;
        } else {
            $errors[] = $lang === 'am' ? 'ንብረት አልተገኘም' : 'Asset not found';
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'ንብረት ሰርዝ - ወራቤ መሬት ሊዝ' : 'Delete Asset - Werabe Land Lease'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <?php if ($lang === 'am'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&display=swap">
        <style>
            body { font-family: 'Noto Sans Ethiopic', Arial, sans-serif; }
            .content-wrapper, .card { text-align: right; }
        </style>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php include '../includes/nav.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo $lang === 'am' ? 'ንብረት ሰርዝ' : 'Delete Asset'; ?></h1>
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
                            <a href="list.php" class="btn btn-secondary"><?php echo $lang === 'am' ? 'ወደ ዝርዝሩ ተመለስ' : 'Back to List'; ?></a>
                        <?php endif; ?>
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
</body>
</html>
?>