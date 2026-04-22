<?php
/**
 * Lead Form Handler - Advanced Business Automation
 * - Stores leads with scoring
 * - Sends admin notification via SMTP
 * - Sends user auto-reply via SMTP
 * - Prevents duplicates
 */

require_once '../config/db.php';
require_once '../config/mailer.php';
require_once '../includes/functions.php';

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$email   = trim($_POST['email']   ?? '');
$message = trim($_POST['message'] ?? '');
$service = trim($_POST['service'] ?? 'General Inquiry');
$source  = trim($_POST['source']  ?? 'contact-form');

if (empty($name) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name and phone are required']);
    exit;
}

$clean_phone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($clean_phone) == 11 && $clean_phone[0] == '0') {
    $clean_phone = substr($clean_phone, 1);
}
if (strlen($clean_phone) > 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}
if (!preg_match('/^[0-9]{10}$/', $clean_phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone must be 10 digits']);
    exit;
}

if ($conn && !DB_UNAVAILABLE) {
    $stmt = $conn->prepare("SELECT id FROM leads WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Please wait before submitting again']);
        $stmt->close();
        exit;
    }
    $stmt->close();
}

// ============================================
// CALCULATE LEAD SCORE
// ============================================
$score = 0;
$service_scores = [
    'Website Development' => 10, 'SEO Services' => 8, 'Social Media Marketing' => 7,
    'Content Marketing' => 6, 'Email Marketing' => 6, 'PPC Services' => 9,
    'Google Ads' => 9, 'E-commerce' => 10, 'Mobile Marketing' => 7,
    'Video Marketing' => 7, 'Digital Strategy' => 8, 'ORM' => 8,
    'CRM Marketing' => 8, 'Analytics' => 6, 'Callback Request' => 5,
    'Consultation Request' => 4,
];
if (isset($service_scores[$service])) $score += $service_scores[$service];
if (!empty($phone))   $score += 5;
if (!empty($email))   $score += 3;
if (strlen($message) > 50) $score += 2;

// ============================================
// STORE LEAD IN DATABASE
// ============================================
if ($conn && !DB_UNAVAILABLE) {
    $stmt = $conn->prepare("INSERT INTO leads (name, phone, email, service, message, source, status, score) VALUES (?, ?, ?, ?, ?, ?, 'new', ?)");
    if ($stmt) {
        $stmt->bind_param('ssssssi', $name, $phone, $email, $service, $message, $source, $score);
        $stmt->execute();
        $stmt->close();
    }
}

// ============================================
// SEND ADMIN NOTIFICATION EMAIL
// ============================================
$admin_email = getenv('CONTACT_RECEIVER_EMAIL') ?: 'info@connectwith.in';

$adminHtml = mailHtmlTemplate('New Lead Received', '
<p><strong>Name:</strong> ' . esc($name) . '</p>
<p><strong>Phone:</strong> ' . esc($phone) . '</p>
<p><strong>Email:</strong> ' . (!empty($email) ? esc($email) : 'Not provided') . '</p>
<p><strong>Service:</strong> ' . esc($service) . '</p>
<p><strong>Source:</strong> ' . esc($source) . '</p>
<p><strong>Lead Score:</strong> ' . (int)$score . '</p>
<p><strong>Message:</strong><br>' . nl2br(esc($message)) . '</p>
<p><strong>Submitted:</strong> ' . date('Y-m-d H:i:s') . '</p>
<hr>
<p><a href="https://connectwith9.com/admin/leads-management.php">View in Admin Panel</a></p>
');

sendMail($admin_email, "New Lead: $name - $service (Score: $score)", $adminHtml, 'ConnectWith9 Team');

// ============================================
// SEND USER AUTO-REPLY EMAIL
// ============================================
if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $userHtml = mailHtmlTemplate('Thank You for Contacting ConnectWith!', '
<p>Hi <strong>' . esc($name) . '</strong>,</p>
<p>Thank you for reaching out to ConnectWith regarding <strong>' . esc($service) . '</strong>.</p>
<p>We have received your inquiry and our team will get back to you within <strong>2 business hours</strong>.</p>
<p>In the meantime, feel free to reach us at:</p>
<ul>
  <li>📞 <strong>Call/WhatsApp:</strong> <a href="tel:09068899033">09068899033</a></li>
  <li>🌐 <strong>Website:</strong> <a href="https://connectwith9.com">connectwith9.com</a></li>
</ul>
<p>Best regards,<br><strong>ConnectWith9 Team</strong><br>Digital Marketing &amp; Business Growth Solutions</p>
');
    sendMail($email, 'Thank You for Contacting ConnectWith!', $userHtml, $name);
}

// ============================================
// WHATSAPP LINK & SUCCESS RESPONSE
// ============================================
$wa_message = "Hi $name,\n\nThank you for contacting ConnectWith!\nWe've received your interest in $service.\nOur team will reach out to you shortly.\n\nMeanwhile, feel free to call or WhatsApp for quick response.\n📞 09068899033";
$wa_link = "https://wa.me/919068899033?text=" . urlencode($wa_message);

http_response_code(200);
echo json_encode([
    'success'       => true,
    'message'       => 'Thank you! We will contact you soon.',
    'whatsapp_link' => $wa_link,
    'score'         => $score,
    'data'          => ['name' => $name, 'phone' => $phone, 'service' => $service]
]);
?>
