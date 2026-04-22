<?php
/**
 * Migration - Add Lead Scoring System
 * Run this file once: navigate to /config/migrate-lead-scoring.php
 */

require_once 'db.php';

if (!$conn) {
    die("Database connection failed");
}

// Check if score column already exists
$check_column = $conn->query("SHOW COLUMNS FROM leads LIKE 'score'");

if ($check_column && $check_column->num_rows > 0) {
    echo "✓ Score column already exists";
} else {
    // Add score column
    $sql = "ALTER TABLE leads ADD score INT DEFAULT 0";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Score column added successfully\n";
    } else {
        echo "✗ Error: " . $conn->error;
    }
}

// Check if user_email column exists (for auto-reply)
$check_email = $conn->query("SHOW COLUMNS FROM leads LIKE 'user_email_sent'");

if ($check_email && $check_email->num_rows > 0) {
    echo "✓ Email tracking column already exists";
} else {
    // Add email sent tracking
    $sql = "ALTER TABLE leads ADD user_email_sent BOOLEAN DEFAULT FALSE";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Email tracking column added successfully\n";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
