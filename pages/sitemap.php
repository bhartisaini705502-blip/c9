<?php
/**
 * Dynamic Sitemap Generation
 * Access via: /sitemap.php or /sitemap.xml
 */

header('Content-Type: application/xml; charset=utf-8');

require '../config/db.php';
require '../includes/functions.php';

// Build base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Add homepage
echo "  <url>\n";
echo "    <loc>" . htmlspecialchars($baseUrl . '/') . "</loc>\n";
echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>1.0</priority>\n";
echo "  </url>\n";

// Add static pages
$staticPages = ['categories.php', 'search-with-filters.php'];
foreach ($staticPages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . '/pages/' . $page) . "</loc>\n";
    echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

// Add all businesses - Clean URLs with SEO-friendly format
$businesses = getRows("SELECT id, name, updated_at FROM extracted_businesses WHERE business_status = 'OPERATIONAL' LIMIT 1000") ?? [];

foreach ($businesses as $business) {
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($business['name']));
    $slug = trim($slug, '-');
    $url = $baseUrl . '/business/' . $business['id'] . '-' . $slug;
    $lastmod = date('Y-m-d', strtotime($business['updated_at'] ?? 'now'));
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

// Add category-city pages (SEO Gold)
$categories_result = $GLOBALS['conn']->query("SELECT DISTINCT types FROM extracted_businesses WHERE types IS NOT NULL LIMIT 50");
$cities_result = $GLOBALS['conn']->query("SELECT DISTINCT search_location FROM extracted_businesses WHERE search_location IS NOT NULL LIMIT 50");

$categories = [];
$cities = [];

if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = trim(explode(',', $row['types'])[0]);
    }
}

if ($cities_result) {
    while ($row = $cities_result->fetch_assoc()) {
        $cities[] = $row['search_location'];
    }
}

foreach ($categories as $category) {
    foreach ($cities as $city) {
        $cat_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($category));
        $city_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($city));
        $url = $baseUrl . '/category/' . trim($cat_slug, '-') . '/' . trim($city_slug, '-');
        
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
        echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";
    }
}

// Add guide pages
foreach ($categories as $category) {
    $cat_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($category));
    $url = $baseUrl . '/guide/category/' . trim($cat_slug, '-');
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.75</priority>\n";
    echo "  </url>\n";
}

foreach ($cities as $city) {
    $city_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($city));
    $url = $baseUrl . '/guide/city/' . trim($city_slug, '-');
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.75</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
?>
