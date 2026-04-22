<?php
/**
 * City Guide Pages
 * Comprehensive guides for each city with all services
 * URL: /guide/city/{city-slug}
 */

require_once '../config/db.php';
require_once '../includes/functions.php';

$city_slug = isset($_GET['city']) ? sanitize_slug($_GET['city']) : '';
if (!$city_slug) {
    header('Location: /');
    exit;
}

$city_name = ucwords(str_replace('-', ' ', $city_slug));

// Get all categories in this city
$categories_query = "SELECT DISTINCT types FROM extracted_businesses 
                   WHERE search_location = ? 
                   ORDER BY types";
$stmt = $conn->prepare($categories_query);
$stmt->bind_param('s', $city_name);
$stmt->execute();
$result = $stmt->get_result();
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['types'];
}
$stmt->close();

// Count businesses
$total_businesses = getRow("SELECT COUNT(*) as count FROM extracted_businesses WHERE search_location = ?", [$city_name], 's')['count'] ?? 0;

$page_title = "Complete Business Guide to {$city_name} | All Services & Categories";
$meta_desc = "Your complete guide to {$city_name}. Find all types of services, categories, and verified businesses in {$city_name}.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .guide-hero { background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%); color: white; padding: 50px 20px; text-align: center; margin-bottom: 40px; }
        .guide-hero h1 { font-size: 48px; margin-bottom: 15px; }
        .guide-hero p { font-size: 18px; opacity: 0.95; }
        .guide-content { background: white; padding: 40px; border-radius: 12px; margin-bottom: 40px; }
        .guide-content h2 { color: #0B1C3D; margin: 30px 0 20px 0; font-size: 24px; }
        .category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .category-btn { background: white; border: 2px solid #FF6A00; color: #FF6A00; padding: 15px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; text-align: center; }
        .category-btn:hover { background: #FF6A00; color: white; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0; }
        .stat { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .stat h3 { color: #666; margin: 0; font-size: 14px; }
        .stat .value { font-size: 36px; font-weight: 700; color: #0B1C3D; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="guide-hero">
        <h1><?php echo htmlspecialchars($city_name); ?> Business Guide</h1>
        <p>Your complete directory of services and businesses in <?php echo htmlspecialchars($city_name); ?></p>
    </div>

    <div class="container">
        <div class="guide-content">
            <div class="stats">
                <div class="stat">
                    <h3>Total Businesses</h3>
                    <div class="value"><?php echo number_format($total_businesses); ?></div>
                </div>
                <div class="stat">
                    <h3>Categories</h3>
                    <div class="value"><?php echo count($categories); ?></div>
                </div>
                <div class="stat">
                    <h3>Verified Providers</h3>
                    <div class="value">1,000+</div>
                </div>
            </div>

            <h2>Service Categories in <?php echo htmlspecialchars($city_name); ?></h2>
            <p>Explore all types of services available in <?php echo htmlspecialchars($city_name); ?>. Click any category to view verified providers.</p>

            <div class="category-grid">
                <?php foreach (array_slice($categories, 0, 15) as $cat): ?>
                    <a href="/category/<?php echo urlencode(sanitize_slug($cat)); ?>/<?php echo urlencode($city_slug); ?>" class="category-btn">
                        <?php echo htmlspecialchars($cat); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <h2>About <?php echo htmlspecialchars($city_name); ?></h2>
            <p><?php echo htmlspecialchars($city_name); ?> has a vibrant business community with thousands of service providers. Whether you're looking for restaurants, salons, professionals, or any other service, our directory helps you find verified, rated businesses in your area.</p>

            <h2>How to Use This Guide</h2>
            <ol>
                <li>Browse service categories above</li>
                <li>Click a category to see all providers</li>
                <li>Check ratings and reviews</li>
                <li>Contact your preferred provider</li>
            </ol>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
