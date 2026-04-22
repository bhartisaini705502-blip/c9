<?php
/**
 * Create Leads Table - Run once to initialize
 */

require_once 'db.php';

if (!$conn) {
    die("Database connection failed");
}

$sql = "CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    service VARCHAR(100),
    message LONGTEXT,
    source VARCHAR(50),
    status VARCHAR(20) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_created_at (created_at),
    INDEX idx_source (source),
    UNIQUE KEY unique_lead (phone, created_at)
)";

if ($conn->query($sql) === TRUE) {
    echo "Leads table created successfully";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
