<?php
/**
 * Admin - Premium Listings Management
 */

require_once '../config/db.php';
require_once '../config/auth.php';

if (!isAdmin()) {
    header('Location: /auth/login.php');
    exit;
}

$success = '';
$action = $_GET['action'] ?? 'overview';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_plan'])) {
        $name = trim($_POST['plan_name']);
        $price = floatval($_POST['plan_price']);
        $duration = intval($_POST['plan_duration']);
        $features = implode(',', $_POST['features'] ?? []);
        
        $stmt = $GLOBALS['conn']->prepare("
            INSERT INTO premium_plans (name, price, duration_days, features) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('sdis', $name, $price, $duration, $features);
        if ($stmt->execute()) {
            $success = "Premium plan created successfully!";
        }
    } elseif (isset($_POST['upgrade_business'])) {
        $businessId = intval($_POST['business_id']);
        $planId = intval($_POST['plan_id']);
        $endsAt = date('Y-m-d', strtotime('+30 days'));
        
        $stmt = $GLOBALS['conn']->prepare("
            UPDATE extracted_businesses 
            SET premium_plan_id = ?, premium_expires_at = ? 
            WHERE id = ?
        ");
        $stmt->bind_param('isi', $planId, $endsAt, $businessId);
        if ($stmt->execute()) {
            $success = "Business upgraded to premium!";
        }
    }
}

