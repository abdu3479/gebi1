<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

$db = new Database();
$conn = $db->getConnection();
$userObj = new User($conn);
$user_id = $_SESSION['user_id'] ?? null;
$errors = [];
$success = '';

if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch user data
$user = $userObj->getById($user_id);
if (!$user) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'lang' => $_POST['lang'] ?? 'en'
        ];
        if ($_POST['confirm_password'] !== $data['password']) {
            $errors[] = "Passwords do not match";
        } else {
            $updated = $userObj->update($user_id, $data);
            if ($updated) {
                $success = "Profile updated successfully!";
                $_SESSION['username'] = $data['username'];
                $_SESSION['lang'] = $data['lang'];
                $user = $userObj->getById($user_id); // Refresh user data
            } else {
                $errors[] = "No changes made to profile";
            }
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Werabe Land Lease</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
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
                        <h1>User Profile</h1>
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

                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g., +251912345678">
                            </div>
                            <div class="form-group">
                                <label for="role">Role (Read-only)</label>
                                <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="is_active">Active Status (Read-only)</label>
                                <input type="text" class="form-control" id="is_active" value="<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="last_login">Last Login (Read-only)</label>
                                <input type="text" class="form-control" id="last_login" value="<?php echo htmlspecialchars($user['last_login'] ?? 'Never', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="login_ip">Last Login IP (Read-only)</label>
                                <input type="text" class="form-control" id="login_ip" value="<?php echo htmlspecialchars($user['login_ip'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="password">New Password (Leave blank to keep current)</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            <div class="form-group">
                                <label for="lang">Language</label>
                                <select class="form-control" id="lang" name="lang">
                                    <option value="en" <?php echo ($_SESSION['lang'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="am" <?php echo ($_SESSION['lang'] ?? 'en') === 'am' ? 'selected' : ''; ?>>Amharic</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</div>

<script src="https://code.jquery.com/jquery-3.71.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
</body>
</html>