<?php
/**
 * API: Track conversion actions (calls, WhatsApp, form submissions)
 */

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$business_id = $data['business_id'] ?? null;
$action_type = $data['action_type'] ?? null;
$phone = $data['phone'] ?? null;

if (!$business_id || !$action_type) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$business_id = (int)$business_id;

// Create analytics_events table if it doesn't exist
if ($GLOBALS['conn']) {
    $create_table = "
        CREATE TABLE IF NOT EXISTS analytics_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            business_id INT NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            phone VARCHAR(20),
            page VARCHAR(255),
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_business_id (business_id),
            INDEX idx_action_type (action_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $GLOBALS['conn']->query($create_table);
    
    // Insert event
    $page = $_SERVER['HTTP_REFERER'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $GLOBALS['conn']->prepare("
        INSERT INTO analytics_events (business_id, action_type, phone, page, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt) {
        $stmt->bind_param('isssss', $business_id, $action_type, $phone, $page, $ip, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

echo json_encode(['success' => true, 'action' => $action_type]);
