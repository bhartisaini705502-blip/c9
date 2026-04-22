<?php
/**
 * Daily AI Content Generation Cron Job
 * Runs daily to generate AI content for 50 businesses
 * Schedule: 0 2 * * * (Daily at 2 AM)
 */

require_once dirname(__DIR__) . '/config/db.php';

// Generate AI content for 50 businesses
$apiKey = getenv('ADMIN_API_KEY');

$url = 'http://localhost:5000/api/generate-ai-content.php?api_key=cron_' . urlencode($apiKey) . '&limit=50';

echo "[" . date('Y-m-d H:i:s') . "] Starting daily AI generation...\n";

$response = @file_get_contents($url);
if ($response) {
    $data = json_decode($response, true);
    echo "[" . date('Y-m-d H:i:s') . "] Result: " . ($data['message'] ?? 'Unknown') . "\n";
    echo "[" . date('Y-m-d H:i:s') . "] Generated: " . ($data['generated'] ?? 0) . " | Failed: " . ($data['failed'] ?? 0) . "\n";
} else {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to call API\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Daily AI generation completed.\n";
?>
