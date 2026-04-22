<?php
/**
 * Track and Store Customer Inquiries
 */

header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['business_id']) || empty($data['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$businessId = intval($data['business_id']);
$name = trim($data['name']);
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$message = trim($data['message'] ?? '');
$type = trim($data['type'] ?? 'general');

// Validate business exists
$check = $GLOBALS['conn']->prepare("SELECT id FROM extracted_businesses WHERE id = ? AND business_status = 'OPERATIONAL'");
$check->bind_param('i', $businessId);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Business not found']);
    exit;
}

// Insert inquiry
$stmt = $GLOBALS['conn']->prepare("
    INSERT INTO inquiries (business_id, name, email, phone, message, inquiry_type) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('isssss', $businessId, $name, $email, $phone, $message, $type);

if ($stmt->execute()) {
    // Update analytics
    $GLOBALS['conn']->query("
        INSERT INTO business_analytics (business_id, inquiries) 
        VALUES ($businessId, 1) 
        ON DUPLICATE KEY UPDATE inquiries = inquiries + 1
    ");
    
    // Update daily analytics
    $today = date('Y-m-d');
    $GLOBALS['conn']->query("
        INSERT INTO daily_analytics (business_id, date, inquiries) 
        VALUES ($businessId, '$today', 1) 
        ON DUPLICATE KEY UPDATE inquiries = inquiries + 1
    ");
    
    echo json_encode(['success' => true, 'inquiry_id' => $GLOBALS['conn']->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit inquiry']);
}
?>
