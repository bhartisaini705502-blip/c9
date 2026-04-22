<?php
/**
 * Individual Blog Post
 */

require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['slug'])) {
    redirect('/pages/blog.php');
}

$slug = $_GET['slug'];

// Get blog post
$stmt = $GLOBALS['conn']->prepare("SELECT * FROM blog_posts WHERE slug = ? AND published_at IS NOT NULL");
$stmt->bind_param('s', $slug);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    redirect('/pages/blog.php');
}

// Increment views
$GLOBALS['conn']->query("UPDATE blog_posts SET views = views + 1 WHERE id = " . $post['id']);

$page_title = $post['title'];
$meta_description = substr($post['excerpt'], 0, 155);

include '../includes/header.php';
?>

<style>
.blog-post-container {
    max-width: 800px;
    margin: 30px auto;
    padding: 0 20px 40px;
}

.blog-post-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.blog-post-category {
    color: #FF6A00;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.blog-post-header h1 {
    color: #0B1C3D;
    font-size: 32px;
    margin: 10px 0;
    line-height: 1.3;
}

.blog-post-meta {
    color: #999;
    font-size: 14px;
    display: flex;
    gap: 20px;
}

.blog-post-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    line-height: 1.8;
    color: #333;
    font-size: 15px;
}

.blog-post-content h2 {
    color: #0B1C3D;
    margin: 30px 0 15px 0;
}

.blog-post-content h3 {
    color: #667eea;
    margin: 20px 0 10px 0;
}

.blog-post-content ul, .blog-post-content ol {
    margin: 15px 0;
    padding-left: 25px;
}

.blog-post-content li {
    margin: 8px 0;
}

.blog-post-content p {
    margin: 15px 0;
}

.related-posts {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #eee;
}

.related-posts h3 {
    color: #0B1C3D;
    margin-bottom: 20px;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.related-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #eee;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
}

.related-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.related-card h4 {
    color: #0B1C3D;
    margin: 0 0 8px 0;
    font-size: 14px;
}

.related-card .date {
    color: #999;
    font-size: 12px;
}
</style>

<div class="blog-post-container">
    <article>
        <div class="blog-post-header">
            <div class="blog-post-category"><?php echo esc($post['category']); ?></div>
            <h1><?php echo esc($post['title']); ?></h1>
            <div class="blog-post-meta">
                <span><?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
                <span>👁 <?php echo $post['views']; ?> views</span>
            </div>
        </div>
        
        <div class="blog-post-content">
            <?php echo $post['content']; ?>
        </div>
    </article>
    
    <?php
    // Show related posts
    $related = $GLOBALS['conn']->query("
        SELECT slug, title, excerpt, created_at 
        FROM blog_posts 
        WHERE category = '" . $GLOBALS['conn']->real_escape_string($post['category']) . "' 
        AND id != " . $post['id'] . " 
        AND published_at IS NOT NULL
        ORDER BY published_at DESC 
        LIMIT 3
    ")->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($related)):
    ?>
    <div class="related-posts">
        <h3>📚 Related Articles</h3>
        <div class="related-grid">
            <?php foreach ($related as $relPost): ?>
            <a href="/pages/blog-post.php?slug=<?php echo urlencode($relPost['slug']); ?>" class="related-card">
                <h4><?php echo esc($relPost['title']); ?></h4>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 13px;"><?php echo esc(substr($relPost['excerpt'], 0, 80)); ?>...</p>
                <div class="date"><?php echo date('M d, Y', strtotime($relPost['created_at'])); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
