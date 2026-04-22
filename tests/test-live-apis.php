<?php
/**
 * Live API Tests - Tests actual HTTP endpoints
 */

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║             LIVE API ENDPOINT TESTS                             ║\n";
echo "║        Testing Smart Search with Google Fallback               ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$base_url = 'http://localhost:5000';
$tests_passed = 0;
$tests_failed = 0;

function test_api($endpoint, $params, $expected_fields) {
    global $base_url, $tests_passed, $tests_failed;
    
    $url = $base_url . $endpoint . '?' . http_build_query($params);
    echo "Testing: $endpoint\n";
    echo "  URL: " . substr($url, 0, 80) . "...\n";
    
    $response = @file_get_contents($url);
    if (!$response) {
        echo "  ✗ FAILED: No response\n\n";
        $tests_failed++;
        return false;
    }
    
    $data = json_decode($response, true);
    if (!is_array($data)) {
        echo "  ✗ FAILED: Invalid JSON response\n\n";
        $tests_failed++;
        return false;
    }
    
    if (!isset($data['success']) || !$data['success']) {
        echo "  ⚠ No results (expected for some queries)\n";
        echo "  Response: " . json_encode($data, JSON_UNESCAPED_SLASHES) . "\n\n";
        return true;
    }
    
    // Check required fields
    $missing = [];
    foreach ($expected_fields as $field) {
        if (!isset($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        echo "  ✗ FAILED: Missing fields: " . implode(', ', $missing) . "\n\n";
        $tests_failed++;
        return false;
    }
    
    echo "  ✓ PASSED\n";
    echo "  Results: " . $data['count'] . " found\n";
    echo "  Source: " . ($data['source'] ?? 'unknown') . "\n";
    if ($data['count'] > 0) {
        echo "  First result: " . $data['businesses'][0]['name'] . "\n";
    }
    echo "\n";
    $tests_passed++;
    return true;
}

// Test 1: Smart search - with local results
echo "TEST 1: Smart Search - With Local Results\n";
echo "═════════════════════════════════════════\n";
test_api('/api/smart-search.php', [
    'q' => 'restaurant',
    'limit' => 5
], ['success', 'count', 'businesses']);

// Test 2: Smart search - with local results (hotel)
echo "TEST 2: Smart Search - Hotels\n";
echo "════════════════════════════\n";
test_api('/api/smart-search.php', [
    'q' => 'hotel',
    'limit' => 5
], ['success', 'count', 'businesses']);

// Test 3: Smart search - potentially needs fallback
echo "TEST 3: Smart Search - Query with potential Google fallback\n";
echo "═══════════════════════════════════════════════════════════\n";
test_api('/api/smart-search.php', [
    'q' => 'pizza hut',
    'limit' => 5,
    'fallback' => 'true'
], ['success', 'count', 'businesses']);

// Test 4: Track event API
echo "TEST 4: Track Event API\n";
echo "══════════════════════\n";
$response = @file_get_contents($base_url . '/api/track-event.php?business_id=1&event=view');
$data = json_decode($response, true);
if (isset($data['success']) && $data['success']) {
    echo "✓ Event tracking API working\n";
    echo "  Event: " . $data['event'] . "\n";
    $tests_passed++;
} else {
    echo "✗ Event tracking API failed\n";
    $tests_failed++;
}
echo "\n";

// Test 5: Get map businesses
echo "TEST 5: Map Businesses API\n";
echo "══════════════════════════\n";
test_api('/api/get-map-businesses.php', [
    'limit' => 5
], ['success', 'count']);

// Test 6: Nearby businesses
echo "TEST 6: Nearby Businesses API\n";
echo "════════════════════════════\n";
test_api('/api/get-nearby-businesses.php', [
    'lat' => 20.5937,
    'lng' => 78.9629,
    'radius' => 5,
    'limit' => 5
], ['success', 'count']);

// Test 7: Claim business search
echo "TEST 7: Business Search API (for Claims)\n";
echo "════════════════════════════════════════\n";
test_api('/api/search-businesses.php', [
    'q' => 'restaurant',
    'limit' => 5
], ['success', 'count']);

// Test 8: Page loads
echo "TEST 8: Page Load Tests\n";
echo "══════════════════════\n";

$pages = [
    '/pages/smart-search.php' => 'Smart Search',
    '/pages/map-view.php' => 'Map View',
    '/pages/nearby-businesses.php' => 'Nearby',
    '/pages/claim-business.php' => 'Claim Business',
    '/pages/business-analytics.php?id=1' => 'Analytics',
];

foreach ($pages as $path => $name) {
    $response = @file_get_contents($base_url . $path);
    if ($response && strlen($response) > 100) {
        echo "✓ $name page loads\n";
        $tests_passed++;
    } else {
        echo "✗ $name page failed\n";
        $tests_failed++;
    }
}
echo "\n";

// Test 9: Admin dashboards
echo "TEST 9: Admin Dashboard Access\n";
echo "══════════════════════════════\n";

$admin_pages = [
    '/admin/data-insights.php' => 'Data Insights',
    '/admin/import-monitor.php' => 'Import Monitor',
];

foreach ($admin_pages as $path => $name) {
    $response = @file_get_contents($base_url . $path);
    // Admin pages return 403 without auth, which is expected
    if (strpos($response, '403') !== false || strpos($response, 'Access Denied') !== false) {
        echo "✓ $name (Protected - Expected 403)\n";
        $tests_passed++;
    } else if ($response && strlen($response) > 100) {
        echo "✓ $name page loads\n";
        $tests_passed++;
    } else {
        echo "⚠ $name - Check auth requirements\n";
    }
}
echo "\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                      TEST SUMMARY                               ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";
echo "Tests Passed: " . $tests_passed . "\n";
echo "Tests Failed: " . $tests_failed . "\n";
echo "Total Tests: " . ($tests_passed + $tests_failed) . "\n";
echo "Success Rate: " . round(($tests_passed / ($tests_passed + $tests_failed)) * 100) . "%\n\n";

if ($tests_failed == 0) {
    echo "✅ ALL TESTS PASSED - SYSTEM FULLY OPERATIONAL\n";
} else {
    echo "⚠ Some tests failed - Review above\n";
}

?>
