<?php
/**
 * Set Telecaller Agent Status API
 */

header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../config/auth.php';
require '../../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$agent_id = $_SESSION['user_id'];

$json = json_decode(file_get_contents('php://input'), true);
$status = $json['status'] ?? null;

if (!in_array($status, ['online', 'offline', 'on_call', 'break'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

$stmt = $conn->prepare("UPDATE telecaller_agents SET status = ? WHERE user_id = ?");
$stmt->bind_param('si', $status, $agent_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update status']);
    exit;
}

echo json_encode(['success' => true, 'status' => $status]);
