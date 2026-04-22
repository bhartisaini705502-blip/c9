<?php
/**
 * Admin - Manage Business Claims
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';
$action = isset($_GET['action']) ? $_GET['action'] : 'pending';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
    $approval_action = isset($_POST['action']) ? $_POST['action'] : '';
    $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    
    if ($claim_id && in_array($approval_action, ['approve', 'reject'])) {
        if ($approval_action === 'approve') {
            // Get business_id from claim
            $stmt = $conn->prepare("SELECT business_id FROM listing_claims WHERE id = ?");
            $stmt->bind_param('i', $claim_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $claim = $result->fetch_assoc();
            
            if ($claim) {
                // Mark business as verified
                $stmt = $conn->prepare("UPDATE extracted_businesses SET verified = 1 WHERE id = ?");
                $stmt->bind_param('i', $claim['business_id']);
                $stmt->execute();
                
                // Update claim status
                $stmt = $conn->prepare("UPDATE listing_claims SET claim_status = 'approved', approved_at = NOW(), approval_notes = ? WHERE id = ?");
                $stmt->bind_param('si', $admin_notes, $claim_id);
                $stmt->execute();
                
                $success = 'Claim approved! Business marked as verified.';
            }
        } else {
            // Reject claim
            $stmt = $conn->prepare("UPDATE listing_claims SET claim_status = 'rejected', approval_notes = ? WHERE id = ?");
            $stmt->bind_param('si', $admin_notes, $claim_id);
            $stmt->execute();
            
            $success = 'Claim rejected.';
        }
    }
}

$page_title = 'Manage Business Claims';
include '../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }
    
    .claims-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 25px;
        border-bottom: 2px solid #ddd;
    }
    
    .claims-tab {
        padding: 12px 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        color: #666;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
    }
    
    .claims-tab.active {
        color: #FF6A00;
        border-bottom-color: #FF6A00;
    }
    
    .claim-card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .claim-info-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 15px;
    }
    
    .claim-info-item {
        display: flex;
        flex-direction: column;
    }
    
    .claim-info-label {
        font-size: 12px;
        color: #999;
        margin-bottom: 3px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .claim-info-value {
        font-size: 15px;
        color: #333;
        font-weight: 500;
    }
    
    .claim-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .claim-btn {
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
    }
    
    .claim-btn-approve {
        background: #22C55E;
        color: white;
    }
    
    .claim-btn-reject {
        background: #EF4444;
        color: white;
    }
    
    .claim-status {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-pending {
        background: #FEF9C3;
        color: #333;
    }
    
    .status-approved {
        background: #DBEAFE;
        color: #0C4A6E;
    }
    
    .status-rejected {
        background: #FEE2E2;
        color: #991B1B;
    }
</style>

<div class="admin-container">
    <h2>🎯 Business Claims Management</h2>
    
    <?php if (isset($success)): ?>
        <div style="background: #D1FAE5; color: #065F46; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            ✅ <?php echo esc($success); ?>
        </div>
    <?php endif; ?>
    
    <div class="claims-tabs">
        <button class="claims-tab <?php echo $action === 'pending' ? 'active' : ''; ?>" onclick="location.href='?action=pending'">
            ⏳ Pending (<?php echo $conn->query("SELECT COUNT(*) as c FROM listing_claims WHERE claim_status = 'pending'")->fetch_assoc()['c']; ?>)
        </button>
        <button class="claims-tab <?php echo $action === 'approved' ? 'active' : ''; ?>" onclick="location.href='?action=approved'">
            ✅ Approved (<?php echo $conn->query("SELECT COUNT(*) as c FROM listing_claims WHERE claim_status = 'approved'")->fetch_assoc()['c']; ?>)
        </button>
        <button class="claims-tab <?php echo $action === 'rejected' ? 'active' : ''; ?>" onclick="location.href='?action=rejected'">
            ❌ Rejected (<?php echo $conn->query("SELECT COUNT(*) as c FROM listing_claims WHERE claim_status = 'rejected'")->fetch_assoc()['c']; ?>)
        </button>
    </div>
    
    <?php
    $status_filter = $action === 'pending' ? 'pending' : ($action === 'approved' ? 'approved' : 'rejected');
    $stmt = $conn->prepare("
        SELECT lc.*, eb.name as business_name, eb.formatted_phone_number,
               u.full_name as manager_name, u.email as manager_email, u.phone as manager_phone, u.company_name as manager_company
        FROM listing_claims lc
        JOIN extracted_businesses eb ON lc.business_id = eb.id
        LEFT JOIN users u ON lc.user_id = u.id
        WHERE lc.claim_status = ?
        ORDER BY lc.claimed_at DESC
    ");
    $stmt->bind_param('s', $status_filter);
    $stmt->execute();
    $claims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($claims)):
    ?>
        <div style="text-align: center; padding: 40px; color: #999;">
            No <?php echo $status_filter; ?> claims
        </div>
    <?php else: ?>
        <?php foreach ($claims as $claim): 
            // Decode claimant info from JSON
            $claimant_info = json_decode($claim['approval_notes'], true) ?? [];
            $claimant_name = $claimant_info['owner_name'] ?? 'Unknown';
            $claimant_email = $claimant_info['email'] ?? '';
            $claimant_phone = $claimant_info['phone'] ?? '';
            $claimant_role = $claimant_info['role'] ?? '';
            $claimant_message = $claimant_info['message'] ?? '';
        ?>
            <div class="claim-card">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; color: #333;"><?php echo esc($claim['business_name']); ?></h3>
                        <span class="claim-status status-<?php echo $claim['claim_status']; ?>">
                            <?php echo ucwords($claim['claim_status']); ?>
                        </span>
                    </div>
                    <div style="text-align: right; color: #999; font-size: 12px;">
                        <?php echo date('M d, Y g:i A', strtotime($claim['claimed_at'])); ?>
                    </div>
                </div>
                
                <div class="claim-info-row">
                    <div class="claim-info-item">
                        <span class="claim-info-label">Claimant Name</span>
                        <span class="claim-info-value"><?php echo esc($claimant_name); ?></span>
                    </div>
                    <div class="claim-info-item">
                        <span class="claim-info-label">Email</span>
                        <span class="claim-info-value"><?php echo esc($claimant_email); ?></span>
                    </div>
                    <div class="claim-info-item">
                        <span class="claim-info-label">Phone</span>
                        <span class="claim-info-value"><?php echo esc($claimant_phone); ?></span>
                    </div>
                    <div class="claim-info-item">
                        <span class="claim-info-label">Role</span>
                        <span class="claim-info-value"><?php echo esc($claimant_role); ?></span>
                    </div>
                    <div class="claim-info-item">
                        <span class="claim-info-label">Listing Manager</span>
                        <span class="claim-info-value"><?php echo esc($claim['manager_name'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="claim-info-item">
                        <span class="claim-info-label">Manager Email</span>
                        <span class="claim-info-value"><?php echo esc($claim['manager_email'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="claim-info-item">
                        <span class="claim-info-label">Manager Phone</span>
                        <span class="claim-info-value"><?php echo esc($claim['manager_phone'] ?: 'N/A'); ?></span>
                    </div>
                    <?php if (!empty($claim['manager_company'])): ?>
                    <div class="claim-info-item">
                        <span class="claim-info-label">Company</span>
                        <span class="claim-info-value"><?php echo esc($claim['manager_company']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($claimant_message)): ?>
                    <div class="claim-info-item" style="grid-column: 1 / -1;">
                        <span class="claim-info-label">Message</span>
                        <span class="claim-info-value"><?php echo esc($claimant_message); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($claim['claim_status'] === 'pending'): ?>
                    <form method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                        
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 13px;">Admin Notes (optional)</label>
                            <textarea name="admin_notes" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; min-height: 80px; box-sizing: border-box;" placeholder="Add any notes..."></textarea>
                        </div>
                        
                        <div class="claim-actions">
                            <button type="submit" name="action" value="approve" class="claim-btn claim-btn-approve">✅ Approve Claim</button>
                            <button type="submit" name="action" value="reject" class="claim-btn claim-btn-reject">❌ Reject Claim</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="margin-top: 15px; padding: 12px; background: #F5F7FA; border-radius: 6px; font-size: 13px;">
                        <strong>Status:</strong> <?php echo ucwords($claim['claim_status']); ?>
                        <?php if ($claim['approved_at']): ?>
                            <br><strong>Approved:</strong> <?php echo date('M d, Y g:i A', strtotime($claim['approved_at'])); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
