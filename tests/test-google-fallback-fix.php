<?php
/**
 * Test: Google Fallback Fix
 * Verify View Details works for both local and Google results
 */

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║        TESTING GOOGLE PLACES VIEW DETAILS FIX                ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/google-api.php';

// Test 1: Check if Google business detail page exists
echo "1. GOOGLE BUSINESS DETAIL PAGE TEST\n";
echo "════════════════════════════════════\n";
if (file_exists(dirname(__DIR__) . '/pages/google-business-detail.php')) {
    echo "✓ google-business-detail.php exists\n";
} else {
    echo "✗ google-business-detail.php NOT found\n";
}

// Test 2: Check smart search API response
echo "\n2. SMART SEARCH API RESPONSE TEST\n";
echo "═════════════════════════════════\n";

// Query that should return Google results
$url = 'http://localhost:5000/api/smart-search.php?q=pizza%20hut&fallback=true&limit=5';
$response = @file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['success']) && $data['success']) {
    echo "✓ Smart Search API responds\n";
    echo "  Source: " . ($data['source'] ?? 'unknown') . "\n";
    echo "  Results: " . $data['count'] . "\n";
    
    if (!empty($data['businesses'])) {
        $first = $data['businesses'][0];
        echo "\n  First result:\n";
        echo "    Name: " . $first['name'] . "\n";
        echo "    Source: " . ($first['source'] ?? 'unknown') . "\n";
        
        // Check if Google result has detail_url
        if ($first['source'] === 'google') {
            if (isset($first['detail_url'])) {
                echo "    ✓ detail_url provided: " . substr($first['detail_url'], 0, 50) . "...\n";
            } else if (isset($first['place_id'])) {
                echo "    ✓ place_id available: " . $first['place_id'] . "\n";
            } else {
                echo "    ✗ No detail_url or place_id\n";
            }
        }
    }
} else {
    echo "✗ Smart Search API failed\n";
}

// Test 3: Check local results still work
echo "\n3. LOCAL RESULTS TEST\n";
echo "═════════════════════════════════\n";

$url = 'http://localhost:5000/api/smart-search.php?q=restaurant&limit=3';
$response = @file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['success']) && $data['success']) {
    echo "✓ Local search works\n";
    echo "  Source: " . ($data['source'] ?? 'unknown') . "\n";
    echo "  Results: " . $data['count'] . "\n";
    
    if (!empty($data['businesses'])) {
        $first = $data['businesses'][0];
        if (isset($first['id']) && $first['id'] !== null) {
            echo "  ✓ First result has numeric ID: " . $first['id'] . "\n";
        }
    }
} else {
    echo "✗ Local search failed\n";
}

// Test 4: Page loads
echo "\n4. PAGE LOAD TESTS\n";
echo "═════════════════════════════════\n";

$pages = [
    'smart-search.php' => '/pages/smart-search.php',
    'google-business-detail.php' => '/pages/google-business-detail.php?place_id=ChIJq6qq5ZiN0wkRv-',
    'business-detail.php' => '/pages/business-detail.php?id=1'
];

foreach ($pages as $name => $path) {
    $response = @file_get_contents('http://localhost:5000' . $path);
    if ($response && strlen($response) > 100) {
        echo "✓ $name loads\n";
    } else {
        echo "✗ $name failed\n";
    }
}

echo "\n✅ GOOGLE FALLBACK FIX VERIFICATION COMPLETE\n";
