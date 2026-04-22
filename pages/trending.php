<?php
/**
 * Trending Businesses Page
 */

require_once '../config/db.php';
require_once '../includes/functions.php';

$page_title = 'Trending Businesses | ConnectWith9';
$meta_description = 'Discover the most searched and trending businesses in your city right now.';

$city = $_GET['city'] ?? '';

// Get trending cities
$cities = $GLOBALS['conn']->query("
    SELECT DISTINCT city FROM trending_searches 
    WHERE city IS NOT NULL 
    ORDER BY last_searched DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get trending businesses
$where = "business_status = 'OPERATIONAL'";
if (!empty($city)) {
    $where .= " AND search_location = '" . $GLOBALS['conn']->real_escape_string($city) . "'";
}

$trendingBiz = $GLOBALS['conn']->query("
    SELECT id, name, types, search_location, rating, user_ratings_total, verified 
    FROM extracted_businesses 
    WHERE $where
    ORDER BY user_ratings_total DESC, rating DESC 
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

// Rename city field to search_location for compatibility
foreach ($cities as &$c) {
    if (!isset($c['search_location']) && isset($c['city'])) {
        $c['search_location'] = $c['city'];
    }
}

include '../includes/header.php';
?>

<style>
.trending-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px 40px;
}

.trending-header {
    background: linear-gradient(135deg, #FF6A00 0%, #FF8C00 100%);
    color: white;
    padding: 40px;
    border-radius: 10px;
    margin-bottom: 30px;
    text-align: center;
}

.trending-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}

.trending-header p {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
}

.city-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.city-btn {
    padding: 10px 20px;
    background: <?php echo empty($city) ? '#FF6A00' : 'white'; ?>;
    color: <?php echo empty($city) ? 'white' : '#FF6A00'; ?>;
    border: 2px solid #FF6A00;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    text-decoration: none;
}

.city-btn:hover {
    background: #FF6A00;
    color: white;
}

.trending-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.trending-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
    text-decoration: none;
    color: inherit;
}

.trending-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    transform: translateY(-5px);
}

.trending-card-header {
    background: linear-gradient(135deg, #FF6A00 0%, #FF8C00 100%);
    color: white;
    padding: 15px;
    position: relative;
}

.trending-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #FFD700;
    color: #333;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.trending-card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.trending-card-category {
    font-size: 12px;
    opacity: 0.8;
    margin-top: 5px;
}

.trending-card-body {
    padding: 15px;
}

.trending-rating {
    color: #FFB800;
    font-size: 14px;
    margin-bottom: 10px;
}

.trending-location {
    color: #666;
    font-size: 12px;
    margin-bottom: 12px;
}

.view-link {
    display: inline-block;
    color: #FF6A00;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
}

.verified-badge {
    display: inline-block;
    background: #D4EDDA;
    color: #155724;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}

.no-results {
    text-align: center;
    padding: 40px;
    color: #999;
}
</style>

<div class="trending-container">
    <div class="trending-header">
        <h1>📈 Trending Now</h1>
        <p>Most searched and popular businesses</p>
    </div>
    
    <h2 style="color: #0B1C3D; margin: 0 0 15px 0;">Select Location</h2>
    <div class="city-selector">
        <a href="/pages/trending.php" class="city-btn" style="<?php echo empty($city) ? 'background:#FF6A00;color:white;' : ''; ?>">
            📍 All Cities
        </a>
        <?php foreach ($cities as $c): 
            $cityName = $c['search_location'] ?? $c['city'] ?? '';
        ?>
        <a href="/pages/trending.php?city=<?php echo urlencode($cityName); ?>" 
           class="city-btn"
           style="<?php echo $city === $cityName ? 'background:#FF6A00;color:white;' : ''; ?>">
            <?php echo esc($cityName); ?>
        </a>
        <?php endforeach; ?>
    </div>
    
    <?php if (!empty($trendingBiz)): ?>
    <h2 style="color: #0B1C3D; margin: 30px 0 20px 0;">Top Trending Businesses</h2>
    <div class="trending-grid">
        <?php foreach ($trendingBiz as $business): 
            $types = array_map('trim', explode(',', $business['types']));
            $category = $types[0] ?? 'Business';
        ?>
        <a href="/pages/business-detail.php?id=<?php echo $business['id']; ?>&name=<?php echo urlencode(slugify($business['name'])); ?>" class="trending-card">
            <div class="trending-card-header">
                <div class="trending-badge">🔥 Trending</div>
                <h3 class="trending-card-title"><?php echo esc($business['name']); ?></h3>
                <div class="trending-card-category"><?php echo esc($category); ?></div>
            </div>
            <div class="trending-card-body">
                <div class="trending-rating">
                    ⭐ <?php echo number_format($business['rating'], 1); ?>/5 (<?php echo $business['user_ratings_total']; ?> reviews)
                    <?php if ($business['verified']): ?>
                    <span class="verified-badge">✓ Verified</span>
                    <?php endif; ?>
                </div>
                <div class="trending-location">📍 <?php echo esc($business['search_location']); ?></div>
                <a href="/pages/business-detail.php?id=<?php echo $business['id']; ?>&name=<?php echo urlencode(slugify($business['name'])); ?>" class="view-link">View Profile →</a>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-results">
        <h3>No trending businesses found</h3>
        <p>Try selecting a different location</p>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
