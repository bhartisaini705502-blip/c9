<?php
/**
 * Sitemap Index
 * Lists all sitemaps for Google to crawl
 * Access via: /sitemap_index.xml or /pages/sitemap-index.xml.php
 */

header('Content-Type: application/xml; charset=utf-8');

require '../config/db.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Main pages sitemap
echo "  <sitemap>\n";
echo "    <loc>" . htmlspecialchars($baseUrl . '/pages/sitemap-pages.xml') . "</loc>\n";
echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
echo "  </sitemap>\n";

// Calculate number of business sitemaps needed (50,000 URLs max per sitemap)
$totalBusinesses = $GLOBALS['conn']->query("SELECT COUNT(*) as cnt FROM extracted_businesses WHERE business_status = 'OPERATIONAL'")->fetch_assoc()['cnt'];
$urlsPerSitemap = 5000; // Conservative limit to stay well under 50k
$totalSitemaps = ceil($totalBusinesses / $urlsPerSitemap);

// Add business sitemaps
for ($i = 1; $i <= $totalSitemaps; $i++) {
    echo "  <sitemap>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . '/pages/sitemap-businesses.xml?page=' . $i) . "</loc>\n";
    echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    echo "  </sitemap>\n";
}

echo '</sitemapindex>' . "\n";
?>
