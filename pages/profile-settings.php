<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireLogin();

$user = getUserData();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');

    if ($full_name === '' || $email === '') {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $GLOBALS['conn']->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, company_name = ? WHERE id = ?");
        $stmt->bind_param('ssssi', $full_name, $email, $phone, $company_name, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $message = 'Profile updated successfully.';
            $user = getUserData();
        } else {
            $error = 'Failed to update profile.';
        }
        $stmt->close();
    }
}

$page_title = 'Profile Settings';
include '../includes/header.php';
?>

<style>
.settings-wrap { max-width: 760px; margin: 40px auto; padding: 0 20px 60px; }
.settings-card { background: #fff; border-radius: 14px; padding: 28px; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.settings-card h1 { margin: 0 0 10px; color: #0B1C3D; }
.settings-card p { margin: 0 0 24px; color: #666; }
.form-row { margin-bottom: 16px; }
.form-row label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
.form-row input { width: 100%; padding: 12px 14px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
.btn-save { padding: 12px 20px; border: none; border-radius: 8px; background: linear-gradient(135deg, #FF6A00, #FF8533); color: #fff; font-weight: 700; cursor: pointer; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
</style>

<div class="settings-wrap">
    <div class="settings-card">
        <h1>Profile Settings</h1>
        <p>Update your account details.</p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-row">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>
            <div class="form-row">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <label>Company Name</label>
                <input type="text" name="company_name" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>