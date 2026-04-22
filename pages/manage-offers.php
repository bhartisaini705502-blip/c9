<?php
/**
 * Manage Business Offers & Promotions
 */

require_once '../config/db.php';
require_once '../config/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Get all claimed businesses
$allClaimedQuery = "SELECT lc.business_id, eb.name FROM listing_claims lc 
                    JOIN extracted_businesses eb ON lc.business_id = eb.id 
                    WHERE lc.user_id = ? AND lc.claim_status = 'approved'
                    ORDER BY lc.claimed_at DESC";
$allClaimedStmt = $GLOBALS['conn']->prepare($allClaimedQuery);
$allClaimedStmt->bind_param('i', $user_id);
$allClaimedStmt->execute();
$allClaimed = $allClaimedStmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($allClaimed)) {
    header('Location: /pages/dashboard.php');
    exit;
}

// Get selected business_id from parameter or use the first one
$selectedBusinessId = isset($_GET['business_id']) ? (int)$_GET['business_id'] : null;

// Validate that the selected business belongs to this user
$claimedBusiness = null;
foreach ($allClaimed as $claimed) {
    if ($selectedBusinessId === $claimed['business_id'] || $selectedBusinessId === null) {
        $claimedBusiness = $claimed;
        $selectedBusinessId = $claimed['business_id'];
        break;
    }
}

if (!$claimedBusiness) {
    header('Location: /pages/dashboard.php');
    exit;
}

$business_id = $claimedBusiness['business_id'];
$message = '';
$error = '';

// Handle offer operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $offer_title = trim($_POST['offer_title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discount_type = $_POST['discount_type'] ?? 'percentage';
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $valid_from = $_POST['valid_from'] ?? null;
        $valid_until = $_POST['valid_until'] ?? null;
        
        if (!$offer_title || !$discount_value) {
            $error = 'Offer title and discount value are required';
        } else {
            $insertQuery = "INSERT INTO business_offers (business_id, editor_id, offer_title, description, discount_type, discount_value, valid_from, valid_until, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $GLOBALS['conn']->prepare($insertQuery);
            $stmt->bind_param('iisssdss', $business_id, $user_id, $offer_title, $description, $discount_type, $discount_value, $valid_from, $valid_until);
            if ($stmt->execute()) {
                $message = 'Offer created and sent for review';
            } else {
                $error = 'Failed to create offer';
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $offer_id = (int)$_POST['offer_id'];
        $deleteQuery = "DELETE FROM business_offers WHERE id = ? AND business_id = ? AND status IN ('pending', 'rejected')";
        $stmt = $GLOBALS['conn']->prepare($deleteQuery);
        $stmt->bind_param('ii', $offer_id, $business_id);
        if ($stmt->execute()) {
            $message = 'Offer deleted';
        } else {
            $error = 'Failed to delete offer';
        }
    }
}

