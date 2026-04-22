<?php
/**
 * Forgot Password Page
 * Sends a password reset link to the user's email.
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../config/mailer.php';

redirectIfLoggedIn('/pages/dashboard.php');

// Ensure password_resets table exists
if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!$conn) {
        $error = 'Database unavailable. Please try again later.';
    } else {
        // Check if email exists in users table
        $stmt = $conn->prepare("SELECT id, full_name, username FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Always show success to prevent email enumeration
        $success = 'If that email is registered, you will receive a password reset link shortly.';

        if ($user) {
            // Delete any existing unused tokens for this email
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->close();

            // Generate secure token
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $email, $token, $expiresAt);
            $stmt->execute();
            $stmt->close();

            // Build reset URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'];
            $resetUrl = $protocol . '://' . $host . '/auth/reset-password.php?token=' . urlencode($token);

            $name    = htmlspecialchars($user['full_name'] ?: $user['username']);
            $subject = 'Reset Your ConnectWith9 Password';
            $body    = mailHtmlTemplate('Password Reset Request', '
                <p>Hi ' . $name . ',</p>
                <p>We received a request to reset the password for your ConnectWith9 account.</p>
                <p>Click the button below to set a new password. This link is valid for <strong>1 hour</strong>.</p>
                <p style="text-align:center;margin:30px 0;">
                    <a href="' . $resetUrl . '" style="background:#667eea;color:white;padding:14px 28px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">Reset Password</a>
                </p>
                <p>If you did not request this, you can safely ignore this email — your password will not change.</p>
                <p style="color:#999;font-size:13px;">Link expires at: ' . date('d M Y, h:i A', strtotime($expiresAt)) . '</p>
            ');

            sendMail($email, $subject, $body, $name);
        }
    }
}

$page_title      = 'Forgot Password';
$meta_description = 'Reset your ConnectWith9 account password.';
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
            <h1>Forgot Password?</h1>
            <p>Enter your email and we'll send you a reset link.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <div class="auth-links">
                <a href="/auth/login.php">&larr; Back to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required autofocus
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <button type="submit" class="btn-submit">Send Reset Link</button>
            </form>
            <div class="auth-links">
                Remember your password? <a href="/auth/login.php">Login here</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
