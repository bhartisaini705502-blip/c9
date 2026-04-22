<?php
/**
 * Dynamic SEO Page Display
 */

require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['slug'])) {
    redirect('/');
}

$slug = $_GET['slug'];

// Get SEO page
$stmt = $GLOBALS['conn']->prepare("SELECT * FROM seo_pages WHERE slug = ?");
$stmt->bind_param('s', $slug);
$stmt->execute();
$result = $stmt->get_result();
$page = $result->fetch_assoc();

if (!$page) {
    redirect('/');
}

$page_title = $page['title'];
$meta_description = $page['meta_description'];

include '../includes/header.php';
?>

<style>
.seo-page-container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px 40px;
}

.seo-page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.seo-page-header h1 {
    margin: 0 0 15px 0;
    font-size: 32px;
}

.seo-page-header p {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
}

.seo-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    line-height: 1.8;
    color: #333;
    margin-bottom: 30px;
}

.seo-content h2 {
    color: #0B1C3D;
    margin: 25px 0 15px 0;
}

.seo-content h3 {
    color: #667eea;
    margin: 20px 0 10px 0;
}

.seo-content ul, .seo-content ol {
    margin: 15px 0;
    padding-left: 25px;
}

.seo-content li {
    margin: 8px 0;
}

.related-listings {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.related-listings h2 {
    color: #0B1C3D;
    margin: 0 0 20px 0;
}

.listings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.listing-card {
    background: #F9F9F9;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #eee;
    transition: all 0.3s;
    text-decoration: none;
    color: inherit;
}

.listing-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-3px);
}

.listing-card h4 {
    margin: 0 0 8px 0;
    color: #0B1C3D;
    font-size: 15px;
}

.listing-card .rating {
    color: #FFB800;
    font-size: 13px;
    margin-bottom: 8px;
}

.listing-card .category {
    color: #666;
    font-size: 12px;
}
</style>

<div class="seo-page-container">
    <div class="seo-page-header">
        <h1><?php echo esc($page['title']); ?></h1>
        <?php if ($page['category']): ?>
        <p><?php echo esc($page['category']); ?> in <?php echo esc($page['city']); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="seo-content">
        <?php echo $page['content']; ?>
    </div>
    
    <?php
    // Show related listings if this is a category page
    if ($page['page_type'] === 'category_city' && !empty($page['category']) && !empty($page['city'])):
        $listings = $GLOBALS['conn']->prepare("
            SELECT id, name, rating, user_ratings_total, types 
            FROM extracted_businesses 
            WHERE types LIKE ? AND search_location = ? AND business_status = 'OPERATIONAL'
            ORDER BY rating DESC LIMIT 6
        ");
        $searchType = '%' . $page['category'] . '%';
        $listings->bind_param('ss', $searchType, $page['city']);
        $listings->execute();
        $results = $listings->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (!empty($results)):
    ?>
    <div class="related-listings">
        <h2>📍 Top <?php echo ucfirst($page['category']); ?> in <?php echo esc($page['city']); ?></h2>
        <div class="listings-grid">
            <?php foreach ($results as $listing): 
                $types = array_map('trim', explode(',', $listing['types']));
            ?>
            <a href="/pages/business-detail.php?id=<?php echo $listing['id']; ?>&name=<?php echo urlencode(slugify($listing['name'])); ?>" class="listing-card">
                <h4><?php echo esc($listing['name']); ?></h4>
                <div class="rating">⭐ <?php echo number_format($listing['rating'], 1); ?>/5 (<?php echo $listing['user_ratings_total']; ?> reviews)</div>
                <div class="category"><?php echo esc($types[0]); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
