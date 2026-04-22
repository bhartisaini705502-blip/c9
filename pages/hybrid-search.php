<?php
/**
 * Hybrid Search Page
 * Combines local DB + Google API caching
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/google-api.php';

// Check if this is an API call
if (isset($_GET['api']) && $_GET['api'] === '1') {
    header('Content-Type: application/json');
    
    $query = trim($_GET['q'] ?? '');
    if (!$query) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Query required']);
        exit;
    }
    
    // 1. Try local DB first
    $localResults = $GLOBALS['conn']->query("
        SELECT 
            id, name, formatted_address, formatted_phone_number,
            lat, lng, rating, user_ratings_total, types,
            'local' as source, place_id
        FROM extracted_businesses
        WHERE business_status = 'OPERATIONAL' 
        AND (name LIKE ? OR types LIKE ?)
        LIMIT 10
    ");
    
    // Prepare with proper binding
    $localStmt = $GLOBALS['conn']->prepare("
        SELECT 
            id, name, formatted_address, formatted_phone_number,
            lat, lng, rating, user_ratings_total, types,
            'local' as source, place_id
        FROM extracted_businesses
        WHERE business_status = 'OPERATIONAL' 
        AND (name LIKE ? OR types LIKE ?)
        ORDER BY rating DESC
        LIMIT 10
    ");
    
    $searchTerm = "%$query%";
    $localStmt->bind_param('ss', $searchTerm, $searchTerm);
    $localStmt->execute();
    $localResult = $localStmt->get_result();
    
    $results = [];
    while ($row = $localResult->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'address' => $row['formatted_address'],
            'phone' => $row['formatted_phone_number'],
            'lat' => (float)$row['lat'],
            'lng' => (float)$row['lng'],
            'rating' => (float)$row['rating'],
            'reviews' => (int)$row['user_ratings_total'],
            'types' => $row['types'],
            'source' => 'local'
        ];
    }
    
    // If local results are empty, fetch from Google
    if (empty($results) && !empty(GOOGLE_PLACES_API_KEY)) {
        $googleUrl = 'https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query([
            'query' => $query,
            'key' => GOOGLE_PLACES_API_KEY,
            'type' => 'establishment'
        ]);
        
        $response = @file_get_contents($googleUrl);
        if ($response) {
            $googleData = json_decode($response, true);
            
            if (isset($googleData['results']) && !empty($googleData['results'])) {
                // Cache and format results
                foreach (array_slice($googleData['results'], 0, 10) as $place) {
                    // Cache in DB
                    $cacheStmt = $GLOBALS['conn']->prepare("
                        INSERT IGNORE INTO google_cache 
                        (search_query, place_id, name, rating, review_count, address, phone, lat, lng) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $placeId = $place['place_id'];
                    $name = $place['name'];
                    $rating = $place['rating'] ?? 0;
                    $reviewCount = $place['user_ratings_total'] ?? 0;
                    $address = $place['formatted_address'];
                    $phone = $place['formatted_phone_number'] ?? '';
                    $lat = $place['geometry']['location']['lat'];
                    $lng = $place['geometry']['location']['lng'];
                    
                    $cacheStmt->bind_param('sssidsddd',
                        $query, $placeId, $name, $rating, $reviewCount, $address, $phone, $lat, $lng
                    );
                    $cacheStmt->execute();
                    
                    // Add to results
                    $results[] = [
                        'place_id' => $placeId,
                        'name' => $name,
                        'address' => $address,
                        'phone' => $phone,
                        'lat' => (float)$lat,
                        'lng' => (float)$lng,
                        'rating' => (float)$rating,
                        'reviews' => (int)$reviewCount,
                        'types' => implode(', ', $place['types'] ?? []),
                        'source' => 'google'
                    ];
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'count' => count($results),
        'results' => $results,
        'source' => empty($results) ? 'none' : (strpos($results[0]['source'] ?? '', 'local') === 0 ? 'local' : 'google')
    ]);
    exit;
}

// Page view
$pageTitle = "Smart Hybrid Search - ConnectWith9";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode(GOOGLE_PLACES_API_KEY); ?>"></script>
    <style>
        .hybrid-search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .search-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .search-header h1 {
            color: #0B1C3D;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .search-header p {
            color: #666;
            font-size: 16px;
        }
        .search-box-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 40px;
        }
        .search-input-wrapper {
            display: flex;
            gap: 10px;
        }
        .search-input-wrapper input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .search-input-wrapper input:focus {
            outline: none;
            border-color: #FF6A00;
        }
        .search-input-wrapper button {
            padding: 15px 30px;
            background: #FF6A00;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .search-input-wrapper button:hover {
            background: #E55A00;
        }
        .results-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .results-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 25px;
        }
        .results-title {
            color: #0B1C3D;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .source-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .source-badge.local {
            background: #c8e6c9;
            color: #2e7d32;
        }
        .source-badge.google {
            background: #bbdefb;
            color: #1565c0;
        }
        .business-item {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .business-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            border-color: #FF6A00;
        }
        .business-name {
            color: #0B1C3D;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .business-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 6px;
        }
        .business-rating {
            color: #FF6A00;
            font-weight: 600;
            font-size: 13px;
        }
        .map-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            height: 500px;
        }
        #hybridMap {
            width: 100%;
            height: 100%;
        }
        .no-results {
            color: #999;
            text-align: center;
            padding: 40px 20px;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #999;
        }
        @media (max-width: 768px) {
            .results-container {
                grid-template-columns: 1fr;
            }
            .map-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <?php require_once dirname(__DIR__) . '/includes/header.php'; ?>
    
    <div class="hybrid-search-container">
        <div class="search-header">
            <h1>🔍 Hybrid Smart Search</h1>
            <p>Local database + Google Places API with intelligent caching</p>
        </div>
        
        <div class="search-box-container">
            <div class="search-input-wrapper">
                <input type="text" id="searchInput" placeholder="Search businesses, categories, locations..." />
                <button id="searchBtn">Search</button>
            </div>
        </div>
        
        <div class="results-container">
            <div class="results-list">
                <div class="results-title">Results</div>
                <div id="resultsList"></div>
            </div>
            
            <div class="map-container">
                <div id="hybridMap"></div>
            </div>
        </div>
    </div>
    
    <script>
        let map;
        let markers = [];
        let currentResults = [];
        
        // Initialize map
        function initMap() {
            map = new google.maps.Map(document.getElementById('hybridMap'), {
                zoom: 12,
                center: { lat: 20.5937, lng: 78.9629 } // Center of India
            });
        }
        
        // Search function
        async function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            if (!query) return;
            
            const resultsList = document.getElementById('resultsList');
            resultsList.innerHTML = '<div class="loading">Searching...</div>';
            
            try {
                const response = await fetch(`/pages/hybrid-search.php?api=1&q=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                currentResults = data.results || [];
                displayResults(data);
                updateMap(currentResults);
            } catch (error) {
                resultsList.innerHTML = '<div class="no-results">Error searching. Please try again.</div>';
            }
        }
        
        // Display results
        function displayResults(data) {
            const resultsList = document.getElementById('resultsList');
            
            if (currentResults.length === 0) {
                resultsList.innerHTML = '<div class="no-results">No results found</div>';
                return;
            }
            
            let html = `<div class="source-badge ${data.source}">${data.source === 'local' ? '✓ Local Database' : '🌐 Google Places'}</div>`;
            
            currentResults.forEach((result, index) => {
                const rating = parseFloat(result.rating || 0).toFixed(1);
                const address = result.address || result.formatted_address || 'No address';
                
                html += `
                    <div class="business-item" onclick="selectResult(${index})">
                        <div class="business-name">${escapeHtml(result.name)}</div>
                        <div class="business-info">${escapeHtml(address.substring(0, 50))}...</div>
                        ${result.phone ? `<div class="business-info">📞 ${result.phone}</div>` : ''}
                        <div class="business-rating">⭐ ${rating} (${result.reviews || 0} reviews)</div>
                    </div>
                `;
            });
            
            resultsList.innerHTML = html;
        }
        
        // Update map markers
        function updateMap(results) {
            // Clear old markers
            markers.forEach(m => m.setMap(null));
            markers = [];
            
            if (results.length === 0) return;
            
            let bounds = new google.maps.LatLngBounds();
            
            results.forEach((result, index) => {
                const marker = new google.maps.Marker({
                    position: { lat: result.lat, lng: result.lng },
                    map: map,
                    title: result.name,
                    label: String(index + 1)
                });
                
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 10px;">
                            <strong>${escapeHtml(result.name)}</strong><br>
                            ⭐ ${(result.rating || 0).toFixed(1)}<br>
                            ${result.phone ? `📞 ${result.phone}<br>` : ''}
                            <small>${escapeHtml((result.address || '').substring(0, 50))}</small>
                        </div>
                    `
                });
                
                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
                
                markers.push(marker);
                bounds.extend(marker.getPosition());
            });
            
            map.fitBounds(bounds);
        }
        
        // Select result
        function selectResult(index) {
            const result = currentResults[index];
            map.panTo({ lat: result.lat, lng: result.lng });
            map.setZoom(15);
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Event listeners
        document.getElementById('searchBtn').addEventListener('click', performSearch);
        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') performSearch();
        });
        
        // Initialize on load
        window.addEventListener('load', initMap);
    </script>
</body>
</html>
