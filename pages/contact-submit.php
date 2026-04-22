<?php
/**
 * Contact Form Handler - sends via SMTP to info@connectwith.in
 */

require_once '../config/db.php';
require_once '../config/mailer.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = trim($_POST['phone']   ?? '');
$subject = trim($_POST['subject'] ?? 'Contact Form Inquiry');
$message = trim($_POST['message'] ?? '');

$errors = [];
if (empty($name))                                   $errors[] = 'Name is required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (empty($message))                                $errors[] = 'Message is required';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Save to DB
if ($conn && !DB_UNAVAILABLE) {
    $stmt = $conn->prepare("INSERT INTO contact_queries (name, email, phone, subject, message, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param('ssssss', $name, $email, $phone, $subject, $message, $ip);
        $stmt->execute();
        $stmt->close();
    }
}

// Send notification to info@connectwith.in
$receiver   = getenv('CONTACT_RECEIVER_EMAIL') ?: 'info@connectwith.in';
$adminHtml  = mailHtmlTemplate('New Contact Form Message', '
<p><strong>Name:</strong> ' . esc($name) . '</p>
<p><strong>Email:</strong> ' . esc($email) . '</p>
<p><strong>Phone:</strong> ' . esc($phone) . '</p>
<p><strong>Subject:</strong> ' . esc($subject) . '</p>
<p><strong>Message:</strong><br>' . nl2br(esc($message)) . '</p>
<p><strong>Submitted:</strong> ' . date('Y-m-d H:i:s') . '</p>
');

$sent = sendMail($receiver, 'Contact Form: ' . $subject, $adminHtml, 'ConnectWith9 Team');

// Auto-reply to sender
$replyHtml = mailHtmlTemplate('We received your message!', '
<p>Hi <strong>' . esc($name) . '</strong>,</p>
<p>Thank you for contacting ConnectWith. We have received your message and will get back to you within 2 business hours.</p>
<p><strong>Your message:</strong><br>' . nl2br(esc($message)) . '</p>
<p>Best regards,<br><strong>ConnectWith9 Team</strong></p>
');
sendMail($email, 'We received your message - ConnectWith9', $replyHtml, $name);

echo json_encode(['success' => true, 'message' => 'Thank you! Your query has been submitted. We will contact you soon.']);
?>
