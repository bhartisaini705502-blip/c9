<?php
/**
 * Advanced Search with Filters - Premium UI
 */

require '../config/db.php';
require '../includes/functions.php';

// Get filter values
$keyword = $_GET['keyword'] ?? '';
$category_filter = $_GET['category'] ?? '';
$city_filter = $_GET['city'] ?? '';
$rating_filter = intval($_GET['rating'] ?? 0);
$verified_only = isset($_GET['verified']) ? 1 : 0;
$featured_only = isset($_GET['featured']) ? 1 : 0;
$sort_by = $_GET['sort'] ?? 'rating';
$page = intval($_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build search query with proper filtering
$where_clauses = ["business_status = 'OPERATIONAL'"];
$params = [];
$types = '';

if (!empty($keyword)) {
    $keyword_esc = $conn->real_escape_string($keyword);
    $where_clauses[] = "(name LIKE '%$keyword_esc%' OR formatted_address LIKE '%$keyword_esc%' OR types LIKE '%$keyword_esc%')";
}

if (!empty($category_filter)) {
    $category_esc = $conn->real_escape_string($category_filter);
    $where_clauses[] = "types LIKE '%$category_esc%'";
}

if (!empty($city_filter)) {
    $city_esc = $conn->real_escape_string($city_filter);
    $where_clauses[] = "search_location = '$city_esc'";
}

if ($rating_filter > 0) {
    $where_clauses[] = "rating >= $rating_filter";
}

if ($verified_only) {
    $where_clauses[] = "verified = 1";
}

if ($featured_only) {
    $where_clauses[] = "is_featured = 1";
}

// Determine sort order
$order_by = "rating DESC, user_ratings_total DESC";
switch ($sort_by) {
    case 'rated':
        $order_by = "rating DESC, user_ratings_total DESC";
        break;
    case 'reviews':
        $order_by = "user_ratings_total DESC, rating DESC";
        break;
    case 'featured':
        $order_by = "is_featured DESC, rating DESC";
        break;
    case 'name':
        $order_by = "name ASC";
        break;
    default:
        $order_by = "is_featured DESC, verified DESC, rating DESC";
}

$where = implode(' AND ', $where_clauses);

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as count FROM extracted_businesses WHERE $where");
$total_count = $count_result->fetch_assoc()['count'];
$total_pages = ceil($total_count / $per_page);

// Get results
$query = "SELECT id, name, types, search_location, formatted_address, formatted_phone_number, rating, user_ratings_total, verified, is_featured, website FROM extracted_businesses WHERE $where ORDER BY $order_by LIMIT $per_page OFFSET $offset";
$result = $conn->query($query);

$results = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
}

// Get distinct categories for filter
$categories_list = [];
$cat_query = $conn->query("SELECT DISTINCT types FROM extracted_businesses WHERE types IS NOT NULL AND types != '' ORDER BY types LIMIT 30");
while ($row = $cat_query->fetch_assoc()) {
    $types_arr = explode(',', $row['types']);
    foreach ($types_arr as $type) {
        $type = trim($type);
        if (!empty($type) && !in_array($type, $categories_list)) {
            $categories_list[] = $type;
        }
    }
}
sort($categories_list);

// Get distinct cities
$cities_list = [];
$city_query = $conn->query("SELECT DISTINCT search_location FROM extracted_businesses WHERE search_location IS NOT NULL AND search_location != '' ORDER BY search_location");
while ($row = $city_query->fetch_assoc()) {
    $cities_list[] = $row['search_location'];
}

// SEO
$page_title = !empty($category_filter) ? "Best " . ucfirst($category_filter) . " in " . (!empty($city_filter) ? $city_filter : "India") : "Search Businesses";
$meta_description = "Find " . (!empty($category_filter) ? strtolower($category_filter) . " " : "") . "businesses in " . (!empty($city_filter) ? $city_filter : "India") . " with verified ratings and contact details.";
$meta_keywords = !empty($category_filter) ? strtolower($category_filter) . ", " . (!empty($city_filter) ? strtolower($city_filter) : "india") : "business directory";

include '../includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.search-page-wrapper {
    min-height: 100vh;
    background: #f9fafb;
    padding-bottom: 80px;
}

/* Sticky Search Bar */
.sticky-search-bar {
    position: sticky;
    top: 0;
    background: white;
    border-bottom: 2px solid #FF6A00;
    padding: 16px 0;
    z-index: 40;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.search-bar-content {
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 30px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.search-input-group {
    display: flex;
    gap: 10px;
    flex: 1;
    min-width: 300px;
}

.search-input-group input,
.search-input-group select {
    padding: 10px 14px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 13px;
    flex: 1;
    transition: all 0.3s;
}

.search-input-group input:focus,
.search-input-group select:focus {
    outline: none;
    border-color: #FF6A00;
    box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
}

.search-btn, .reset-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 13px;
    white-space: nowrap;
}

.search-btn {
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    color: white;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 106, 0, 0.3);
}

