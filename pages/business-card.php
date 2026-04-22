<?php
/**
 * Business Card Component - Reusable for listing pages
 * Shows verified badge, claim button, and key details
 */

if (!isset($business)) {
    return; // Card needs $business variable
}

$verified = isset($business['verified']) ? $business['verified'] : 0;
?>

<div class="business-card" style="border-top: 4px solid transparent;">
    <div class="business-card-image">
        <?php echo $business['types'] ? substr($business['types'], 0, 1) : '📍'; ?>
    </div>
    
    <div class="business-card-content">
        <!-- Business Name with Verified Badge -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
            <div class="business-card-title" style="flex: 1; margin: 0;">
                <?php echo esc($business['name']); ?>
            </div>
            <?php if ($verified): ?>
                <span style="background: #22C55E; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; white-space: nowrap;">
                    ✔ Verified
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Rating -->
        <div class="business-card-rating">
            ⭐ <?php echo $business['rating']; ?> (<?php echo $business['user_ratings_total']; ?> reviews)
        </div>
        
        <!-- Category & Address -->
        <div class="business-card-category">
            <?php echo esc(explode(',', $business['types'])[0]); ?>
        </div>
        <div class="business-card-location">
            📍 <?php echo esc(explode(',', $business['formatted_address'])[0]); ?>
        </div>
        
        <!-- Phone -->
        <?php if ($business['formatted_phone_number']): ?>
            <div style="margin-bottom: 10px; color: #333; font-size: 14px;">
                📱 <a href="tel:<?php echo esc($business['formatted_phone_number']); ?>" style="color: #1E3A8A; text-decoration: none; font-weight: 500;">
                    <?php echo esc($business['formatted_phone_number']); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Unverified Warning -->
        <?php if (!$verified): ?>
            <div style="background: #FEF9C3; border-left: 4px solid #FF6A00; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-size: 12px; color: #333;">
                <strong>⚠️ Unverified</strong> - Are you the owner? 
                <a href="#" onclick="showClaimForm(<?php echo $business['id']; ?>, '<?php echo esc(addslashes($business['name'])); ?>')" style="color: #FF6A00; text-decoration: none; font-weight: 600;">
                    Claim for FREE
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 8px;">
            <a href="tel:<?php echo esc($business['formatted_phone_number'] ?? ''); ?>" style="flex: 1; padding: 10px; background: #FF6A00; color: white; text-align: center; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">
                📞 Call
            </a>
            <a href="/pages/business-detail.php?id=<?php echo $business['id']; ?>&name=<?php echo urlencode(slugify($business['name'])); ?>" style="flex: 1; padding: 10px; background: #1E3A8A; color: white; text-align: center; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">
                View Details
            </a>
        </div>
    </div>
</div>

<script>
function showClaimForm(businessId, businessName) {
    const modal = document.getElementById('claimModal');
    document.getElementById('claimBusinessId').value = businessId;
    document.getElementById('claimBusinessName').textContent = businessName;
    modal.style.display = 'flex';
}

function closeClaimForm() {
    document.getElementById('claimModal').style.display = 'none';
}

function submitClaimForm() {
    const form = document.getElementById('claimForm');
    const formData = new FormData(form);
    
    fetch('/api/claim-business.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ Claim submitted! An admin will review and verify your business.');
            closeClaimForm();
            form.reset();
        } else {
            alert('❌ ' + (data.error || 'Error submitting claim'));
        }
    })
    .catch(err => {
        alert('❌ Error submitting claim');
        console.error(err);
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('claimModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>
