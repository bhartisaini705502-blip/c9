<?php
/**
 * Smart Display System
 * Unified intelligent business display for modern discovery experience
 */

/**
 * Get best picks (top 3 businesses)
 */
function getBestPicks($businesses, $category = '') {
    if (empty($businesses)) return [];
    
    $picks = [];
    
    // Sort by rating
    $sorted = $businesses;
    usort($sorted, function($a, $b) {
        return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
    });
    
    // Best Overall - highest rated
    if (!empty($sorted[0])) {
        $picks['best_overall'] = $sorted[0];
        $picks['best_overall']['best_pick_type'] = 'Best Overall';
        $picks['best_overall']['best_pick_icon'] = '🏆';
    }
    
    // Best Budget - find affordable option (lower pricing mentioned in summary or tags)
    foreach ($sorted as $biz) {
        if (strpos(strtolower($biz['ai_short_summary'] ?? ''), 'affordable') !== false ||
            strpos(strtolower($biz['ai_short_summary'] ?? ''), 'budget') !== false ||
            strpos(strtolower($biz['ai_tags'] ?? ''), 'budget') !== false) {
            $picks['best_budget'] = $biz;
            $picks['best_budget']['best_pick_type'] = 'Best Budget';
            $picks['best_budget']['best_pick_icon'] = '💰';
            break;
        }
    }
    
    // If no budget found, use 2nd or 3rd highest rated
    if (empty($picks['best_budget']) && !empty($sorted[1])) {
        $picks['best_budget'] = $sorted[1];
        $picks['best_budget']['best_pick_type'] = 'Popular Choice';
        $picks['best_budget']['best_pick_icon'] = '⭐';
    }
    
    // Top Rated - with most reviews
    $mostReviews = null;
    foreach ($sorted as $biz) {
        if (empty($mostReviews) || ($biz['review_count'] ?? 0) > ($mostReviews['review_count'] ?? 0)) {
            $mostReviews = $biz;
        }
    }
    if ($mostReviews && !isset($picks['best_overall']) || $mostReviews['id'] !== $picks['best_overall']['id']) {
        $picks['top_rated'] = $mostReviews;
        $picks['top_rated']['best_pick_type'] = 'Top Rated';
        $picks['top_rated']['best_pick_icon'] = '⭐';
    }
    
    return array_slice($picks, 0, 3);
}

/**
 * Get short AI summary or generate fallback
 */
function getShortSummary($business) {
    if (!empty($business['ai_short_summary'])) {
        return substr($business['ai_short_summary'], 0, 120);
    }
    
    if (!empty($business['ai_description'])) {
        return substr($business['ai_description'], 0, 120);
    }
    
    // Fallback: generate from category
    $category = $business['category'] ?? $business['types'] ?? '';
    $name = $business['name'] ?? '';
    
    if ($category) {
        return "Quality " . strtolower($category) . " services in your area";
    }
    
    return "Trusted business in your area";
}

/**
 * Format distance in km
 */
function formatDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
        return null;
    }
    
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;
    
    if ($distance < 1) {
        return round($distance * 1000) . ' m';
    }
    
    return round($distance, 1) . ' km';
}

/**
 * Get user's location from geolocation or return null
 */
function getUserLocation() {
    // This will be handled via JavaScript on client side
    return null;
}

/**
 * Render smart result header
 */
function renderSmartHeader($count, $category, $city, $searchQuery = '') {
    $category = htmlspecialchars($category);
    $city = htmlspecialchars($city);
    $searchQuery = htmlspecialchars($searchQuery);
    
    if ($category && $city) {
        $title = "Top $count " . strtolower($category) . " in " . ucfirst($city);
    } elseif ($searchQuery) {
        $title = "Search results for \"$searchQuery\"";
    } else {
        $title = "Discover $count businesses near you";
    }
    
    ?>
    <div class="smart-header">
        <div class="smart-header-content">
            <h1 class="smart-title"><?php echo $title; ?></h1>
            <p class="smart-subtitle">Showing results based on rating, popularity, and proximity</p>
        </div>
    </div>
    <?php
}

/**
 * Render Best Picks section
 */
