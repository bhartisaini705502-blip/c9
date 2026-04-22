<?php
/**
 * Get Call History for Business
 */

header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../config/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$business_id = isset($_GET['business_id']) ? (int)$_GET['business_id'] : null;

if (!$business_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Fetch all call records for this business, ordered by latest first
$stmt = $conn->prepare("
    SELECT cl.id, cl.call_status, cl.notes, cl.created_at
    FROM call_logs cl
    WHERE cl.business_id = ?
    ORDER BY cl.created_at DESC
    LIMIT 20
");
$stmt->bind_param('i', $business_id);
$stmt->execute();
$result = $stmt->get_result();
$calls = $result->fetch_all(MYSQLI_ASSOC);

http_response_code(200);
echo json_encode([
    'success' => true,
    'calls' => $calls
]);
