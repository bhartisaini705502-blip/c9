<?php
/**
 * API: Get businesses for map display
 * Phase 2: Map Integration
 */

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'get-businesses';
$category = $_GET['category'] ?? '';
$city = $_GET['city'] ?? '';
$limit = min($_GET['limit'] ?? 50, 100);

if ($action === 'get-businesses') {
    $sql = "SELECT id, name, types as category, vicinity as city, lat as latitude, lng as longitude, rating, user_ratings_total as review_count, formatted_phone_number as phone, formatted_address as address, ai_description FROM extracted_businesses WHERE business_status = 'OPERATIONAL'";
    
    $params = [];
    $types = '';
    
    if ($category) {
        $sql .= " AND LOWER(types) LIKE ?";
        $params[] = '%' . strtolower($category) . '%';
        $types .= 's';
    }
    
    if ($city) {
        $sql .= " AND LOWER(vicinity) LIKE ?";
        $params[] = '%' . strtolower($city) . '%';
        $types .= 's';
    }
    
    $sql .= " AND lat IS NOT NULL AND lng IS NOT NULL LIMIT ?";
    $params[] = $limit;
    $types .= 'i';
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $businesses = [];
    while ($row = $result->fetch_assoc()) {
        $businesses[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($businesses),
        'businesses' => $businesses
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
