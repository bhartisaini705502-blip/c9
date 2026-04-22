<?php
/**
 * API: Search businesses (for claim form)
 */

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$limit = min($_GET['limit'] ?? 10, 50);

if (!$q || strlen($q) < 2) {
    echo json_encode(['success' => false, 'error' => 'Search term too short']);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, name, formatted_address as address, rating, types as category 
    FROM extracted_businesses 
    WHERE (LOWER(name) LIKE ? OR LOWER(formatted_address) LIKE ?)
    AND business_status = 'OPERATIONAL'
    LIMIT ?
");

$searchParam = '%' . strtolower($q) . '%';
$stmt->bind_param('ssi', $searchParam, $searchParam, $limit);
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
