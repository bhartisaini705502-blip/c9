<?php
/**
 * Sitemap for Businesses (Paginated)
 * Access via: /pages/sitemap-businesses.xml?page=1
 */

header('Content-Type: application/xml; charset=utf-8');

require '../config/db.php';
require '../includes/functions.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

$page = intval($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$urlsPerPage = 5000;
$offset = ($page - 1) * $urlsPerPage;

// Get all operational businesses (paginated)
$businesses = getRows("SELECT id, name, updated_at FROM extracted_businesses WHERE business_status = 'OPERATIONAL' ORDER BY id ASC LIMIT " . $urlsPerPage . " OFFSET " . $offset) ?? [];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

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

echo '</urlset>' . "\n";
?>
