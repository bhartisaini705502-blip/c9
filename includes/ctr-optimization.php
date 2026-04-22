<?php
/**
 * CTR Optimization System
 * Generates optimized titles and descriptions with power words and numbers
 */

class CTROptimization {
    
    // Power words that increase CTR
    private static $power_words = [
        'best', 'top', 'ultimate', 'complete', 'essential', 'proven',
        'verified', 'trusted', 'certified', 'professional', 'expert',
        'exclusive', 'premium', 'authentic', 'official'
    ];
    
    private static $urgency_words = [
        'now', 'today', '2024', '2025', 'latest', 'updated',
        'new', 'exclusive', 'limited'
    ];

    /**
     * Generate CTR-optimized title
     */
    public static function generateTitle($page_type, $category, $city, $count = null) {
        $titles = [
            'best' => "Top 10 Best {$category} in {$city} | Verified & Rated 2024",
            'top' => "Top {$category} Services in {$city} (Proven Results)",
            'affordable' => "Affordable {$category} in {$city} | Budget-Friendly Options",
            'near' => "{$category} Near Me in {$city} | Quick & Easy",
            'services' => "Professional {$category} Services in {$city} | Trusted",
            'cheap' => "Cheap {$category} in {$city} | Affordable & Verified"
        ];

        $base_title = $titles[$page_type] ?? "Best {$category} in {$city}";
        
        // Add count if available
        if ($count && $count > 0) {
            $base_title = str_replace('Top 10', "Top {$count}", $base_title);
        }
        
        return $base_title;
    }

    /**
     * Generate CTR-optimized meta description
     */
    public static function generateDescription($page_type, $category, $city, $count = null) {
        $descriptions = [
            'best' => "Find the best verified {$category} in {$city}. Read real ratings, contact instantly, compare verified professionals. Updated 2024.",
            'top' => "Discover top-rated {$category} services in {$city}. Verified providers with trusted reviews. Contact now!",
            'affordable' => "Looking for affordable {$category} in {$city}? Find budget-friendly options with verified ratings and reviews.",
            'near' => "{$category} near you in {$city}. Find verified, rated providers. Quick contact and booking.",
            'services' => "Professional {$category} services in {$city}. Certified, verified, trusted by thousands. Contact today.",
            'cheap' => "Cheap {$category} in {$city} without compromising quality. Verified and rated by real customers."
        ];

        return $descriptions[$page_type] ?? "Find the best {$category} in {$city} on ConnectWith9 - trusted local business directory.";
    }

    /**
     * Add CTR-boosting badges to listings
     */
    public static function renderBadges($business) {
        $badges = '';
        
        if ($business['is_featured']) {
            $badges .= '<span class="ctr-badge badge-featured">⭐ Featured</span>';
        }
        
        if ($business['is_verified']) {
            $badges .= '<span class="ctr-badge badge-verified">✓ Verified</span>';
        }
        
        if ($business['rating'] && $business['rating'] >= 4.5) {
            $badges .= '<span class="ctr-badge badge-highly-rated">★ Highly Rated</span>';
        }
        
        if ($business['user_ratings_total'] && $business['user_ratings_total'] > 50) {
            $badges .= '<span class="ctr-badge badge-popular">🔥 Popular</span>';
        }
        
        return $badges;
    }
}
?>
