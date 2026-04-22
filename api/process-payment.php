<?php
/**
 * Process Premium Upgrade Payment
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/email-service.php';

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user = getUserData();

// Validate required fields
$required = ['plan_id', 'business_id', 'payment_method', 'amount'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing: $field"]);
        exit;
    }
}

$planId = intval($data['plan_id']);
$businessId = intval($data['business_id']);
$paymentMethod = trim($data['payment_method']);
$amount = floatval($data['amount']);

// Verify user owns business
$verify = $GLOBALS['conn']->prepare("SELECT id, contact_email FROM extracted_businesses WHERE id = ? AND claimed_by = ?");
$verify->bind_param('ii', $businessId, $user['id']);
$verify->execute();
$business = $verify->get_result()->fetch_assoc();

if (!$business) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get plan details
$plan = $GLOBALS['conn']->prepare("SELECT * FROM premium_plans WHERE id = ?");
$plan->bind_param('i', $planId);
$plan->execute();
$planData = $plan->get_result()->fetch_assoc();

if (!$planData || $planData['price'] != $amount) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid plan or amount']);
    exit;
}

// Generate transaction ID
$transactionId = 'TXN-' . time() . '-' . rand(1000, 9999);

// In production, integrate with Stripe/Razorpay here
// For now, we'll simulate successful payment
$paymentSuccessful = true; // In real implementation, call payment gateway

if ($paymentSuccessful) {
    // Create payment record
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $planData['duration_days'] . ' days'));
    
    $stmt = $GLOBALS['conn']->prepare("
        INSERT INTO payments (business_id, plan_id, amount, payment_method, transaction_id, status, expires_at)
        VALUES (?, ?, ?, ?, ?, 'completed', ?)
    ");
    $stmt->bind_param('iidsss', $businessId, $planId, $amount, $paymentMethod, $transactionId, $expiresAt);
    $stmt->execute();

    // Update business with premium plan
    $stmt = $GLOBALS['conn']->prepare("
        UPDATE extracted_businesses 
        SET premium_plan_id = ?, premium_expires_at = ? 
        WHERE id = ?
    ");
    $stmt->bind_param('isi', $planId, $expiresAt, $businessId);
    $stmt->execute();

    // Send confirmation email
    EmailService::sendUpgradeConfirmation($business, $planData, $expiresAt);

    echo json_encode([
        'success' => true,
        'transaction_id' => $transactionId,
        'expires_at' => $expiresAt,
        'message' => 'Payment successful! Your premium upgrade is now active.'
    ]);
} else {
    // Payment failed
    $stmt = $GLOBALS['conn']->prepare("
        INSERT INTO payments (business_id, plan_id, amount, payment_method, transaction_id, status)
        VALUES (?, ?, ?, ?, ?, 'failed')
    ");
    $stmt->bind_param('iidss', $businessId, $planId, $amount, $paymentMethod, $transactionId);
    $stmt->execute();

    http_response_code(400);
    echo json_encode(['error' => 'Payment processing failed', 'amount' => $amount]);
}
?>
