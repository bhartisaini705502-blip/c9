<?php
/**
 * API: Get nearby businesses based on user location
 * Calculates distance using Haversine formula
 */

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;
$radius = $_GET['radius'] ?? 5; // km
$category = $_GET['category'] ?? '';
$limit = min($_GET['limit'] ?? 20, 50);

if (!$lat || !$lng) {
    echo json_encode(['success' => false, 'error' => 'Location required']);
    exit;
}

// Haversine formula to calculate distance
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
}

// Get businesses with coordinates
$sql = "SELECT id, name, types as category, rating, user_ratings_total as review_count, formatted_phone_number as phone, formatted_address as address, 
               lat as latitude, lng as longitude, ai_description, ai_tags 
        FROM extracted_businesses 
        WHERE business_status = 'OPERATIONAL' 
        AND lat IS NOT NULL AND lng IS NOT NULL";

if ($category) {
    $sql .= " AND LOWER(types) LIKE ?";
}

$sql .= " LIMIT 1000";

$stmt = $conn->prepare($sql);
if ($category) {
    $categoryParam = '%' . strtolower($category) . '%';
    $stmt->bind_param('s', $categoryParam);
}
$stmt->execute();
$result = $stmt->get_result();

$businesses = [];
while ($row = $result->fetch_assoc()) {
    $distance = calculateDistance(
        $lat, $lng,
        $row['latitude'], $row['longitude']
    );
    
    if ($distance <= $radius) {
        $row['distance'] = round($distance, 2);
        $row['distance_text'] = $distance < 1 ? 
            round($distance * 1000) . 'm away' : 
            round($distance, 1) . ' km away';
        $businesses[] = $row;
    }
}

// Sort by distance
usort($businesses, function($a, $b) {
    return $a['distance'] - $b['distance'];
});

// Limit results
$businesses = array_slice($businesses, 0, $limit);

echo json_encode([
    'success' => true,
    'count' => count($businesses),
    'radius' => $radius,
    'businesses' => $businesses
]);
