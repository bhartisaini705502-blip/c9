<?php
/**
 * Admin - AI Blog Post Generator
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/ai-features.php';

if (!isAdmin()) {
    header('Location: /auth/login.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_trending_blogs'])) {
        $generated = generateTrendingBlogs();
        $success = "Generated $generated blog posts from trending searches!";
    } elseif (isset($_POST['generate_category_blogs'])) {
        $generated = generateCategoryBlogs();
        $success = "Generated $generated category guide blogs!";
    } elseif (isset($_POST['publish_blog'])) {
        $blogId = (int)$_POST['blog_id'];
        $GLOBALS['conn']->query("UPDATE blog_posts SET published_at = NOW() WHERE id = $blogId");
        $success = "Blog published successfully!";
    }
}

// Get stats
$totalBlogs = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM blog_posts")->fetch_assoc()['c'];
$publishedBlogs = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM blog_posts WHERE published_at IS NOT NULL")->fetch_assoc()['c'];
$draftBlogs = $totalBlogs - $publishedBlogs;

// Get recent blogs
$recentBlogs = $GLOBALS['conn']->query("
    SELECT id, title, category, published_at, views 
    FROM blog_posts 
    ORDER BY created_at DESC 
    LIMIT 15
")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Blog Generator';
include '../includes/header.php';
?>

<style>
.blog-gen-panel {
    max-width: 1100px;
    margin: 30px auto;
    padding: 0 20px;
}

.gen-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 8px;
}

.stat-label {
    color: #666;
    font-size: 13px;
}

.gen-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.gen-card h3 {
    margin-top: 0;
    color: #0B1C3D;
}

.gen-btn {
    padding: 12px 25px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.gen-btn:hover {
    background: #5568d3;
}

.gen-btn-success {
    background: #28a745;
}

.gen-btn-success:hover {
    background: #218838;
}

.success-msg {
    background: #D4EDDA;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #28a745;
}

.blog-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.blog-table th {
    background: #667eea;
    color: white;
    padding: 12px;
    text-align: left;
}

.blog-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.blog-table tr:hover {
    background: #F9F9F9;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-published {
    background: #D4EDDA;
    color: #155724;
}

.status-draft {
    background: #FFF3CD;
    color: #856404;
}
</style>

<div class="blog-gen-panel">
    <h1>📝 Blog Post Generator</h1>
    
    <?php if (!empty($success)): ?>
    <div class="success-msg"><?php echo esc($success); ?></div>
    <?php endif; ?>
    
    <div class="gen-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalBlogs; ?></div>
            <div class="stat-label">Total Blog Posts</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $publishedBlogs; ?></div>
            <div class="stat-label">Published</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $draftBlogs; ?></div>
            <div class="stat-label">Drafts</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php 
                $views = $GLOBALS['conn']->query("SELECT SUM(views) as v FROM blog_posts")->fetch_assoc()['v'];
                echo $views ?? 0;
            ?></div>
            <div class="stat-label">Total Views</div>
        </div>
    </div>
    
    <div class="gen-card">
        <h3>⚡ Generate Trending Blog Posts</h3>
        <p>Auto-generate blog posts from the top trending searches in your database.</p>
        <form method="POST">
            <button type="submit" name="generate_trending_blogs" class="gen-btn gen-btn-success">
                🚀 Generate Trending Posts
            </button>
        </form>
    </div>
    
    <div class="gen-card">
        <h3>📚 Generate Category Guide Blogs</h3>
        <p>Create comprehensive guide posts for popular business categories.</p>
        <form method="POST">
            <button type="submit" name="generate_category_blogs" class="gen-btn gen-btn-success">
                📖 Generate Category Guides
            </button>
        </form>
    </div>
    
    <div class="gen-card">
        <h2>📋 Recent Blog Posts</h2>
        <table class="blog-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentBlogs as $blog): ?>
                <tr>
                    <td><?php echo esc($blog['title']); ?></td>
                    <td><?php echo esc($blog['category']); ?></td>
                    <td>
                        <?php if ($blog['published_at']): ?>
                        <span class="status-badge status-published">Published</span>
                        <?php else: ?>
                        <span class="status-badge status-draft">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $blog['views']; ?></td>
                    <td>
                        <?php if (!$blog['published_at']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
                            <button type="submit" name="publish_blog" class="gen-btn" style="padding: 6px 12px; font-size: 12px;">Publish</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
function generateTrendingBlogs() {
    global $conn;
    
    $generated = 0;
    
    // Get top trending searches
    $trending = $conn->query("
        SELECT search_term, category, city, search_count 
        FROM trending_searches 
        ORDER BY search_count DESC 
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);
    
    foreach ($trending as $trend) {
        $slug = slugify($trend['search_term']);
        
        // Check if already exists
        $check = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
        $check->bind_param('s', $slug);
        $check->execute();
        
        if ($check->get_result()->num_rows === 0) {
            $title = "The Ultimate Guide to Finding the Best " . ucfirst($trend['search_term']);
            $category = $trend['category'] ?? 'General';
            $city = $trend['city'] ?? null;
            
            $content = generateBlogContent($trend['search_term'], $trend['category'] ?? 'Business', $city);
            $excerpt = "Discover the best " . $trend['search_term'] . " near you. Our comprehensive guide covers everything you need to know.";
            
            $stmt = $conn->prepare("
                INSERT INTO blog_posts (slug, title, excerpt, content, category, city) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('ssssss', $slug, $title, $excerpt, $content, $category, $city);
            
            if ($stmt->execute()) {
                $generated++;
            }
        }
    }
    
    return $generated;
}

function generateCategoryBlogs() {
    global $conn;
    
    $generated = 0;
    
    // Get top categories
    $categories = $conn->query("
        SELECT DISTINCT 
            TRIM(SUBSTRING_INDEX(types, ',', 1)) as category 
        FROM extracted_businesses 
        WHERE business_status = 'OPERATIONAL' AND types IS NOT NULL
        LIMIT 8
    ")->fetch_all(MYSQLI_ASSOC);
    
    foreach ($categories as $cat) {
        $category = $cat['category'] ?? 'Business';
        $slug = slugify("how-to-choose-best-" . $category);
        
        $check = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
        $check->bind_param('s', $slug);
        $check->execute();
        
        if ($check->get_result()->num_rows === 0) {
            $title = "How to Choose the Best " . ucfirst($category) . " - Complete Guide";
            $content = generateGuideContent($category);
            $excerpt = "Expert tips and recommendations for finding the perfect $category that matches your needs.";
            
            $stmt = $conn->prepare("
                INSERT INTO blog_posts (slug, title, excerpt, content, category) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssss', $slug, $title, $excerpt, $content, $category);
            
            if ($stmt->execute()) {
                $generated++;
            }
        }
    }
    
    return $generated;
}

function generateBlogContent($topic, $category, $city) {
    $cityStr = !empty($city) ? " in " . $city : " near you";
    
    return "
    <h2>The Ultimate Guide to Finding the Best $topic</h2>
    <p>Are you looking for quality $topic services? This comprehensive guide will help you find exactly what you need and make an informed decision.</p>
    
    <h3>What Makes a Great $category?</h3>
    <p>When searching for $topic, there are several key factors to consider:</p>
    <ul>
    <li><strong>Experience & Reputation:</strong> Look for businesses with proven track records and positive customer reviews</li>
    <li><strong>Quality of Service:</strong> Read detailed reviews to understand the quality standards</li>
    <li><strong>Customer Support:</strong> Reliable communication and responsive support teams</li>
    <li><strong>Value for Money:</strong> Compare prices and services to find the best value</li>
    <li><strong>Certifications & Credentials:</strong> Verify professional qualifications and certifications</li>
    </ul>
    
    <h3>How to Evaluate Your Options</h3>
    <p>Start by:</p>
    <ol>
    <li>Reading authentic customer reviews and ratings</li>
    <li>Checking for verified certifications and credentials</li>
    <li>Comparing pricing and service packages</li>
    <li>Contacting multiple providers for quotes</li>
    <li>Checking response times and customer service quality</li>
    </ol>
    
    <h3>Find the Best $topic$cityStr</h3>
    <p>Use our directory to discover top-rated $topic providers. Filter by rating, location, and specific services to find your perfect match.</p>
    
    <h3>Key Takeaways</h3>
    <ul>
    <li>Don't just go with the cheapest option</li>
    <li>Read multiple customer reviews</li>
    <li>Verify credentials and certifications</li>
    <li>Ask for references when possible</li>
    <li>Trust your instincts about customer service</li>
    </ul>
    ";
}

function generateGuideContent($category) {
    return "
    <h2>How to Choose the Best $category - Expert Guide</h2>
    <p>Finding the right $category can be challenging. This guide breaks down everything you need to know to make the best choice.</p>
    
    <h3>Understanding Your Needs</h3>
    <p>Before you start searching, define what you're looking for:</p>
    <ul>
    <li>Specific services or features you require</li>
    <li>Your budget range</li>
    <li>Location and convenience preferences</li>
    <li>Preferred business hours</li>
    </ul>
    
    <h3>Research & Comparison</h3>
    <p>Compare multiple options by checking:</p>
    <ul>
    <li>Customer ratings and reviews</li>
    <li>Service range and expertise</li>
    <li>Professional certifications</li>
    <li>Years of experience in the industry</li>
    </ul>
    
    <h3>Questions to Ask</h3>
    <p>Don't hesitate to ask potential $category providers:</p>
    <ul>
    <li>What's your experience with my specific needs?</li>
    <li>Can you provide references?</li>
    <li>What's your pricing structure?</li>
    <li>Do you offer any guarantees?</li>
    </ul>
    
    <h3>Making Your Decision</h3>
    <p>Consider:</p>
    <ul>
    <li>Overall reputation and ratings</li>
    <li>Quality vs. price balance</li>
    <li>Customer service responsiveness</li>
    <li>Gut feeling and comfort level</li>
    </ul>
    ";
}

function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}
?>

<?php include '../includes/footer.php'; ?>
