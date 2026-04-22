<?php
/**
 * Send Agent Reply API
 */

header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../config/auth.php';
require '../../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$agent_id = $_SESSION['user_id'];
$conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : null;
$message_text = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$conversation_id || !$message_text) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Insert agent message
$stmt = $conn->prepare("
    INSERT INTO support_messages (conversation_id, sender_id, sender_type, message) 
    VALUES (?, ?, 'agent', ?)
");
$stmt->bind_param('iis', $conversation_id, $agent_id, $message_text);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save message']);
    exit;
}

// Update conversation updated_at
$stmt = $conn->prepare("UPDATE support_conversations SET updated_at = NOW() WHERE id = ?");
$stmt->bind_param('i', $conversation_id);
$stmt->execute();

echo json_encode(['success' => true]);
