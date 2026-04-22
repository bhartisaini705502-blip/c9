<?php
/**
 * Recent Searches API
 * Get or save recent searches for user
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

try {
    if ($action === 'get') {
        // Get recent searches for current user or session
        if (isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            $query = "SELECT DISTINCT query, category FROM search_history 
                     WHERE user_id = ? 
                     ORDER BY searched_at DESC 
                     LIMIT 5";
            $stmt = $GLOBALS['conn']->prepare($query);
            $stmt->bind_param('i', $userId);
        } else {
            // For non-logged-in users, use session-based storage
            $searches = $_SESSION['recent_searches'] ?? [];
            echo json_encode([
                'success' => true,
                'searches' => array_slice($searches, 0, 5)
            ]);
            exit;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $searches = [];
        
        while ($row = $result->fetch_assoc()) {
            $searches[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'searches' => $searches
        ]);
        
    } elseif ($action === 'save') {
        $query = trim($_POST['query'] ?? '');
        $category = trim($_POST['category'] ?? '');
        
        if (empty($query)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing query']);
            exit;
        }
        
        if (isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            $insertQuery = "INSERT INTO search_history (user_id, query, category) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE searched_at = CURRENT_TIMESTAMP";
            $stmt = $GLOBALS['conn']->prepare($insertQuery);
            $stmt->bind_param('iss', $userId, $query, $category);
            $stmt->execute();
        } else {
            // Store in session for non-logged-in users
            if (!isset($_SESSION['recent_searches'])) {
                $_SESSION['recent_searches'] = [];
            }
            
            $newSearch = ['query' => $query, 'category' => $category];
            // Add to beginning and limit to 5
            array_unshift($_SESSION['recent_searches'], $newSearch);
            $_SESSION['recent_searches'] = array_slice($_SESSION['recent_searches'], 0, 5);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Search saved'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
