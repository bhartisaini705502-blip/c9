<?php
/**
 * Blog List Page
 */

require_once '../config/db.php';
require_once '../includes/functions.php';

$page_title = 'Business Blog | ConnectWith9';
$meta_description = 'Read latest articles about finding businesses, industry trends, and local recommendations.';

$category = $_GET['category'] ?? '';
$page = $_GET['page'] ?? 1;
$page = max(1, (int)$page);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$where = "published_at IS NOT NULL";
if (!empty($category)) {
    $where .= " AND category = '" . $GLOBALS['conn']->real_escape_string($category) . "'";
}
$total = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM blog_posts WHERE $where")->fetch_assoc()['c'];
$totalPages = ceil($total / $limit);

// Get blog posts
$query = "SELECT id, slug, title, excerpt, featured_image, category, created_at, views 
          FROM blog_posts 
          WHERE $where
          ORDER BY published_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $GLOBALS['conn']->prepare($query);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<style>
.blog-container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px 40px;
}

.blog-header {
    text-align: center;
    margin-bottom: 40px;
}

.blog-header h1 {
    color: #0B1C3D;
    font-size: 32px;
    margin: 0 0 10px 0;
}

.blog-header p {
    color: #666;
    font-size: 16px;
    margin: 0;
}

.blog-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.blog-filters a {
    padding: 8px 15px;
    background: <?php echo empty($category) ? '#667eea' : 'white'; ?>;
    color: <?php echo empty($category) ? 'white' : '#667eea'; ?>;
    border: 1px solid #667eea;
    border-radius: 20px;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s;
}

.blog-filters a:hover {
    background: #667eea;
    color: white;
}

.blog-posts {
    display: grid;
    gap: 25px;
}

.blog-post-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
    text-decoration: none;
    color: inherit;
    display: grid;
    grid-template-columns: 200px 1fr;
}

.blog-post-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    transform: translateY(-3px);
}

.blog-post-image {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: white;
}

.blog-post-content {
    padding: 20px;
    display: flex;
    flex-direction: column;
}

.blog-post-category {
    color: #FF6A00;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

.blog-post-title {
    color: #0B1C3D;
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 10px 0;
    line-height: 1.4;
}

.blog-post-excerpt {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    flex: 1;
    margin-bottom: 10px;
}

.blog-post-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #999;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 40px;
}

.pagination a, .pagination span {
    padding: 8px 12px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    text-decoration: none;
    color: #667eea;
}

.pagination .current {
    background: #667eea;
    color: white;
}

.pagination a:hover {
    background: #667eea;
    color: white;
}

.no-posts {
    text-align: center;
    padding: 40px;
    color: #999;
}
</style>

<div class="blog-container">
    <div class="blog-header">
        <h1>📰 Business Blog</h1>
        <p>Latest insights and trends about finding great businesses</p>
    </div>
    
    <div class="blog-filters">
        <a href="/pages/blog.php">📌 All Posts</a>
        <a href="/pages/blog.php?category=restaurant" <?php echo $category === 'restaurant' ? 'style="background:#667eea;color:white;"' : ''; ?>>Restaurants</a>
        <a href="/pages/blog.php?category=salon" <?php echo $category === 'salon' ? 'style="background:#667eea;color:white;"' : ''; ?>>Salons</a>
        <a href="/pages/blog.php?category=gym" <?php echo $category === 'gym' ? 'style="background:#667eea;color:white;"' : ''; ?>>Fitness</a>
    </div>
    
    <?php if (!empty($posts)): ?>
    <div class="blog-posts">
        <?php foreach ($posts as $post): ?>
        <a href="/pages/blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" class="blog-post-card">
            <div class="blog-post-image">📝</div>
            <div class="blog-post-content">
                <div class="blog-post-category"><?php echo esc($post['category']); ?></div>
                <h3 class="blog-post-title"><?php echo esc($post['title']); ?></h3>
                <p class="blog-post-excerpt"><?php echo esc(substr($post['excerpt'], 0, 150)); ?>...</p>
                <div class="blog-post-meta">
                    <span><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                    <span>👁 <?php echo $post['views']; ?> views</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="/pages/blog.php?page=<?php echo $i; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="no-posts">
        <h3>No blog posts yet</h3>
        <p>Check back soon for updates!</p>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
