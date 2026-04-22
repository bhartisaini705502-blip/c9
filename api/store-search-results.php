<?php
/**
 * Store Search Results API
 * Save business search results to database for analytics
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['query']) || empty($data['results'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing query or results']);
    exit;
}

try {
    $query = trim($data['query']);
    $category = trim($data['category'] ?? '');
    $results = (array)$data['results'];
    $userId = isLoggedIn() ? $_SESSION['user_id'] : NULL;
    
    // Insert or get search history record
    $searchQuery = "INSERT INTO search_history (user_id, query, category) 
                   VALUES (?, ?, ?) 
                   ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
    $searchStmt = $GLOBALS['conn']->prepare($searchQuery);
    $searchStmt->bind_param('iss', $userId, $query, $category);
    $searchStmt->execute();
    $searchId = $GLOBALS['conn']->insert_id;
    
    // Get the actual search_id (for ON DUPLICATE KEY case)
    if (!$searchId) {
        $getIdQuery = "SELECT id FROM search_history WHERE query = ? AND (user_id = ? OR user_id IS NULL) ORDER BY searched_at DESC LIMIT 1";
        $getIdStmt = $GLOBALS['conn']->prepare($getIdQuery);
        $getIdStmt->bind_param('si', $query, $userId);
        $getIdStmt->execute();
        $idResult = $getIdStmt->get_result();
        if ($idRow = $idResult->fetch_assoc()) {
            $searchId = $idRow['id'];
        }
    }
    
    // Store individual search results
    if ($searchId) {
        $position = 1;
        $resultQuery = "INSERT INTO search_results (search_id, business_id, business_name, category, rating, position) 
                       VALUES (?, ?, ?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE position = VALUES(position)";
        $resultStmt = $GLOBALS['conn']->prepare($resultQuery);
        
        foreach ($results as $result) {
            $businessId = (int)($result['id'] ?? 0);
            $businessName = $result['name'] ?? '';
            $resultCategory = $result['category'] ?? '';
            $rating = (float)($result['rating'] ?? 0);
            
            $resultStmt->bind_param('iissdi', $searchId, $businessId, $businessName, $resultCategory, $rating, $position);
            $resultStmt->execute();
            $position++;
        }
    }
    
    echo json_encode(['success' => true, 'search_id' => $searchId]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