// Get all offers
$offersQuery = "SELECT * FROM business_offers WHERE business_id = ? ORDER BY created_at DESC";
$offersStmt = $GLOBALS['conn']->prepare($offersQuery);
$offersStmt->bind_param('i', $business_id);
$offersStmt->execute();
$offers = $offersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pending = array_filter($offers, fn($o) => $o['status'] === 'pending');
$approved = array_filter($offers, fn($o) => $o['status'] === 'approved');
$rejected = array_filter($offers, fn($o) => $o['status'] === 'rejected');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Offers - <?php echo htmlspecialchars($claimedBusiness['name']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-primary { background: #1E3A8A; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .offer-card { border: 1px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 8px; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>🎉 Manage Offers & Promotions</h1>
        <?php if (count($allClaimed) > 1): ?>
        <div style="margin: 20px 0; padding: 15px; background: #f0f7ff; border-radius: 6px;">
            <label style="font-weight: bold; margin-right: 10px;">Select Business:</label>
            <select onchange="window.location.href='/pages/manage-offers.php?business_id=' + this.value" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ccc;">
                <?php foreach ($allClaimed as $claimed): ?>
                <option value="<?php echo $claimed['business_id']; ?>" <?php echo $selectedBusinessId === $claimed['business_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($claimed['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <p>Business: <strong><?php echo htmlspecialchars($claimedBusiness['name']); ?></strong></p>
        
        <?php if ($message): ?>
            <div style="background: #d4edda; padding: 15px; border-radius: 6px; margin: 15px 0; color: #155724;">✓ <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background: #f8d7da; padding: 15px; border-radius: 6px; margin: 15px 0; color: #721c24;">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Form -->
        <div class="offer-card" style="background: #f9f9f9; border: 2px solid #0B1C3D;">
            <h3>Create New Offer</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Offer Title *</label>
                        <input type="text" name="offer_title" placeholder="e.g., 50% Off on Haircuts" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group">
                            <label>Discount Type</label>
                            <select name="discount_type">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed (₹)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Discount Value *</label>
                            <input type="number" name="discount_value" placeholder="e.g., 50" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Valid From</label>
                        <input type="date" name="valid_from">
                    </div>
                    <div class="form-group">
                        <label>Valid Until</label>
                        <input type="date" name="valid_until">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Details about this offer..." style="min-height: 100px;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">🎊 Create Offer</button>
            </form>
        </div>
        
        <!-- Tabs -->
        <div style="border-bottom: 2px solid #ddd; margin: 20px 0;">
            <button class="tab-btn" onclick="showTab('pending')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Pending (<?php echo count($pending); ?>)</button>
            <button class="tab-btn" onclick="showTab('approved')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Approved (<?php echo count($approved); ?>)</button>
            <button class="tab-btn" onclick="showTab('rejected')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Rejected (<?php echo count($rejected); ?>)</button>
        </div>
        
        <!-- Offers by Status -->
        <div id="pending" class="tab-content">
            <?php if (empty($pending)): ?>
                <p style="color: #666;">No pending offers</p>
            <?php else: ?>
                <?php foreach ($pending as $offer): ?>
                    <div class="offer-card">
                        <h4><?php echo htmlspecialchars($offer['offer_title']); ?></h4>
                        <span class="status-badge status-pending">⏳ Pending</span>
                        <p><strong>Discount:</strong> <?php echo $offer['discount_value']; ?><?php echo $offer['discount_type'] === 'percentage' ? '%' : '₹'; ?></p>
                        <p><strong>Valid:</strong> <?php echo $offer['valid_from'] ? date('M d, Y', strtotime($offer['valid_from'])) : 'From now'; ?> to <?php echo $offer['valid_until'] ? date('M d, Y', strtotime($offer['valid_until'])) : 'No end date'; ?></p>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete?')">🗑️ Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div id="approved" class="tab-content" style="display: none;">
            <?php if (empty($approved)): ?>
                <p style="color: #666;">No approved offers</p>
            <?php else: ?>
                <?php foreach ($approved as $offer): ?>
                    <div class="offer-card">
                        <h4><?php echo htmlspecialchars($offer['offer_title']); ?></h4>
                        <span class="status-badge status-approved">✅ Approved</span>
                        <p><strong>Discount:</strong> <?php echo $offer['discount_value']; ?><?php echo $offer['discount_type'] === 'percentage' ? '%' : '₹'; ?></p>
                        <p><strong>Valid:</strong> <?php echo $offer['valid_from'] ? date('M d, Y', strtotime($offer['valid_from'])) : 'From now'; ?> to <?php echo $offer['valid_until'] ? date('M d, Y', strtotime($offer['valid_until'])) : 'No end date'; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div id="rejected" class="tab-content" style="display: none;">
            <?php if (empty($rejected)): ?>
                <p style="color: #666;">No rejected offers</p>
            <?php else: ?>
                <?php foreach ($rejected as $offer): ?>
                    <div class="offer-card">
                        <h4><?php echo htmlspecialchars($offer['offer_title']); ?></h4>
                        <span class="status-badge status-rejected">❌ Rejected</span>
                        <p>Reason: <?php echo htmlspecialchars($offer['rejection_reason'] ?? 'No reason provided'); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showTab(name) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.getElementById(name).style.display = 'block';
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
