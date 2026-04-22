<?php
/**
 * Smart Search API
 * Intelligent keyword mapping and filtering
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/google-api.php';
require_once dirname(__DIR__) . '/includes/tracking.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$tag = $_GET['tag'] ?? '';
$minRating = floatval($_GET['minRating'] ?? 0);
$category = $_GET['category'] ?? '';
$limit = min($_GET['limit'] ?? 20, 100);
$useGoogleFallback = $_GET['fallback'] ?? 'true'; // Enable Google fallback by default

if (!$q) {
    echo json_encode(['success' => false, 'error' => 'Search query required']);
    exit;
}

// Build SQL query
$sql = "SELECT id, name, types as category, rating, user_ratings_total as review_count, formatted_phone_number as phone, formatted_address as address, ai_description, ai_tags 
        FROM extracted_businesses 
        WHERE business_status = 'OPERATIONAL'
        AND (
            LOWER(name) LIKE ? OR 
            LOWER(types) LIKE ? OR 
            LOWER(formatted_address) LIKE ?
        )";

$params = [
    '%' . strtolower($q) . '%',
    '%' . strtolower($q) . '%',
    '%' . strtolower($q) . '%'
];
$types = 'sss';

// Add rating filter
if ($minRating > 0) {
    $sql .= " AND rating >= ?";
    $params[] = $minRating;
    $types .= 'f';
}

// Add category filter
if ($category) {
    $sql .= " AND LOWER(types) LIKE ?";
    $params[] = '%' . strtolower($category) . '%';
    $types .= 's';
}

// Add tag filter
if ($tag) {
    $sql .= " AND ai_tags LIKE ?";
    $params[] = '%' . $tag . '%';
    $types .= 's';
}

// Prioritize featured listings
$sql .= " ORDER BY is_featured DESC, (boost_expiry > NOW()) DESC, rating DESC LIMIT ?";
$params[] = $limit;
$types .= 'i';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$businesses = [];
while ($row = $result->fetch_assoc()) {
    // Add featured status
    $featuredQuery = "SELECT plan_type, expires_at FROM featured_listings 
                     WHERE business_id = ? AND expires_at > NOW()";
    $featStmt = $conn->prepare($featuredQuery);
    $featStmt->bind_param('i', $row['id']);
    $featStmt->execute();
    $featResult = $featStmt->get_result();
    
    if ($featResult->num_rows > 0) {
        $featRow = $featResult->fetch_assoc();
        $row['is_featured'] = true;
        $row['plan_type'] = $featRow['plan_type'];
        $row['boost_expiry'] = $featRow['expires_at'];
    } else {
        $row['is_featured'] = false;
    }
    
    $businesses[] = $row;
}

$source = 'local';

// If no local results and Google fallback enabled, use hybrid import system
if (empty($businesses) && $useGoogleFallback !== 'false') {
    // 1. Check cache first (avoid redundant API calls)
    $cached = getFromCache($q, $limit);
    if (!empty($cached)) {
        $businesses = $cached;
        $source = 'cached';
    } 
    // 2. If not cached and API available, fetch and cache
    elseif (!empty(GOOGLE_PLACES_API_KEY)) {
        $businesses = fetchFromGooglePlaces($q, $limit);
        $source = 'google';
    }
}

// Log the search
logSearch($q, $category, '', count($businesses));

echo json_encode([
    'success' => true,
    'count' => count($businesses),
    'query' => $q,
    'source' => $source,
    'businesses' => $businesses
]);

/**
 * Get businesses from Google cache
 */
function getFromCache($query, $limit = 20) {
    global $conn;
    
    $sql = "SELECT 
                place_id, name, rating, review_count, 
                address, phone, lat, lng
            FROM google_cache 
            WHERE search_query = ? 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $query, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $businesses = [];
    while ($row = $result->fetch_assoc()) {
        $businesses[] = [
            'id' => null,
            'place_id' => $row['place_id'],
            'name' => $row['name'],
            'category' => '',
            'rating' => (float)$row['rating'],
            'review_count' => (int)$row['review_count'],
            'phone' => $row['phone'],
            'address' => $row['address'],
            'ai_description' => 'Cached from Google',
            'ai_tags' => '',
            'source' => 'cached',
            'lat' => (float)$row['lat'],
            'lng' => (float)$row['lng'],
            'detail_url' => '/pages/google-business-detail.php?place_id=' . urlencode($row['place_id'])
        ];
    }
    
    return $businesses;
}

/**
 * Fetch businesses from Google Places API (and cache results)
 */
function fetchFromGooglePlaces($query, $limit = 20) {
    $apiKey = GOOGLE_PLACES_API_KEY;
    
    if (empty($apiKey)) {
        return [];
    }
    
    // Search using nearby text search or find place
    $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query([
        'query' => $query,
        'key' => $apiKey,
        'type' => 'establishment'
    ]);
    
    $response = @file_get_contents($url);
    if (!$response) {
        return [];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['results']) || empty($data['results'])) {
        return [];
    }
    
    $businesses = [];
    foreach (array_slice($data['results'], 0, $limit) as $place) {
        $placeId = $place['place_id'] ?? null;
        $name = $place['name'] ?? '';
        $rating = $place['rating'] ?? 0;
        $reviewCount = $place['user_ratings_total'] ?? 0;
        $phone = $place['formatted_phone_number'] ?? '';
        $address = $place['formatted_address'] ?? '';
        $lat = $place['geometry']['location']['lat'] ?? null;
        $lng = $place['geometry']['location']['lng'] ?? null;
        
        // Cache the result (minimal data only)
        cacheGoogleResult($query, $placeId, $name, $rating, $reviewCount, $address, $phone, $lat, $lng);
        
        $businesses[] = [
            'id' => null, // Google results don't have a local ID
            'place_id' => $placeId,
            'name' => $name,
            'category' => implode(', ', $place['types'] ?? []),
            'rating' => $rating,
            'review_count' => $reviewCount,
            'phone' => $phone,
            'address' => $address,
            'ai_description' => 'Google Places: ' . $name,
            'ai_tags' => implode(', ', $place['types'] ?? []),
            'source' => 'google',
            'lat' => $lat,
            'lng' => $lng,
            'detail_url' => '/pages/google-business-detail.php?place_id=' . urlencode($placeId)
        ];
    }
    
    return $businesses;
}

/**
 * Cache Google result to avoid redundant API calls
 */
function cacheGoogleResult($query, $placeId, $name, $rating, $reviewCount, $address, $phone, $lat, $lng) {
    global $conn;
    
    $sql = "INSERT IGNORE INTO google_cache 
            (search_query, place_id, name, rating, review_count, address, phone, lat, lng) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssidsddd', $query, $placeId, $name, $rating, $reviewCount, $address, $phone, $lat, $lng);
    $stmt->execute();
}
