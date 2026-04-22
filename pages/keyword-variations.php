<?php
/**
 * Keyword Variation Pages
 * Targets multiple keyword variations: near me, top, best, cheap, services
 * URL Examples: 
 * - /pages/keyword-variations.php?type=near-me&category=restaurants&city=delhi
 * - /pages/keyword-variations.php?type=cheap&category=salons&city=mumbai
 */

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/ctr-optimization.php';

// Get parameters
$variation_type = isset($_GET['type']) ? sanitize_slug($_GET['type']) : 'best';
$category_slug = isset($_GET['category']) ? sanitize_slug($_GET['category']) : '';
$city_slug = isset($_GET['city']) ? sanitize_slug($_GET['city']) : '';

if (!$category_slug || !$city_slug) {
    header('Location: /');
    exit;
}

// Convert slugs to readable names
$category_name = ucwords(str_replace('-', ' ', $category_slug));
$city_name = ucwords(str_replace('-', ' ', $city_slug));

// Get businesses
$query = "SELECT * FROM extracted_businesses 
          WHERE LOWER(REPLACE(types, ' ', '')) = LOWER(REPLACE(?, ' ', ''))
          AND LOWER(search_location) = LOWER(?)
          ORDER BY is_featured DESC, verified DESC, rating DESC
          LIMIT 50";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $category_name, $city_name);
$stmt->execute();
$result = $stmt->get_result();
$businesses = [];
while ($row = $result->fetch_assoc()) {
    $businesses[] = $row;
}
$stmt->close();

// Generate optimized meta tags using CTR system
$page_title = CTROptimization::generateTitle($variation_type, $category_name, $city_name, count($businesses));
$meta_description = CTROptimization::generateDescription($variation_type, $category_name, $city_name, count($businesses));
$canonical_url = "https://" . $_SERVER['HTTP_HOST'] . "/kw/" . $variation_type . "/" . strtolower(str_replace(' ', '-', $category_name)) . "/" . strtolower(str_replace(' ', '-', $city_name));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars("{$variation_type} {$category_name}, {$city_name}, services, verified"); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/mega-menu.css">
    <style>
        .kw-hero { background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%); color: white; padding: 40px 20px; text-align: center; margin-bottom: 40px; }
        .kw-hero h1 { font-size: 42px; margin-bottom: 15px; font-weight: 700; }
        .business-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .business-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #FF6A00; transition: all 0.3s ease; }
        .business-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        .business-header { padding: 20px; }
        .business-name { font-size: 18px; font-weight: 600; color: #0B1C3D; margin-bottom: 8px; }
        .ctr-badge { display: inline-block; font-size: 11px; padding: 4px 8px; border-radius: 4px; margin-right: 6px; margin-bottom: 8px; font-weight: 600; }
        .badge-featured { background: #FFD700; color: #333; }
        .badge-verified { background: #25D366; color: white; }
        .badge-highly-rated { background: #FF6A00; color: white; }
        .badge-popular { background: #667eea; color: white; }
        .rating { font-size: 14px; color: #FF6A00; margin-bottom: 12px; }
        .business-footer { padding: 15px 20px; background: #f8f9fa; display: flex; gap: 10px; }
        .btn-small { flex: 1; padding: 10px; border-radius: 6px; text-decoration: none; text-align: center; font-weight: 600; font-size: 13px; transition: all 0.3s ease; }
        .btn-call { background: #25D366; color: white; }
        .btn-call:hover { opacity: 0.9; }
        .btn-view { background: #FF6A00; color: white; }
        .btn-view:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="kw-hero">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <p>Find trusted, verified <?php echo htmlspecialchars(strtolower($category_name)); ?> in <?php echo htmlspecialchars($city_name); ?></p>
    </div>

    <div class="container">
        <?php if (empty($businesses)): ?>
            <div style="text-align: center; padding: 60px 20px;">
                <p style="color: #999;">No businesses found for this search.</p>
            </div>
        <?php else: ?>
            <div class="business-grid">
                <?php foreach (array_slice($businesses, 0, 24) as $biz): ?>
                    <div class="business-card">
                        <div class="business-header">
                            <div class="business-name"><?php echo htmlspecialchars($biz['business_name'] ?? $biz['name']); ?></div>
                            <div class="badges">
                                <?php echo CTROptimization::renderBadges($biz); ?>
                            </div>
                            <div class="rating">⭐ <?php echo number_format(floatval($biz['rating'] ?? 0), 1); ?>/5 (<?php echo intval($biz['user_ratings_total'] ?? 0); ?>)</div>
                        </div>
                        <div class="business-footer">
                            <?php if ($biz['phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($biz['phone']); ?>" class="btn-small btn-call">📞 Call</a>
                            <?php endif; ?>
                            <a href="/pages/business-detail.php?id=<?php echo intval($biz['id']); ?>&name=<?php echo urlencode(slugify($biz['name'])); ?>" class="btn-small btn-view">View →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
