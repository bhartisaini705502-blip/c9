<?php
/**
 * Track Views, Clicks, and Page Analytics
 */

header('Content-Type: application/json');
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true) ?? $_GET;

if (empty($data['business_id']) || empty($data['event'])) {
    echo json_encode(['success' => false]);
    exit;
}

$businessId = intval($data['business_id']);
$event = trim($data['event']); // 'view', 'click', 'phone_click', 'direction_click', 'website_click'

// Verify business exists
$check = $GLOBALS['conn']->query("SELECT id FROM extracted_businesses WHERE id = $businessId AND business_status = 'OPERATIONAL'");
if ($check->num_rows === 0) {
    echo json_encode(['success' => false]);
    exit;
}

// Initialize analytics if not exists
$exists = $GLOBALS['conn']->query("SELECT id FROM business_analytics WHERE business_id = $businessId");
if ($exists->num_rows === 0) {
    $GLOBALS['conn']->query("INSERT INTO business_analytics (business_id) VALUES ($businessId)");
}

// Update analytics
$eventCol = match($event) {
    'view' => 'views',
    'click' => 'clicks',
    'phone_click' => 'phone_clicks',
    'direction_click' => 'direction_clicks',
    'website_click' => 'website_clicks',
    default => null
};

if ($eventCol) {
    $GLOBALS['conn']->query("
        UPDATE business_analytics 
        SET $eventCol = $eventCol + 1 
        WHERE business_id = $businessId
    ");
    
    // Also update daily analytics
    $today = date('Y-m-d');
    if ($event === 'view') {
        $GLOBALS['conn']->query("
            INSERT INTO daily_analytics (business_id, date, views) 
            VALUES ($businessId, '$today', 1) 
            ON DUPLICATE KEY UPDATE views = views + 1
        ");
    } elseif ($event === 'click') {
        $GLOBALS['conn']->query("
            INSERT INTO daily_analytics (business_id, date, clicks) 
            VALUES ($businessId, '$today', 1) 
            ON DUPLICATE KEY UPDATE clicks = clicks + 1
        ");
    }
}

echo json_encode(['success' => true]);
?>
