<?php
/**
 * AI Chat API - Process user queries and return business recommendations
 * Supports both directory search and business-specific chat
 * Uses knowledge base for intelligent Q&A
 */

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../config/ai-knowledge-base.php';

header('Content-Type: application/json');

// Accept both POST JSON and FormData
$input = [];
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

$query = trim($input['message'] ?? $input['query'] ?? '');
$business_id = isset($input['business_id']) ? (int)$input['business_id'] : null;

if (empty($query)) {
    echo json_encode(['error' => 'Query is required', 'success' => false]);
    exit;
}

// If business_id is provided, handle business-specific chat
if ($business_id) {
    $response = handleBusinessChat($query, $business_id);
} else {
    // Try knowledge base first, then fallback to search
    $kb = require '../config/ai-knowledge-base.php';
    $kbResponse = matchKnowledgeBase($query, $kb);
    
    if ($kbResponse) {
        $response = ['success' => true, 'response' => $kbResponse];
    } else {
        // Fallback to directory search chat
        $keywords = extractSearchIntent($query);
        $response = searchBusinessesByIntent($keywords);
    }
}

echo json_encode($response);

/**
 * Extract search intent from query using simple NLP
 */
function extractSearchIntent($query) {
    $lowQuery = strtolower($query);
    
    // Extract category keywords
    $categories = ['restaurant', 'cafe', 'gym', 'salon', 'hotel', 'hospital', 'pharmacy', 'bank', 'school', 'shop'];
    $foundCategory = null;
    
    foreach ($categories as $cat) {
        if (strpos($lowQuery, $cat) !== false) {
            $foundCategory = $cat;
            break;
        }
    }
    
    // Extract price/quality indicators
    $priceIndicators = [];
    if (preg_match('/(cheap|affordable|budget|inexpensive|economical)/i', $query)) {
        $priceIndicators[] = 'affordable';
    }
    if (preg_match('/(premium|luxury|high-end|expensive|best)/i', $query)) {
        $priceIndicators[] = 'premium';
    }
    
    // Extract other attributes
    $attributes = [];
    if (preg_match('/(family|kids|children)/i', $query)) {
        $attributes[] = 'family-friendly';
    }
    if (preg_match('/(quick|fast|quick-service)/i', $query)) {
        $attributes[] = 'quick-service';
    }
    
    return [
        'category' => $foundCategory,
        'price' => !empty($priceIndicators) ? $priceIndicators[0] : null,
        'attributes' => $attributes,
        'original' => $query
    ];
}

/**
 * Search businesses based on extracted intent
 */
function searchBusinessesByIntent($intent) {
    global $conn;
    
    $where = ["business_status = 'OPERATIONAL'"];
    $params = [];
    
    // Add category filter
    if (!empty($intent['category'])) {
        $where[] = "types LIKE ?";
        $params[] = '%' . $intent['category'] . '%';
    }
    
    // Add quality filter (by rating if premium indicated)
    if ($intent['price'] === 'premium') {
        $where[] = "rating >= 4.0";
    } elseif ($intent['price'] === 'affordable') {
        $where[] = "(price_level IS NULL OR price_level <= 2)";
    }
    
    $whereClause = implode(' AND ', $where);
    $sql = "SELECT id, name, types, formatted_address, rating, user_ratings_total, search_location 
            FROM extracted_businesses 
            WHERE $whereClause
            ORDER BY verified DESC, rating DESC, user_ratings_total DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Format response
    $businessResults = [];
    foreach ($results as $business) {
        $types = array_map('trim', explode(',', $business['types']));
        $businessResults[] = [
            'id' => $business['id'],
            'name' => $business['name'],
            'category' => $types[0] ?? 'Business',
            'rating' => number_format($business['rating'], 1),
            'reviews' => $business['user_ratings_total'],
            'location' => $business['search_location']
        ];
    }
    
    // Generate AI response message
    $responseText = generateChatResponse($intent, $businessResults);
    
    return [
        'success' => true,
        'response' => $responseText,
        'results' => $businessResults
    ];
}

/**
 * Simple search without AI
 */
function simplePhraseSearch($query) {
    global $conn;
    
    $searchTerm = '%' . $query . '%';
    $sql = "SELECT id, name, types, formatted_address, rating, user_ratings_total, search_location 
            FROM extracted_businesses 
            WHERE (name LIKE ? OR types LIKE ?)
            AND business_status = 'OPERATIONAL'
            ORDER BY verified DESC, rating DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $businessResults = [];
    foreach ($results as $business) {
        $types = array_map('trim', explode(',', $business['types']));
        $businessResults[] = [
            'id' => $business['id'],
            'name' => $business['name'],
            'category' => $types[0] ?? 'Business',
            'rating' => number_format($business['rating'], 1),
            'reviews' => $business['user_ratings_total'],
            'location' => $business['search_location']
        ];
    }
    
    $responseText = !empty($businessResults) 
        ? "I found " . count($businessResults) . " great options for you! Here are the top results."
        : "Sorry, I didn't find any businesses matching that search. Try a different query!";
    
    return [
        'success' => true,
        'response' => $responseText,
        'results' => $businessResults
    ];
}