.reset-btn {
    background: #e0e0e0;
    color: #333;
}

.reset-btn:hover {
    background: #d0d0d0;
}

.toggle-filters-btn {
    display: none;
    background: #0B1C3D;
    color: white;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 700;
}

/* Main Container */
.search-container {
    max-width: 1300px;
    margin: 0 auto;
    padding: 30px;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
}

/* Sidebar Filters */
.filter-sidebar {
    background: white;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    height: fit-content;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.filter-section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid #f0f0f0;
}

.filter-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.filter-title {
    font-weight: 700;
    color: #0B1C3D;
    margin-bottom: 12px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-option {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    gap: 8px;
}

.filter-option input[type="checkbox"],
.filter-option input[type="radio"] {
    cursor: pointer;
    width: 18px;
    height: 18px;
    accent-color: #FF6A00;
}

.filter-option label {
    cursor: pointer;
    font-size: 13px;
    color: #555;
    flex: 1;
    user-select: none;
}

.filter-option input:checked + label {
    color: #0B1C3D;
    font-weight: 600;
}

.sort-dropdown {
    padding: 10px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 13px;
    width: 100%;
}

/* Results Section */
.results-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    background: white;
    padding: 20px 24px;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
}

.results-header h2 {
    font-size: 18px;
    color: #0B1C3D;
    margin: 0;
}

.sort-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sort-container label {
    font-size: 13px;
    color: #666;
    font-weight: 600;
}

.sort-container select {
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
}

/* Business Grid */
.business-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.business-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    position: relative;
}

.business-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #FF6A00, #FFB347);
}

.business-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
    border-color: #FF6A00;
}

.business-card.featured {
    grid-column: span 1;
    border-top: 3px solid #FFD700;
}

.business-content {
    padding: 20px;
    flex-grow: 1;
}

.business-name {
    font-size: 16px;
    font-weight: 700;
    color: #0B1C3D;
    margin-bottom: 8px;
    line-height: 1.3;
}

