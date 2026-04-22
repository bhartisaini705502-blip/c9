<?php
/**
 * API: Track user events (views, clicks, calls)
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/tracking.php';

header('Content-Type: application/json');

$business_id = $_POST['business_id'] ?? $_GET['business_id'] ?? null;
$event = $_POST['event'] ?? $_GET['event'] ?? null;

if (!$business_id || !$event) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$business_id = (int)$business_id;

switch ($event) {
    case 'view':
        trackBusinessView($business_id);
        break;
    case 'click':
        trackBusinessClick($business_id);
        break;
    case 'call':
        trackPhoneCall($business_id);
        break;
    case 'whatsapp':
        trackWhatsApp($business_id);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown event']);
        exit;
}

echo json_encode(['success' => true, 'event' => $event]);
