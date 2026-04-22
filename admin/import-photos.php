<?php
/**
 * Admin Photo Import Tool
 * Imports Google Place photos for specific businesses
 */

session_start();
require_once '../config/db.php';
require_once '../config/google-api.php';
require_once '../includes/functions.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die('Unauthorized');
}

$business_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$place_id = isset($_GET['place_id']) ? trim($_GET['place_id']) : null;

if (!$business_id && !$place_id) {
    die('Business ID or Place ID required');
}

// Get business details
if ($business_id) {
    $query = "SELECT id, place_id, name FROM extracted_businesses WHERE id = ?";
    $stmt = $GLOBALS['conn']->prepare($query);
    $stmt->bind_param('i', $business_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $business = $result->fetch_assoc();
} else {
    $query = "SELECT id, place_id, name FROM extracted_businesses WHERE place_id = ?";
    $stmt = $GLOBALS['conn']->prepare($query);
    $stmt->bind_param('s', $place_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $business = $result->fetch_assoc();
    $business_id = $business['id'] ?? null;
}

if (!$business || !$business['place_id']) {
    http_response_code(404);
    die('Business not found or missing place_id');
}

// Fetch place details from Google Places API
$apiKey = GOOGLE_PLACES_API_KEY;
if (empty($apiKey)) {
    http_response_code(500);
    die('Google API key not configured');
}

$url = "https://maps.googleapis.com/maps/api/place/details/json";
$params = [
    'place_id' => $business['place_id'],
    'fields' => 'photos',
    'key' => $apiKey
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url . '?' . http_build_query($params),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    http_response_code(500);
    die('Failed to fetch from Google API');
}

$data = json_decode($response, true);
if ($data['status'] !== 'OK' || empty($data['result']['photos'])) {
    http_response_code(200);
    die('No photos found for this business');
}

// Extract photo references
$photoReferences = [];
foreach ($data['result']['photos'] as $photo) {
    if (isset($photo['photo_reference'])) {
        $photoReferences[] = $photo['photo_reference'];
    }
}

if (empty($photoReferences)) {
    http_response_code(200);
    die('No photo references extracted');
}

// Save to database as pipe-separated string
$photoReferencesStr = implode('|', $photoReferences);
$updateQuery = "UPDATE extracted_businesses SET photo_references = ? WHERE id = ?";
$updateStmt = $GLOBALS['conn']->prepare($updateQuery);
$updateStmt->bind_param('si', $photoReferencesStr, $business_id);
$updateStmt->execute();

if ($updateStmt->affected_rows > 0) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Imported ' . count($photoReferences) . ' photos for ' . $business['name'],
        'count' => count($photoReferences)
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update database'
    ]);
}
?>
