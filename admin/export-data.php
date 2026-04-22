<?php
/**
 * Admin: Export tracking data to CSV
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Access Denied');
}

$type = $_GET['type'] ?? 'searches';
$filename = 'data_' . $type . '_' . date('Y-m-d_H-i-s') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

if ($type === 'searches') {
    // Export search logs
    fputcsv($output, ['Search Query', 'Category', 'City', 'Results Found', 'Date']);
    
    $result = $GLOBALS['conn']->query("
        SELECT search_query, category, city, results_found, created_at
        FROM search_logs
        ORDER BY created_at DESC
        LIMIT 10000
    ");
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['search_query'],
            $row['category'],
            $row['city'],
            $row['results_found'],
            $row['created_at']
        ]);
    }
} elseif ($type === 'imports') {
    // Export import logs
    fputcsv($output, ['Search Query', 'Category', 'City', 'Records Fetched', 'Source', 'Date']);
    
    $result = $GLOBALS['conn']->query("
        SELECT search_query, category, city, records_fetched, source, created_at
        FROM import_logs
        ORDER BY created_at DESC
        LIMIT 10000
    ");
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['search_query'],
            $row['category'],
            $row['city'],
            $row['records_fetched'],
            $row['source'],
            $row['created_at']
        ]);
    }
} elseif ($type === 'stats') {
    // Export business stats
    fputcsv($output, ['Business ID', 'Business Name', 'Views', 'Clicks', 'Calls', 'WhatsApp Clicks', 'Last Updated']);
    
    $result = $GLOBALS['conn']->query("
        SELECT bs.business_id, eb.name, bs.views, bs.clicks, bs.calls, bs.whatsapp_clicks, bs.last_updated
        FROM business_stats bs
        LEFT JOIN extracted_businesses eb ON bs.business_id = eb.id
        ORDER BY bs.views DESC
        LIMIT 10000
    ");
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['business_id'],
            $row['name'] ?? 'Unknown',
            $row['views'],
            $row['clicks'],
            $row['calls'],
            $row['whatsapp_clicks'],
            $row['last_updated']
        ]);
    }
}

fclose($output);
exit;
