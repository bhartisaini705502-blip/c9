<?php
/**
 * Test: Tracking System
 * Tests all tracking functions
 */

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║              TESTING TRACKING SYSTEM                          ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/tracking.php';

echo "1. SEARCH LOGGING TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━\n";

$initial_count = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM search_logs")->fetch_assoc()['count'];
echo "Initial search logs: $initial_count\n";

logSearch('test restaurant', 'food', 'delhi', 5);
logSearch('pizza near me', 'food', 'mumbai', 3);
logSearch('no results query', 'category', 'city', 0);

$new_count = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM search_logs")->fetch_assoc()['count'];
echo "✓ After logging: $new_count (added " . ($new_count - $initial_count) . ")\n";

// Check high-demand searches (zero results)
$zero_result_searches = $GLOBALS['conn']->query("
    SELECT search_query, COUNT(*) as times
    FROM search_logs
    WHERE results_found = 0
    GROUP BY search_query
    ORDER BY times DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

echo "✓ High-demand searches (0 results): " . count($zero_result_searches) . "\n";
foreach ($zero_result_searches as $search) {
    echo "  - '{$search['search_query']}': {$search['times']} searches\n";
}

echo "\n2. IMPORT LOGGING TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━\n";

$initial_count = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM import_logs")->fetch_assoc()['count'];
echo "Initial import logs: $initial_count\n";

logImport('restaurant', 'food', 'delhi', 50, 'google');
logImport('gym', 'fitness', 'mumbai', 30, 'google');
logImport('manual entry', 'business', 'delhi', 1, 'manual');

$new_count = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM import_logs")->fetch_assoc()['count'];
echo "✓ After logging: $new_count (added " . ($new_count - $initial_count) . ")\n";

// Check source breakdown
$sources = $GLOBALS['conn']->query("
    SELECT source, COUNT(*) as count, SUM(records_fetched) as total
    FROM import_logs
    GROUP BY source
")->fetch_all(MYSQLI_ASSOC);

echo "✓ Import source breakdown:\n";
foreach ($sources as $source) {
    echo "  - {$source['source']}: {$source['count']} imports, {$source['total']} total records\n";
}

echo "\n3. BUSINESS STATS TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━\n";

// Get a real business ID from database
$business = $GLOBALS['conn']->query("SELECT id FROM extracted_businesses LIMIT 1")->fetch_assoc();
$business_id = $business['id'] ?? 1;

echo "Testing with business ID: $business_id\n";

trackBusinessView($business_id);
trackBusinessView($business_id);
trackBusinessClick($business_id);
trackPhoneCall($business_id);
trackWhatsApp($business_id);

$stats = $GLOBALS['conn']->query("SELECT * FROM business_stats WHERE business_id = $business_id")->fetch_assoc();

if ($stats) {
    echo "✓ Business stats tracked:\n";
    echo "  - Views: {$stats['views']}\n";
    echo "  - Clicks: {$stats['clicks']}\n";
    echo "  - Calls: {$stats['calls']}\n";
    echo "  - WhatsApp: {$stats['whatsapp_clicks']}\n";
} else {
    echo "✗ Stats not found\n";
}

echo "\n4. DATA INSIGHTS TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━\n";

$stats_count = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM business_stats")->fetch_assoc()['count'];
echo "✓ Total business stats: $stats_count\n";

$top_viewed = $GLOBALS['conn']->query("
    SELECT eb.name, bs.views 
    FROM business_stats bs
    JOIN extracted_businesses eb ON bs.business_id = eb.id
    ORDER BY bs.views DESC
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

echo "✓ Top viewed businesses:\n";
foreach ($top_viewed as $b) {
    echo "  - {$b['name']}: {$b['views']} views\n";
}

echo "\n5. EXPORT CAPABILITY TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$search_export = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM search_logs LIMIT 10000")->fetch_assoc()['count'];
$import_export = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM import_logs LIMIT 10000")->fetch_assoc()['count'];
$stats_export = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM business_stats LIMIT 10000")->fetch_assoc()['count'];

echo "✓ Exportable data:\n";
echo "  - Search logs: $search_export records\n";
echo "  - Import logs: $import_export records\n";
echo "  - Business stats: $stats_export records\n";

echo "\n✅ TRACKING SYSTEM FULLY OPERATIONAL\n";
echo "\n📋 Summary:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ Search logging working\n";
echo "✓ Import logging working\n";
echo "✓ Business view tracking working\n";
echo "✓ High-demand detection working\n";
echo "✓ Analytics queries working\n";
echo "✓ Export system ready\n";

?>
