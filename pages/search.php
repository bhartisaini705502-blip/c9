<?php
/**
 * Search Results Page - Advanced Google Maps Style Search
 */

require '../config/db.php';
require '../includes/functions.php';

// Get search parameters and ensure they're strings
$business = isset($_GET['business']) ? trim($_GET['business']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0; // 0=all, 4=4+, 3=3+
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'verified'; // verified, rating, reviews
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

// Handle category - extract first category if comma-separated
if ($category && strpos($category, ',') !== false) {
    $parts = explode(',', $category);
    $category = trim($parts[0]);
}

// Use advanced search algorithm (already sorted by verified DESC, rating DESC in DB)
$all_results = searchBusinesses($business, $category, $location, 500);

// Apply rating filter
if ($rating_filter > 0) {
    $all_results = array_filter($all_results, function($item) use ($rating_filter) {
        return ($item['rating'] ?? 0) >= $rating_filter;
    });
}

// Apply custom sorting
switch ($sort) {
    case 'rating':
        usort($all_results, function($a, $b) {
            return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
        });
        break;
    case 'reviews':
        usort($all_results, function($a, $b) {
            return ($b['user_ratings_total'] ?? 0) <=> ($a['user_ratings_total'] ?? 0);
        });
        break;
    case 'verified':
    default:
        // Already sorted by DB
        break;
}

// Pagination
$total = count($all_results);
$perPage = 12;
$pagination = paginate($total, $perPage, $page);

// Get current page results
$results = array_slice($all_results, $pagination['offset'], $perPage);

// SEO
$page_title = 'Search Results';
if ($business) $page_title .= ' - ' . $business;
if ($category) $page_title .= ' - ' . $category;
if ($location) $page_title .= ' - ' . $location;

$meta_description = "Found $total businesses" . ($business ? " for '$business'" : "") . ". Browse and compare ratings.";

include '../includes/header.php';
?>

<!-- Smart Search Section -->
<section class="search-hero-section">
    <div class="container">
        <h1 class="search-hero-title">Search & Refine Your Results</h1>
        
        <!-- Smart Search Box -->
        <form class="smart-search-form" method="GET">
            <div class="search-row">
                <div class="search-input-group">
                    <input type="text" name="business" placeholder="Business name, service, or category..." value="<?php echo esc($business); ?>">
                </div>
                <div class="search-input-group">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php
                        $allCategories = [];
                        $catResult = $GLOBALS['conn']->query("SELECT DISTINCT types FROM extracted_businesses WHERE business_status = 'OPERATIONAL' LIMIT 100");
                        if ($catResult) {
                            while ($catRow = $catResult->fetch_assoc()) {
                                if (!empty($catRow['types'])) {
                                    $types = explode(',', $catRow['types']);
                                    foreach ($types as $type) {
                                        $type = trim($type);
                                        if ($type && !in_array($type, $allCategories)) {
                                            $allCategories[] = $type;
                                        }
                                    }
                                }
                            }
                        }
                        sort($allCategories);
                        foreach ($allCategories as $cat):
                        ?>
                            <option value="<?php echo esc($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo esc($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-input-group">
                    <select name="location">
                        <option value="">All Locations</option>
                        <?php
                        $allLocations = [];
                        $locResult = $GLOBALS['conn']->query("SELECT DISTINCT search_location FROM extracted_businesses WHERE business_status = 'OPERATIONAL' AND search_location IS NOT NULL ORDER BY search_location LIMIT 50");
                        if ($locResult) {
                            while ($locRow = $locResult->fetch_assoc()) {
                                if (!empty($locRow['search_location'])) {
                                    $allLocations[] = $locRow['search_location'];
                                }
                            }
                        }
                        foreach ($allLocations as $loc):
                        ?>
                            <option value="<?php echo esc($loc); ?>" <?php echo $location === $loc ? 'selected' : ''; ?>>
                                <?php echo esc($loc); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </div>

            <!-- Filter Options -->
            <div class="search-filters-row">
                <select name="rating" class="filter-select">
                    <option value="0" <?php echo $rating_filter == 0 ? 'selected' : ''; ?>>All Ratings</option>
                    <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>⭐ 4.0+ Only</option>
                    <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>⭐ 3.0+ Only</option>
                </select>
                
                <select name="sort" class="filter-select">
                    <option value="verified" <?php echo $sort == 'verified' ? 'selected' : ''; ?>>Verified First</option>
                    <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rating</option>
                    <option value="reviews" <?php echo $sort == 'reviews' ? 'selected' : ''; ?>>Most Reviews</option>
                </select>

                <?php if ($rating_filter > 0 || $sort != 'verified' || $business || $category || $location): ?>
                    <a href="/pages/search.php" class="btn-clear">✕ Clear All</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Results Info -->
        <div class="search-results-info">
            <span class="result-count">Found <strong><?php echo $total; ?></strong> businesses</span>
            <?php if ($business || $category || $location): ?>
                <span class="result-filters">
                    <?php if ($business): ?>
                        <span class="filter-tag">🔍 <?php echo esc($business); ?></span>
                    <?php endif; ?>
                    <?php if ($category): ?>
                        <span class="filter-tag">📂 <?php echo esc($category); ?></span>
                    <?php endif; ?>
                    <?php if ($location): ?>
                        <span class="filter-tag">📍 <?php echo esc($location); ?></span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container">

    <?php if (!empty($results)): ?>
        <div class="business-grid">
            <?php foreach ($results as $business): ?>
                <?php include '../pages/business-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['totalPages'] > 1): ?>
            <div class="pagination">
                <?php 
                $businessVal = is_array($business) ? '' : (string)$business;
                $categoryVal = is_array($category) ? '' : (string)$category;
                $locationVal = is_array($location) ? '' : (string)$location;
                $paginationParams = '&business=' . urlencode($businessVal) . '&category=' . urlencode($categoryVal) . '&location=' . urlencode($locationVal) . '&rating=' . $rating_filter . '&sort=' . urlencode($sort);
                ?>
                <?php if ($pagination['hasPrev']): ?>
                    <a href="?page=1<?php echo $paginationParams; ?>">« First</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $paginationParams; ?>">‹ Prev</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($pagination['totalPages'], $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo $paginationParams; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['hasNext']): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $paginationParams; ?>">Next ›</a>
                    <a href="?page=<?php echo $pagination['totalPages']; ?><?php echo $paginationParams; ?>">Last »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p style="text-align: center; padding: 40px; font-size: 18px;">
            No businesses found matching your search. Try different keywords or filters.
        </p>
    <?php endif; ?>
</div>

<?php include '../includes/claim-modal.php'; ?>
<?php include '../includes/footer.php'; ?>
