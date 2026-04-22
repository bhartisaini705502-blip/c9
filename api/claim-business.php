<?php
/**
 * API - Claim Business Endpoint
 */

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../config/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$business_id = isset($_POST['business_id']) ? (int)$_POST['business_id'] : 0;
$owner_name = isset($_POST['owner_name']) ? trim($_POST['owner_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
if (!$business_id || empty($owner_name) || empty($email) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email']);
    exit;
}

// Get user ID if logged in
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

// Check if business exists
$stmt = $conn->prepare("SELECT id FROM extracted_businesses WHERE id = ?");
$stmt->bind_param('i', $business_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Business not found']);
    exit;
}

// Check if already claimed
$stmt = $conn->prepare("SELECT id FROM listing_claims WHERE business_id = ? AND claim_status IN ('pending', 'approved')");
$stmt->bind_param('i', $business_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'This business has already been claimed']);
    exit;
}

// Insert claim with approval notes containing claimant details
$claim_status = 'pending';
$approval_notes = json_encode([
    'owner_name' => $owner_name,
    'email' => $email,
    'phone' => $phone,
    'role' => $role,
    'message' => $message
]);

$stmt = $conn->prepare("INSERT INTO listing_claims (user_id, business_id, claim_status, approval_notes, claimed_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param('iiss', $user_id, $business_id, $claim_status, $approval_notes);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Claim submitted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to submit claim']);
}
?>
