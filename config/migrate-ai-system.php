<?php
/**
 * AI System Migration
 * Adds AI scoring and follow-up tracking
 * Run once: navigate to /config/migrate-ai-system.php
 */

require_once 'db.php';

if (!$conn) {
    die("Database connection failed");
}

echo "<h2>AI System Migration</h2>";

// ============================================
// 1. ADD AI_SCORE COLUMN TO LEADS
// ============================================
$check_ai_score = $conn->query("SHOW COLUMNS FROM leads LIKE 'ai_score'");

if ($check_ai_score && $check_ai_score->num_rows > 0) {
    echo "✓ AI score column already exists<br>";
} else {
    $sql = "ALTER TABLE leads ADD ai_score INT DEFAULT 0";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ AI score column added<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

// ============================================
// 2. ADD FOLLOWUP_DATE COLUMN TO LEADS
// ============================================
$check_followup = $conn->query("SHOW COLUMNS FROM leads LIKE 'followup_date'");

if ($check_followup && $check_followup->num_rows > 0) {
    echo "✓ Follow-up date column already exists<br>";
} else {
    $sql = "ALTER TABLE leads ADD followup_date DATE DEFAULT NULL";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Follow-up date column added<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

// ============================================
// 3. ADD FOLLOWUP_STATUS COLUMN TO LEADS
// ============================================
$check_followup_status = $conn->query("SHOW COLUMNS FROM leads LIKE 'followup_status'");

if ($check_followup_status && $check_followup_status->num_rows > 0) {
    echo "✓ Follow-up status column already exists<br>";
} else {
    $sql = "ALTER TABLE leads ADD followup_status VARCHAR(50) DEFAULT 'pending'";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Follow-up status column added<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

// ============================================
// 4. CREATE AI_INSIGHTS_CACHE TABLE
// ============================================
$check_cache = $conn->query("SHOW TABLES LIKE 'ai_insights_cache'");

if ($check_cache && $check_cache->num_rows > 0) {
    echo "✓ AI insights cache table already exists<br>";
} else {
    $sql = "CREATE TABLE ai_insights_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cache_key VARCHAR(255) UNIQUE,
        cache_value LONGTEXT,
        expires_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_key (cache_key),
        INDEX idx_expires (expires_at)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ AI insights cache table created<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

// ============================================
// 5. CREATE AI_PROMPTS TABLE
// ============================================
$check_prompts = $conn->query("SHOW TABLES LIKE 'ai_prompts'");

if ($check_prompts && $check_prompts->num_rows > 0) {
    echo "✓ AI prompts table already exists<br>";
} else {
    $sql = "CREATE TABLE ai_prompts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        prompt TEXT,
        category VARCHAR(50),
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ AI prompts table created<br>";
        
        // Insert default prompts
        $prompts = [
            ['Business Description', 'Write a professional business description for a {business_type}. Include key services, benefits, and what makes it unique. Keep it under 150 words.', 'content'],
            ['SEO Meta Title', 'Create 5 SEO-optimized meta titles for a {business_type} targeting "{keyword}". Each title should be 50-60 characters.', 'seo'],
            ['Blog Ideas', 'Generate 5 blog post ideas for a {business_type} targeting "{keyword}". Include title and brief description for each.', 'content'],
            ['Email Subject', 'Create 5 compelling email subject lines for a {business_type} offering "{service}". Make them engaging and clickable.', 'marketing']
        ];
        
        foreach ($prompts as $p) {
            $conn->query("INSERT INTO ai_prompts (title, prompt, category) VALUES ('{$p[0]}', '{$p[1]}', '{$p[2]}')");
        }
        echo "✓ Default AI prompts inserted<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

echo "<h3>✅ AI Migration Complete!</h3>";
echo "<p>All AI system tables created successfully. You can now:</p>";
echo "<ul>";
echo "<li>1. Use AI lead scoring</li>";
echo "<li>2. Set follow-up reminders</li>";
echo "<li>3. View AI insights</li>";
echo "<li>4. Generate AI content</li>";
echo "</ul>";

$conn->close();
?>
