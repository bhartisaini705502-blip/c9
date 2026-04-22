<?php
/**
 * Admin SMTP Test Page
 */
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin/index.php');
    exit;
}

require_once '../config/mailer.php';

$result  = null;
$tested  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tested = true;
    $to      = trim($_POST['test_email'] ?? 'info@connectwith.in');
    $subject = 'ConnectWith9 SMTP Test - ' . date('Y-m-d H:i:s');
    $body    = mailHtmlTemplate('SMTP Test Successful!', '
<p>This is a test email sent from <strong>ConnectWith9</strong>.</p>
<p><strong>SMTP Config:</strong></p>
<ul>
  <li>Host: ' . htmlspecialchars(getenv('SMTP_HOST') ?: 'smtp.hostinger.com') . '</li>
  <li>Port: ' . htmlspecialchars(getenv('SMTP_PORT') ?: '465') . '</li>
  <li>From: ' . htmlspecialchars(getenv('SMTP_FROM_EMAIL') ?: 'support@connectwith.in') . '</li>
  <li>Encryption: SSL</li>
</ul>
<p>If you see this email, your SMTP is working correctly!</p>
');
    $result = sendMail($to, $subject, $body, 'ConnectWith9 Admin');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SMTP Test - Admin</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
.card { background: white; border-radius: 12px; padding: 40px; max-width: 560px; width: 90%; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
h1 { font-size: 24px; color: #0B1C3D; margin-bottom: 8px; }
.subtitle { color: #666; font-size: 14px; margin-bottom: 30px; }
.config-box { background: #f8f9fa; border-radius: 8px; padding: 16px; margin-bottom: 24px; font-size: 14px; color: #444; }
.config-box table { width: 100%; border-collapse: collapse; }
.config-box td { padding: 6px 10px; }
.config-box td:first-child { font-weight: 600; color: #0B1C3D; width: 40%; }
label { display: block; font-weight: 600; color: #333; margin-bottom: 6px; font-size: 14px; }
input[type="email"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
button { background: #FF6A00; color: white; border: none; border-radius: 8px; padding: 12px 28px; font-size: 15px; font-weight: 700; cursor: pointer; width: 100%; }
button:hover { background: #e55a00; }
.alert { border-radius: 8px; padding: 16px; margin-bottom: 20px; font-size: 14px; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.back-link { display: inline-block; margin-top: 20px; color: #666; font-size: 13px; text-decoration: none; }
.back-link:hover { color: #FF6A00; }
</style>
</head>
<body>
<div class="card">
    <h1>📧 SMTP Test</h1>
    <p class="subtitle">Send a test email to verify your SMTP configuration is working.</p>

    <div class="config-box">
        <table>
            <tr><td>Host:</td><td><?php echo htmlspecialchars(getenv('SMTP_HOST') ?: 'smtp.hostinger.com'); ?></td></tr>
            <tr><td>Port:</td><td><?php echo htmlspecialchars(getenv('SMTP_PORT') ?: '465'); ?></td></tr>
            <tr><td>Encryption:</td><td>SSL</td></tr>
            <tr><td>From:</td><td><?php echo htmlspecialchars(getenv('SMTP_FROM_EMAIL') ?: 'support@connectwith.in'); ?></td></tr>
            <tr><td>Password:</td><td><?php echo getenv('SMTP_PASSWORD') ? '✅ Set' : '❌ Not set'; ?></td></tr>
        </table>
    </div>

    <?php if ($tested && $result): ?>
        <?php if ($result['success']): ?>
            <div class="alert alert-success">✅ <strong>Email sent successfully!</strong> Check your inbox.</div>
        <?php else: ?>
            <div class="alert alert-error">❌ <strong>Failed to send:</strong> <?php echo htmlspecialchars($result['error'] ?? 'Unknown error'); ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST">
        <label>Send test email to:</label>
        <input type="email" name="test_email" value="info@connectwith.in" required>
        <button type="submit">Send Test Email</button>
    </form>

    <a href="/admin/dashboard.php" class="back-link">← Back to Dashboard</a>
</div>
</body>
</html>
