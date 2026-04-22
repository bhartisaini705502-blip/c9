<?php
/**
 * Database Migration: SEO Content & Blog Tables
 * Run once to create tables for programmatic SEO engine
 */

require_once 'db.php';

$migrations = [
    // SEO Content Cache Table
    "CREATE TABLE IF NOT EXISTS seo_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(255) UNIQUE NOT NULL,
        page_type VARCHAR(50) NOT NULL,
        category VARCHAR(100),
        city VARCHAR(100),
        keyword VARCHAR(255),
        title TEXT,
        description TEXT,
        content LONGTEXT,
        businesses_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_type_city (page_type, city),
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Blog Articles Table
    "CREATE TABLE IF NOT EXISTS seo_blogs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(255) UNIQUE NOT NULL,
        title VARCHAR(255) NOT NULL,
        content LONGTEXT,
        excerpt TEXT,
        featured_image VARCHAR(255),
        category VARCHAR(100),
        tags VARCHAR(255),
        seo_keywords VARCHAR(255),
        published BOOLEAN DEFAULT FALSE,
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_published (published),
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Internal Linking Table
    "CREATE TABLE IF NOT EXISTS seo_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_page VARCHAR(255),
        to_page VARCHAR(255),
        link_text VARCHAR(255),
        link_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_from (from_page),
        INDEX idx_to (to_page)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // SEO Page Analytics
    "CREATE TABLE IF NOT EXISTS seo_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(255),
        views INT DEFAULT 0,
        clicks INT DEFAULT 0,
        avg_position FLOAT,
        ctr FLOAT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

echo "🔄 Running SEO Content migrations...\n";

foreach ($migrations as $sql) {
    try {
        if ($conn->query($sql)) {
            echo "✅ Table created/verified\n";
        } else {
            echo "⚠️ Query error: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ SEO Content migrations complete!\n";
echo "\nTables created:\n";
echo "- seo_content (AI-generated page content cache)\n";
echo "- seo_blogs (Blog articles)\n";
echo "- seo_links (Internal linking network)\n";
echo "- seo_analytics (Page performance tracking)\n";
?>
