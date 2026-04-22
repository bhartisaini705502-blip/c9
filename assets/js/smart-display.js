/**
 * Smart Display System - Interactive Features
 */

// Initialize smart display on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeSaveButtons();
    initializeMapSync();
    loadRecentlyViewed();
});

/**
 * Save/Unsave Business
 */
function saveBusinessToggle(businessId, businessName) {
    const btn = event.target.closest('.save-btn');
    const isSaved = btn.classList.contains('saved');
    
    const action = isSaved ? 'remove' : 'add';
    
    fetch('/api/save-business.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: action,
            business_id: businessId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.classList.toggle('saved');
            const message = isSaved ? 'Removed from favorites' : 'Saved to favorites';
            showToast(message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating favorites', 'error');
    });
}

/**
 * Initialize save button states (check if already saved)
 */
function initializeSaveButtons() {
    const buttons = document.querySelectorAll('.save-btn');
    
    buttons.forEach(btn => {
        const businessCard = btn.closest('.business-card');
        if (businessCard) {
            // Check if saved (this would be done via API or data attribute)
            const cardId = businessCard.dataset.id;
            if (cardId) {
                checkIfSaved(cardId, btn);
            }
        }
    });
}

/**
 * Check if business is saved
 */
function checkIfSaved(businessId, btn) {
    fetch(`/api/save-business.php?check=${businessId}`)
        .then(response => response.json())
        .then(data => {
            if (data.saved) {
                btn.classList.add('saved');
            }
        })
        .catch(error => console.error('Error checking saved status:', error));
}

/**
 * Map Integration - Sync Cards with Map
 */
let businessMap = null;
let markers = {};
let infoWindows = {};

function initializeMapSync() {
    const mapElement = document.getElementById('businessMap');
    if (!mapElement) return;
    
    // Initialize Google Map
    const defaultCenter = { lat: 20.5937, lng: 78.9629 }; // India center
    businessMap = new google.maps.Map(mapElement, {
        zoom: 12,
        center: defaultCenter,
        styles: [
            {
                "featureType": "poi",
                "elementType": "labels",
                "stylers": [{ "visibility": "off" }]
            }
        ]
    });
    
    // Add markers for each business
    const businessCards = document.querySelectorAll('.business-card');
    let bounds = new google.maps.LatLngBounds();
    
    businessCards.forEach((card, index) => {
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        const name = card.dataset.name;
        const cardIndex = card.dataset.index;
        
        if (lat && lng) {
            const marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: businessMap,
                title: name,
                label: String(parseInt(cardIndex) + 1)
            });
            
            // Info window
            const infoWindow = new google.maps.InfoWindow({
                content: `<div style="padding: 10px; font-size: 13px; font-weight: 600;">${name}</div>`
            });
            
            markers[cardIndex] = marker;
            infoWindows[cardIndex] = infoWindow;
            
            // Click marker to highlight card
            marker.addListener('click', () => {
                highlightCard(cardIndex);
                infoWindow.open(businessMap, marker);
            });
            
            bounds.extend(marker.getPosition());
        }
    });
    
    // Fit bounds to all markers
    if (bounds.getNorthEast().toString() !== bounds.getSouthWest().toString()) {
        businessMap.fitBounds(bounds);
    }
    
    // Card hover to highlight marker
    businessCards.forEach((card) => {
        const index = card.dataset.index;
        
        card.addEventListener('mouseenter', () => {
            highlightMarker(index);
        });
        
        card.addEventListener('mouseleave', () => {
            clearMarkerHighlight(index);
        });
        
        card.addEventListener('click', () => {
            highlightMarker(index);
        });
    });
}

/**
 * Highlight card and pan map to it
 */
function highlightCard(index) {
    document.querySelectorAll('.business-card').forEach(card => {
        card.classList.remove('highlighted');
    });
    
    const card = document.querySelector(`[data-index="${index}"]`);
    if (card) {
        card.classList.add('highlighted');
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    highlightMarker(index);
}

/**
 * Highlight marker on map
 */
function highlightMarker(index) {
    if (markers[index]) {
        markers[index].setOpacity(1);
        markers[index].setScale(1.3);
        
        // Pan to marker
        if (businessMap) {
            businessMap.panTo(markers[index].getPosition());
        }
        
        // Open info window
        if (infoWindows[index]) {
            Object.keys(infoWindows).forEach(key => {
                if (key !== index) {
                    infoWindows[key].close();
                }
            });
            infoWindows[index].open(businessMap, markers[index]);
        }
    }
}

/**
 * Clear marker highlight
 */
function clearMarkerHighlight(index) {
    if (markers[index]) {
        markers[index].setOpacity(0.7);
        markers[index].setScale(1);
    }
}

/**
 * Show Toast Notification
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    
    if (type === 'error') {
        toast.style.background = '#f44336';
    }
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

/**
 * Track search for recently viewed
 */
function trackSearch(query, resultCount) {
    fetch('/api/recent-searches.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            search_query: query,
            results_found: resultCount
        })
    })
    .catch(error => console.error('Error tracking search:', error));
}

/**
 * Load Recently Viewed Businesses (for future use)
 */
function loadRecentlyViewed() {
    const recentSection = document.getElementById('recentlyViewed');
    if (!recentSection) return;
    
    fetch('/api/recent-searches.php?get_recent=1')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.recent && data.recent.length > 0) {
                renderRecentlyViewed(data.recent, recentSection);
            }
        })
        .catch(error => console.error('Error loading recent:', error));
}

/**
 * Render Recently Viewed
 */
function renderRecentlyViewed(recent, container) {
    // Implementation for recently viewed section
    // This can be expanded based on requirements
}

/**
 * Get user location for distance calculation
 */
function getUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                
                // Update distances for all cards
                updateAllDistances(userLat, userLng);
            },
            (error) => {
                console.log('Geolocation error:', error);
            }
        );
    }
}

/**
 * Update distances for all cards
 */
function updateAllDistances(userLat, userLng) {
    const cards = document.querySelectorAll('.business-card');
    
    cards.forEach(card => {
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        
        if (lat && lng) {
            const distance = calculateDistance(userLat, userLng, lat, lng);
            const distanceTag = card.querySelector('.distance-tag');
            
            if (distanceTag) {
                if (distance < 1) {
                    distanceTag.textContent = `📍 ${Math.round(distance * 1000)} m`;
                } else {
                    distanceTag.textContent = `📍 ${distance.toFixed(1)} km`;
                }
            }
        }
    });
}

/**
 * Calculate distance between two coordinates (Haversine formula)
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Radius of Earth in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

/**
 * Lazy load images
 */
function initializeLazyLoad() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img.lazy').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

// Initialize lazy load
window.addEventListener('load', initializeLazyLoad);

// Request user location
window.addEventListener('load', getUserLocation);

