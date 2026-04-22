<?php
/**
 * Premium Upgrade Checkout Page
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$user = getUserData();
$planId = intval($_GET['plan_id'] ?? 0);
$businessId = intval($_GET['bid'] ?? 0);

// Get plan
$plan = $GLOBALS['conn']->prepare("SELECT * FROM premium_plans WHERE id = ?");
$plan->bind_param('i', $planId);
$plan->execute();
$planData = $plan->get_result()->fetch_assoc();

if (!$planData) {
    redirect('/');
}

// Verify user owns business
if ($businessId > 0) {
    $verify = $GLOBALS['conn']->prepare("SELECT id FROM extracted_businesses WHERE id = ? AND claimed_by = ?");
    $verify->bind_param('ii', $businessId, $user['id']);
    $verify->execute();
    if ($verify->get_result()->num_rows === 0) {
        redirect('/pages/dashboard.php');
    }
    $business = $GLOBALS['conn']->query("SELECT * FROM extracted_businesses WHERE id = $businessId")->fetch_assoc();
} else {
    redirect('/');
}

$page_title = 'Upgrade to ' . $planData['name'];
include '../includes/header.php';
?>

<style>
.checkout-container {
    max-width: 800px;
    margin: 30px auto;
    padding: 0 20px 40px;
}

.checkout-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.checkout-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.checkout-header h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
}

.checkout-body {
    padding: 30px;
}

.plan-summary {
    background: #F0F9FF;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    border-left: 4px solid #667eea;
}

.plan-summary h3 {
    margin: 0 0 15px 0;
    color: #0B1C3D;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #ddd;
}

.summary-row:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 18px;
    color: #667eea;
    padding-top: 12px;
    padding-bottom: 0;
}

.features-list {
    background: #FFF9E6;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.features-list h4 {
    margin: 0 0 12px 0;
    color: #333;
}

.features-list ul {
    margin: 0;
    padding-left: 20px;
}

.features-list li {
    margin: 6px 0;
    color: #333;
}

.payment-methods {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 25px;
}

.payment-btn {
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s;
    font-weight: 600;
    color: #333;
    background: white;
}

.payment-btn:hover {
    border-color: #667eea;
    background: #F0F9FF;
}

.payment-btn.selected {
    border-color: #667eea;
    background: #F0F9FF;
    color: #667eea;
}

.checkout-form {
    display: grid;
    gap: 15px;
}

.form-group {
    display: grid;
    gap: 6px;
}

.form-group label {
    font-weight: 600;
    color: #333;
    font-size: 13px;
}

.form-group input,
.form-group select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 13px;
}

.terms {
    background: #F9F9F9;
    padding: 12px;
    border-radius: 5px;
    font-size: 12px;
    color: #666;
    margin-bottom: 15px;
}

.terms input {
    margin-right: 8px;
}

.checkout-btn {
    padding: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.checkout-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.security-badge {
    text-align: center;
    margin-top: 20px;
    color: #999;
    font-size: 12px;
}
</style>

<div class="checkout-container">
    <div class="checkout-card">
        <div class="checkout-header">
            <h1>Upgrade to <?php echo esc($planData['name']); ?></h1>
            <p><?php echo esc($business['name']); ?></p>
        </div>
        
        <div class="checkout-body">
            <!-- Order Summary -->
            <div class="plan-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span><?php echo esc($planData['name']); ?> Plan</span>
                    <span>$<?php echo number_format($planData['price'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Duration</span>
                    <span><?php echo $planData['duration_days']; ?> days</span>
                </div>
                <div class="summary-row">
                    <span>Total</span>
                    <span>$<?php echo number_format($planData['price'], 2); ?></span>
                </div>
            </div>
            
            <!-- Features -->
            <div class="features-list">
                <h4>✨ What's Included:</h4>
                <ul>
                    <?php foreach (explode(',', $planData['features']) as $feature): ?>
                    <li><?php echo esc(trim($feature)); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Payment Form -->
            <form id="checkoutForm" class="checkout-form" method="POST">
                <input type="hidden" name="plan_id" value="<?php echo $planId; ?>">
                <input type="hidden" name="business_id" value="<?php echo $businessId; ?>">
                
                <h3 style="margin-top: 0; color: #0B1C3D;">Payment Method</h3>
                
                <div class="payment-methods">
                    <label class="payment-btn" style="cursor: pointer;">
                        <input type="radio" name="payment_method" value="card" checked style="display: none;">
                        <div style="font-size: 20px; margin-bottom: 5px;">💳</div>
                        Credit Card
                    </label>
                    <label class="payment-btn" style="cursor: pointer;">
                        <input type="radio" name="payment_method" value="paypal" style="display: none;">
                        <div style="font-size: 20px; margin-bottom: 5px;">🅿️</div>
                        PayPal
                    </label>
                </div>
                
                <!-- Card Details (shown for card payment) -->
                <div id="cardFields" style="display: grid; gap: 15px;">
                    <div class="form-group">
                        <label>Cardholder Name *</label>
                        <input type="text" name="cardholder_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Card Number *</label>
                        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Expiry Date *</label>
                            <input type="text" name="expiry" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label>CVV *</label>
                            <input type="text" name="cvv" placeholder="123" maxlength="4" required>
                        </div>
                    </div>
                </div>
                
                <!-- Billing Address -->
                <div>
                    <h3 style="color: #0B1C3D; margin-bottom: 15px;">Billing Address</h3>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo esc($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" value="<?php echo esc($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Country *</label>
                        <input type="text" name="country" placeholder="United States" required>
                    </div>
                </div>
                
                <!-- Terms -->
                <div class="terms">
                    <label style="display: flex; align-items: center; margin: 0; cursor: pointer;">
                        <input type="checkbox" name="agree_terms" required>
                        <span>I agree to the Terms of Service and authorize this payment</span>
                    </label>
                </div>
                
                <button type="submit" class="checkout-btn">
                    Complete Purchase - $<?php echo number_format($planData['price'], 2); ?>
                </button>
            </form>
            
            <!-- Security Badge -->
            <div class="security-badge">
                🔒 Your payment is secure and encrypted | 30-day money-back guarantee
            </div>
        </div>
    </div>
</div>

<script>
// Payment method selector
document.querySelectorAll('.payment-methods input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-btn').forEach(btn => btn.classList.remove('selected'));
        this.parentElement.classList.add('selected');
    });
});

// Initialize
document.querySelector('.payment-methods').querySelector('input[type="radio"]:checked').parentElement.classList.add('selected');

// Form validation and submission
document.getElementById('checkoutForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Processing...';
    
    try {
        const response = await fetch('/api/process-payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                plan_id: this.querySelector('input[name="plan_id"]').value,
                business_id: this.querySelector('input[name="business_id"]').value,
                payment_method: this.querySelector('input[name="payment_method"]:checked').value,
                amount: <?php echo $planData['price']; ?>,
                currency: 'USD'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Payment successful! Your premium upgrade is now active.');
            window.location.href = '/pages/dashboard.php';
        } else {
            alert('Payment failed: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.textContent = 'Complete Purchase - $' + data.amount;
        }
    } catch (error) {
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.textContent = 'Complete Purchase';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