/**
 * Generate natural language response
 */
function generateChatResponse($intent, $results) {
    if (empty($results)) {
        return "Sorry, I couldn't find any businesses matching that description. Try searching for a different category!";
    }
    
    $responses = [
        "Great! I found the perfect match for you. Check out these options:",
        "Here are the top-rated businesses that match your needs:",
        "Based on your search, I found these amazing options:",
        "Perfect timing! Here are the best options available:"
    ];
    
    $response = $responses[array_rand($responses)];
    
    // Add category-specific context if available
    if (!empty($intent['category'])) {
        $response = str_replace('options', ucfirst($intent['category']) . ' options', $response);
    }
    
    return $response;
}

/**
 * Match query against knowledge base
 */
function matchKnowledgeBase($query, $kb) {
    $lowerQuery = strtolower(trim($query));
    
    // Search through all knowledge base categories
    foreach ($kb as $category => $items) {
        if ($category === 'business_template') continue; // Skip template
        
        foreach ($items as $item) {
            if (isset($item['question']) && isset($item['answer'])) {
                // Check if query matches any variant of the question
                foreach ((array)$item['question'] as $questionVariant) {
                    if (strpos($lowerQuery, $questionVariant) !== false || strpos($questionVariant, $lowerQuery) !== false) {
                        return $item['answer'];
                    }
                }
            }
        }
    }
    
    return null; // No match found
}

/**
 * Handle business-specific chat on listing pages
 */
function handleBusinessChat($query, $business_id) {
    global $conn;
    
    // Fetch business data
    $stmt = $conn->prepare("SELECT * FROM extracted_businesses WHERE id = ?");
    $stmt->bind_param('i', $business_id);
    $stmt->execute();
    $business = $stmt->get_result()->fetch_assoc();
    
    if (!$business) {
        return [
            'success' => false,
            'response' => 'Business not found'
        ];
    }
    
    $msg = strtolower($query);
    $response = '';
    
    // Services query
    if (preg_match('/(service|offer|what do you.*offer|what services)/i', $query)) {
        $response = "📋 Check the 'Services Offered' section above to see all services from " . htmlspecialchars($business['name']) . "!";
    }
    // Contact/Phone query
    elseif (preg_match('/(contact|call|phone|number|reach|message)/i', $query)) {
        $phone = $business['formatted_phone_number'] ?? 'Not listed';
        $address = $business['formatted_address'] ?? 'Not available';
        $response = "📞 **Contact " . htmlspecialchars($business['name']) . ":**\n\nPhone: " . htmlspecialchars($phone) . "\nAddress: " . htmlspecialchars($address) . "\n\nYou can call, message, or visit them!";
    }
    // Hours/Timing query
    elseif (preg_match('/(hour|open|timing|when open|when available)/i', $query)) {
        $hours = $business['opening_hours_weekday'] ?? 'Not listed';
        $response = "🕐 **Business Hours:**\n" . htmlspecialchars($hours) . "\n\nHours may vary on weekends and holidays.";
    }
    // Location/Address query
    elseif (preg_match('/(location|address|where|how.*reach)/i', $query)) {
        $addr = $business['formatted_address'] ?? 'Not listed';
        $response = "📍 **Location:** " . htmlspecialchars($addr) . "\n\nFind the map above for exact location & directions!";
    }
    // Rating/Reviews query
    elseif (preg_match('/(rating|review|feedback|opinion|customer)/i', $query)) {
        $rating = $business['rating'] ?? '0';
        $count = $business['user_ratings_total'] ?? '0';
        $response = "⭐ **Rating:** " . htmlspecialchars($rating) . "/5 (Based on " . number_format($count) . " reviews)\n\nCheck the Reviews section below!";
    }
    // Offers/Discounts query
    elseif (preg_match('/(offer|discount|deal|promotion|special)/i', $query)) {
        $response = "🎉 Check the 'Special Offers & Promotions' section above to see current deals!";
    }
    // About business
    elseif (preg_match('/(about|tell me|who|what is)/i', $query)) {
        $types = array_map('trim', explode(',', $business['types'] ?? ''));
        $category = $types[0] ?? 'Business';
        $desc = $business['ai_description'] ?? '';
        if ($desc) {
            $response = htmlspecialchars(substr($desc, 0, 300)) . "...\n\nLearn more by scrolling down!";
        } else {
            $response = "📌 " . htmlspecialchars($business['name']) . " is a " . htmlspecialchars($category) . " listed in our directory. Scroll down for more details!";
        }
    }
    // Default response
    else {
        $types = array_map('trim', explode(',', $business['types'] ?? ''));
        $category = $types[0] ?? 'Business';
        $response = "🤔 I can help you with:\n\n📋 Services & pricing\n📞 Contact & location\n🕐 Opening hours\n⭐ Ratings & reviews\n🎉 Offers & discounts\n\nWhat would you like to know about " . htmlspecialchars($business['name']) . "?";
    }
    
    return [
        'success' => true,
        'response' => $response
    ];
}
?>
