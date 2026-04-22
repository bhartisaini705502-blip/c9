<?php
/**
 * API: Search Cities by Name (for autocomplete search)
 */
require_once '../config/db.php';

header('Content-Type: application/json');

$search = '%' . $conn->real_escape_string($_GET['q'] ?? '') . '%';

if (strlen($_GET['q'] ?? '') < 2) {
    echo json_encode([]);
    exit;
}

$result = $conn->query("
    SELECT c.id, c.name, s.name as state_name 
    FROM cities c
    JOIN states s ON c.state_id = s.id
    WHERE c.name LIKE '$search'
    ORDER BY c.name
    LIMIT 10
");

$cities = [];
while ($row = $result->fetch_assoc()) {
    $cities[] = $row;
}

echo json_encode($cities);
@$conn->close();
?>
