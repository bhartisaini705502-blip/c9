<?php
/**
 * Dynamic Category-City Page for SEO
 * URL: /category/{category-slug}/{city-slug}
 */

require_once '../config/db.php';
require_once '../includes/functions.php';

// Get parameters from URL
$category_slug = isset($_GET['category']) ? sanitize_slug($_GET['category']) : '';
$city_slug = isset($_GET['city']) ? sanitize_slug($_GET['city']) : '';

if (!$category_slug || !$city_slug) {
    header('Location: /');
    exit;
}

// Decode slugs back to readable form
$category_name = str_replace('-', ' ', $category_slug);
$city_name = str_replace('-', ' ', $city_slug);
$category_name = ucwords($category_name);
$city_name = ucwords($city_name);

// Get businesses for this category and city
$query = "SELECT * FROM extracted_businesses 
          WHERE LOWER(REPLACE(types, ' ', '-')) = LOWER(?) 
          AND LOWER(REPLACE(search_location, ' ', '-')) = LOWER(?) 
          ORDER BY is_featured DESC, verified DESC, rating DESC 
          LIMIT 100";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param('ss', $category_slug, $city_slug);
$stmt->execute();
$result = $stmt->get_result();
$businesses = [];
while ($row = $result->fetch_assoc()) {
    $businesses[] = $row;
}
$stmt->close();

// Get related cities and categories for internal linking
$related_cities_query = "SELECT DISTINCT search_location FROM extracted_businesses 
                        WHERE LOWER(REPLACE(types, ' ', '-')) = LOWER(?) 
                        AND LOWER(search_location) != LOWER(?) 
                        ORDER BY search_location LIMIT 5";
$stmt2 = $conn->prepare($related_cities_query);
$stmt2->bind_param('ss', $category_slug, $city_name);
$stmt2->execute();
$related_cities_result = $stmt2->get_result();
$related_cities = [];
while ($row = $related_cities_result->fetch_assoc()) {
    $related_cities[] = $row;
}
$stmt2->close();

