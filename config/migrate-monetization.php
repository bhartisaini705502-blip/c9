<?php
/**
 * Database Migration: Monetization Platform
 * Creates plans, subscriptions, and adds fields for featured listings
 * Run once in browser: http://localhost:5000/config/migrate-monetization.php
 */

require_once 'db.php';

if (!$conn) {
    die('Database connection failed');
}

$errors = [];
$success = [];

try {
    // ====== 1. CREATE PLANS TABLE ======
    $sql = "CREATE TABLE IF NOT EXISTS plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        features TEXT,
        duration_days INT DEFAULT 30,
        rank_priority INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        $success[] = '✓ Plans table created';
    } else {
        $errors[] = 'Plans table: ' . $conn->error;
    }

    // ====== 2. CREATE SUBSCRIPTIONS TABLE ======
    $sql = "CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        business_id INT NOT NULL,
        plan_id INT NOT NULL,
        user_id INT,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status VARCHAR(50) DEFAULT 'active',
        payment_status VARCHAR(50) DEFAULT 'pending',
        payment_method VARCHAR(50),
        transaction_id VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (plan_id) REFERENCES plans(id),
        FOREIGN KEY (business_id) REFERENCES extracted_businesses(id),
        INDEX idx_business_id (business_id),
        INDEX idx_status (status),
        INDEX idx_end_date (end_date)
    )";
    
    if ($conn->query($sql)) {
        $success[] = '✓ Subscriptions table created';
    } else {
        $errors[] = 'Subscriptions table: ' . $conn->error;
    }

    // ====== 3. CREATE CLAIMS TABLE ======
    $sql = "CREATE TABLE IF NOT EXISTS business_claims (
        id INT AUTO_INCREMENT PRIMARY KEY,
        business_id INT NOT NULL,
        user_id INT NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        message TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        claimed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (business_id) REFERENCES extracted_businesses(id),
        INDEX idx_business_id (business_id),
        INDEX idx_status (status),
        UNIQUE KEY unique_claim (business_id, user_id)
    )";
    
    if ($conn->query($sql)) {
        $success[] = '✓ Business claims table created';
    } else {
        $errors[] = 'Business claims table: ' . $conn->error;
    }

    // ====== 4. UPDATE BUSINESSES TABLE ======
    $alterQueries = [
        "ALTER TABLE extracted_businesses ADD COLUMN IF NOT EXISTS is_featured TINYINT DEFAULT 0",
        "ALTER TABLE extracted_businesses ADD COLUMN IF NOT EXISTS is_verified TINYINT DEFAULT 0",
        "ALTER TABLE extracted_businesses ADD COLUMN IF NOT EXISTS claimed_by INT",
        "ALTER TABLE extracted_businesses ADD COLUMN IF NOT EXISTS plan_id INT",
        "ALTER TABLE extracted_businesses ADD INDEX IF NOT EXISTS idx_is_featured (is_featured)",
        "ALTER TABLE extracted_businesses ADD INDEX IF NOT EXISTS idx_is_verified (is_verified)",
        "ALTER TABLE extracted_businesses ADD INDEX IF NOT EXISTS idx_plan_id (plan_id)"
    ];
    
    foreach ($alterQueries as $query) {
        if (!$conn->query($query)) {
            if (strpos($conn->error, 'Duplicate column') === false && strpos($conn->error, 'Duplicate key') === false) {
                $errors[] = 'Alter table: ' . $conn->error;
            }
        }
    }
    $success[] = '✓ Businesses table updated';

    // ====== 5. INSERT PRICING PLANS ======
    $plans = [
        ['Free', 0, 'Basic listing with standard visibility', 'Basic listing|Standard ranking', 365],
        ['Basic', 499, 'Verified badge with higher ranking', 'Verified badge|Higher ranking|Email support', 30],
        ['Premium', 999, 'Featured listing with top ranking', 'Featured listing|Top ranking|Priority support|Analytics', 30]
    ];
    
    $inserted_plans = 0;
    foreach ($plans as $plan) {
        $stmt = $conn->prepare("INSERT IGNORE INTO plans (name, price, description, features, duration_days, rank_priority) VALUES (?, ?, ?, ?, ?, ?)");
        $rank = $plan[0] === 'Free' ? 0 : ($plan[0] === 'Basic' ? 1 : 2);
        $stmt->bind_param('sdssi', $plan[0], $plan[1], $plan[2], $plan[3], $plan[4]);
        if ($stmt->execute()) {
            $inserted_plans++;
        }
        $stmt->close();
    }
    $success[] = "✓ Plans inserted: $inserted_plans";

} catch (Exception $e) {
    $errors[] = 'Exception: ' . $e->getMessage();
}

@$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Monetization System Migration</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #155724; background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 4px; }
        h1 { color: #0B1C3D; }
        .features { background: #f0f4ff; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>💰 Monetization System Migration</h1>
        
        <?php if (!empty($success)): ?>
            <h2>✅ Success</h2>
            <?php foreach ($success as $msg): ?>
                <div class="success"><?php echo $msg; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <h2>❌ Errors</h2>
            <?php foreach ($errors as $msg): ?>
                <div class="error"><?php echo $msg; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <hr>
        
        <div class="features">
            <h3>📋 Created Tables:</h3>
            <ul>
                <li><strong>plans</strong> - Pricing plans (Free, Basic, Premium)</li>
                <li><strong>subscriptions</strong> - Active subscriptions per business</li>
                <li><strong>business_claims</strong> - Business claim requests</li>
            </ul>
        </div>
        
        <div class="features">
            <h3>🎁 Pricing Plans:</h3>
            <ul>
                <li><strong>Free</strong> - ₹0/month (basic listing)</li>
                <li><strong>Basic</strong> - ₹499/month (verified badge + higher rank)</li>
                <li><strong>Premium</strong> - ₹999/month (featured + top rank)</li>
            </ul>
        </div>
        
        <div class="features">
            <h3>🔄 Businesses Table Updated:</h3>
            <ul>
                <li>is_featured - For featured listings</li>
                <li>is_verified - For verified businesses</li>
                <li>claimed_by - Owner user_id</li>
                <li>plan_id - Current subscription plan</li>
            </ul>
        </div>
        
        <p><a href="/">Back to Home</a></p>
    </div>
</body>
</html>
