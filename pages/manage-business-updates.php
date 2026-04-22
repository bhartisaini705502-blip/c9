<?php
/**
 * Manage Business Updates/Blog Posts
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

// Handle new/edit update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $update_id = isset($_POST['update_id']) ? (int)$_POST['update_id'] : null;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        if (!$title || !$content) {
            $error = 'Title and content are required';
        } else {
            if ($update_id) {
                // Update existing
                $updateQuery = "UPDATE business_updates SET title = ?, content = ?, status = 'pending' WHERE id = ? AND business_id = ?";
                $stmt = $GLOBALS['conn']->prepare($updateQuery);
                $stmt->bind_param('ssii', $title, $content, $update_id, $business_id);
                if ($stmt->execute()) {
                    $message = 'Update saved and sent for review';
                } else {
                    $error = 'Failed to save update';
                }
            } else {
                // Create new
                $insertQuery = "INSERT INTO business_updates (business_id, editor_id, title, content, status) VALUES (?, ?, ?, ?, 'pending')";
                $stmt = $GLOBALS['conn']->prepare($insertQuery);
                $stmt->bind_param('iiss', $business_id, $user_id, $title, $content);
                if ($stmt->execute()) {
                    $message = 'Update created and sent for admin review';
                } else {
                    $error = 'Failed to create update';
                }
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $update_id = (int)$_POST['update_id'];
        $deleteQuery = "DELETE FROM business_updates WHERE id = ? AND business_id = ? AND status IN ('pending', 'rejected')";
        $stmt = $GLOBALS['conn']->prepare($deleteQuery);
        $stmt->bind_param('ii', $update_id, $business_id);
        if ($stmt->execute()) {
            $message = 'Update deleted';
        } else {
            $error = 'Failed to delete update';
        }
    }
}

// Get all updates for this business
$updatesQuery = "SELECT * FROM business_updates WHERE business_id = ? ORDER BY created_at DESC";
$updatesStmt = $GLOBALS['conn']->prepare($updatesQuery);
$updatesStmt->bind_param('i', $business_id);
$updatesStmt->execute();
$updates = $updatesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group by status
$pending = array_filter($updates, fn($u) => $u['status'] === 'pending');
$approved = array_filter($updates, fn($u) => $u['status'] === 'approved');
$rejected = array_filter($updates, fn($u) => $u['status'] === 'rejected');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Updates - <?php echo htmlspecialchars($claimedBusiness['name']); ?></title>
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
        .update-card { border: 1px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 8px; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { min-height: 150px; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>📝 Manage Business Updates</h1>
        <?php if (count($allClaimed) > 1): ?>
        <div style="margin: 20px 0; padding: 15px; background: #f0f7ff; border-radius: 6px;">
            <label style="font-weight: bold; margin-right: 10px;">Select Business:</label>
            <select onchange="window.location.href='/pages/manage-business-updates.php?business_id=' + this.value" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ccc;">
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
        
        <!-- Form to create/edit update -->
        <div class="update-card" style="background: #f9f9f9; border: 2px solid #0B1C3D;">
            <h3>Create New Update</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" placeholder="e.g., New Menu Item Added, Extended Hours..." required>
                </div>
                <div class="form-group">
                    <label>Content/Description</label>
                    <textarea name="content" placeholder="Share your update details..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">📤 Submit for Review</button>
            </form>
        </div>
        
        <!-- Tabs -->
        <div style="border-bottom: 2px solid #ddd; margin: 20px 0;">
            <button class="tab-btn" onclick="showTab('pending')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Pending (<?php echo count($pending); ?>)</button>
            <button class="tab-btn" onclick="showTab('approved')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Approved (<?php echo count($approved); ?>)</button>
            <button class="tab-btn" onclick="showTab('rejected')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Rejected (<?php echo count($rejected); ?>)</button>
        </div>
        
        <!-- Pending Updates -->
        <div id="pending" class="tab-content">
            <?php if (empty($pending)): ?>
                <p style="color: #666;">No pending updates</p>
            <?php else: ?>
                <?php foreach ($pending as $update): ?>
                    <div class="update-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4><?php echo htmlspecialchars($update['title']); ?></h4>
                                <span class="status-badge status-pending">⏳ Pending Review</span>
                                <p style="color: #666; font-size: 13px; margin: 10px 0;">Submitted: <?php echo date('M d, Y g:i A', strtotime($update['created_at'])); ?></p>
                                <p><?php echo htmlspecialchars(substr($update['content'], 0, 100)); ?>...</p>
                            </div>
                            <div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="update_id" value="<?php echo $update['id']; ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this update?')">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Approved Updates -->
        <div id="approved" class="tab-content" style="display: none;">
            <?php if (empty($approved)): ?>
                <p style="color: #666;">No approved updates</p>
            <?php else: ?>
                <?php foreach ($approved as $update): ?>
                    <div class="update-card">
                        <h4><?php echo htmlspecialchars($update['title']); ?></h4>
                        <span class="status-badge status-approved">✅ Approved</span>
                        <p style="color: #666; font-size: 13px; margin: 10px 0;">Approved: <?php echo date('M d, Y', strtotime($update['reviewed_at'])); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($update['content'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Rejected Updates -->
        <div id="rejected" class="tab-content" style="display: none;">
            <?php if (empty($rejected)): ?>
                <p style="color: #666;">No rejected updates</p>
            <?php else: ?>
                <?php foreach ($rejected as $update): ?>
                    <div class="update-card">
                        <h4><?php echo htmlspecialchars($update['title']); ?></h4>
                        <span class="status-badge status-rejected">❌ Rejected</span>
                        <p style="color: #666; font-size: 13px; margin: 10px 0;">Reason: <?php echo htmlspecialchars($update['rejection_reason'] ?? 'No reason provided'); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($update['content'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showTab(name) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.getElementById(name).style.display = 'block';
            document.querySelectorAll('.tab-btn').forEach(el => el.style.background = '#f0f0f0');
            event.target.style.background = '#1E3A8A';
            event.target.style.color = 'white';
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
