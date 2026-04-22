<?php
/**
 * API - Submit User Review
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login to submit a review']);
    exit;
}

$businessId = isset($_POST['business_id']) ? (int)$_POST['business_id'] : 0;
$rating = isset($_POST['rating']) ? (float)$_POST['rating'] : 0;
$reviewText = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
$userData = getUserData();

if (!$businessId || $businessId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid business ID']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Rating must be between 1 and 5']);
    exit;
}

if (empty($reviewText) || strlen($reviewText) < 10) {
    http_response_code(400);
    echo json_encode(['error' => 'Review text must be at least 10 characters']);
    exit;
}

// Check if business exists
$bizCheck = $GLOBALS['conn']->prepare("SELECT id FROM extracted_businesses WHERE id = ? AND business_status = 'OPERATIONAL'");
$bizCheck->bind_param('i', $businessId);
$bizCheck->execute();
if ($bizCheck->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Business not found']);
    exit;
}

// Check if user already reviewed this business
$existingReview = $GLOBALS['conn']->prepare("SELECT id FROM user_reviews WHERE business_id = ? AND user_id = ?");
$existingReview->bind_param('ii', $businessId, $userData['id']);
$existingReview->execute();
if ($existingReview->get_result()->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['error' => 'You have already reviewed this business']);
    exit;
}

// Insert review
$stmt = $GLOBALS['conn']->prepare("
    INSERT INTO user_reviews (business_id, user_id, rating, review_text, status) 
    VALUES (?, ?, ?, ?, 'pending')
");
$stmt->bind_param('iids', $businessId, $userData['id'], $rating, $reviewText);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully. It will appear after moderation.',
        'reviewId' => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit review']);
}
?>
