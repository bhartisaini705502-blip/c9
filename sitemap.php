<?php
/**
 * Dynamic XML Sitemap for SEO
 * Generates sitemap for all category-city combinations and individual businesses
 */

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: max-age=86400'); // Cache for 24 hours

require_once 'config/db.php';

$domain = 'https://' . $_SERVER['HTTP_HOST'];
$timestamp = date('c');

// Start XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Homepage
echo "  <url>\n";
echo "    <loc>{$domain}/</loc>\n";
echo "    <lastmod>{$timestamp}</lastmod>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>1.0</priority>\n";
echo "  </url>\n";

// Search pages
echo "  <url>\n";
echo "    <loc>{$domain}/pages/search-with-filters.php</loc>\n";
echo "    <lastmod>{$timestamp}</lastmod>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>0.9</priority>\n";
echo "  </url>\n";

// City guides
try {
    $result = $conn->query("SELECT DISTINCT search_location FROM extracted_businesses WHERE search_location IS NOT NULL LIMIT 500");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $city_slug = strtolower(str_replace(' ', '-', $row['search_location']));
            $url = "{$domain}/guide/city/{$city_slug}";
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
            echo "    <lastmod>{$timestamp}</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.75</priority>\n";
            echo "  </url>\n";
        }
    }
} catch (Exception $e) {}

// Category guides
try {
    $result = $conn->query("SELECT DISTINCT types FROM extracted_businesses WHERE types IS NOT NULL LIMIT 500");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cat_slug = strtolower(str_replace(' ', '-', $row['types']));
            $url = "{$domain}/guide/category/{$cat_slug}";
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
            echo "    <lastmod>{$timestamp}</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.75</priority>\n";
            echo "  </url>\n";
        }
    }
} catch (Exception $e) {}

// Get all unique category-city combinations
try {
    $result = $conn->query("
        SELECT DISTINCT 
            LOWER(REPLACE(types, ' ', '-')) as category_slug,
            LOWER(REPLACE(search_location, ' ', '-')) as city_slug,
            types,
            search_location
        FROM extracted_businesses 
        WHERE types IS NOT NULL 
        AND search_location IS NOT NULL 
        ORDER BY types, search_location
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['category_slug'] && $row['city_slug']) {
                $url = "{$domain}/category/{$row['category_slug']}/{$row['city_slug']}";
                echo "  <url>\n";
                echo "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
                echo "    <lastmod>{$timestamp}</lastmod>\n";
                echo "    <changefreq>weekly</changefreq>\n";
                echo "    <priority>0.8</priority>\n";
                echo "  </url>\n";
            }
        }
    }
} catch (Exception $e) {
    // Table doesn't exist, skip
}

// Get SEO pages (programmatic content)
try {
    $result = $conn->query("
        SELECT slug, updated_at 
        FROM seo_content 
        ORDER BY updated_at DESC
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $url = "{$domain}/seo/{$row['slug']}";
            $lastmod = date('c', strtotime($row['updated_at']));
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }
    }
} catch (Exception $e) {
    // Table doesn't exist, skip
}

// Get blog articles
try {
    $result = $conn->query("
        SELECT slug, updated_at 
        FROM seo_blogs 
        WHERE published = 1
        ORDER BY updated_at DESC
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $url = "{$domain}/blog/{$row['slug']}";
            $lastmod = date('c', strtotime($row['updated_at']));
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.5</priority>\n";
            echo "  </url>\n";
        }
    }
} catch (Exception $e) {
    // Table doesn't exist, skip
}

// Get individual business pages
try {
    $result = $conn->query("
        SELECT id, business_name, name 
        FROM extracted_businesses 
        ORDER BY id DESC 
        LIMIT 5000
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bid = intval($row['id']);
            $name = $row['business_name'] ?? $row['name'] ?? 'Business';
            $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $name));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            
            $url = "{$domain}/business/{$bid}-{$slug}";
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
            echo "    <lastmod>{$timestamp}</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
        }
    }
} catch (Exception $e) {
    // Table doesn't exist, skip
}

// Close XML
echo "</urlset>\n";
?>
