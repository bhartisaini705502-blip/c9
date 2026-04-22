<?php
/**
 * Google Indexing Ping Service
 * Automatically notifies Google when new pages are created
 * Speeds up indexing of new content
 */

function ping_google_sitemap() {
    $domain = $_SERVER['HTTP_HOST'] ?? 'connectwith9.in';
    $sitemap_url = "https://{$domain}/sitemap.php";
    
    $google_ping_url = "https://www.google.com/ping?sitemap=" . urlencode($sitemap_url);
    $bing_ping_url = "https://www.bing.com/ping?sitemap=" . urlencode($sitemap_url);
    
    // Ping Google
    @file_get_contents($google_ping_url);
    
    // Ping Bing
    @file_get_contents($bing_ping_url);
    
    return true;
}

function ping_google_url($url) {
    $ping_url = "https://www.google.com/webmasters/tools/ping?url=" . urlencode($url);
    @file_get_contents($ping_url);
    return true;
}

// Auto-ping when new SEO content is created
if (defined('AUTO_PING_GOOGLE') && AUTO_PING_GOOGLE) {
    register_shutdown_function('ping_google_sitemap');
}
?>
