<?php
/**
 * Notification Preferences Settings
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';
require_once '../includes/email-service.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$user = getUserData();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferences = [
        'email_on_inquiry' => isset($_POST['email_on_inquiry']) ? 1 : 0,
        'email_on_review' => isset($_POST['email_on_review']) ? 1 : 0,
        'email_on_message' => isset($_POST['email_on_message']) ? 1 : 0,
        'daily_digest' => isset($_POST['daily_digest']) ? 1 : 0,
        'weekly_report' => isset($_POST['weekly_report']) ? 1 : 0,
    ];
    
    if (EmailService::savePreferences($user['id'], $preferences)) {
        $success = 'Preferences saved successfully!';
    }
}

$prefs = EmailService::getPreferences($user['id']);

$page_title = 'Notification Preferences';
include '../includes/header.php';
?>

<style>
.preferences-container {
    max-width: 600px;
    margin: 30px auto;
    padding: 0 20px 40px;
}

.preferences-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    background: #667eea;
    color: white;
    padding: 20px;
}

.card-header h1 {
    margin: 0;
    font-size: 24px;
}

.card-body {
    padding: 25px;
}

.pref-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.pref-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.pref-section h3 {
    margin: 0 0 15px 0;
    color: #0B1C3D;
    font-size: 16px;
}

.pref-section p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 13px;
}

.toggle-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.toggle-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #F9F9F9;
    border-radius: 5px;
}

.toggle-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    margin-right: 12px;
}

.toggle-label {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.toggle-label strong {
    color: #333;
    margin-bottom: 3px;
    font-size: 14px;
}

.toggle-label small {
    color: #999;
    font-size: 12px;
}

.save-btn {
    padding: 12px 25px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
    width: 100%;
}

.save-btn:hover {
    background: #5568d3;
}

.success-msg {
    background: #D4EDDA;
    color: #155724;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    border-left: 4px solid #28a745;
}
</style>

<div class="preferences-container">
    <div class="preferences-card">
        <div class="card-header">
            <h1>🔔 Notification Preferences</h1>
        </div>
        
        <div class="card-body">
            <?php if (!empty($success)): ?>
            <div class="success-msg"><?php echo esc($success); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <!-- Instant Notifications -->
                <div class="pref-section">
                    <h3>⚡ Instant Notifications</h3>
                    <p>Get immediate alerts for important business events</p>
                    
                    <div class="toggle-group">
                        <label class="toggle-item">
                            <input type="checkbox" name="email_on_inquiry" <?php echo $prefs['email_on_inquiry'] ? 'checked' : ''; ?>>
                            <span class="toggle-label">
                                <strong>New Inquiry Alerts</strong>
                                <small>Get notified when a customer sends an inquiry</small>
                            </span>
                        </label>
                        
                        <label class="toggle-item">
                            <input type="checkbox" name="email_on_review" <?php echo $prefs['email_on_review'] ? 'checked' : ''; ?>>
                            <span class="toggle-label">
                                <strong>New Review Notifications</strong>
                                <small>Be notified when customers leave reviews</small>
                            </span>
                        </label>
                        
                        <label class="toggle-item">
                            <input type="checkbox" name="email_on_message" <?php echo $prefs['email_on_message'] ? 'checked' : ''; ?>>
                            <span class="toggle-label">
                                <strong>Message Notifications</strong>
                                <small>Get alerts for direct messages and conversations</small>
                            </span>
                        </label>
                    </div>
                </div>
                
                <!-- Scheduled Reports -->
                <div class="pref-section">
                    <h3>📊 Scheduled Reports</h3>
                    <p>Receive periodic summaries of your business performance</p>
                    
                    <div class="toggle-group">
                        <label class="toggle-item">
                            <input type="checkbox" name="daily_digest" <?php echo $prefs['daily_digest'] ? 'checked' : ''; ?>>
                            <span class="toggle-label">
                                <strong>Daily Digest</strong>
                                <small>Daily summary of views, clicks, and inquiries</small>
                            </span>
                        </label>
                        
                        <label class="toggle-item">
                            <input type="checkbox" name="weekly_report" <?php echo $prefs['weekly_report'] ? 'checked' : ''; ?>>
                            <span class="toggle-label">
                                <strong>Weekly Report</strong>
                                <small>Weekly performance analysis and trends</small>
                            </span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="save-btn">💾 Save Preferences</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
