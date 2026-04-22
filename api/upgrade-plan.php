<?php
/**
 * API: Upgrade Plan - Create Subscription
 */

require_once '../config/db.php';

header('Content-Type: application/json');

$business_id = intval($_GET['business_id'] ?? $_POST['business_id'] ?? 0);
$plan_id = intval($_GET['plan_id'] ?? $_POST['plan_id'] ?? 0);

if ($business_id <= 0 || $plan_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid business or plan']);
    exit;
}

// Get plan details
$plan = $conn->query("SELECT * FROM plans WHERE id = $plan_id")->fetch_assoc();
if (!$plan) {
    echo json_encode(['success' => false, 'error' => 'Plan not found']);
    exit;
}

// Get business
$business = $conn->query("SELECT * FROM extracted_businesses WHERE id = $business_id")->fetch_assoc();
if (!$business) {
    echo json_encode(['success' => false, 'error' => 'Business not found']);
    exit;
}

// Cancel existing active subscription
$conn->query("UPDATE subscriptions SET status = 'cancelled' WHERE business_id = $business_id AND status = 'active'");

// Create new subscription
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));
$payment_status = ($plan['price'] == 0) ? 'paid' : 'pending';

$stmt = $conn->prepare("
    INSERT INTO subscriptions (business_id, plan_id, start_date, end_date, status, payment_status)
    VALUES (?, ?, ?, ?, 'active', ?)
");

$stmt->bind_param('iisss', $business_id, $plan_id, $start_date, $end_date, $payment_status);

if ($stmt->execute()) {
    $subscription_id = $conn->insert_id;
    
    // Update business plan
    $conn->query("UPDATE extracted_businesses SET plan_id = $plan_id WHERE id = $business_id");
    
    // If Free plan, mark as paid
    if ($plan['price'] == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Plan upgraded successfully!',
            'subscription_id' => $subscription_id,
            'redirect' => "/pages/business-details.php?id=$business_id"
        ]);
    } else {
        // Redirect to payment (placeholder)
        echo json_encode([
            'success' => true,
            'message' => 'Subscription created. Proceed to payment.',
            'subscription_id' => $subscription_id,
            'requires_payment' => true,
            'amount' => $plan['price'],
            'redirect' => "/pages/payment.php?sub_id=$subscription_id"
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create subscription']);
}

$stmt->close();
@$conn->close();
?>
