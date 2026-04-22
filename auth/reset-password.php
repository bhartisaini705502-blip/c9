<?php
/**
 * Reset Password Page
 * Validates the token from the email link and allows user to set a new password.
 */

require_once '../config/db.php';
require_once '../config/auth.php';

redirectIfLoggedIn('/pages/dashboard.php');

$token    = trim($_GET['token'] ?? '');
$error    = '';
$success  = '';
$validToken = false;
$tokenData  = null;

if (empty($token)) {
    $error = 'Invalid or missing reset token.';
} elseif (!$conn) {
    $error = 'Database unavailable. Please try again later.';
} else {
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $tokenData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tokenData) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $validToken = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password        = $_POST['password']         ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwords do not match.';
    } else {
        // Update user's password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param('ss', $hashed, $tokenData['email']);
        $updated = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        if ($updated && $affectedRows > 0) {
            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $stmt->close();

            $success    = 'Your password has been reset successfully. You can now log in.';
            $validToken = false;
        } else {
            $error = 'Could not update password. Please try again.';
        }
    }
}

$page_title      = 'Reset Password';
$meta_description = 'Set a new password for your ConnectWith9 account.';
include '../includes/header.php';
?>

<style>
.auth-container {
    max-width: 420px;
    margin: 60px auto;
    padding: 40px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.auth-header { text-align: center; margin-bottom: 28px; }
.auth-header h1 { margin: 0 0 8px; color: #333; font-size: 26px; }
.auth-header p  { color: #666; margin: 0; font-size: 14px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 7px; color: #333; font-weight: 600; font-size: 14px; }
.form-group input { width: 100%; padding: 12px; border: 1px solid #DDD; border-radius: 5px; font-size: 14px; font-family: inherit; box-sizing: border-box; }
.form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }
.password-hint { font-size: 12px; color: #999; margin-top: 5px; }
.btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; font-weight: 600; font-size: 16px; cursor: pointer; transition: all 0.3s ease; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
.auth-links { text-align: center; margin-top: 20px; font-size: 14px; color: #666; }
.auth-links a { color: #667eea; text-decoration: none; font-weight: 600; }
.auth-links a:hover { text-decoration: underline; }
.alert { padding: 14px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; }
.alert-error   { background: #F8D7DA; color: #721C24; border: 1px solid #F5C6CB; }
.alert-success { background: #D4EDDA; color: #155724; border: 1px solid #C3E6CB; }
@media (max-width: 600px) { .auth-container { margin: 20px; padding: 20px; } }
</style>

<div class="container">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Reset Password</h1>
            <p>Enter your new password below.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php if (!$validToken): ?>
                <div class="auth-links">
                    <a href="/auth/forgot-password.php">Request a new reset link</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <div class="auth-links">
                <a href="/auth/login.php">Login now &rarr;</a>
            </div>
        <?php endif; ?>

        <?php if ($validToken && !$success): ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label for="password">New Password *</label>
                    <input type="password" id="password" name="password" required minlength="6" autofocus>
                    <div class="password-hint">Minimum 6 characters.</div>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm Password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
                </div>
                <button type="submit" class="btn-submit">Set New Password</button>
            </form>
            <div class="auth-links">
                <a href="/auth/login.php">&larr; Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
