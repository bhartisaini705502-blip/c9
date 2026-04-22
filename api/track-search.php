<?php
/**
 * Track Searches for Trending Data
 */

header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['search_term'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing search_term']);
    exit;
}

$searchTerm = trim($data['search_term']);
$category = trim($data['category'] ?? '');
$city = trim($data['city'] ?? '');

// Insert or update trending search
$stmt = $GLOBALS['conn']->prepare("
    INSERT INTO trending_searches (search_term, category, city, search_count) 
    VALUES (?, ?, ?, 1) 
    ON DUPLICATE KEY UPDATE search_count = search_count + 1
");
$stmt->bind_param('sss', $searchTerm, $category, $city);
$stmt->execute();

echo json_encode(['success' => true]);
?>
