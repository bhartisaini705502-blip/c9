<?php
/**
 * Featured Listing Management API
 * Admin endpoint to feature/boost businesses
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

// Admin only
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$businessId = (int)($_POST['business_id'] ?? $_GET['business_id'] ?? 0);
$planType = $_POST['plan_type'] ?? 'featured'; // featured or boosted
$duration = (int)($_POST['duration'] ?? 30); // days

try {
    switch ($action) {
        case 'feature':
            if (!$businessId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing business_id']);
                exit;
            }
            
            $expiryDate = date('Y-m-d H:i:s', strtotime("+$duration days"));
            
            // Check if already featured
            $checkQuery = "SELECT id FROM featured_listings WHERE business_id = ?";
            $checkStmt = $GLOBALS['conn']->prepare($checkQuery);
            $checkStmt->bind_param('i', $businessId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing
                $updateQuery = "UPDATE featured_listings 
                               SET expires_at = ?, plan_type = ? 
                               WHERE business_id = ?";
                $updateStmt = $GLOBALS['conn']->prepare($updateQuery);
                $updateStmt->bind_param('ssi', $expiryDate, $planType, $businessId);
                $updateStmt->execute();
                
                // Update business table
                $updateBizQuery = "UPDATE extracted_businesses 
                                  SET is_featured = 1, boost_expiry = ? 
                                  WHERE id = ?";
                $updateBizStmt = $GLOBALS['conn']->prepare($updateBizQuery);
                $updateBizStmt->bind_param('si', $expiryDate, $businessId);
                $updateBizStmt->execute();
            } else {
                // Insert new
                $insertQuery = "INSERT INTO featured_listings (business_id, expires_at, plan_type) 
                               VALUES (?, ?, ?)";
                $insertStmt = $GLOBALS['conn']->prepare($insertQuery);
                $insertStmt->bind_param('iss', $businessId, $expiryDate, $planType);
                $insertStmt->execute();
                
                // Update business table
                $updateBizQuery = "UPDATE extracted_businesses 
                                  SET is_featured = 1, boost_expiry = ? 
                                  WHERE id = ?";
                $updateBizStmt = $GLOBALS['conn']->prepare($updateBizQuery);
                $updateBizStmt->bind_param('si', $expiryDate, $businessId);
                $updateBizStmt->execute();
            }
            
            echo json_encode([
                'success' => true,
                'message' => ucfirst($planType) . ' listing created for ' . $duration . ' days',
                'expires_at' => $expiryDate
            ]);
            break;
            
        case 'unfeature':
            if (!$businessId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing business_id']);
                exit;
            }
            
            // Delete from featured
            $deleteQuery = "DELETE FROM featured_listings WHERE business_id = ?";
            $deleteStmt = $GLOBALS['conn']->prepare($deleteQuery);
            $deleteStmt->bind_param('i', $businessId);
            $deleteStmt->execute();
            
            // Update business table
            $updateBizQuery = "UPDATE extracted_businesses 
                              SET is_featured = 0, boost_expiry = NULL 
                              WHERE id = ?";
            $updateBizStmt = $GLOBALS['conn']->prepare($updateBizQuery);
            $updateBizStmt->bind_param('i', $businessId);
            $updateBizStmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Featured listing removed'
            ]);
            break;
            
        case 'list':
            // List all featured listings
            $query = "SELECT fl.id, fl.business_id, fl.plan_type, fl.expires_at,
                             b.name, b.rating, b.types
                      FROM featured_listings fl
                      JOIN extracted_businesses b ON fl.business_id = b.id
                      WHERE fl.expires_at > NOW()
                      ORDER BY fl.expires_at DESC";
            
            $result = $GLOBALS['conn']->query($query);
            $listings = [];
            
            while ($row = $result->fetch_assoc()) {
                $listings[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'count' => count($listings),
                'listings' => $listings
            ]);
            break;
            
        case 'status':
            if (!$businessId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing business_id']);
                exit;
            }
            
            $query = "SELECT fl.plan_type, fl.expires_at FROM featured_listings 
                     WHERE business_id = ? AND expires_at > NOW()";
            $stmt = $GLOBALS['conn']->prepare($query);
            $stmt->bind_param('i', $businessId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'featured' => true,
                    'plan_type' => $row['plan_type'],
                    'expires_at' => $row['expires_at']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'featured' => false
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
