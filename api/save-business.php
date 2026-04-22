<?php
/**
 * Save/Favorite Business API
 * Toggle business save status for authenticated users
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$businessId = (int)($_POST['business_id'] ?? $_GET['business_id'] ?? 0);
$action = $_POST['action'] ?? $_GET['action'] ?? 'toggle';

if (!$businessId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing business_id']);
    exit;
}

try {
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User session invalid']);
        exit;
    }
    
    // Check if already saved
    $checkQuery = "SELECT id FROM saved_businesses WHERE user_id = ? AND business_id = ?";
    $checkStmt = $GLOBALS['conn']->prepare($checkQuery);
    $checkStmt->bind_param('ii', $userId, $businessId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $isSaved = $result->num_rows > 0;
    
    if ($action === 'toggle' || ($action === 'save' && !$isSaved) || ($action === 'unsave' && $isSaved)) {
        if ($isSaved) {
            // Remove save
            $deleteQuery = "DELETE FROM saved_businesses WHERE user_id = ? AND business_id = ?";
            $deleteStmt = $GLOBALS['conn']->prepare($deleteQuery);
            $deleteStmt->bind_param('ii', $userId, $businessId);
            $deleteStmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Business removed from favorites',
                'saved' => false
            ]);
        } else {
            // Add save
            $insertQuery = "INSERT INTO saved_businesses (user_id, business_id) VALUES (?, ?)";
            $insertStmt = $GLOBALS['conn']->prepare($insertQuery);
            $insertStmt->bind_param('ii', $userId, $businessId);
            $insertStmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Business added to favorites',
                'saved' => true
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'saved' => $isSaved
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