// Get stats
$totalPremium = $GLOBALS['conn']->query("
    SELECT COUNT(*) as c FROM extracted_businesses 
    WHERE premium_plan_id IS NOT NULL AND premium_expires_at > NOW()
")->fetch_assoc()['c'];

$activePlans = $GLOBALS['conn']->query("
    SELECT COUNT(*) as c FROM premium_plans
")->fetch_assoc()['c'];

$premiumRevenue = $GLOBALS['conn']->query("
    SELECT SUM(price) as total FROM premium_plans 
    JOIN extracted_businesses ON extracted_businesses.premium_plan_id = premium_plans.id 
    WHERE extracted_businesses.premium_expires_at > NOW()
")->fetch_assoc()['total'] ?? 0;

// Get plans
$plans = $GLOBALS['conn']->query("
    SELECT * FROM premium_plans ORDER BY price ASC
")->fetch_all(MYSQLI_ASSOC);

// Check if table exists
$result = $GLOBALS['conn']->query("SHOW TABLES LIKE 'premium_plans'");
if ($result->num_rows === 0) {
    $GLOBALS['conn']->query("
        CREATE TABLE premium_plans (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            duration_days INT DEFAULT 30,
            features TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_price (price)
        )
    ");
}

// Add premium columns if not exist
$columns = $GLOBALS['conn']->query("SHOW COLUMNS FROM extracted_businesses LIKE 'premium%'");
if ($columns->num_rows === 0) {
    $GLOBALS['conn']->query("ALTER TABLE extracted_businesses ADD COLUMN premium_plan_id INT NULL");
    $GLOBALS['conn']->query("ALTER TABLE extracted_businesses ADD COLUMN premium_expires_at TIMESTAMP NULL");
    $GLOBALS['conn']->query("ALTER TABLE extracted_businesses ADD COLUMN featured BOOLEAN DEFAULT FALSE");
}

$page_title = 'Premium Listings';
include '../includes/header.php';
?>

<style>
.premium-panel {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px;
}

.revenue-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.revenue-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.revenue-label {
    color: #666;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.revenue-amount {
    font-size: 28px;
    font-weight: bold;
    color: #FF6A00;
}

.panel-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.panel-section h2 {
    margin-top: 0;
    color: #0B1C3D;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.plan-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.plan-name {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px 0;
}

.plan-price {
    font-size: 28px;
    font-weight: bold;
    margin: 0 0 5px 0;
}

.plan-duration {
    font-size: 12px;
    opacity: 0.9;
    margin-bottom: 15px;
}

.plan-features {
    text-align: left;
    font-size: 12px;
    list-style: none;
    padding: 0;
    margin: 15px 0;
}

.plan-features li {
    padding: 4px 0;
}

.plan-features li:before {
    content: "✓ ";
    margin-right: 5px;
}

.input-group {
    margin-bottom: 15px;
}

.input-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #333;
    font-size: 13px;
}

.input-group input,
.input-group select,
.input-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: inherit;
    font-size: 13px;
}

.checkboxes {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.checkbox-item {
    display: flex;
    align-items: center;
}

.checkbox-item input {
    width: auto;
    margin-right: 8px;
}

.btn {
    padding: 12px 25px;
    background: #FF6A00;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.btn:hover {
    background: #E55A00;
}

.success-msg {
    background: #D4EDDA;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #28a745;
}
</style>

<div class="premium-panel">
    <h1>💎 Premium Listings Management</h1>
    
    <?php if (!empty($success)): ?>
    <div class="success-msg"><?php echo esc($success); ?></div>
    <?php endif; ?>
    
    <div class="revenue-cards">
        <div class="revenue-card">
            <div class="revenue-label">Active Premium Listings</div>
            <div class="revenue-amount"><?php echo $totalPremium; ?></div>
        </div>
        <div class="revenue-card">
            <div class="revenue-label">Premium Plans Available</div>
            <div class="revenue-amount"><?php echo $activePlans; ?></div>
        </div>
        <div class="revenue-card">
            <div class="revenue-label">Monthly Revenue (Est.)</div>
            <div class="revenue-amount">$<?php echo number_format($premiumRevenue, 0); ?></div>
        </div>
        <div class="revenue-card">
            <div class="revenue-label">Total Businesses</div>
            <div class="revenue-amount"><?php 
                $total = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM extracted_businesses WHERE business_status = 'OPERATIONAL'")->fetch_assoc()['c'];
                echo $total;
            ?></div>
        </div>
    </div>
    
    <div class="panel-section">
        <h2>📋 Premium Plans</h2>
        
        <div class="plans-grid">
            <?php foreach ($plans as $plan): 
                $features = explode(',', $plan['features']);
            ?>
            <div class="plan-card">
                <h3 class="plan-name"><?php echo esc($plan['name']); ?></h3>
                <div class="plan-price">$<?php echo number_format($plan['price'], 0); ?></div>
                <div class="plan-duration"><?php echo $plan['duration_days']; ?> days</div>
                <ul class="plan-features">
                    <?php foreach ($features as $feature): ?>
                    <li><?php echo esc(trim($feature)); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="panel-section">
        <h2>➕ Create New Premium Plan</h2>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="input-group">
                    <label>Plan Name *</label>
                    <input type="text" name="plan_name" required placeholder="e.g., Gold, Platinum, Premium">
                </div>
                <div class="input-group">
                    <label>Monthly Price ($) *</label>
                    <input type="number" name="plan_price" required step="0.01" min="0" placeholder="99.99">
                </div>
            </div>
            
            <div class="input-group">
                <label>Duration (Days) *</label>
                <input type="number" name="plan_duration" required min="1" value="30">
            </div>
            
            <div class="input-group">
                <label>Features (Check all that apply)</label>
                <div class="checkboxes">
                    <div class="checkbox-item">
                        <input type="checkbox" name="features" value="Featured Listing">
                        <label style="margin: 0;">Featured Listing</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="features" value="Top Search Results">
                        <label style="margin: 0;">Top Search Results</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="features" value="Premium Badge">
                        <label style="margin: 0;">Premium Badge</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="features" value="Photo Gallery">
                        <label style="margin: 0;">Photo Gallery</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="features" value="Video Support">
                        <label style="margin: 0;">Video Support</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="features" value="Analytics Dashboard">
                        <label style="margin: 0;">Analytics Dashboard</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="features" value="Lead Notifications">
                        <label style="margin: 0;">Lead Notifications</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="features" value="24/7 Support">
                        <label style="margin: 0;">24/7 Support</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="create_plan" class="btn">✨ Create Plan</button>
        </form>
    </div>
    
    <div class="panel-section">
        <h2>🎁 Premium Features</h2>
        <div style="background: #F0F9FF; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea;">
            <h3 style="margin: 0 0 12px 0; color: #0B1C3D;">What Businesses Get with Premium:</h3>
            <ul style="margin: 0; padding-left: 20px;">
                <li><strong>⭐ Featured Placement:</strong> Appear at the top of search results</li>
                <li><strong>🎖️ Premium Badge:</strong> Visible badge showing premium status</li>
                <li><strong>📊 Analytics:</strong> Track views, clicks, and lead conversions</li>
                <li><strong>🖼️ Media:</strong> Unlimited photos and video support</li>
                <li><strong>📱 Mobile Priority:</strong> Optimized mobile appearance</li>
                <li><strong>🔔 Smart Alerts:</strong> Real-time notifications on new inquiries</li>
                <li><strong>💬 Message Priority:</strong> Faster response times for inquiries</li>
                <li><strong>📈 Growth Tools:</strong> SEO optimization recommendations</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
