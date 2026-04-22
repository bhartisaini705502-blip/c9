<?php
/**
 * Map View - Display all businesses on interactive map
 * Phase 2: Google Maps Integration
 */

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/db.php';

$category = $_GET['category'] ?? '';
$city = $_GET['city'] ?? '';
$radius = $_GET['radius'] ?? 5; // km

// Get user location from query params
$userLat = $_GET['lat'] ?? null;
$userLng = $_GET['lng'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Map - ConnectWith9</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode(GOOGLE_PLACES_API_KEY); ?>&libraries=places"></script>
    <style>
        #map {
            width: 100%;
            height: calc(100vh - 100px);
            border-radius: 8px;
        }
        .map-container {
            position: relative;
            height: 100vh;
        }
        .controls {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10;
            width: 300px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .control-group {
            margin-bottom: 20px;
        }
        .control-label {
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            color: #0B1C3D;
        }
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .radius-slider {
            width: 100%;
        }
        .info-window-content {
            padding: 15px;
            min-width: 250px;
        }
        .business-name {
            font-weight: 600;
            color: #0B1C3D;
            margin-bottom: 5px;
        }
        .business-rating {
            color: #FF6A00;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .business-category {
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 10px;
        }
        .business-distance {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .btn-small {
            background: #FF6A00;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 5px;
        }
        .btn-small:hover {
            background: #E55A00;
        }
        .user-location {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 10;
        }
        .locate-btn {
            background: white;
            border: 1px solid #ddd;
            padding: 12px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .locate-btn:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="map-container">
        <div id="map"></div>
        
        <div class="controls">
            <h3 style="margin-bottom: 20px; color: #0B1C3D;">🗺️ Map Filters</h3>
            
            <div class="control-group">
                <label class="control-label">Category</label>
                <select id="categoryFilter" onchange="updateMap()">
                    <option value="">All Categories</option>
                    <option value="restaurant">Restaurant</option>
                    <option value="cafe">Cafe</option>
                    <option value="hotel">Hotel</option>
                    <option value="shop">Shop</option>
                    <option value="hospital">Hospital</option>
                    <option value="bank">Bank</option>
                </select>
            </div>
            
            <div class="control-group">
                <label class="control-label">City</label>
                <input type="text" id="cityFilter" placeholder="Enter city" onchange="updateMap()">
            </div>
            
            <div class="control-group">
                <label class="control-label">Radius: <span id="radiusValue">5</span> km</label>
                <input type="range" id="radiusFilter" min="1" max="50" value="5" class="radius-slider" onchange="updateRadius()">
            </div>
            
            <div id="resultsList" style="margin-top: 20px;"></div>
        </div>
        
        <div class="user-location">
            <button class="locate-btn" onclick="getUserLocation()">📍 Find Me</button>
        </div>
    </div>
    
    <script>
        let map;
        let markers = [];
        let userMarker = null;
        let infoWindows = [];
        
        function initMap() {
            const defaultCenter = { lat: 20.5937, lng: 78.9629 }; // India center
            
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: defaultCenter,
                styles: [
                    { featureType: 'water', stylers: [{ color: '#b3d9ff' }] },
                    { featureType: 'land', stylers: [{ color: '#f3f3f3' }] }
                ]
            });
            
            updateMap();
        }
        
        async function updateMap() {
            clearMarkers();
            
            const category = document.getElementById('categoryFilter').value;
            const city = document.getElementById('cityFilter').value;
            const radius = document.getElementById('radiusFilter').value;
            
            try {
                const params = new URLSearchParams({
                    action: 'get-businesses',
                    category,
                    city,
                    limit: 50
                });
                
                const response = await fetch(`/api/get-map-businesses.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    data.businesses.forEach(business => {
                        if (business.latitude && business.longitude) {
                            addMarker(business);
                        }
                    });
                    
                    updateResultsList(data.businesses);
                }
            } catch (error) {
                console.error('Map update error:', error);
            }
        }
        
        function addMarker(business) {
            const position = {
                lat: parseFloat(business.latitude),
                lng: parseFloat(business.longitude)
            };
            
            // Color based on rating
            let color = '#808080'; // gray
            if (business.rating >= 4.5) color = '#FFD700'; // gold
            else if (business.rating >= 4.0) color = '#FF6A00'; // orange
            
            const marker = new google.maps.Marker({
                position,
                map,
                title: business.name,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: color,
                    fillOpacity: 0.9,
                    strokeColor: '#fff',
                    strokeWeight: 2
                }
            });
            
            const infoWindow = new google.maps.InfoWindow({
                content: createInfoWindowContent(business)
            });
            
            marker.addListener('click', () => {
                infoWindows.forEach(iw => iw.close());
                infoWindow.open(map, marker);
            });
            
            markers.push(marker);
            infoWindows.push(infoWindow);
        }
        
        function createInfoWindowContent(business) {
            return `
                <div class="info-window-content">
                    <div class="business-name">${business.name}</div>
                    <div class="business-rating">⭐ ${business.rating} (${business.review_count || 0} reviews)</div>
                    <div class="business-category">${business.category}</div>
                    <div class="business-distance">${business.distance || 'N/A'}</div>
                    <p style="font-size: 13px; color: #666; margin-bottom: 10px;">${business.ai_description || business.address}</p>
                    <a href="/pages/business-detail.php?id=${business.id}" class="btn-small">View Details</a>
                    <a href="tel:${business.phone}" class="btn-small">Call</a>
                </div>
            `;
        }
        
        function clearMarkers() {
            markers.forEach(marker => marker.setMap(null));
            infoWindows.forEach(iw => iw.close());
            markers = [];
            infoWindows = [];
        }
        
        function updateRadius() {
            const radius = document.getElementById('radiusFilter').value;
            document.getElementById('radiusValue').textContent = radius;
            updateMap();
        }
        
        function updateResultsList(businesses) {
            const list = document.getElementById('resultsList');
            list.innerHTML = `<strong>${businesses.length} Results</strong><hr>`;
            
            businesses.slice(0, 10).forEach(b => {
                list.innerHTML += `
                    <div style="padding: 10px 0; border-bottom: 1px solid #eee;">
                        <strong>${b.name}</strong><br>
                        <small>⭐ ${b.rating} · ${b.category}</small><br>
                        <a href="/pages/business-detail.php?id=${b.id}" style="color: #FF6A00; font-size: 12px;">View →</a>
                    </div>
                `;
            });
        }
        
        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const { latitude, longitude } = position.coords;
                    
                    if (userMarker) userMarker.setMap(null);
                    
                    userMarker = new google.maps.Marker({
                        position: { lat: latitude, lng: longitude },
                        map,
                        title: 'Your Location',
                        icon: '/images/user-marker.png'
                    });
                    
                    map.setCenter({ lat: latitude, lng: longitude });
                });
            }
        }
        
        // Initialize on load
        window.addEventListener('load', initMap);
    </script>
</body>
</html>
