<?php
/**
 * Claim Listing Functionality
 */

require_once '../config/db.php';
require_once '../config/auth.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: /pages/search.php');
    exit;
}

$business_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Check if listing is already claimed
$checkQuery = "SELECT id, claim_status FROM listing_claims WHERE business_id = ?";
$checkStmt = $GLOBALS['conn']->prepare($checkQuery);
$checkStmt->bind_param('i', $business_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $existingClaim = $checkResult->fetch_assoc();
    
    if ($existingClaim['claim_status'] === 'approved') {
        // Already claimed by someone else
        $nameSlug = !empty($business_name) ? urlencode(slugify($business_name)) : '';
        header('Location: /pages/business-detail.php?id=' . $business_id . '&name=' . $nameSlug . '&claimed=1');
    } else {
        // Claim exists but not approved yet
        header('Location: /pages/dashboard.php?status=pending');
    }
    exit;
}

// Get business details
$businessQuery = "SELECT id, name, formatted_address FROM extracted_businesses WHERE id = ?";
$businessStmt = $GLOBALS['conn']->prepare($businessQuery);
$businessStmt->bind_param('i', $business_id);
$businessStmt->execute();
$businessResult = $businessStmt->get_result();
$business = $businessResult->fetch_assoc();

if (!$business) {
    header('Location: /pages/search.php');
    exit;
}

// Claim the listing
$insertQuery = "INSERT INTO listing_claims (user_id, business_id, claim_status) VALUES (?, ?, 'pending')";
$insertStmt = $GLOBALS['conn']->prepare($insertQuery);
$insertStmt->bind_param('ii', $user_id, $business_id);

if ($insertStmt->execute()) {
    // Send notification to admin (in a real system)
    header('Location: /pages/dashboard.php?claimed=success&name=' . urlencode($business['name']));
} else {
    $nameSlug = !empty($business_name) ? urlencode(slugify($business_name)) : '';
    header('Location: /pages/business-detail.php?id=' . $business_id . '&name=' . $nameSlug . '&error=claim_failed');
}
exit;
?>
