<?php
/**
 * Upgrade Plan - Purchase Subscription
 */

require '../config/db.php';
require '../includes/functions.php';

$business_id = intval($_GET['id'] ?? 0);
$plan_id = intval($_GET['plan'] ?? 0);

if ($business_id <= 0) {
    header('Location: /');
    exit;
}

// Get business
$business = $conn->query("SELECT * FROM extracted_businesses WHERE id = $business_id")->fetch_assoc();
if (!$business) {
    header('Location: /');
    exit;
}

// Get all plans
$plans = [];
$result = $conn->query("SELECT * FROM premium_plans ORDER BY price ASC");
while ($row = $result->fetch_assoc()) {
    $plans[] = $row;
}

// Get current featured listing status
$current_sub = null;
$featured_result = $conn->query("SELECT * FROM featured_listings WHERE business_id = $business_id AND expires_at > NOW() ORDER BY featured_at DESC LIMIT 1");
if ($featured_result && $featured_result->num_rows > 0) {
    $current_sub = $featured_result->fetch_assoc();
}

$page_title = 'Upgrade Plan - ' . $business['name'];
include '../includes/header.php';
?>

<section class="upgrade-section">
    <div class="container">
        <h1>💰 Upgrade Your Plan</h1>
        <p class="subtitle"><?php echo esc($business['name']); ?></p>
        
        <?php if ($current_sub): ?>
            <div class="current-plan">
                <p>📌 Current Status: <strong><?php echo ucfirst($current_sub['plan_type']); ?> Listing</strong></p>
                <p>Expires: <?php echo date('M d, Y', strtotime($current_sub['expires_at'])); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="plans-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card <?php echo ($current_sub && $current_sub['plan_id'] === (int)$plan['id']) ? 'current' : ''; ?>">
                    <h2><?php echo $plan['name']; ?></h2>
                    <div class="plan-price">₹<?php echo number_format($plan['price']); ?></div>
                    <p class="plan-duration"><?php echo $plan['duration_days']; ?> days</p>
                    
                    <div class="plan-features">
                        <?php 
                        $features = explode('|', $plan['features']);
                        foreach ($features as $feature): 
                        ?>
                            <p>✓ <?php echo trim($feature); ?></p>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="POST" action="/api/manage-featured.php" style="display: inline;">
                        <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
                        <input type="hidden" name="plan_type" value="<?php echo $plan['id'] === 1 ? 'featured' : 'boosted'; ?>">
                        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                        <button type="submit" class="plan-btn">
                            <?php echo $plan['price'] > 0 ? '💳 Upgrade' : '✅ Choose'; ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="payment-info">
            <h3>💳 Payment Methods</h3>
            <p>We accept UPI, Net Banking, and Card payments via Razorpay</p>
            <p class="note">After selecting a plan, you'll be guided to secure payment</p>
        </div>
    </div>
</section>

<style>
    .upgrade-section { padding: 40px 0; background: #f9f9f9; }
    .upgrade-section h1 { color: #0B1C3D; text-align: center; margin-bottom: 10px; }
    .subtitle { text-align: center; color: #666; margin-bottom: 30px; font-size: 18px; }
    .current-plan { background: #E3F2FD; padding: 15px; border-radius: 4px; margin-bottom: 30px; border-left: 4px solid #1E3A8A; }
    .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .plan-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; transition: transform 0.2s, box-shadow 0.2s; border-top: 4px solid #999; }
    .plan-card.current { border-top-color: #28a745; box-shadow: 0 4px 16px rgba(40, 167, 69, 0.2); transform: translateY(-8px); }
    .plan-card h2 { color: #0B1C3D; margin: 0 0 10px 0; font-size: 24px; }
    .plan-price { font-size: 32px; font-weight: bold; color: #FF6A00; margin: 15px 0; }
    .plan-duration { color: #666; margin: 0 0 20px 0; }
    .plan-features { text-align: left; background: #f9f9f9; padding: 20px; border-radius: 4px; margin: 20px 0; }
    .plan-features p { margin: 8px 0; color: #333; }
    .plan-btn { width: 100%; padding: 12px; background: #FF6A00; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px; }
    .plan-btn:hover { background: #E55A00; }
    .plan-btn.active { background: #28a745; cursor: default; }
    .plan-btn.active:hover { background: #28a745; }
    .payment-info { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
    .payment-info h3 { color: #0B1C3D; margin-top: 0; }
    .payment-info p { color: #666; margin: 10px 0; }
    .payment-info .note { font-style: italic; font-size: 12px; }
</style>

<?php include '../includes/footer.php'; ?>
