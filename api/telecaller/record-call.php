<?php
/**
 * Record Call API
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
$business_id = isset($_POST['business_id']) ? (int)$_POST['business_id'] : null;
$call_status = isset($_POST['status']) ? $_POST['status'] : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$callback_time = isset($_POST['callback_time']) ? $_POST['callback_time'] : null;

if (!$business_id || !$call_status) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Validate status
$valid_statuses = ['not_received', 'busy', 'not_interested', 'irritated', 'interested', 'call_again'];
if (!in_array($call_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

// Insert call log
$stmt = $conn->prepare("
    INSERT INTO call_logs (business_id, agent_id, call_status, notes) 
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param('iiss', $business_id, $agent_id, $call_status, $notes);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save call']);
    exit;
}

$call_log_id = $stmt->insert_id;

// Update or insert business_call_status
$stmt = $conn->prepare("
    INSERT INTO business_call_status (business_id, last_call_status, last_call_by, last_call_date, call_count)
    VALUES (?, ?, ?, NOW(), 1)
    ON DUPLICATE KEY UPDATE 
        last_call_status = ?, 
        last_call_by = ?, 
        last_call_date = NOW(),
        call_count = call_count + 1
");
$stmt->bind_param('isisi', $business_id, $call_status, $agent_id, $call_status, $agent_id);
$stmt->execute();

// If call_again, create callback schedule
if ($call_status === 'call_again' && $callback_time) {
    $stmt = $conn->prepare("
        INSERT INTO callback_schedule (call_log_id, business_id, agent_id, scheduled_time, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param('iiss', $call_log_id, $business_id, $agent_id, $callback_time);
    $stmt->execute();
}

// Update agent stats
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_calls,
           SUM(CASE WHEN call_status = 'interested' THEN 1 ELSE 0 END) as interested
    FROM call_logs
    WHERE agent_id = ? AND DATE(created_at) = ?
");
$stmt->bind_param('is', $agent_id, $today);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$interested = $result['interested'] ?? 0;

$stmt = $conn->prepare("
    UPDATE telecaller_agents 
    SET calls_today = ?, interested_leads = ?
    WHERE user_id = ?
");
$stmt->bind_param('iii', $result['total_calls'], $interested, $agent_id);
$stmt->execute();

echo json_encode(['success' => true, 'call_log_id' => $call_log_id]);
