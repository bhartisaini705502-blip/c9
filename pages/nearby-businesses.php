<?php
/**
 * Nearby Businesses Page
 * Shows businesses near user's location
 */

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/smart-display.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nearby Businesses - ConnectWith9</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/smart-display.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode(GOOGLE_PLACES_API_KEY); ?>"></script>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .location-header {
            background: linear-gradient(135deg, #1E3A8A 0%, #0B1C3D 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        .location-name {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .location-distance {
            font-size: 16px;
            opacity: 0.9;
        }
        .filters {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-group label {
            font-weight: 600;
            color: #0B1C3D;
            white-space: nowrap;
        }
        .filter-group select, .filter-group input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-secondary {
            background: #1E3A8A;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-secondary:hover {
            background: #0B1C3D;
        }
        .businesses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .business-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .business-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(-4px);
        }
        .business-header {
            padding: 20px;
            background: #f9f9f9;
        }
        .business-name {
            font-size: 18px;
            font-weight: 600;
            color: #0B1C3D;
            margin-bottom: 8px;
        }
        .business-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        .stars {
            color: #FF6A00;
            font-size: 16px;
        }
        .review-count {
            color: #666;
            font-size: 13px;
        }
        .business-category {
            display: inline-block;
            background: #e3f2fd;
            color: #1E3A8A;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .business-body {
            padding: 20px;
        }
        .distance {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #FF6A00;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .business-address {
            color: #666;
            font-size: 13px;
            margin-bottom: 12px;
        }
        .ai-summary {
            background: #fff3e0;
            border-left: 4px solid #FF6A00;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .business-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 15px;
        }
        .tag {
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: #555;
        }
        .business-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .btn {
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-align: center;
            text-decoration: none;
        }
        .btn-primary {
            background: #FF6A00;
            color: white;
        }
        .btn-primary:hover {
            background: #E55A00;
        }
        .btn-outline {
            background: white;
            border: 1px solid #ddd;
            color: #0B1C3D;
        }
        .btn-outline:hover {
            border-color: #FF6A00;
            color: #FF6A00;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .no-results-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #FF6A00;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="location-header">
            <div class="location-name">📍 Nearby Businesses</div>
            <div class="location-distance">Based on your current location</div>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label>Category:</label>
                <select id="categoryFilter" onchange="applyFilters()">
                    <option value="">All Categories</option>
                    <option value="restaurant">Restaurant</option>
                    <option value="cafe">Cafe</option>
                    <option value="hotel">Hotel</option>
                    <option value="shop">Shop</option>
                    <option value="hospital">Hospital</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Radius:</label>
                <select id="radiusFilter" onchange="applyFilters()">
                    <option value="1">1 km</option>
                    <option value="5" selected>5 km</option>
                    <option value="10">10 km</option>
                    <option value="25">25 km</option>
                    <option value="50">50 km</option>
                </select>
            </div>
            
            <button class="btn-secondary" onclick="getCurrentLocation()">
                📍 Update Location
            </button>
        </div>
        
        <div id="content">
            <div class="loading">
                <div class="spinner"></div>
                <p>Finding nearby businesses...</p>
            </div>
        </div>
    </div>
    
    <script>
        let userLocation = null;
        
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        applyFilters();
                    },
                    error => {
                        alert('Unable to get your location. Please enable location services.');
                    }
                );
            }
        }
        
        async function applyFilters() {
            if (!userLocation) {
                alert('Please allow location access first');
                return;
            }
            
            const category = document.getElementById('categoryFilter').value;
            const radius = document.getElementById('radiusFilter').value;
            
            const params = new URLSearchParams({
                lat: userLocation.lat,
                lng: userLocation.lng,
                category,
                radius,
                limit: 50
            });
            
            try {
                const response = await fetch(`/api/get-nearby-businesses.php?${params}`);
                const data = await response.json();
                
                displayResults(data);
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('content').innerHTML = '<p>Error loading businesses</p>';
            }
        }
        
        function displayResults(data) {
            const content = document.getElementById('content');
            
            if (!data.success || data.count === 0) {
                content.innerHTML = `
                    <div class="no-results">
                        <div class="no-results-icon">🏪</div>
                        <h3>No businesses found</h3>
                        <p>Try increasing the search radius or changing filters</p>
                    </div>
                `;
                return;
            }
            
            let html = `<div class="businesses-grid">`;
            
            data.businesses.forEach(business => {
                const tagsHtml = business.ai_tags ? 
                    business.ai_tags.split(',').slice(0, 3).map(tag => 
                        `<span class="tag">${tag.trim()}</span>`
                    ).join('') : '';
                
                html += `
                    <div class="business-card">
                        <div class="business-header">
                            <div class="business-name">${business.name}</div>
                            <div class="business-rating">
                                <span class="stars">⭐ ${business.rating}</span>
                                <span class="review-count">(${business.review_count || 0})</span>
                            </div>
                            <span class="business-category">${business.category}</span>
                        </div>
                        <div class="business-body">
                            <div class="distance">
                                📍 ${business.distance_text}
                            </div>
                            <div class="business-address">${business.address}</div>
                            ${business.ai_description ? `
                                <div class="ai-summary">
                                    ${business.ai_description.substring(0, 100)}...
                                </div>
                            ` : ''}
                            ${tagsHtml ? `<div class="business-tags">${tagsHtml}</div>` : ''}
                            <div class="business-actions">
                                <a href="/pages/business-detail.php?id=${business.id}" class="btn btn-primary">
                                    View Details
                                </a>
                                <a href="tel:${business.phone}" class="btn btn-outline">
                                    📞 Call
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `</div>`;
            content.innerHTML = html;
        }
        
        // Auto-load on page load
        window.addEventListener('load', getCurrentLocation);
    </script>
    
    <script src="/assets/js/smart-display.js"></script>
</body>
</html>
