<?php
/**
 * Send Enquiry / Lead Capture API
 * Stores the lead and emails the verified listing manager.
 */

require_once '../config/db.php';
require_once '../config/mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$businessId = intval($_POST['business_id'] ?? 0);
$name       = trim($_POST['name']       ?? '');
$phone      = trim($_POST['phone']      ?? '');
$email      = trim($_POST['email']      ?? '');
$service    = trim($_POST['service']    ?? '');
$message    = trim($_POST['message']    ?? $service);
$type       = trim($_POST['type']       ?? 'general');

if (!$businessId || !$name || (!$phone && !$email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

if (!$GLOBALS['conn']) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

// Validate business exists
$check = $GLOBALS['conn']->prepare("SELECT id, name FROM extracted_businesses WHERE id = ? AND business_status = 'OPERATIONAL'");
$check->bind_param('i', $businessId);
$check->execute();
$business = $check->get_result()->fetch_assoc();
$check->close();

if (!$business) {
    http_response_code(404);
    echo json_encode(['error' => 'Business not found']);
    exit;
}

$businessName = $business['name'];
$msg = $service ?: $message;

// Prevent duplicate inquiries from the same visitor for the same listing/service/message combination
$dupCheck = $GLOBALS['conn']->prepare("
    SELECT id FROM inquiries
    WHERE business_id = ?
      AND name = ?
      AND COALESCE(phone, '') = ?
      AND COALESCE(email, '') = ?
      AND COALESCE(message, '') = ?
      AND COALESCE(inquiry_type, 'general') = ?
    ORDER BY id DESC
    LIMIT 1
");
$dupCheck->bind_param('isssss', $businessId, $name, $phone, $email, $msg, $type);
$dupCheck->execute();
$existingInquiry = $dupCheck->get_result()->fetch_assoc();
$dupCheck->close();

if ($existingInquiry) {
    echo json_encode(['success' => true, 'inquiry_id' => $existingInquiry['id'], 'duplicate' => true]);
    exit;
}

// Insert inquiry
$stmt = $GLOBALS['conn']->prepare("
    INSERT INTO inquiries (business_id, name, email, phone, message, inquiry_type, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())
");
$stmt->bind_param('isssss', $businessId, $name, $email, $phone, $msg, $type);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save enquiry']);
    exit;
}
$inquiryId = $GLOBALS['conn']->insert_id;
$stmt->close();

// Update analytics
$GLOBALS['conn']->query("
    INSERT INTO business_analytics (business_id, inquiries)
    VALUES ($businessId, 1)
    ON DUPLICATE KEY UPDATE inquiries = inquiries + 1
");
$today = date('Y-m-d');
$GLOBALS['conn']->query("
    INSERT INTO daily_analytics (business_id, date, inquiries)
    VALUES ($businessId, '$today', 1)
    ON DUPLICATE KEY UPDATE inquiries = inquiries + 1
");

// Find the verified listing manager for this business
$managerStmt = $GLOBALS['conn']->prepare("
    SELECT u.email, u.full_name
    FROM listing_claims lc
    JOIN users u ON lc.user_id = u.id
    WHERE lc.business_id = ? AND lc.claim_status = 'approved'
    LIMIT 1
");
$managerStmt->bind_param('i', $businessId);
$managerStmt->execute();
$manager = $managerStmt->get_result()->fetch_assoc();
$managerStmt->close();

// Send email to the listing manager if verified
if ($manager && !empty($manager['email'])) {
    $serviceLine = $service ? "<p><strong>Service Needed:</strong> " . htmlspecialchars($service) . "</p>" : '';
    $messageLine = $message && $message !== $service ? "<p><strong>Message:</strong> " . nl2br(htmlspecialchars($message)) . "</p>" : '';
    $phoneLine   = $phone ? "<p><strong>Phone:</strong> <a href='tel:{$phone}'>{$phone}</a></p>" : '';
    $emailLine   = $email ? "<p><strong>Email:</strong> <a href='mailto:{$email}'>{$email}</a></p>" : '';

    $subject = "New Lead: {$name} is interested in your listing";
    $body    = mailHtmlTemplate('New Enquiry / Lead', "
        <p>Hi " . htmlspecialchars($manager['full_name']) . ",</p>
        <p>You have received a new enquiry on your listing <strong>" . htmlspecialchars($businessName) . "</strong>.</p>
        <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
        <p><strong>Customer Name:</strong> " . htmlspecialchars($name) . "</p>
        {$phoneLine}
        {$emailLine}
        {$serviceLine}
        {$messageLine}
        <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
        <p><a href='https://connectwith9.com/pages/business-inquiries.php?bid={$businessId}' 
           style='background:#667eea;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;'>
           View All Leads
        </a></p>
        <p style='color:#999;font-size:12px;margin-top:20px;'>This lead was submitted on " . date('d M Y, h:i A') . ".</p>
    ");

    sendMail($manager['email'], $subject, $body, $manager['full_name']);
}

echo json_encode(['success' => true, 'inquiry_id' => $inquiryId]);
?>