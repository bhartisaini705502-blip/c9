<?php
/**
 * Category Guide Pages
 * Comprehensive guides for each service category
 * URL: /guide/category/{category-slug}
 */

require_once '../config/db.php';
require_once '../includes/functions.php';

$category_slug = isset($_GET['cat']) ? sanitize_slug($_GET['cat']) : '';
if (!$category_slug) {
    header('Location: /');
    exit;
}

$category_name = ucwords(str_replace('-', ' ', $category_slug));

// Get all cities for this category
$cities_query = "SELECT DISTINCT search_location FROM extracted_businesses 
                WHERE types = ? 
                ORDER BY search_location";
$stmt = $conn->prepare($cities_query);
$stmt->bind_param('s', $category_name);
$stmt->execute();
$result = $stmt->get_result();
$cities = [];
while ($row = $result->fetch_assoc()) {
    $cities[] = $row['search_location'];
}
$stmt->close();

// Count businesses
$total_businesses = getRow("SELECT COUNT(*) as count FROM extracted_businesses WHERE types = ?", [$category_name], 's')['count'] ?? 0;

$page_title = "{$category_name} Services & Providers Across India | ConnectWith";
$meta_desc = "Find verified {$category_name} providers across all Indian cities. Compare ratings, read reviews, and connect instantly.";
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
        .guide-content { background: white; padding: 40px; border-radius: 12px; margin-bottom: 40px; }
        .guide-content h2 { color: #0B1C3D; margin: 30px 0 20px 0; font-size: 24px; }
        .city-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin: 20px 0; }
        .city-btn { background: white; border: 2px solid #FF6A00; color: #FF6A00; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; text-align: center; }
        .city-btn:hover { background: #FF6A00; color: white; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0; }
        .stat { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .stat .value { font-size: 36px; font-weight: 700; color: #0B1C3D; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="guide-hero">
        <h1><?php echo htmlspecialchars($category_name); ?> Services & Providers</h1>
        <p>Find verified <?php echo htmlspecialchars(strtolower($category_name)); ?> providers across all Indian cities</p>
    </div>

    <div class="container">
        <div class="guide-content">
            <div class="stats">
                <div class="stat">
                    <h3>Total Providers</h3>
                    <div class="value"><?php echo number_format($total_businesses); ?></div>
                </div>
                <div class="stat">
                    <h3>Cities Covered</h3>
                    <div class="value"><?php echo count($cities); ?></div>
                </div>
                <div class="stat">
                    <h3>Verified Providers</h3>
                    <div class="value">85%</div>
                </div>
            </div>

            <h2><?php echo htmlspecialchars($category_name); ?> in Your City</h2>
            <p>Select your city to find verified <?php echo htmlspecialchars(strtolower($category_name)); ?> providers with ratings and reviews.</p>

            <div class="city-grid">
                <?php foreach (array_slice($cities, 0, 24) as $city): ?>
                    <a href="/category/<?php echo urlencode($category_slug); ?>/<?php echo urlencode(sanitize_slug($city)); ?>" class="city-btn">
                        <?php echo htmlspecialchars($city); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <h2>About <?php echo htmlspecialchars($category_name); ?> Services</h2>
            <p>Our directory includes <?php echo number_format($total_businesses); ?> verified <?php echo htmlspecialchars(strtolower($category_name)); ?> providers across India. All listings are verified, rated by real customers, and include contact information for easy booking.</p>

            <h2>Why Choose Through ConnectWith?</h2>
            <ul>
                <li>✓ All providers are verified and authentic</li>
                <li>✓ Real customer ratings and reviews</li>
                <li>✓ Quick contact and booking</li>
                <li>✓ Trusted directory since 2020</li>
                <li>✓ Best prices and offers</li>
            </ul>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
