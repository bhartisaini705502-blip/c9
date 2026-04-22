<?php
/**
 * Claim Business Modal - Include on pages that display businesses
 */
require_once __DIR__ . '/../config/auth.php';
$claimUser = getUserData();
$claimBlocked = false;
if (isset($GLOBALS['conn']) && isset($business['id'])) {
    $claimCheck = $GLOBALS['conn']->prepare("SELECT id FROM listing_claims WHERE business_id = ? AND claim_status IN ('pending', 'approved') LIMIT 1");
    if ($claimCheck) {
        $claimCheck->bind_param('i', $business['id']);
        $claimCheck->execute();
        $claimBlocked = $claimCheck->get_result()->num_rows > 0;
        $claimCheck->close();
    }
}
?>

<style>
#claimModal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.claim-modal-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.claim-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
}

.claim-modal-header h2 {
    margin: 0;
    color: #0B1C3D;
    font-size: 22px;
}

.claim-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #999;
}

.claim-form-group {
    margin-bottom: 15px;
}

.claim-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
    font-size: 14px;
}

.claim-form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
}

.claim-form-group input:focus {
    outline: none;
    border-color: #FF6A00;
    box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
}

.claim-form-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.claim-btn-submit {
    flex: 1;
    padding: 12px;
    background: #FF6A00;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
}

.claim-btn-submit:hover {
    background: #e65c00;
}

.claim-btn-cancel {
    flex: 1;
    padding: 12px;
    background: #F5F7FA;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
}

.claim-btn-cancel:hover {
    background: #eee;
}

.claim-info {
    background: #FEF9C3;
    border-left: 4px solid #FF6A00;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-size: 13px;
    color: #333;
}

@media (max-width: 480px) {
    .claim-modal-content {
        padding: 20px;
    }
    
    .claim-modal-header h2 {
        font-size: 18px;
    }
}
</style>

<?php if (!$claimBlocked): ?>
<div id="claimModal">
    <div class="claim-modal-content">
        <div class="claim-modal-header">
            <h2>Claim Business</h2>
            <button class="claim-modal-close" onclick="closeClaimForm()">✕</button>
        </div>
        
        <div class="claim-info">
            <strong>Claiming:</strong> <span id="claimBusinessName"></span>
        </div>
        
        <form id="claimForm" onsubmit="submitClaimForm(); return false;">
            <input type="hidden" id="claimBusinessId" name="business_id">
            
            <div class="claim-form-group">
                <label>Your Name *</label>
                <input type="text" name="owner_name" required placeholder="John Doe" value="<?php echo htmlspecialchars($claimUser['full_name'] ?? ''); ?>">
            </div>
            
            <div class="claim-form-group">
                <label>Email Address *</label>
                <input type="email" name="email" required placeholder="john@example.com" value="<?php echo htmlspecialchars($claimUser['email'] ?? ''); ?>">
            </div>
            
            <div class="claim-form-group">
                <label>Phone Number *</label>
                <input type="tel" name="phone" required placeholder="+91 9876543210" value="<?php echo htmlspecialchars($claimUser['phone'] ?? ''); ?>">
            </div>
            
            <div class="claim-form-group">
                <label>Your Role at Business *</label>
                <select name="role" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    <option value="">Select...</option>
                    <option value="owner">Owner</option>
                    <option value="manager">Manager</option>
                    <option value="employee">Employee</option>
                </select>
            </div>
            
            <div class="claim-form-group">
                <label>Message (Optional)</label>
                <textarea name="message" placeholder="Tell us why you want to claim this business..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; min-height: 80px; box-sizing: border-box;"></textarea>
            </div>
            
            <div class="claim-info">
                📋 An admin will verify your claim within 24 hours. Once approved, your listing will show a ✓ Verified badge.
            </div>
            
            <div class="claim-form-buttons">
                <button type="submit" class="claim-btn-submit">Submit Claim</button>
                <button type="button" class="claim-btn-cancel" onclick="closeClaimForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
