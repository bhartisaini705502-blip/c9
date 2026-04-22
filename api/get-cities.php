<?php
/**
 * API: Get Cities by State (for autocomplete)
 */
require_once '../config/db.php';

header('Content-Type: application/json');

$state_id = intval($_GET['state_id'] ?? 0);

if ($state_id <= 0) {
    echo json_encode([]);
    exit;
}

$result = $conn->query("SELECT id, name FROM cities WHERE state_id = $state_id ORDER BY name");

$cities = [];
while ($row = $result->fetch_assoc()) {
    $cities[] = $row;
}

echo json_encode($cities);
@$conn->close();
?>