function renderBestPicks($bestPicks) {
    if (empty($bestPicks)) {
        return;
    }
    
    ?>
    <div class="best-picks-section">
        <div class="section-title">🏆 Best Picks for You</div>
        <div class="best-picks-grid">
            <?php foreach ($bestPicks as $pick): ?>
            <div class="best-pick-card">
                <div class="best-pick-badge"><?php echo $pick['best_pick_icon']; ?> <?php echo $pick['best_pick_type']; ?></div>
                <div class="best-pick-name"><?php echo htmlspecialchars($pick['name']); ?></div>
                <div class="best-pick-rating">
                    <span class="rating-stars">⭐ <?php echo number_format($pick['rating'] ?? 0, 1); ?></span>
                    <span class="review-count">(<?php echo $pick['review_count'] ?? 0; ?> reviews)</span>
                </div>
                <div class="best-pick-summary"><?php echo getShortSummary($pick); ?></div>
                <div class="best-pick-category"><?php echo htmlspecialchars($pick['category'] ?? $pick['types'] ?? ''); ?></div>
                <a href="<?php echo isset($pick['place_id']) ? '/pages/google-business-detail.php?place_id=' . urlencode($pick['place_id']) : '/pages/business-detail.php?id=' . $pick['id']; ?>" class="best-pick-link">View Details →</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Render listing card
 */
function renderBusinessCard($business, $userLat = null, $userLng = null, $index = 0) {
    $id = $business['id'] ?? null;
    $placeId = $business['place_id'] ?? null;
    $name = htmlspecialchars($business['name']);
    $rating = $business['rating'] ?? 0;
    $reviews = $business['review_count'] ?? $business['user_ratings_total'] ?? 0;
    $summary = getShortSummary($business);
    $category = htmlspecialchars($business['category'] ?? $business['types'] ?? '');
    $address = htmlspecialchars($business['formatted_address'] ?? $business['address'] ?? '');
    $phone = $business['phone'] ?? $business['formatted_phone_number'] ?? '';
    $lat = $business['lat'] ?? null;
    $lng = $business['lng'] ?? null;
    $source = $business['source'] ?? 'local';
    $isFeatured = $business['is_featured'] ?? false;
    $distance = null;
    
    if ($userLat && $userLng && $lat && $lng) {
        $distance = formatDistance($userLat, $userLng, $lat, $lng);
    }
    
    $detailUrl = $placeId 
        ? '/pages/google-business-detail.php?place_id=' . urlencode($placeId)
        : '/pages/business-detail.php?id=' . $id;
    
    ?>
    <div class="business-card" data-index="<?php echo $index; ?>" data-lat="<?php echo $lat; ?>" data-lng="<?php echo $lng; ?>" data-name="<?php echo $name; ?>">
        <?php if ($isFeatured): ?>
        <div class="featured-badge">⭐ Featured</div>
        <?php endif; ?>
        
        <div class="card-header">
            <div class="card-title-section">
                <h3 class="card-title"><?php echo $name; ?></h3>
                <div class="card-rating">
                    <span class="stars">⭐ <?php echo number_format($rating, 1); ?></span>
                    <span class="review-count">(<?php echo $reviews; ?>)</span>
                </div>
            </div>
            <button class="save-btn" onclick="saveBusinessToggle(<?php echo $id ?? "'$placeId'"; ?>, '<?php echo htmlspecialchars($name); ?>')" title="Save this business">
                <span class="save-icon">❤️</span>
            </button>
        </div>
        
        <div class="card-body">
            <p class="card-summary"><?php echo $summary; ?></p>
            
            <div class="card-meta">
                <span class="category-tag"><?php echo $category; ?></span>
                <?php if ($distance): ?>
                <span class="distance-tag">📍 <?php echo $distance; ?></span>
                <?php endif; ?>
            </div>
            
            <p class="card-address">📍 <?php echo substr($address, 0, 60); ?></p>
            
            <div class="source-indicator">
                <?php if ($source === 'google' || $source === 'cached'): ?>
                <small>📍 Data sourced from Google</small>
                <?php else: ?>
                <small>✔ Verified by Platform</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card-actions">
            <?php if ($phone): ?>
            <a href="tel:<?php echo urlencode($phone); ?>" class="action-btn call-btn">📞 Call</a>
            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $phone); ?>" target="_blank" class="action-btn whatsapp-btn">💬 WhatsApp</a>
            <?php endif; ?>
            <a href="<?php echo $detailUrl; ?>" class="action-btn details-btn">🔍 Details</a>
        </div>
    </div>
    <?php
}

/**
 * Render listings grid
 */
function renderBusinessGrid($businesses, $userLat = null, $userLng = null) {
    if (empty($businesses)) {
        echo '<div class="no-results"><p>No businesses found. Try adjusting your search.</p></div>';
        return;
    }
    
    ?>
    <div class="businesses-grid">
        <?php foreach ($businesses as $index => $business): ?>
            <?php renderBusinessCard($business, $userLat, $userLng, $index); ?>
        <?php endforeach; ?>
    </div>
    <?php
}

?>
