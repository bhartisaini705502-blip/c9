<?php
/**
 * Smart Search Page
 * Intelligent keyword mapping and intent detection
 */

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/smart-display.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/google-api.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Search - ConnectWith9</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/smart-display.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode(GOOGLE_PLACES_API_KEY); ?>"></script>
    <style>
        .search-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .search-hero {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            padding: 60px 30px;
            border-radius: 12px;
            margin-bottom: 40px;
            text-align: center;
        }
        .search-hero h1 {
            font-size: 36px;
            margin-bottom: 15px;
        }
        .search-hero p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        .search-input-group {
            display: flex;
            gap: 10px;
            max-width: 600px;
            margin: 0 auto;
        }
        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
        }
        .search-btn {
            background: #FF6A00;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .search-btn:hover {
            background: #E55A00;
        }
        .suggestions {
            margin-top: 30px;
        }
        .suggestions h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .suggestion-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .suggestion-tag {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .suggestion-tag:hover {
            background: #FF6A00;
            border-color: #FF6A00;
        }
        .results {
            max-width: 1000px;
            margin: 0 auto;
        }
        .results-header {
            margin-bottom: 30px;
        }
        .results-header h2 {
            color: #0B1C3D;
            margin-bottom: 10px;
        }
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .result-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }
        .result-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(-4px);
        }
        .result-name {
            font-size: 18px;
            font-weight: 600;
            color: #0B1C3D;
            margin-bottom: 10px;
        }
        .result-rating {
            color: #FF6A00;
            margin-bottom: 10px;
        }
        .result-category {
            display: inline-block;
            background: #e3f2fd;
            color: #1E3A8A;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .result-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .result-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .result-btn {
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
        }
        .result-btn-primary {
            background: #FF6A00;
            color: white;
        }
        .result-btn-primary:hover {
            background: #E55A00;
        }
        .result-btn-secondary {
            background: #f0f0f0;
            color: #0B1C3D;
            border: 1px solid #ddd;
        }
        .result-btn-secondary:hover {
            border-color: #FF6A00;
            color: #FF6A00;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
        }
        .no-results-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        /* Override smart-header for results view */
        #resultsWrapper .smart-header {
            max-width: 1400px;
            margin: 0 auto 40px auto;
            padding: 40px 20px;
        }
        
        #resultsWrapper .smart-title {
            font-size: 32px;
        }
        
        #resultsWrapper .smart-subtitle {
            font-size: 15px;
        }
        
        /* Ensure search-results-container has proper padding */
        .search-results-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }
        
        /* Add wrapper for best picks and results */
        #resultsWrapper > div:not(.search-results-container) {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .best-picks-section {
            margin-bottom: 50px;
        }
        .best-picks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .best-pick-card {
            background: white;
            border: 2px solid #FF6A00;
            border-radius: 12px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(255, 106, 0, 0.15);
        }
        .best-pick-card:hover {
            box-shadow: 0 8px 24px rgba(255, 106, 0, 0.25);
            transform: translateY(-6px);
        }
        .best-pick-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #FF6A00;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }
        .best-pick-name {
            font-size: 20px;
            font-weight: 700;
            color: #0B1C3D;
            margin-bottom: 8px;
            margin-top: 20px;
            padding-right: 100px;
        }
        .best-pick-rating {
            color: #FF6A00;
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 600;
        }
        .best-pick-category {
            display: inline-block;
            background: #e3f2fd;
            color: #1E3A8A;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 12px;
        }
        .best-pick-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .best-pick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .all-results-header {
            font-size: 20px;
            color: #0B1C3D;
            margin-bottom: 20px;
            font-weight: 700;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .results-wrapper {
            display: grid;
            grid-template-columns: 60% 40%;
            gap: 20px;
            min-height: 600px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .results-list {
            overflow-y: auto;
            max-height: 800px;
            padding-right: 10px;
        }
        .results-list::-webkit-scrollbar {
            width: 8px;
        }
        .results-list::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 4px;
        }
        .results-list::-webkit-scrollbar-thumb {
            background: #FF6A00;
            border-radius: 4px;
        }
        .map-container {
            background: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            height: 600px;
        }
        #map {
            width: 100%;
            height: 100%;
        }
        .result-card.active {
            background: #FFF8F0;
            border: 2px solid #FF6A00;
        }
        .result-distance {
            font-size: 13px;
            color: #FF6A00;
            font-weight: 600;
            margin-top: 5px;
        }
        .recent-searches {
            margin-top: 30px;
        }
        .recent-searches h3 {
            color: white;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .recent-search-items {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .recent-search-item {
            background: rgba(255,255,255,0.15);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .recent-search-item:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.4);
        }
        .save-btn {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            transition: all 0.2s;
        }
        .save-btn.saved {
            color: #FF6A00;
        }
        .save-btn:hover {
            transform: scale(1.2);
        }
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .result-card.featured {
            background: linear-gradient(135deg, rgba(255, 106, 0, 0.05) 0%, rgba(255, 255, 255, 1) 100%);
            border: 2px solid #FF6A00;
            box-shadow: 0 4px 16px rgba(255, 106, 0, 0.2);
        }
        .featured-badge {
            background: linear-gradient(135deg, #FF6A00, #FFB84D);
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 8px;
        }
        .boost-badge {
            background: linear-gradient(135deg, #00D4FF, #0099CC);
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 8px;
        }
        @media (max-width: 1024px) {
            .results-wrapper {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .map-container {
                position: static;
                height: 400px;
                margin-bottom: 30px;
            }
        }
        @media (max-width: 768px) {
            .best-picks-grid {
                grid-template-columns: 1fr;
            }
            .smart-header h2 {
                font-size: 22px;
            }
            .results-wrapper {
                padding: 0 10px;
            }
            .results-list {
                max-height: none;
            }
            .map-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="search-container">
        <div class="search-hero">
            <h1>🔍 Smart Search</h1>
            <p>Find exactly what you're looking for with AI-powered search</p>
            
            <div class="search-input-group">
                <input type="text" id="searchInput" class="search-input" placeholder="e.g., 'cheap restaurants', 'best hotels', 'near me'">
                <button class="search-btn" onclick="performSearch()">Search</button>
            </div>
            
            <div class="suggestions">
                <h3>Popular Searches</h3>
                <div class="suggestion-tags">
                    <div class="suggestion-tag" onclick="setSearch('cheap restaurants')">💰 Cheap Restaurants</div>
                    <div class="suggestion-tag" onclick="setSearch('best hospitals')">🏥 Best Hospitals</div>
                    <div class="suggestion-tag" onclick="setSearch('luxury hotels')">🏨 Luxury Hotels</div>
                    <div class="suggestion-tag" onclick="setSearch('family-friendly cafes')">☕ Family Cafes</div>
                </div>
            </div>
            
            <div class="recent-searches" id="recentSearchesContainer" style="display: none;">
                <h3>📋 Recent Searches</h3>
                <div class="recent-search-items" id="recentSearchesList"></div>
            </div>
        </div>
        
        <div id="resultsContainer" style="display: none;"></div>
        <div class="search-results-container" id="resultsWrapper" style="display: none;">
            <div class="results-column" id="resultsList"></div>
            <div class="map-column">
                <div class="map-wrapper">
                    <div id="businessMap"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Keyword mapping for smart intent detection
        const keywordMap = {
            'cheap': { tag: 'affordable', minRating: 0 },
            'affordable': { tag: 'affordable', minRating: 0 },
            'budget': { tag: 'affordable', minRating: 0 },
            'best': { tag: 'highly-rated', minRating: 4.0 },
            'top': { tag: 'highly-rated', minRating: 4.0 },
            'premium': { tag: 'premium', minRating: 3.5 },
            'luxury': { tag: 'premium', minRating: 4.0 },
            'family': { tag: 'family-friendly', minRating: 0 },
            'kids': { tag: 'family-friendly', minRating: 0 },
        };
        
        function setSearch(query) {
            document.getElementById('searchInput').value = query;
            performSearch();
        }
        
        async function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            if (!query) return;
            
            // Detect intent from keywords
            const intent = detectIntent(query);
            
            try {
                const params = new URLSearchParams({
                    q: query,
                    tag: intent.tag,
                    minRating: intent.minRating,
                    category: intent.category,
                    limit: 20
                });
                
                const response = await fetch(`/api/smart-search.php?${params}`);
                const data = await response.json();
                
                // Store search results in database
                if (data.success && data.businesses && data.businesses.length > 0) {
                    storeSearchResults(query, intent.category, data.businesses);
                }
                
                displayResults(data, query);
            } catch (error) {
                console.error('Search error:', error);
            }
        }
        
        async function storeSearchResults(query, category, businesses) {
            try {
                const response = await fetch('/api/store-search-results.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        query: query,
                        category: category,
                        results: businesses.slice(0, 20).map(b => ({
                            id: b.id,
                            name: b.name,
                            category: b.category,
                            rating: b.rating
                        }))
                    })
                });
                
                const result = await response.json();
                if (!result.success) {
                    console.warn('Failed to store search results:', result.error);
                }
            } catch (error) {
                console.warn('Error storing search results:', error);
                // Don't interrupt search display if storage fails
            }
        }
        
        function detectIntent(query) {
            const lowerQuery = query.toLowerCase();
            let tag = '';
            let minRating = 0;
            let category = '';
            
            // Check keywords
            for (const [keyword, config] of Object.entries(keywordMap)) {
                if (lowerQuery.includes(keyword)) {
                    tag = config.tag;
                    minRating = config.minRating;
                    break;
                }
            }
            
            // Detect category
            const categoryKeywords = {
                'restaurant': ['restaurant', 'food', 'eating', 'dine'],
                'cafe': ['cafe', 'coffee', 'tea'],
                'hotel': ['hotel', 'stay', 'accommodation'],
                'hospital': ['hospital', 'doctor', 'medical'],
                'shop': ['shop', 'store', 'buy'],
            };
            
            for (const [cat, keywords] of Object.entries(categoryKeywords)) {
                if (keywords.some(kw => lowerQuery.includes(kw))) {
                    category = cat;
                    break;
                }
            }
            
            return { tag, minRating, category };
        }
        
        let map = null;
        let markers = [];
        let userLocation = null;
        
        function displayResults(data, query) {
            if (!data.success || data.businesses.length === 0) {
                document.getElementById('resultsContainer').innerHTML = `
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <h3>No results found</h3>
                        <p>Try different keywords or browse our categories</p>
                    </div>
                `;
                document.getElementById('resultsContainer').style.display = 'block';
                document.getElementById('resultsWrapper').style.display = 'none';
                return;
            }
            
            // Show map layout
            document.getElementById('resultsContainer').style.display = 'none';
            document.getElementById('resultsWrapper').style.display = 'grid';
            
            // Get user location for distance calculation
            if (navigator.geolocation && !userLocation) {
                navigator.geolocation.getCurrentPosition(
                    pos => { userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude }; },
                    err => console.log('Location access denied')
                );
            }
            
            // Extract category and city from first business
            const firstBusiness = data.businesses[0];
            const category = firstBusiness.category || 'businesses';
            const city = extractCity(firstBusiness.address) || 'your area';
            const count = data.businesses.length;
            
            // Smart header
            const smartHeader = `Top ${count} ${category.toLowerCase()}s in ${city} based on rating, popularity & distance`;
            
            // Identify best picks
            const bestPicks = selectBestPicks(data.businesses);
            
            let html = `
                <div class="smart-header">
                    <div class="smart-header-content">
                        <h1 class="smart-title">${smartHeader}</h1>
                        <p class="smart-subtitle">Showing results based on rating, popularity, and proximity</p>
                    </div>
                </div>
            `;
            
            // Best picks section
            if (bestPicks.bestOverall) {
                html += `<div class="best-picks-section">
                    <div class="section-title">🏆 Best Picks for You</div>
                    <div class="best-picks-grid">
                `;
                
                if (bestPicks.bestOverall) {
                    html += buildBestPickCard(bestPicks.bestOverall, '🏆 Best Overall');
                }
                
                if (bestPicks.topRated && bestPicks.topRated.id !== bestPicks.bestOverall.id) {
                    html += buildBestPickCard(bestPicks.topRated, '⭐ Top Rated');
                }
                
                if (bestPicks.bestBudget && bestPicks.bestBudget.id !== bestPicks.bestOverall.id) {
                    html += buildBestPickCard(bestPicks.bestBudget, '💰 Best Budget');
                }
                
                html += `</div></div>`;
            }
            
            // All results with modern card design
            html += `<div class="businesses-grid">`;
            
            data.businesses.forEach((business, idx) => {
                let detailUrl = business.detail_url || `/pages/business-detail.php?id=${business.id}`;
                
                // Calculate distance if user location available
                let distanceHtml = '';
                if (userLocation && business.lat && business.lng) {
                    const distance = calculateDistance(userLocation.lat, userLocation.lng, business.lat, business.lng);
                    distanceHtml = `<span class="distance-tag">📍 ${distance} km</span>`;
                }
                
                // Check if featured
                let featuredBadge = '';
                if (business.is_featured) {
                    featuredBadge = `<div class="featured-badge">⭐ Featured</div>`;
                }
                
                html += `
                    <div class="business-card" data-index="${idx}" data-lat="${business.lat}" data-lng="${business.lng}" data-name="${business.name}">
                        ${featuredBadge}
                        <div class="card-header">
                            <div class="card-title-section">
                                <h3 class="card-title">${business.name}</h3>
                                <div class="card-rating">
                                    <span class="stars">⭐ ${parseFloat(business.rating).toFixed(1)}</span>
                                    <span class="review-count">(${business.review_count || 0})</span>
                                </div>
                            </div>
                            <button class="save-btn" onclick="saveBusinessToggle(${business.id}, '${business.name}')" title="Save to favorites">
                                <span class="save-icon">❤️</span>
                            </button>
                        </div>
                        <div class="card-body">
                            <p class="card-summary">${business.ai_short_summary || business.ai_description || business.address}</p>
                            <div class="card-meta">
                                <span class="category-tag">${business.category}</span>
                                ${distanceHtml}
                            </div>
                            <p class="card-address">📍 ${business.address}</p>
                            <div class="source-indicator">
                                <small>${business.source === 'google' || business.source === 'cached' ? '📍 Data sourced from Google' : '✔ Verified by Platform'}</small>
                            </div>
                        </div>
                        <div class="card-actions">
                            ${business.phone ? `<a href="tel:${business.phone}" class="action-btn call-btn">📞 Call</a>` : ''}
                            ${business.phone ? `<a href="https://wa.me/${business.phone.replace(/\D/g, '')}" target="_blank" class="action-btn whatsapp-btn">💬 WhatsApp</a>` : ''}
                            <a href="${detailUrl}" class="action-btn details-btn">🔍 Details</a>
                        </div>
                    </div>
                `;
            });
            
            html += `</div>`;
            
            // Render into results wrapper properly
            const resultsWrapper = document.getElementById('resultsWrapper');
            resultsWrapper.innerHTML = html + resultsWrapper.innerHTML;
            
            // Move search-results-container outside of results-column
            const searchResultsContainer = document.querySelector('.search-results-container');
            if (searchResultsContainer) {
                resultsWrapper.appendChild(searchResultsContainer);
            }
            
            // Save search to history
            saveSearch(query);
            loadRecentSearches();
            
            // Initialize map
            setTimeout(() => initializeMap(data.businesses), 100);
        }
        
        async function toggleSave(businessId, button) {
            try {
                const response = await fetch('/api/save-business.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `business_id=${businessId}&action=toggle`
                });
                
                const data = await response.json();
                if (data.success) {
                    if (data.saved) {
                        button.classList.add('saved');
                        button.textContent = '❤️';
                    } else {
                        button.classList.remove('saved');
                        button.textContent = '🤍';
                    }
                }
            } catch (error) {
                console.error('Save error:', error);
            }
        }
        
        async function saveSearch(query) {
            try {
                await fetch('/api/recent-searches.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=save&query=${encodeURIComponent(query)}`
                });
            } catch (error) {
                console.error('Save search error:', error);
            }
        }
        
        async function loadRecentSearches() {
            try {
                const response = await fetch('/api/recent-searches.php?action=get');
                const data = await response.json();
                
                if (data.success && data.searches && data.searches.length > 0) {
                    const container = document.getElementById('recentSearchesContainer');
                    const list = document.getElementById('recentSearchesList');
                    
                    list.innerHTML = data.searches.map(search => 
                        `<div class="recent-search-item" onclick="setSearch('${search.query.replace(/'/g, "\\'")}')">${search.query}</div>`
                    ).join('');
                    
                    container.style.display = 'block';
                }
            } catch (error) {
                console.error('Load recent searches error:', error);
            }
        }
        
        function initializeMap(businesses) {
            const centerLat = parseFloat(businesses[0]?.lat || 28.6139);
            const centerLng = parseFloat(businesses[0]?.lng || 77.2090);
            
            const mapOptions = {
                zoom: 13,
                center: { lat: centerLat, lng: centerLng },
                styles: [
                    { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
                    { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] }
                ]
            };
            
            if (!map) {
                map = new google.maps.Map(document.getElementById('businessMap'), mapOptions);
            }
            
            // Clear old markers
            markers.forEach(m => m.setMap(null));
            markers = [];
            
            // Add markers
            businesses.forEach((business, idx) => {
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(business.lat), lng: parseFloat(business.lng) },
                    map: map,
                    title: business.name
                });
                
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 10px; max-width: 250px;">
                            <strong style="font-size: 14px;">${business.name}</strong><br>
                            ⭐ ${business.rating} (${business.review_count || 0} reviews)<br>
                            <div style="color: #666; font-size: 12px; margin-top: 5px;">${business.ai_short_summary || business.address}</div>
                        </div>
                    `
                });
                
                marker.addListener('click', () => {
                    // Close all other info windows
                    document.querySelectorAll('.gm-ui-hover-effect').forEach(el => el.style.display = 'none');
                    infoWindow.open(map, marker);
                    highlightMarker(idx);
                    // Scroll to card
                    const card = document.querySelector(`[data-business-id="${business.id}"]`);
                    if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
                
                markers.push(marker);
            });
        }
        
        function highlightMarker(index) {
            // Remove active from all cards
            document.querySelectorAll('.result-card').forEach(card => card.classList.remove('active'));
            // Add active to clicked card
            document.querySelectorAll('.result-card')[index]?.classList.add('active');
            // Pan map to marker
            if (markers[index]) {
                map.panTo(markers[index].getPosition());
            }
        }
        
        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return (R * c).toFixed(1);
        }
        
        function extractCity(address) {
            // Extract city from address (usually after last comma or last part)
            if (!address) return '';
            const parts = address.split(',').map(p => p.trim());
            return parts[parts.length - 1] || '';
        }
        
        function selectBestPicks(businesses) {
            if (!businesses || businesses.length === 0) return {};
            
            const picks = {
                bestOverall: businesses[0], // Highest rated (first in sorted list)
                topRated: businesses[0]
            };
            
            // Find best budget (has affordable tag)
            const bestBudget = businesses.find(b => 
                b.ai_tags && b.ai_tags.toLowerCase().includes('affordable')
            );
            
            if (bestBudget) {
                picks.bestBudget = bestBudget;
            }
            
            // Find actual top rated if different from first
            if (businesses.length > 1) {
                let topRatedBusiness = businesses[0];
                for (let i = 1; i < businesses.length; i++) {
                    if (businesses[i].rating > topRatedBusiness.rating) {
                        topRatedBusiness = businesses[i];
                    }
                }
                picks.topRated = topRatedBusiness;
            }
            
            return picks;
        }
        
        function buildBestPickCard(business, badge) {
            const detailUrl = business.detail_url || `/pages/business-detail.php?id=${business.id}`;
            const [icon, label] = badge.split(' ');
            
            return `
                <div class="best-pick-card">
                    <div class="best-pick-badge">${badge}</div>
                    <div class="best-pick-name">${business.name}</div>
                    <div class="best-pick-rating">
                        <span class="rating-stars">⭐ ${parseFloat(business.rating).toFixed(1)}</span>
                        <span class="review-count">(${business.review_count || 0} reviews)</span>
                    </div>
                    <div class="best-pick-summary">${business.ai_short_summary || business.ai_description || business.address}</div>
                    <div class="best-pick-category">${business.category}</div>
                    <a href="${detailUrl}" class="best-pick-link">View Details →</a>
                </div>
            `;
        }
        
        // Load recent searches on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadRecentSearches();
        });
        
        // Allow Enter key to search
        document.getElementById('searchInput')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') performSearch();
        });
    </script>
    
    <script src="/assets/js/smart-display.js"></script>
    
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(getenv('GOOGLE_API_KEY') ?: ''); ?>&libraries=places"></script>
</body>
</html>
