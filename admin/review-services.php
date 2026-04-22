<?php
/**
 * Admin Review - Business Services
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';

$message = '';
$error = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = (int)$_POST['service_id'];
    $action = $_POST['action'];
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $updateQuery = "UPDATE business_services SET status = ?, rejection_reason = ?, reviewed_at = NOW() WHERE id = ?";
    $stmt = $GLOBALS['conn']->prepare($updateQuery);
    $stmt->bind_param('ssi', $status, $rejection_reason, $service_id);
    
    if ($stmt->execute()) {
        $message = ($action === 'approve' ? 'Service approved' : 'Service rejected') . ' successfully';
    } else {
        $error = 'Failed to process action';
    }
}

$filter = $_GET['filter'] ?? 'pending';

$query = "SELECT bs.*, eb.name as business_name, u.full_name as editor_name 
          FROM business_services bs
          JOIN extracted_businesses eb ON bs.business_id = eb.id
          LEFT JOIN users u ON bs.editor_id = u.id
          WHERE bs.status = ?
          ORDER BY bs.created_at DESC";

$stmt = $GLOBALS['conn']->prepare($query);
$stmt->bind_param('s', $filter);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Review Services - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .service-card { border: 1px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 8px; background: #f9f9f9; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .form-group { margin: 15px 0; }
        .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; min-height: 80px; }
    </style>
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="container">
        <h1>🔧 Review Services</h1>
        
        <?php if ($message): ?>
            <div style="background: #d4edda; padding: 15px; border-radius: 6px; margin: 15px 0; color: #155724;">✓ <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background: #f8d7da; padding: 15px; border-radius: 6px; margin: 15px 0; color: #721c24;">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div style="margin: 20px 0; border-bottom: 2px solid #ddd;">
            <a href="?filter=pending" style="padding: 10px 20px; display: inline-block; <?php echo $filter === 'pending' ? 'border-bottom: 3px solid #1E3A8A;' : ''; ?>">⏳ Pending</a>
            <a href="?filter=approved" style="padding: 10px 20px; display: inline-block; <?php echo $filter === 'approved' ? 'border-bottom: 3px solid #1E3A8A;' : ''; ?>">✅ Approved</a>
            <a href="?filter=rejected" style="padding: 10px 20px; display: inline-block; <?php echo $filter === 'rejected' ? 'border-bottom: 3px solid #1E3A8A;' : ''; ?>">❌ Rejected</a>
        </div>
        
        <?php if (empty($services)): ?>
            <p style="color: #666; text-align: center; padding: 40px;">No <?php echo $filter; ?> services</p>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <div style="display: flex; justify-content: space-between;">
                        <div>
                            <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                            <p style="color: #666; margin: 5px 0;">Business: <strong><?php echo htmlspecialchars($service['business_name']); ?></strong></p>
                            <p style="color: #666; margin: 5px 0;">By: <?php echo htmlspecialchars($service['editor_name'] ?? 'Unknown'); ?> on <?php echo date('M d, Y g:i A', strtotime($service['created_at'])); ?></p>
                        </div>
                        <span class="status-badge status-<?php echo $service['status']; ?>"><?php echo ucfirst($service['status']); ?></span>
                    </div>
                    
                    <div style="margin: 15px 0; padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #1E3A8A;">
                        <p><strong>Price:</strong> ₹<?php echo $service['price'] ?: 'N/A'; ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($service['duration'] ?: 'N/A'); ?></p>
                        <?php if ($service['description']): ?>
                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($service['status'] === 'pending'): ?>
                        <form method="POST">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <div class="form-group">
                                <label>Rejection Reason (if rejecting):</label>
                                <textarea name="rejection_reason" placeholder="Explain why..."></textarea>
                            </div>
                            <div>
                                <button type="submit" name="action" value="approve" class="btn btn-approve">✅ Approve</button>
                                <button type="submit" name="action" value="reject" class="btn btn-reject">❌ Reject</button>
                            </div>
                        </form>
                    <?php elseif ($service['status'] === 'rejected' && $service['rejection_reason']): ?>
                        <p style="color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px;"><strong>Reason:</strong> <?php echo htmlspecialchars($service['rejection_reason']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
