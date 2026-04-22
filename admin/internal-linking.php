<?php
/**
 * Admin - Internal Linking Engine
 */

require_once '../config/db.php';
require_once '../config/auth.php';

if (!isAdmin()) {
    header('Location: /auth/login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['regenerate_links'])) {
        $updated = regenerateInternalLinks();
        $success = "Updated internal links for $updated pages!";
    } elseif (isset($_POST['add_related_seo'])) {
        $updated = addRelatedLinksToBlogs();
        $success = "Added related links to $updated blog posts!";
    }
}

// Get stats
$pagesWithLinks = $GLOBALS['conn']->query("
    SELECT COUNT(*) as c FROM seo_pages 
    WHERE content LIKE '%href=%' AND content LIKE '%/pages/%'
")->fetch_assoc()['c'];

$totalSEOPages = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM seo_pages")->fetch_assoc()['c'];
$totalBlogs = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM blog_posts")->fetch_assoc()['c'];

$page_title = 'Internal Linking Engine';
include '../includes/header.php';
?>

<style>
.linking-panel {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px;
}

.linking-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-num {
    font-size: 32px;
    font-weight: bold;
    color: #FF6A00;
    margin-bottom: 5px;
}

.stat-text {
    color: #666;
    font-size: 13px;
}

.action-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.action-card h3 {
    margin-top: 0;
    color: #0B1C3D;
}

.action-btn {
    padding: 12px 25px;
    background: #FF6A00;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.action-btn:hover {
    background: #E55A00;
}

.success-banner {
    background: #D4EDDA;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #28a745;
}

.link-strategy {
    background: #F0F9FF;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #667eea;
}

.link-strategy h4 {
    margin: 0 0 8px 0;
    color: #0B1C3D;
}

.link-strategy p {
    margin: 0;
    color: #333;
    font-size: 13px;
    line-height: 1.5;
}
</style>

<div class="linking-panel">
    <h1>🔗 Internal Linking Engine</h1>
    
    <?php if (!empty($success)): ?>
    <div class="success-banner"><?php echo esc($success); ?></div>
    <?php endif; ?>
    
    <div class="linking-stats">
        <div class="stat-box">
            <div class="stat-num"><?php echo $totalSEOPages; ?></div>
            <div class="stat-text">SEO Pages</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?php echo $totalBlogs; ?></div>
            <div class="stat-text">Blog Posts</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?php echo $pagesWithLinks; ?></div>
            <div class="stat-text">Pages with Internal Links</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?php echo round(($pagesWithLinks / ($totalSEOPages + $totalBlogs)) * 100); ?>%</div>
            <div class="stat-text">Link Coverage</div>
        </div>
    </div>
    
    <div class="action-card">
        <h3>🔄 Regenerate Internal Links</h3>
        
        <div class="link-strategy">
            <h4>✓ SEO Page Linking Strategy</h4>
            <p>Automatically adds cross-links between related SEO pages (category pages link to other categories, location pages link to other locations)</p>
        </div>
        
        <div class="link-strategy">
            <h4>✓ Contextual Blog References</h4>
            <p>Inserts contextual links from blog posts to relevant SEO pages when topics match</p>
        </div>
        
        <div class="link-strategy">
            <h4>✓ Smart Anchor Text</h4>
            <p>Uses natural, SEO-friendly anchor text that matches user intent and page content</p>
        </div>
        
        <form method="POST" style="margin-top: 20px;">
            <button type="submit" name="regenerate_links" class="action-btn">
                🚀 Regenerate All Links
            </button>
        </form>
    </div>
    
    <div class="action-card">
        <h3>📚 Add Related Blog Links</h3>
        <p>Update blog posts with contextual links to relevant SEO pages and other blog posts.</p>
        
        <form method="POST" style="margin-top: 15px;">
            <button type="submit" name="add_related_seo" class="action-btn">
                🔗 Add Related Links
            </button>
        </form>
    </div>
    
    <div class="action-card">
        <h2>💡 How This Works</h2>
        <p><strong>Internal linking is crucial for SEO:</strong></p>
        <ul style="margin: 12px 0; padding-left: 20px;">
            <li>Helps search engines understand site structure and hierarchy</li>
            <li>Distributes page authority throughout your site</li>
            <li>Improves user navigation and reduces bounce rate</li>
            <li>Increases pages per session and time on site</li>
            <li>Helps establish topic clusters and semantic relevance</li>
        </ul>
        <p><strong>This tool automatically:</strong></p>
        <ul style="margin: 12px 0; padding-left: 20px;">
            <li>Creates contextual links between related pages</li>
            <li>Uses natural anchor text that matches keywords</li>
            <li>Avoids over-linking and keyword stuffing</li>
            <li>Links to pages with complementary content</li>
            <li>Preserves readability and user experience</li>
        </ul>
    </div>
</div>

<?php
function regenerateInternalLinks() {
    global $conn;
    $updated = 0;
    
    // Get all SEO pages
    $pages = $conn->query("
        SELECT id, slug, title, content, category, city 
        FROM seo_pages 
        LIMIT 100
    ")->fetch_all(MYSQLI_ASSOC);
    
    foreach ($pages as $page) {
        $newContent = $page['content'];
        
        // Get related pages (same category or city)
        $relatedStmt = $conn->prepare("
            SELECT slug, title 
            FROM seo_pages 
            WHERE (category = ? OR city = ?) 
            AND id != ? 
            AND slug IS NOT NULL
            LIMIT 5
        ");
        $relatedStmt->bind_param('ssi', $page['category'], $page['city'], $page['id']);
        $relatedStmt->execute();
        $relatedPages = $relatedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Add links to content if not already linked
        foreach ($relatedPages as $related) {
            $linkUrl = "/pages/seo-page.php?slug=" . $related['slug'];
            $linkText = $related['title'];
            $link = "<a href=\"$linkUrl\" title=\"$linkText\">$linkText</a>";
            
            // Only add if not already in content
            if (strpos($newContent, $linkUrl) === false) {
                // Add at the end before closing tags
                $newContent = str_replace('</p>', ' <a href="' . $linkUrl . '">' . $linkText . '</a></p>', $newContent, 1);
            }
        }
        
        // Update if changed
        if ($newContent !== $page['content']) {
            $updateStmt = $conn->prepare("UPDATE seo_pages SET content = ? WHERE id = ?");
            $updateStmt->bind_param('si', $newContent, $page['id']);
            if ($updateStmt->execute()) {
                $updated++;
            }
        }
    }
    
    return $updated;
}

function addRelatedLinksToBlogs() {
    global $conn;
    $updated = 0;
    
    $blogs = $conn->query("
        SELECT id, content, category, city 
        FROM blog_posts 
        WHERE published_at IS NOT NULL
        LIMIT 50
    ")->fetch_all(MYSQLI_ASSOC);
    
    foreach ($blogs as $blog) {
        $newContent = $blog['content'];
        
        // Find related SEO pages
        $relatedStmt = $conn->prepare("
            SELECT slug, title 
            FROM seo_pages 
            WHERE (category LIKE ? OR city = ?) 
            AND slug IS NOT NULL
            LIMIT 3
        ");
        $categoryLike = '%' . $blog['category'] . '%';
        $relatedStmt->bind_param('ss', $categoryLike, $blog['city']);
        $relatedStmt->execute();
        $relatedPages = $relatedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Add "Related Resources" section
        if (!empty($relatedPages) && strpos($newContent, 'Related Resources') === false) {
            $relatedLinksHTML = '<div style="background:#F0F9FF; padding:15px; border-radius:8px; margin-top:20px;"><h3>Related Resources</h3><ul>';
            
            foreach ($relatedPages as $related) {
                $linkUrl = "/pages/seo-page.php?slug=" . $related['slug'];
                $relatedLinksHTML .= '<li><a href="' . $linkUrl . '">' . $related['title'] . '</a></li>';
            }
            
            $relatedLinksHTML .= '</ul></div>';
            $newContent .= $relatedLinksHTML;
            
            // Update blog
            $updateStmt = $conn->prepare("UPDATE blog_posts SET content = ? WHERE id = ?");
            $updateStmt->bind_param('si', $newContent, $blog['id']);
            if ($updateStmt->execute()) {
                $updated++;
            }
        }
    }
    
    return $updated;
}
?>

<?php include '../includes/footer.php'; ?>
