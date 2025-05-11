<?php
include '../includes/nav.php';
require_once '../config/database.php';
require_once '../classes/Owner.php';

$lang = $_SESSION['lang'] ?? 'en';
if (($_SESSION['role'] ?? '') === 'viewer') {
    header("Location: ../dashboard.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$ownerObj = new Owner($conn);

$errors = [];
$success = '';
$form_errors = [];
$owner = [];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errors[] = $lang === 'am' ? 'ተገቢ ያልሆነ መለያ ቁጥር' : 'Invalid owner ID';
} else {
    $owner = $ownerObj->getById((int)$_GET['id']);
    if (!$owner) {
        $errors[] = $lang === 'am' ? 'ባለቤት አልተገኘም' : 'Owner not found';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'id_number' => trim($_POST['id_number'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'status' => trim($_POST['status'] ?? 'active'),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    // Server-side validation
    if (empty($data['first_name'])) {
        $form_errors['first_name'] = $lang === 'am' ? 'ስም ያስፈልጋል' : 'First name is required';
    }
    if (empty($data['last_name'])) {
        $form_errors['last_name'] = $lang === 'am' ? 'የአባት ስም ያስፈልጋል' : 'Last name is required';
    }
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = $lang === 'am' ? 'ትክክለኛ ኢሜይል ያስፈልጋል' : 'Invalid email format';
    }
    if (!empty($data['phone']) && !preg_match('/^\+?\d{9,15}$/', $data['phone'])) {
        $form_errors['phone'] = $lang === 'am' ? 'ትክክለኛ ስልክ ቁጥር ያስፈልጋል' : 'Invalid phone number';
    }

    if (empty($form_errors)) {
        try {
            $updated = $ownerObj->update((int)$_GET['id'], $data);
            if ($updated) {
                $success = $lang === 'am' ? 'ባለቤት በተሳካ ሁኔታ ተዘምኗል' : 'Owner updated successfully';
                header("Location: list.php");
                exit;
            } else {
                $errors[] = $lang === 'am' ? 'ምንም ለውጦች አልተደረጉም' : 'No changes made';
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'am' ? 'ባለቤት አስተካክል - ወራቤ መሬት ሊዝ' : 'Edit Owner - Werabe Land Lease'; ?></title>
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
                        <h1><?php echo $lang === 'am' ? 'ባለቤት አስተካክል' : 'Edit Owner'; ?></h1>
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

                        <?php if (empty($errors) && $owner): ?>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="first_name"><?php echo $lang === 'am' ? 'ስም' : 'First Name'; ?></label>
                                    <input type="text" class="form-control <?php echo isset($form_errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($owner['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if (isset($form_errors['first_name'])): ?>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($form_errors['first_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="last_name"><?php echo $lang === 'am' ? 'የአባት ስም' : 'Last Name'; ?></label>
                                    <input type="text" class="form-control <?php echo isset($form_errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($owner['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if (isset($form_errors['last_name'])): ?>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($form_errors['last_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="email"><?php echo $lang === 'am' ? 'ኢሜይል' : 'Email'; ?></label>
                                    <input type="email" class="form-control <?php echo isset($form_errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($owner['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if (isset($form_errors['email'])): ?>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($form_errors['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="phone"><?php echo $lang === 'am' ? 'ስልክ' : 'Phone'; ?></label>
                                    <input type="text" class="form-control <?php echo isset($form_errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" placeholder="<?php echo $lang === 'am' ? 'ለምሳሌ፡ +251912345678' : 'e.g., +251912345678'; ?>" value="<?php echo htmlspecialchars($owner['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if (isset($form_errors['phone'])): ?>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($form_errors['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="id_number"><?php echo $lang === 'am' ? 'የመታወቂያ ቁጥር' : 'ID Number'; ?></label>
                                    <input type="text" class="form-control" id="id_number" name="id_number" value="<?php echo htmlspecialchars($owner['id_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="address"><?php echo $lang === 'am' ? 'አድራሻ' : 'Address'; ?></label>
                                    <textarea class="form-control" id="address" name="address"><?php echo htmlspecialchars($owner['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="status"><?php echo $lang === 'am' ? 'ሁኔታ' : 'Status'; ?></label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="active" <?php echo ($owner['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'ንቁ' : 'Active'; ?></option>
                                        <option value="inactive" <?php echo ($owner['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>><?php echo $lang === 'am' ? 'እንቅስቃሴ የለም' : 'Inactive'; ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="is_active" value="1" <?php echo ($owner['is_active'] ?? 1) ? 'checked' : ''; ?>> <?php echo $lang === 'am' ? 'ንቁ ነው' : 'Is Active'; ?></label>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $lang === 'am' ? 'ባለቤት አስተካክል' : 'Update Owner'; ?></button>
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