<?php
/**
 * Database Setup Script
 * Run this once to create tables and seed sample data
 */

require '../config/db.php';

echo "Setting up Business Directory Database...\n\n";

// Read SQL file
$sql = file_get_contents('database.sql');

// Extract individual queries
$queries = array_filter(
    array_map('trim', explode(';', $sql)),
    function($q) { return !empty($q) && !str_starts_with($q, '--'); }
);

$success = 0;
$failed = 0;

foreach ($queries as $query) {
    if ($conn->query($query)) {
        $success++;
        echo "✓ Query executed successfully\n";
    } else {
        $failed++;
        echo "✗ Error: " . $conn->error . "\n";
    }
}

echo "\n" . $success . " queries executed, " . $failed . " failed\n";
echo "Database setup complete!\n";
?>
