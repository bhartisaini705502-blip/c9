<?php
/**
 * API: Import Single Google Listing
 * Fetch a business from Google Places API and store in extracted_businesses table
 */

session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/google-api.php';

// Check admin access
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

// Handle recent imports list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'recent') {
    $sql = "SELECT name, imported_at FROM extracted_businesses 
            WHERE source = 'google' 
            ORDER BY imported_at DESC 
            LIMIT 10";
    
    $result = $GLOBALS['conn']->query($sql);
    $imports = [];
    
    while ($row = $result->fetch_assoc()) {
        $imports[] = [
            'name' => $row['name'],
            'timestamp' => date('M d, Y H:i', strtotime($row['imported_at']))
        ];
    }
    
    echo json_encode(['success' => true, 'imports' => $imports]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$method = $data['method'] ?? '';

try {
    if ($method === 'place_id') {
        handlePlaceIdMethod($data);
    } elseif ($method === 'search') {
        handleSearchMethod($data);
    } elseif ($method === 'confirm') {
        handleConfirmMethod($data);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid method']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handlePlaceIdMethod($data) {
    $placeId = $data['place_id'] ?? '';
    
    if (empty($placeId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Place ID required']);
        exit;
    }
    
    $listing = fetchPlaceDetails($placeId);
    
    if (empty($listing)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Business not found on Google Places']);
        exit;
    }
    
    echo json_encode(['success' => true, 'listing' => $listing]);
}

function handleSearchMethod($data) {
    $name = $data['name'] ?? '';
    $location = $data['location'] ?? '';
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Business name required']);
        exit;
    }
    
    $query = $name . ($location ? ', ' . $location : '');
    $listings = searchGooglePlaces($query, 5);
    
    if (empty($listings)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No businesses found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'listings' => $listings]);
}

function handleConfirmMethod($data) {
    $listing = $data['listing'] ?? null;
    
    if (empty($listing) || empty($listing['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid listing data']);
        exit;
    }
    
    $businessId = storeListing($listing);
    
    echo json_encode(['success' => true, 'business_id' => $businessId]);
}

function fetchPlaceDetails($placeId) {
    $apiKey = GOOGLE_PLACES_API_KEY;
    
    if (empty($apiKey)) {
        throw new Exception('Google API key not configured');
    }
    
    $url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
        'place_id' => $placeId,
        'key' => $apiKey,
        'fields' => 'name,formatted_address,rating,user_ratings_total,formatted_phone_number,website,types,geometry,photos'
    ]);
    
    $context = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($url, false, $context);
    
    if (!$response) {
        throw new Exception('Failed to connect to Google Places API');
    }
    
    // Check if response is HTML (error page) instead of JSON
    if (strpos($response, '<html') !== false || strpos($response, '<br') !== false) {
        throw new Exception('Google API returned an error. Please check your API key.');
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid response from Google API: ' . json_last_error_msg());
    }
    
    if (!isset($data['result'])) {
        throw new Exception('Business not found: ' . ($data['status'] ?? 'Unknown error'));
    }
    
    $place = $data['result'];
    
    // Extract photo references (pipe-separated format)
    $photoReferences = [];
    if (isset($place['photos']) && is_array($place['photos'])) {
        foreach ($place['photos'] as $photo) {
            if (isset($photo['photo_reference'])) {
                $photoReferences[] = $photo['photo_reference'];
            }
        }
    }
    
    return [
        'place_id' => $placeId,
        'name' => $place['name'] ?? '',
        'address' => $place['formatted_address'] ?? '',
        'rating' => $place['rating'] ?? 0,
        'reviews' => $place['user_ratings_total'] ?? 0,
        'phone' => $place['formatted_phone_number'] ?? '',
        'website' => $place['website'] ?? '',
        'category' => implode(', ', $place['types'] ?? []),
        'lat' => $place['geometry']['location']['lat'] ?? null,
        'lng' => $place['geometry']['location']['lng'] ?? null,
        'photo_references' => implode('|', $photoReferences)
    ];
}

function searchGooglePlaces($query, $limit = 5) {
    $apiKey = GOOGLE_PLACES_API_KEY;
    
    if (empty($apiKey)) {
        throw new Exception('Google API key not configured');
    }
    
    $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query([
        'query' => $query,
        'key' => $apiKey
    ]);
    
    $context = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($url, false, $context);
    
    if (!$response) {
        throw new Exception('Failed to connect to Google Places API');
    }
    
    // Check if response is HTML (error page) instead of JSON
    if (strpos($response, '<html') !== false || strpos($response, '<br') !== false) {
        throw new Exception('Google API returned an error. Please check your API key.');
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid response from Google API: ' . json_last_error_msg());
    }
    
    if (!isset($data['results']) || empty($data['results'])) {
        throw new Exception('No businesses found matching your search');
    }
    
    $listings = [];
    foreach (array_slice($data['results'], 0, $limit) as $place) {
        $placeId = $place['place_id'] ?? null;
        
        if ($placeId) {
            try {
                // Fetch full details
                $details = fetchPlaceDetails($placeId);
                if ($details) {
                    $listings[] = $details;
                }
            } catch (Exception $e) {
                // Log error but continue with other listings
                error_log('Error fetching details for place ' . $placeId . ': ' . $e->getMessage());
            }
        }
    }
    
    return $listings;
}

function storeListing($listing) {
    global $conn;
    
    $sql = "INSERT INTO extracted_businesses 
            (place_id, name, formatted_address, rating, user_ratings_total, formatted_phone_number, website, types, lat, lng, photo_references, business_status, source, imported_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'OPERATIONAL', 'google', NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $placeId = $listing['place_id'] ?? null;
    $name = $listing['name'] ?? '';
    $address = $listing['address'] ?? '';
    $rating = (float)($listing['rating'] ?? 0);
    $reviews = (int)($listing['reviews'] ?? 0);
    $phone = $listing['phone'] ?? '';
    $website = $listing['website'] ?? '';
    $category = $listing['category'] ?? '';
    $lat = (float)($listing['lat'] ?? 0);
    $lng = (float)($listing['lng'] ?? 0);
    $photoReferences = $listing['photo_references'] ?? '';
    
    $stmt->bind_param('sssdisssdds', $placeId, $name, $address, $rating, $reviews, $phone, $website, $category, $lat, $lng, $photoReferences);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to store listing: " . $stmt->error);
    }
    
    return $conn->insert_id;
}
?>
