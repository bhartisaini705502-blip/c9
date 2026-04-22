<?php
/**
 * Chatbot System Migration
 * Creates chat logs table
 * Run once: navigate to /config/migrate-chatbot.php
 */

require_once 'db.php';

if (!$conn) {
    die("Database connection failed");
}

echo "<h2>Chatbot System Migration</h2>";

// ============================================
// 1. CREATE CHAT_LOGS TABLE
// ============================================
$check_chat_logs = $conn->query("SHOW TABLES LIKE 'chat_logs'");

if ($check_chat_logs && $check_chat_logs->num_rows > 0) {
    echo "✓ Chat logs table already exists<br>";
} else {
    $sql = "CREATE TABLE chat_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_message TEXT,
        bot_response TEXT,
        user_name VARCHAR(100),
        user_phone VARCHAR(20),
        user_email VARCHAR(100),
        lead_captured BOOLEAN DEFAULT FALSE,
        page_url VARCHAR(255),
        session_id VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_session (session_id),
        INDEX idx_lead_captured (lead_captured)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Chat logs table created<br>";
    } else {
        echo "✗ Error creating chat logs table: " . $conn->error . "<br>";
    }
}

echo "<h3>✅ Chatbot Migration Complete!</h3>";
echo "<p>All chatbot tables created successfully. You can now:</p>";
echo "<ul>";
echo "<li>1. Use AI chatbot on website</li>";
echo "<li>2. Capture leads via chat</li>";
echo "<li>3. View chat logs in admin</li>";
echo "</ul>";

$conn->close();
?>
