<?php
/**
 * Tracking Module - Log user searches and API interactions
 */

require_once dirname(__DIR__) . '/config/db.php';

/**
 * Log a search query
 */
function logSearch($search_query, $category = '', $city = '', $results_found = 0) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO search_logs (search_query, category, city, results_found, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param('sssi', $search_query, $category, $city, $results_found);
    return $stmt->execute();
}

/**
 * Log a Google API import
 */
function logImport($search_query, $category, $city, $records_fetched, $source = 'google') {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO import_logs (search_query, category, city, records_fetched, source, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param('sssss', $search_query, $category, $city, $records_fetched, $source);
    return $stmt->execute();
}

/**
 * Track business view
 */
function trackBusinessView($business_id) {
    global $conn;
    
    // Create stats row if not exists
    $conn->query("INSERT IGNORE INTO business_stats (business_id) VALUES ($business_id)");
    
    // Increment views
    $conn->query("UPDATE business_stats SET views = views + 1 WHERE business_id = $business_id");
    
    return true;
}

/**
 * Track business click
 */
function trackBusinessClick($business_id) {
    global $conn;
    
    $conn->query("INSERT IGNORE INTO business_stats (business_id) VALUES ($business_id)");
    $conn->query("UPDATE business_stats SET clicks = clicks + 1 WHERE business_id = $business_id");
    
    return true;
}

/**
 * Track phone call
 */
function trackPhoneCall($business_id) {
    global $conn;
    
    $conn->query("INSERT IGNORE INTO business_stats (business_id) VALUES ($business_id)");
    $conn->query("UPDATE business_stats SET calls = calls + 1 WHERE business_id = $business_id");
    
    return true;
}

/**
 * Track WhatsApp click
 */
function trackWhatsApp($business_id) {
    global $conn;
    
    $conn->query("INSERT IGNORE INTO business_stats (business_id) VALUES ($business_id)");
    $conn->query("UPDATE business_stats SET whatsapp_clicks = whatsapp_clicks + 1 WHERE business_id = $business_id");
    
    return true;
}