// Dynamic SEO Meta Tags
$page_title = "Best {$category_name} in {$city_name} | ConnectWith9";
$meta_description = "Find top {$category_name} services in {$city_name}. Explore verified businesses, ratings, and contact instantly with ConnectWith9.";
$canonical_url = "https://" . $_SERVER['HTTP_HOST'] . "/category/" . $category_slug . "/" . $city_slug;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars("{$category_name}, {$city_name}, services, businesses"); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    
    <!-- Schema Markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "<?php echo htmlspecialchars($page_title); ?>",
        "description": "<?php echo htmlspecialchars($meta_description); ?>",
        "url": "<?php echo htmlspecialchars($canonical_url); ?>",
        "breadcrumb": {
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Home",
                    "item": "https://<?php echo $_SERVER['HTTP_HOST']; ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "<?php echo htmlspecialchars($category_name); ?>",
                    "item": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/category/<?php echo htmlspecialchars($category_slug); ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "<?php echo htmlspecialchars($city_name); ?>",
                    "item": "<?php echo htmlspecialchars($canonical_url); ?>"
                }
            ]
        },
        "mainEntity": {
            "@type": "LocalBusiness",
            "areaServed": "<?php echo htmlspecialchars($city_name); ?>",
            "priceRange": "$",
            "serviceType": "<?php echo htmlspecialchars($category_name); ?>"
        },
        "aggregateOffer": {
            "@type": "AggregateOffer",
            "priceCurrency": "INR",
            "availability": "https://schema.org/InStock"
        }
    }
    </script>

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/mega-menu.css">
    <style>
        .seo-hero {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 40px;
        }

        .seo-hero h1 {
            font-size: 42px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .seo-hero p {
            font-size: 18px;
            opacity: 0.95;
            max-width: 800px;
            margin: 0 auto;
        }

        .business-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .business-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #FF6A00;
        }

        .business-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .business-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .business-name {
            font-size: 18px;
            font-weight: 600;
            color: #0B1C3D;
            margin-bottom: 8px;
        }

        .business-badges {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-featured {
            background: #FFD700;
            color: #333;
        }

        .badge-verified {
            background: #25D366;
            color: white;
        }

        .rating {
            font-size: 14px;
            color: #FF6A00;
            margin-bottom: 12px;
        }

        .business-details {
            padding: 15px 20px;
            font-size: 14px;
            color: #666;
        }

        .business-detail-item {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .business-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            gap: 10px;
        }

        .btn-small {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .btn-call {
            background: #25D366;
            color: white;
        }

        .btn-call:hover {
            background: #1fa955;
        }

        .btn-view {
            background: #FF6A00;
            color: white;
        }

        .btn-view:hover {
            background: #E55A00;
        }

        .related-section {
            background: #f5f7fa;
            padding: 40px 20px;
            border-radius: 12px;
            margin-bottom: 40px;
        }

        .related-section h2 {
            color: #0B1C3D;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .related-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .related-link {
            background: white;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            color: #FF6A00;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid #FF6A00;
        }

        .related-link:hover {
            background: #FF6A00;
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-results h2 {
            color: #0B1C3D;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="seo-hero">
        <h1>Top <?php echo htmlspecialchars($category_name); ?> in <?php echo htmlspecialchars($city_name); ?></h1>
        <p>Find trusted, verified <?php echo htmlspecialchars(strtolower($category_name)); ?> services in <?php echo htmlspecialchars($city_name); ?>. Compare ratings, reviews, and contact instantly.</p>
    </div>

    <div class="container">
        <?php if (empty($businesses)): ?>
            <div class="no-results">
                <h2>No businesses found</h2>
                <p>We couldn't find any <?php echo htmlspecialchars(strtolower($category_name)); ?> services in <?php echo htmlspecialchars($city_name); ?> at the moment.</p>
                <p><a href="/pages/search-with-filters.php" style="color: #FF6A00; text-decoration: none; font-weight: 600;">← Browse all services</a></p>
            </div>
        <?php else: ?>
            <div class="business-grid">
                <?php foreach ($businesses as $biz): ?>
                    <div class="business-card">
                        <div class="business-header">
                            <div class="business-name"><?php echo htmlspecialchars($biz['business_name'] ?? $biz['name'] ?? 'N/A'); ?></div>
                            
                            <div class="business-badges">
                                <?php if ($biz['is_featured']): ?>
                                    <span class="badge badge-featured">⭐ Featured</span>
                                <?php endif; ?>
                                <?php if ($biz['verified']): ?>
                                    <span class="badge badge-verified">✓ Verified</span>
                                <?php endif; ?>
                            </div>

                            <div class="rating">
                                ⭐ <?php echo number_format(floatval($biz['rating'] ?? 0), 1); ?>/5
                                <?php if ($biz['user_ratings_total'] ?? 0): ?>
                                    (<?php echo intval($biz['user_ratings_total']); ?> reviews)
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="business-details">
                            <?php if ($biz['address'] ?? null): ?>
                                <div class="business-detail-item">
                                    📍 <?php echo htmlspecialchars(substr($biz['address'], 0, 40)); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($biz['phone'] ?? null): ?>
                                <div class="business-detail-item">
                                    📞 <?php echo htmlspecialchars($biz['phone']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="business-footer">
                            <?php if ($biz['phone'] ?? null): ?>
                                <a href="tel:<?php echo htmlspecialchars($biz['phone']); ?>" class="btn-small btn-call">📞 Call</a>
                            <?php endif; ?>
                            <a href="/pages/business-detail.php?id=<?php echo intval($biz['id']); ?>&name=<?php echo urlencode(slugify($biz['name'])); ?>" class="btn-small btn-view">View Profile →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Related Cities Section -->
        <?php if (!empty($related_cities)): ?>
            <div class="related-section">
                <h2>Also check <?php echo htmlspecialchars($category_name); ?> in nearby cities</h2>
                <div class="related-links">
                    <?php foreach ($related_cities as $city): ?>
                        <a href="/category/<?php echo urlencode(strtolower(str_replace(' ', '-', $category_slug))); ?>/<?php echo urlencode(strtolower(str_replace(' ', '-', $city['search_location']))); ?>" class="related-link">
                            → <?php echo htmlspecialchars($city['search_location']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
