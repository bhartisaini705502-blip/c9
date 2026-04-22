<?php
/**
 * Hybrid Import System
 * Smart caching of Google API results to minimize API calls
 * 
 * Flow:
 * 1. Check if already cached
 * 2. Check if already in main DB
 * 3. If neither, call Google API
 * 4. Cache the result
 * 5. Return to user
 * 6. Optionally import to main DB
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/google-api.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? $_POST['q'] ?? '');
$action = $_GET['action'] ?? $_POST['action'] ?? 'get'; // get, import, clear_cache

if (!$query && $action === 'get') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Query required']);
    exit;
}

try {
    switch ($action) {
        case 'get':
            $results = getCachedOrFresh($query);
            echo json_encode([
                'success' => true,
                'query' => $query,
                'count' => count($results),
                'results' => $results,
                'message' => count($results) > 0 ? 'Results found' : 'No results'
            ]);
            break;
            
        case 'import':
            // Import cached Google result to main database
            $placeId = $_POST['place_id'] ?? '';
            $businessId = importCachedResult($placeId);
            
            if ($businessId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Business imported successfully',
                    'business_id' => $businessId
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to import business'
                ]);
            }
            break;
            
        case 'cache_status':
            // Get cache statistics
            $stats = $GLOBALS['conn']->query("
                SELECT 
                    COUNT(*) as total_cached,
                    SUM(CASE WHEN imported_to_main = 1 THEN 1 ELSE 0 END) as imported,
                    SUM(CASE WHEN imported_to_main = 0 THEN 1 ELSE 0 END) as pending
                FROM google_cache
            ")->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'cache_stats' => $stats
            ]);
            break;
            
        case 'clear_cache':
            // Clear old cache entries (older than 30 days)
            $GLOBALS['conn']->query("
                DELETE FROM google_cache 
                WHERE cached_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            echo json_encode([
                'success' => true,
                'message' => 'Cache cleaned'
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Get cached results or fetch fresh from Google API
 */
function getCachedOrFresh($query) {
    global $GLOBALS;
    
    // 1. Check if cached
    $cacheQuery = "SELECT * FROM google_cache WHERE search_query = ? LIMIT 10";
    $stmt = $GLOBALS['conn']->prepare($cacheQuery);
    $stmt->bind_param('s', $query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = formatCacheResult($row);
        }
        return $results;
    }
    
    // 2. Not cached, call Google API
    $googleResults = fetchFromGoogle($query);
    
    // 3. Cache the results
    if (!empty($googleResults)) {
        foreach ($googleResults as $place) {
            cacheGoogleResult($query, $place);
        }
    }
    
    return $googleResults;
}

/**
 * Fetch from Google Places API
 */
function fetchFromGoogle($query) {
    $apiKey = GOOGLE_PLACES_API_KEY;
    
    if (empty($apiKey)) {
        return [];
    }
    
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
    foreach (array_slice($data['results'], 0, 10) as $place) {
        $businesses[] = [
            'place_id' => $place['place_id'] ?? null,
            'name' => $place['name'] ?? '',
            'rating' => $place['rating'] ?? 0,
            'review_count' => $place['user_ratings_total'] ?? 0,
            'address' => $place['formatted_address'] ?? '',
            'phone' => $place['formatted_phone_number'] ?? '',
            'lat' => $place['geometry']['location']['lat'] ?? null,
            'lng' => $place['geometry']['location']['lng'] ?? null,
            'types' => implode(', ', $place['types'] ?? []),
            'source' => 'google'
        ];
    }
    
    return $businesses;
}

/**
 * Cache Google result (minimal data only)
 */
function cacheGoogleResult($query, $place) {
    global $GLOBALS;
    
    // Only store essential data
    $insertQuery = "INSERT IGNORE INTO google_cache 
                    (search_query, place_id, name, rating, review_count, address, phone, lat, lng) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $GLOBALS['conn']->prepare($insertQuery);
    $stmt->bind_param('sssidsddd',
        $query,
        $place['place_id'],
        $place['name'],
        $place['rating'],
        $place['review_count'],
        $place['address'],
        $place['phone'],
        $place['lat'],
        $place['lng']
    );
    
    $stmt->execute();
}

/**
 * Format cache result for display
 */
function formatCacheResult($row) {
    return [
        'place_id' => $row['place_id'],
        'name' => $row['name'],
        'rating' => (float)$row['rating'],
        'review_count' => (int)$row['review_count'],
        'address' => $row['address'],
        'phone' => $row['phone'],
        'lat' => (float)$row['lat'],
        'lng' => (float)$row['lng'],
        'source' => 'google_cache',
        'cached' => true
    ];
}

/**
 * Import cached result to main database
 */
function importCachedResult($placeId) {
    global $GLOBALS;
    
    // Get from cache
    $cacheQuery = "SELECT * FROM google_cache WHERE place_id = ?";
    $stmt = $GLOBALS['conn']->prepare($cacheQuery);
    $stmt->bind_param('s', $placeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $cached = $result->fetch_assoc();
    
    // Check if already exists
    $checkQuery = "SELECT id FROM extracted_businesses WHERE place_id = ?";
    $checkStmt = $GLOBALS['conn']->prepare($checkQuery);
    $checkStmt->bind_param('s', $placeId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existingRow = $checkResult->fetch_assoc();
        return $existingRow['id'];
    }
    
    // Insert into main database (minimal data)
    $insertQuery = "INSERT INTO extracted_businesses 
                    (name, types, rating, user_ratings_total, formatted_address, formatted_phone_number, 
                     lat, lng, place_id, business_status, from_google_cache, source, imported_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'OPERATIONAL', 1, 'google', NOW())";
    
    $insertStmt = $GLOBALS['conn']->prepare($insertQuery);
    $insertStmt->bind_param('sssidsdds',
        $cached['name'],
        $cached['name'], // Use name as type for now
        $cached['rating'],
        $cached['review_count'],
        $cached['address'],
        $cached['phone'],
        $cached['lat'],
        $cached['lng'],
        $cached['place_id']
    );
    
    if ($insertStmt->execute()) {
        $businessId = $GLOBALS['conn']->insert_id;
        
        // Mark as imported in cache
        $updateQuery = "UPDATE google_cache SET imported_to_main = 1, business_id = ? WHERE place_id = ?";
        $updateStmt = $GLOBALS['conn']->prepare($updateQuery);
        $updateStmt->bind_param('is', $businessId, $placeId);
        $updateStmt->execute();
        
        return $businessId;
    }
    
    return false;
}
?>