.business-badges {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.badge {
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    white-space: nowrap;
}

.badge-featured { background: #FFE082; color: #7D5A00; }
.badge-verified { background: #C8E6C9; color: #1B5E20; }
.badge-google { background: #4CAF50; color: white; }

.business-details {
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.detail-item {
    font-size: 12px;
    color: #666;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.detail-item strong {
    color: #FF6A00;
    min-width: 20px;
}

.rating-section {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 8px 0;
    padding: 8px 0;
    border-top: 1px solid #f0f0f0;
    border-bottom: 1px solid #f0f0f0;
}

.stars {
    font-weight: 700;
    color: #FFC107;
}

.review-count {
    font-size: 12px;
    color: #999;
}

.business-cta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.cta-btn {
    padding: 10px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.cta-call {
    background: #FF6A00;
    color: white;
    grid-column: 1 / 2;
}

.cta-call:hover {
    background: #E55A00;
    transform: translateY(-2px);
}

.cta-whatsapp {
    background: #25D366;
    color: white;
    grid-column: 2 / 3;
}

.cta-whatsapp:hover {
    background: #20BA58;
    transform: translateY(-2px);
}

.cta-details {
    background: linear-gradient(135deg, #0B1C3D, #1E3A8A);
    color: white;
    grid-column: 1 / -1;
    text-decoration: none;
    text-align: center;
}

.cta-details:hover {
    box-shadow: 0 4px 12px rgba(11, 28, 61, 0.3);
    transform: translateY(-2px);
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 40px;
    background: white;
    border-radius: 12px;
    border: 2px dashed #e0e0e0;
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.empty-state-title {
    font-size: 20px;
    color: #0B1C3D;
    margin-bottom: 8px;
}

.empty-state-text {
    color: #666;
    margin-bottom: 20px;
}

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 30px;
}

.pagination-btn {
    padding: 10px 16px;
    border: 1px solid #e0e0e0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 600;
    font-size: 13px;
}

.pagination-btn:hover:not(:disabled) {
    background: #FF6A00;
    color: white;
    border-color: #FF6A00;
}

.pagination-btn.active {
    background: #FF6A00;
    color: white;
    border-color: #FF6A00;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .search-container {
        grid-template-columns: 1fr;
    }

    .filter-sidebar {
        display: none;
        position: fixed;
        left: 0;
        top: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        padding: 0;
        border: none;
        border-radius: 0;
        z-index: 1000;
        overflow-y: auto;
        height: auto !important;
    }

    .filter-sidebar.show {
        display: block;
    }

    .filter-sidebar-content {
        background: white;
        padding: 20px;
        border-radius: 0;
        margin: 60px 20px 20px 20px;
        max-width: calc(100% - 40px);
    }

    .toggle-filters-btn {
        display: block;
    }

    .search-input-group {
        flex-direction: column;
        min-width: unset;
    }

    .business-grid {
        grid-template-columns: 1fr;
    }

    .business-cta {
        grid-template-columns: 1fr 1fr;
    }

    .cta-details {
        grid-column: 1 / -1;
    }

    .search-bar-content {
        flex-direction: column;
        gap: 10px;
    }

    .search-input-group {
        width: 100%;
    }

    .search-btn, .reset-btn {
        flex: 1;
    }

    .toggle-filters-btn {
        flex: 1;
    }
}

@media (max-width: 480px) {
    .search-container {
        padding: 20px 16px;
    }

    .search-bar-content {
        padding: 0 16px;
    }

    .results-header {
        padding: 16px;
    }

    .results-header h2 {
        font-size: 16px;
    }

    .sort-container {
        flex-direction: column;
        width: 100%;
        gap: 8px;
    }

    .sort-container select {
        width: 100%;
    }

    .business-card {
        margin-bottom: 0;
    }

    .empty-state {
        padding: 60px 20px;
    }
}
</style>

<div class="search-page-wrapper">
    <!-- Sticky Search Bar -->
    <div class="sticky-search-bar">
        <div class="search-bar-content">
            <form method="GET" style="display: flex; gap: 12px; flex: 1; align-items: center; flex-wrap: wrap; width: 100%;">
                <div class="search-input-group" style="flex: 1; min-width: 250px;">
                    <input type="text" name="keyword" placeholder="🔍 Business name or service..." value="<?php echo esc($keyword); ?>">
                    <select name="city">
                        <option value="">📍 All Cities</option>
                        <?php foreach ($cities_list as $city): ?>
                        <option value="<?php echo esc($city); ?>" <?php echo $city_filter === $city ? 'selected' : ''; ?>><?php echo esc($city); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="category">
                        <option value="">🏷️ All Categories</option>
                        <?php foreach ($categories_list as $cat): ?>
                        <option value="<?php echo esc($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>><?php echo esc($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="search-btn">🔍 Search</button>
                <a href="/pages/search-with-filters.php" class="reset-btn">↻ Reset</a>
            </form>
            <button class="toggle-filters-btn" onclick="document.querySelector('.filter-sidebar').classList.toggle('show'); document.querySelector('.filter-sidebar-content').classList.toggle('show');">⚙️ Filters</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="search-container">
        <!-- Filter Sidebar -->
        <div class="filter-sidebar">
            <div class="filter-sidebar-content">
                <form method="GET" onchange="this.submit()">
                    <input type="hidden" name="keyword" value="<?php echo esc($keyword); ?>">
                    <input type="hidden" name="city" value="<?php echo esc($city_filter); ?>">
                    <input type="hidden" name="category" value="<?php echo esc($category_filter); ?>">

                    <!-- Rating Filter -->
                    <div class="filter-section">
                        <div class="filter-title">⭐ Rating</div>
                        <div class="filter-option">
                            <input type="radio" id="rating_all" name="rating" value="0" <?php echo $rating_filter == 0 ? 'checked' : ''; ?>>
                            <label for="rating_all">All Ratings</label>
                        </div>
                        <div class="filter-option">
                            <input type="radio" id="rating_4" name="rating" value="4" <?php echo $rating_filter == 4 ? 'checked' : ''; ?>>
                            <label for="rating_4">4+ Stars</label>
                        </div>
                        <div class="filter-option">
                            <input type="radio" id="rating_3" name="rating" value="3" <?php echo $rating_filter == 3 ? 'checked' : ''; ?>>
                            <label for="rating_3">3+ Stars</label>
                        </div>
                    </div>

                    <!-- Verification Filter -->
                    <div class="filter-section">
                        <div class="filter-title">✔ Status</div>
                        <div class="filter-option">
                            <input type="checkbox" id="verified" name="verified" value="1" <?php echo $verified_only ? 'checked' : ''; ?>>
                            <label for="verified">Verified Only</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" id="featured" name="featured" value="1" <?php echo $featured_only ? 'checked' : ''; ?>>
                            <label for="featured">Featured Only</label>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section">
            <div class="results-header">
                <h2>📊 <?php echo $total_count; ?> Results Found</h2>
                <div class="sort-container">
                    <label>Sort:</label>
                    <select name="sort" onchange="window.location.href='/pages/search-with-filters.php?keyword=<?php echo urlencode($keyword); ?>&city=<?php echo urlencode($city_filter); ?>&category=<?php echo urlencode($category_filter); ?>&sort=' + this.value;">
                        <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Recommended</option>
                        <option value="rated" <?php echo $sort_by === 'rated' ? 'selected' : ''; ?>>Highest Rated</option>
                        <option value="reviews" <?php echo $sort_by === 'reviews' ? 'selected' : ''; ?>>Most Reviewed</option>
                        <option value="featured" <?php echo $sort_by === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                        <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>A to Z</option>
                    </select>
                </div>
            </div>

            <?php if (empty($results)): ?>
            <div class="business-grid">
                <div class="empty-state">
                    <div class="empty-state-icon">🔍</div>
                    <h3 class="empty-state-title">No Results Found</h3>
                    <p class="empty-state-text">Try adjusting your filters or search terms</p>
                </div>
            </div>
            <?php else: ?>
            <div class="business-grid">
                <?php foreach ($results as $index => $business): 
                    $is_featured = $business['is_featured'] && $index < 3;
                ?>
                <div class="business-card <?php echo $is_featured ? 'featured' : ''; ?>">
                    <div class="business-content">
                        <h3 class="business-name"><?php echo esc($business['name']); ?></h3>
                        
                        <div class="business-badges">
                            <?php if ($business['is_featured']): ?>
                            <span class="badge badge-featured">⭐ Featured</span>
                            <?php endif; ?>
                            <?php if ($business['verified']): ?>
                            <span class="badge badge-verified">✔ Verified</span>
                            <?php elseif (!$business['verified']): ?>
                            <span class="badge badge-google">By Google</span>
                            <?php endif; ?>
                        </div>

                        <div class="business-details">
                            <div class="detail-item">
                                <strong>🏷️</strong>
                                <span><?php echo esc(strtok($business['types'], ',')); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>📍</strong>
                                <span><?php echo esc($business['search_location']); ?></span>
                            </div>
                            <?php if (!empty($business['formatted_address'])): ?>
                            <div class="detail-item">
                                <strong>📌</strong>
                                <span><?php echo esc(substr($business['formatted_address'], 0, 50)) . '...'; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($business['rating']): ?>
                        <div class="rating-section">
                            <span class="stars">⭐ <?php echo number_format($business['rating'], 1); ?>/5</span>
                            <span class="review-count">(<?php echo $business['user_ratings_total'] ?? 0; ?> reviews)</span>
                        </div>
                        <?php endif; ?>

                        <div class="business-cta">
                            <?php if (!empty($business['formatted_phone_number'])): ?>
                            <a href="tel:<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>" class="cta-btn cta-call">📞 Call</a>
                            <?php endif; ?>
                            
                            <?php if (!empty($business['formatted_phone_number'])): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>?text=Hi, I'm interested in your services" target="_blank" rel="noopener" class="cta-btn cta-whatsapp">💬 Chat</a>
                            <?php endif; ?>
                            
                            <a href="/pages/business-detail.php?id=<?php echo $business['id']; ?>" class="cta-btn cta-details">View Details →</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <?php if ($page > 1): ?>
                <a href="/pages/search-with-filters.php?keyword=<?php echo urlencode($keyword); ?>&city=<?php echo urlencode($city_filter); ?>&category=<?php echo urlencode($category_filter); ?>&page=1" class="pagination-btn">« First</a>
                <a href="/pages/search-with-filters.php?keyword=<?php echo urlencode($keyword); ?>&city=<?php echo urlencode($city_filter); ?>&category=<?php echo urlencode($category_filter); ?>&page=<?php echo $page - 1; ?>" class="pagination-btn">‹ Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="/pages/search-with-filters.php?keyword=<?php echo urlencode($keyword); ?>&city=<?php echo urlencode($city_filter); ?>&category=<?php echo urlencode($category_filter); ?>&page=<?php echo $i; ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <a href="/pages/search-with-filters.php?keyword=<?php echo urlencode($keyword); ?>&city=<?php echo urlencode($city_filter); ?>&category=<?php echo urlencode($category_filter); ?>&page=<?php echo $page + 1; ?>" class="pagination-btn">Next ›</a>
                <a href="/pages/search-with-filters.php?keyword=<?php echo urlencode($keyword); ?>&city=<?php echo urlencode($city_filter); ?>&category=<?php echo urlencode($category_filter); ?>&page=<?php echo $total_pages; ?>" class="pagination-btn">Last »</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
