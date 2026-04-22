<?php
/**
 * Email Service - Handles all email notifications via SMTP (PHPMailer)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php';

class EmailService {
    private static $senderName = 'ConnectWith9';

    public static function sendInquiryNotification($inquiry, $business) {
        $email = $business['contact_email'] ?? null;
        if (!$email) return false;

        $subject = "New Inquiry: " . $inquiry['name'] . " - " . $business['name'];
        $body = mailHtmlTemplate('New Inquiry for ' . htmlspecialchars($business['name']), '
            <p><strong>Customer Name:</strong> ' . htmlspecialchars($inquiry['name']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($inquiry['email']) . '</p>
            <p><strong>Phone:</strong> ' . htmlspecialchars($inquiry['phone']) . '</p>
            <p><strong>Type:</strong> ' . htmlspecialchars(ucfirst($inquiry['inquiry_type'])) . '</p>
            <p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($inquiry['message'])) . '</p>
            <p><a href="https://connectwith9.com/pages/business-inquiries.php?bid=' . (int)$business['id'] . '" style="background:#FF6A00;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;">View in Dashboard</a></p>
        ');

        $result = sendMail($email, $subject, $body);
        self::logEmail($email, $subject, 'inquiry', $business['id'], $inquiry['id'] ?? null, $result['success']);
        return $result['success'];
    }

    public static function sendUpgradeConfirmation($business, $plan, $expiresAt) {
        $email = $business['contact_email'] ?? null;
        if (!$email) return false;

        $subject = "Premium Upgrade Confirmed - " . $plan['name'];
        $featureItems = implode('', array_map(fn($f) => '<li>' . htmlspecialchars(trim($f)) . '</li>', explode(',', $plan['features'])));
        $body = mailHtmlTemplate('Welcome to Premium!', '
            <p>Your business <strong>' . htmlspecialchars($business['name']) . '</strong> has been upgraded to <strong>' . htmlspecialchars($plan['name']) . '</strong>.</p>
            <h3>Your Benefits:</h3>
            <ul>' . $featureItems . '</ul>
            <p><strong>Valid Until:</strong> ' . date('F d, Y', strtotime($expiresAt)) . '</p>
            <p><a href="https://connectwith9.com/pages/dashboard.php" style="background:#FF6A00;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;">View Your Dashboard</a></p>
        ');

        $result = sendMail($email, $subject, $body);
        self::logEmail($email, $subject, 'upgrade', $business['id'], null, $result['success']);
        return $result['success'];
    }

    public static function sendDailyDigest($business, $analytics) {
        $email = $business['contact_email'] ?? null;
        if (!$email) return false;

        $subject = "Daily Report - " . $business['name'];
        $body = mailHtmlTemplate('Daily Performance Report', '
            <p>Business: <strong>' . htmlspecialchars($business['name']) . '</strong></p>
            <h3>Today\'s Metrics</h3>
            <table style="border-collapse:collapse;width:100%;">
                <tr style="border-bottom:1px solid #ddd;"><td style="padding:8px;"><strong>Views</strong></td><td style="padding:8px;text-align:right;font-weight:bold;">' . (int)$analytics['views'] . '</td></tr>
                <tr style="border-bottom:1px solid #ddd;"><td style="padding:8px;"><strong>Clicks</strong></td><td style="padding:8px;text-align:right;font-weight:bold;">' . (int)$analytics['clicks'] . '</td></tr>
                <tr><td style="padding:8px;"><strong>Inquiries</strong></td><td style="padding:8px;text-align:right;font-weight:bold;">' . (int)$analytics['inquiries'] . '</td></tr>
            </table>
            <p><a href="https://connectwith9.com/pages/business-inquiries.php?bid=' . (int)$business['id'] . '" style="background:#FF6A00;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:15px;">View Full Analytics</a></p>
        ');

        $result = sendMail($email, $subject, $body);
        self::logEmail($email, $subject, 'digest', $business['id'], null, $result['success']);
        return $result['success'];
    }

    public static function send($toEmail, $subject, $body, $emailType = 'general', $businessId = null, $inquiryId = null) {
        $result = sendMail($toEmail, $subject, $body);
        self::logEmail($toEmail, $subject, $emailType, $businessId, $inquiryId, $result['success']);
        return $result['success'];
    }

    private static function logEmail($toEmail, $subject, $emailType, $businessId, $inquiryId, $success) {
        global $conn;
        if (!$conn) return;
        try {
            $status = $success ? 'sent' : 'failed';
            $stmt = $conn->prepare("
                INSERT INTO email_logs (recipient_email, subject, email_type, status, related_business_id, related_inquiry_id, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            if ($stmt) {
                $stmt->bind_param('sssiii', $toEmail, $subject, $emailType, $businessId, $inquiryId, $success);
                $stmt->bind_param('sssiii', $toEmail, $subject, $emailType, $status, $businessId, $inquiryId);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {}
    }

    public static function getPreferences($userId) {
        global $conn;
        if (!$conn) return self::defaultPrefs();
        $stmt = $conn->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?? self::defaultPrefs();
    }

    private static function defaultPrefs() {
        return ['email_on_inquiry' => true, 'email_on_review' => true, 'email_on_message' => true, 'daily_digest' => false, 'weekly_report' => false];
    }

    public static function savePreferences($userId, $preferences) {
        global $conn;
        $stmt = $conn->prepare("
            INSERT INTO notification_preferences (user_id, email_on_inquiry, email_on_review, email_on_message, daily_digest, weekly_report)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                email_on_inquiry = VALUES(email_on_inquiry),
                email_on_review = VALUES(email_on_review),
                email_on_message = VALUES(email_on_message),
                daily_digest = VALUES(daily_digest),
                weekly_report = VALUES(weekly_report)
        ");
        return $stmt->bind_param('iiiiii', $userId, $preferences['email_on_inquiry'] ?? 1, $preferences['email_on_review'] ?? 1, $preferences['email_on_message'] ?? 1, $preferences['daily_digest'] ?? 0, $preferences['weekly_report'] ?? 0) && $stmt->execute();
    }
}
?>
