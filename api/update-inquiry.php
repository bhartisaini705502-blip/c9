<?php
/**
 * Update Inquiry Status
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['inquiry_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing inquiry_id']);
    exit;
}

$inquiryId = intval($data['inquiry_id']);
$status = trim($data['status'] ?? 'new');

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user = getUserData();

$verify = $GLOBALS['conn']->prepare("\n    SELECT i.id, i.status FROM inquiries i\n    JOIN listing_claims lc ON lc.business_id = i.business_id\n    WHERE i.id = ? AND lc.user_id = ? AND lc.claim_status = 'approved'\n");
$verify->bind_param('ii', $inquiryId, $user['id']);
$verify->execute();
$current = $verify->get_result()->fetch_assoc();
$verify->close();

if (!$current) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (($current['status'] ?? '') === $status) {
    echo json_encode(['success' => true, 'duplicate' => true]);
    exit;
}

$timestamp = $status === 'contacted' ? date('Y-m-d H:i:s') : null;
$updateField = $status === 'contacted' ? 'contacted_at' : 'status';
$updateValue = $status === 'contacted' ? $timestamp : $status;

$stmt = $GLOBALS['conn']->prepare("UPDATE inquiries SET $updateField = ? WHERE id = ?");
$stmt->bind_param('si', $updateValue, $inquiryId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed']);
}
?>