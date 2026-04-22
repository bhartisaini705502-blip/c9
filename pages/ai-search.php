<?php
/**
 * AI-Powered Intent-Based Search
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

$page_title = 'AI Smart Search | ConnectWith9';
$meta_description = 'Search businesses by intent. Find the perfect business with AI-powered recommendations.';

// Get search parameters
$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$priceLevel = $_GET['price'] ?? '';
$minRating = $_GET['rating'] ?? 0;
$location = $_GET['location'] ?? '';

$results = [];
if (!empty($query) || !empty($category)) {
    $results = performAISearch($query, $category, $priceLevel, $minRating, $location);
}

include '../includes/header.php';
?>

<style>
.search-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
}

.search-box {
    max-width: 700px;
    margin: 0 auto;
    display: flex;
    gap: 10px;
    padding: 0 20px;
}

.search-box input {
    flex: 1;
    padding: 15px 20px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
}

.search-box button {
    padding: 15px 30px;
    background: white;
    color: #667eea;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.filters {
    max-width: 1200px;
    margin: 0 auto 30px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.filter-group {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    font-size: 13px;
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.results-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 40px;
}

.results-header {
    margin-bottom: 25px;
}

.results-header h2 {
    color: #0B1C3D;
    margin: 0 0 5px 0;
}

.results-header p {
    color: #666;
    margin: 0;
    font-size: 14px;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.business-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.business-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    transform: translateY(-5px);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
}

.card-header h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.card-category {
    font-size: 12px;
    opacity: 0.9;
}

.card-body {
    padding: 15px;
}

.card-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.card-rating .stars {
    color: #FFB800;
    font-size: 14px;
}

.card-rating .count {
    color: #666;
    font-size: 12px;
}

.card-location {
    color: #666;
    font-size: 12px;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 5px;
}

.card-actions {
    display: flex;
    gap: 8px;
}

.card-actions a {
    flex: 1;
    padding: 8px;
    text-align: center;
    font-size: 12px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.card-actions .view-btn {
    background: #667eea;
    color: white;
}

.card-actions .call-btn {
    background: #FF6A00;
    color: white;
}

.no-results {
    text-align: center;
    padding: 40px;
    color: #999;
}

.no-results h3 {
    color: #666;
    margin: 0 0 10px 0;
}

.ai-badge {
    background: #E3F2FD;
    color: #1565C0;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 10px;
}
</style>

<div class="search-container">
    <div class="search-box">
        <form style="display: flex; gap: 10px; flex: 1;">
            <input type="text" name="q" placeholder="Search businesses..." value="<?php echo esc($query); ?>" />
            <button type="submit">🔍 Search</button>
        </form>
    </div>
</div>

<div class="filters">
    <form method="GET" style="display: contents;">
        <div class="filter-group">
            <label>Category</label>
            <select name="category" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <option value="restaurant" <?php echo $category === 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                <option value="cafe" <?php echo $category === 'cafe' ? 'selected' : ''; ?>>Cafe</option>
                <option value="gym" <?php echo $category === 'gym' ? 'selected' : ''; ?>>Gym</option>
                <option value="salon" <?php echo $category === 'salon' ? 'selected' : ''; ?>>Salon</option>
                <option value="hotel" <?php echo $category === 'hotel' ? 'selected' : ''; ?>>Hotel</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Price Level</label>
            <select name="price" onchange="this.form.submit()">
                <option value="">All Prices</option>
                <option value="1" <?php echo $priceLevel === '1' ? 'selected' : ''; ?>>Budget (₹)</option>
                <option value="2" <?php echo $priceLevel === '2' ? 'selected' : ''; ?>>Moderate (₹₹)</option>
                <option value="3" <?php echo $priceLevel === '3' ? 'selected' : ''; ?>>Expensive (₹₹₹)</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Min Rating</label>
            <select name="rating" onchange="this.form.submit()">
                <option value="0" <?php echo $minRating === '0' ? 'selected' : ''; ?>>All Ratings</option>
                <option value="4" <?php echo $minRating === '4' ? 'selected' : ''; ?>>4★+ Only</option>
                <option value="4.5" <?php echo $minRating === '4.5' ? 'selected' : ''; ?>>4.5★+ Only</option>
            </select>
        </div>

        <input type="hidden" name="q" value="<?php echo esc($query); ?>" />
    </form>
</div>

<div class="results-container">
    <?php if (!empty($results)): ?>
    <div class="results-header">
        <h2><?php echo count($results); ?> Results Found</h2>
        <p><span class="ai-badge">🤖 AI-Powered Search Results</span></p>
    </div>

    <div class="results-grid">
        <?php foreach ($results as $business): ?>
        <div class="business-card">
            <div class="card-header">
                <h3><?php echo esc($business['name']); ?></h3>
                <div class="card-category"><?php echo esc($business['category']); ?></div>
            </div>
            <div class="card-body">
                <div class="card-rating">
                    <div class="stars">★★★★☆</div>
                    <div class="count"><?php echo number_format($business['rating'], 1); ?> (<?php echo $business['reviews']; ?>)</div>
                </div>
                <div class="card-location">
                    <span>📍</span>
                    <span><?php echo esc($business['location']); ?></span>
                </div>
                <div class="card-actions">
                    <a href="/pages/business-detail.php?id=<?php echo $business['id']; ?>&name=<?php echo urlencode(slugify($business['name'])); ?>" class="view-btn">View Details</a>
                    <?php if (!empty($business['phone'])): ?>
                    <a href="tel:<?php echo preg_replace('/\D/', '', $business['phone']); ?>" class="call-btn">Call</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif (!empty($query) || !empty($category)): ?>
    <div class="no-results">
        <h3>No Results Found</h3>
        <p>Try adjusting your filters or search for a different category.</p>
    </div>
    <?php else: ?>
    <div class="no-results">
        <h3>Start Searching</h3>
        <p>Use the search box above to find businesses by category, location, or type.</p>
    </div>
    <?php endif; ?>
</div>

<?php
function performAISearch($query, $category, $priceLevel, $minRating, $location) {
    global $conn;
    
    $where = ["business_status = 'OPERATIONAL'"];
    $params = [];
    
    // Search by query
    if (!empty($query)) {
        $where[] = "(name LIKE ? OR types LIKE ?)";
        $searchTerm = '%' . $query . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Filter by category
    if (!empty($category)) {
        $where[] = "types LIKE ?";
        $params[] = '%' . $category . '%';
    }
    
    // Filter by price level
    if (!empty($priceLevel)) {
        $where[] = "price_level = ?";
        $params[] = (int)$priceLevel;
    }
    
    // Filter by minimum rating
    if (!empty($minRating)) {
        $where[] = "rating >= ?";
        $params[] = (float)$minRating;
    }
    
    $whereClause = implode(' AND ', $where);
    $sql = "SELECT id, name, types, formatted_address, search_location, rating, user_ratings_total, 
            formatted_phone_number as phone, price_level
            FROM extracted_businesses 
            WHERE $whereClause
            ORDER BY verified DESC, rating DESC, user_ratings_total DESC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $types = str_repeat('s', count(preg_grep('/^%.+%$/', $params))) . 
                 str_repeat('i', count(preg_grep('/^\d+$/', $params))) .
                 str_repeat('d', count(preg_grep('/^\d+\.\d+$/', $params)));
        
        // Rebuild types string properly
        $types = '';
        foreach ($params as $p) {
            if (is_int($p)) {
                $types .= 'i';
            } elseif (is_float($p)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Format results
    $formatted = [];
    foreach ($results as $business) {
        $types = array_map('trim', explode(',', $business['types']));
        $formatted[] = [
            'id' => $business['id'],
            'name' => $business['name'],
            'category' => $types[0] ?? 'Business',
            'rating' => number_format($business['rating'], 1),
            'reviews' => $business['user_ratings_total'],
            'location' => $business['search_location'],
            'phone' => $business['phone']
        ];
    }
    
    return $formatted;
}
?>

<?php include '../includes/footer.php'; ?>
