<?php
include '../includes/nav.php';
require_once '../config/database.php';
require_once '../classes/Asset.php';

$lang = $_SESSION['lang'] ?? 'en';
if (($_SESSION['role'] ?? '') === 'viewer') {
    header("Location: ../dashboard.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$assetObj = new Asset($conn);
$owners = $assetObj->getOwners();

$errors = [];
$success = '';
$asset = [];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errors[] = $lang === 'am' ? 'ተገቢ ያልሆነ መለያ ቁጥር' : 'Invalid asset ID';
} else {
    $asset = $assetObj->getById((int)$_GET['id']);
    if (!$asset) {
        $errors[] = $lang === 'am' ? 'ንብረት አልተገኘም' : 'Asset not found';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $data = [
        'asset_code' => trim($_POST['asset_code'] ?? ''),
        'property_name' => trim($_POST['property_name'] ?? ''),
        'block' => trim($_POST['block'] ?? ''),
        'kebele' => trim($_POST['kebele'] ?? ''),
        'lot_number' => trim($_POST['lot_number'] ?? ''),
        'owner_id' => !empty($_POST['owner_id']) ? (int)$_POST['owner_id'] : null,
        'owner_name' => trim($_POST['owner_name'] ?? ''),
        'property_type_id' => !empty($_POST['property_type_id']) ? (int)$_POST['property_type_id'] : null,
        'construction_type_id' => !empty($_POST['construction_type_id']) ? (int)$_POST['construction_type_id'] : null,
        'address' => trim($_POST['address'] ?? ''),
        'size' => !empty($_POST['size']) ? (float)$_POST['size'] : null,
        'annual_lease_rate' => !empty($_POST['annual_lease_rate']) ? (float)$_POST['annual_lease_rate'] : null,
        'status' => trim($_POST['status'] ?? ''),
        'registration_date' => trim($_POST['registration_date'] ?? ''),
        'construction_type' => trim($_POST['construction_type'] ?? ''),
        'registration_number' => trim($_POST['registration_number'] ?? ''),
        'tax_identification_number' => trim($_POST['tax_identification_number'] ?? ''),
        'has_electricity' => isset($_POST['has_electricity']) ? 1 : 0,
        'has_water' => isset($_POST['has_water']) ? 1 : 0,
        'has_sewage' => isset($_POST['has_sewage']) ? 1 : 0,
        'property_type' => trim($_POST['property_type'] ?? '')
    ];

    try {
        $updated = $assetObj->update((int)$_GET['id'], $data);
        if ($updated) {
            $success = $lang === 'am' ? 'ንብረት በተሳካ ሁኔታ ተዘምኗል' : 'Asset updated successfully';
            header("Location: list.php");
            exit;
        } else {
            $errors[] = $lang === 'am' ? 'ምንም ለውጦች አልተደረጉም' : 'No changes made';
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
    <title><?php echo $lang === 'am' ? 'ንብረት አስተካክል - ወራቤ መሬት ሊዝ' : 'Edit Asset - Werabe Land Lease'; ?></title>
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
    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo $lang === 'am' ? 'ንብረት አስተካክል' : 'Edit Asset'; ?></h1>
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

                        <?php if (empty($errors) && $asset): ?>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="asset_code"><?php echo $lang === 'am' ? 'ንብረት ኮድ' : 'Asset Code'; ?> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="asset_code" name="asset_code" required value="<?php echo htmlspecialchars($asset['asset_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="property_name"><?php echo $lang === 'am' ? 'የንብረት ስም' : 'Property Name'; ?></label>
                                    <input type="text" class="form-control" id="property_name" name="property_name" value="<?php echo htmlspecialchars($asset['property_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="block"><?php echo $lang === 'am' ? 'ብሎክ' : 'Block'; ?></label>
                                    <input type="text" class="form-control" id="block" name="block" value="<?php echo htmlspecialchars($asset['block'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="kebele"><?php echo $lang === 'am' ? 'ቀበሌ' : 'Kebele'; ?></label>
                                    <input type="text" class="form-control" id="kebele" name="kebele" value="<?php echo htmlspecialchars($asset['kebele'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="lot_number"><?php echo $lang === 'am' ? 'የመጠን ቁጥር' : 'Lot Number'; ?></label>
                                    <input type="text" class="form-control" id="lot_number" name="lot_number" value="<?php echo htmlspecialchars($asset['lot_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="owner_id"><?php echo $lang === 'am' ? 'ባለቤት' : 'Owner'; ?></label>
                                    <select class="form-control" id="owner_id" name="owner_id">
                                        <option value=""><?php echo $lang === 'am' ? 'ባለቤት ይምረጡ' : 'Select Owner'; ?></option>
                                        <?php foreach ($owners as $owner): ?>
                                            <option value="<?php echo $owner['id']; ?>" <?php echo ($asset['owner_id'] ?? '') == $owner['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($owner['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="owner_name"><?php echo $lang === 'am' ? 'የባለቤት ስም' : 'Owner Name'; ?></label>
                                    <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($asset['owner_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="property_type_id"><?php echo $lang === 'am' ? 'የንብረት አይነት መለያ' : 'Property Type ID'; ?> <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="property_type_id" name="property_type_id" required value="<?php echo htmlspecialchars($asset['property_type_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="construction_type_id"><?php echo $lang === 'am' ? 'የግንባታ አይነት መለያ' : 'Construction Type ID'; ?></label>
                                    <input type="number" class="form-control" id="construction_type_id" name="construction_type_id" value="<?php echo htmlspecialchars($asset['construction_type_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="address"><?php echo $lang === 'am' ? 'አድራሻ' : 'Address'; ?></label>
                                    <textarea class="form-control" id="address" name="address"><?php echo htmlspecialchars($asset['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="size"><?php echo $lang === 'am' ? 'መጠን (ስኩዌር ሜትር)' : 'Size (sqm)'; ?> <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="size" name="size" required value="<?php echo htmlspecialchars($asset['size'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="annual_lease_rate"><?php echo $lang === 'am' ? 'ዓመታዊ የሊዝ ተመን' : 'Annual Lease Rate'; ?> <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="annual_lease_rate" name="annual_lease_rate" required value="<?php echo htmlspecialchars($asset['annual_lease_rate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="status"><?php echo $lang === 'am' ? 'ሁኔታ' : 'Status'; ?> <span class="text-danger">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="Active" <?php echo ($asset['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'ንቁ' : 'Active'; ?></option>
                                        <option value="Inactive" <?php echo ($asset['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'እንቅስቃሴ የለም' : 'Inactive'; ?></option>
                                        <option value="Under Maintenance" <?php echo ($asset['status'] ?? '') === 'Under Maintenance' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'በጥገና ላይ' : 'Under Maintenance'; ?></option>
                                        <option value="Leased" <?php echo ($asset['status'] ?? '') === 'Leased' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'ተከራይቷል' : 'Leased'; ?></option>
                                        <option value="Sold" <?php echo ($asset['status'] ?? '') === 'Sold' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'ተሽጧል' : 'Sold'; ?></option>
                                        <option value="Confiscated" <?php echo ($asset['status'] ?? '') === 'Confiscated' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'ተወስዷል' : 'Confiscated'; ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="registration_date"><?php echo $lang === 'am' ? 'የመመዝገቢያ ቀን' : 'Registration Date'; ?> <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="registration_date" name="registration_date" required value="<?php echo htmlspecialchars($asset['registration_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="construction_type"><?php echo $lang === 'am' ? 'የግንባታ አይነት' : 'Construction Type'; ?></label>
                                    <input type="text" class="form-control" id="construction_type" name="construction_type" value="<?php echo htmlspecialchars($asset['construction_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="registration_number"><?php echo $lang === 'am' ? 'የመመዝገቢያ ቁጥር' : 'Registration Number'; ?></label>
                                    <input type="text" class="form-control" id="registration_number" name="registration_number" value="<?php echo htmlspecialchars($asset['registration_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="tax_identification_number"><?php echo $lang === 'am' ? 'የግብር መለያ ቁጥር' : 'Tax Identification Number'; ?></label>
                                    <input type="text" class="form-control" id="tax_identification_number" name="tax_identification_number" value="<?php echo htmlspecialchars($asset['tax_identification_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="has_electricity" value="1" <?php echo ($asset['has_electricity'] ?? 0) ? 'checked' : ''; ?>> <?php echo $lang === 'am' ? 'ኤሌክትሪክ አለው' : 'Has Electricity'; ?></label>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="has_water" value="1" <?php echo ($asset['has_water'] ?? 0) ? 'checked' : ''; ?>> <?php echo $lang === 'am' ? 'ውሃ አለው' : 'Has Water'; ?></label>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="has_sewage" value="1" <?php echo ($asset['has_sewage'] ?? 0) ? 'checked' : ''; ?>> <?php echo $lang === 'am' ? 'ፍሳሽ አለው' : 'Has Sewage'; ?></label>
                                </div>
                                <div class="form-group">
                                    <label for="property_type"><?php echo $lang === 'am' ? 'የንብረት አይነት' : 'Property Type'; ?> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="property_type" name="property_type" required value="<?php echo htmlspecialchars($asset['property_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $lang === 'am' ? 'ንብረት አስተካክል' : 'Update Asset'; ?></button>
                                <a href="list.php" class="btn btn-secondary"><?php echo $lang === 'am' ? 'ተመለስ' : 'Cancel'; ?></a>
                            </form>
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
<script>
$(document).ready(function() {
    $('form').on('submit', function() {
        $(this).find('input, select, textarea').each(function() {
            $(this).val($(this).val().trim());
        });
    });
});
</script>
</body>
</html>
?>