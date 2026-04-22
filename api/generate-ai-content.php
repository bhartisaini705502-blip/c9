<?php
/**
 * AI Content Generation API
 * Generates short summaries, full descriptions, and tags using Gemini API
 * Stores results in database to avoid regeneration
 */

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Only allow admin/cron access
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if ($api_key !== getenv('ADMIN_API_KEY') && $api_key !== 'cron_' . getenv('ADMIN_API_KEY')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 50);
$limit = min($limit, 100); // Max 100 at a time

try {
    // Get businesses without AI content
    $query = "SELECT id, name, types, vicinity, rating 
              FROM extracted_businesses 
              WHERE ai_generated = 0 
              AND business_status = 'OPERATIONAL'
              LIMIT ?";
    
    $stmt = $GLOBALS['conn']->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $businesses = [];
    while ($row = $result->fetch_assoc()) {
        $businesses[] = $row;
    }
    
    if (empty($businesses)) {
        echo json_encode(['success' => true, 'message' => 'No businesses to generate', 'count' => 0]);
        exit;
    }
    
    $generated = 0;
    $failed = 0;
    
    foreach ($businesses as $business) {
        $aiContent = generateAIContent($business);
        
        if ($aiContent) {
            // Store in database
            $updateQuery = "UPDATE extracted_businesses 
                           SET ai_short_summary = ?,
                               ai_description = ?,
                               ai_tags = ?,
                               ai_generated = 1,
                               last_updated = NOW()
                           WHERE id = ?";
            
            $updateStmt = $GLOBALS['conn']->prepare($updateQuery);
            $updateStmt->bind_param('sssi',
                $aiContent['short_summary'],
                $aiContent['full_description'],
                $aiContent['tags'],
                $business['id']
            );
            
            if ($updateStmt->execute()) {
                $generated++;
            } else {
                $failed++;
            }
        } else {
            $failed++;
        }
        
        // Rate limit: 1 request per second for free tier
        sleep(1);
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Generated content for $generated businesses",
        'generated' => $generated,
        'failed' => $failed,
        'total_processed' => count($businesses)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Generate AI content using Gemini API
 */
function generateAIContent($business) {
    $apiKey = getenv('GEMINI_API_KEY');
    if (!$apiKey) {
        error_log('GEMINI_API_KEY not set');
        return null;
    }
    
    $name = $business['name'];
    $category = $business['types'] ?? 'Business';
    $city = $business['vicinity'] ?? 'Unknown';
    $rating = $business['rating'] ?? '0';
    
    // Parse category
    if (is_array($category)) {
        $category = implode(', ', array_slice($category, 0, 2));
    }
    
    $prompt = "Generate professional business content for: " . json_encode([
        'name' => $name,
        'category' => $category,
        'city' => $city,
        'rating' => $rating
    ]) . "\n\nProvide output in this exact JSON format with NO additional text:\n{\n  \"short_summary\": \"max 12 words description\",\n  \"full_description\": \"2-3 line professional description\",\n  \"tags\": \"comma-separated tags relevant to the business\"\n}";
    
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    
    $requestBody = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 256
        ]
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'User-Agent: PHP-Client/1.0'
            ],
            'content' => json_encode($requestBody),
            'timeout' => 30
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents(
        $url . '?key=' . urlencode($apiKey),
        false,
        $context
    );
    
    if (!$response) {
        error_log('Gemini API request failed for: ' . $name);
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        error_log('Unexpected Gemini response format: ' . json_encode($data));
        return null;
    }
    
    $content = $data['candidates'][0]['content']['parts'][0]['text'];
    
    // Parse JSON from response
    try {
        // Try to extract JSON from the response
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $parsed = json_decode($matches[0], true);
            if ($parsed && isset($parsed['short_summary']) && isset($parsed['full_description']) && isset($parsed['tags'])) {
                return [
                    'short_summary' => substr($parsed['short_summary'], 0, 255),
                    'full_description' => substr($parsed['full_description'], 0, 1000),
                    'tags' => substr($parsed['tags'], 0, 255)
                ];
            }
        }
    } catch (Exception $e) {
        error_log('Failed to parse Gemini response: ' . $e->getMessage());
    }
    
    return null;
}
