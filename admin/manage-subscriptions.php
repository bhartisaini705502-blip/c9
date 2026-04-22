<?php
/**
 * Admin: Manage Subscriptions
 */
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';

$message = '';
$message_type = 'success';

// Handle subscription actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action === 'mark_paid') {
            $sub_id = intval($_POST['sub_id']);
            $stmt = $conn->prepare("UPDATE subscriptions SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param('i', $sub_id);
            if ($stmt->execute()) {
                $message = '✅ Subscription marked as paid';
                $message_type = 'success';
            }
            $stmt->close();
        } elseif ($action === 'renew') {
            $sub_id = intval($_POST['sub_id']);
            $stmt = $conn->prepare("
                UPDATE subscriptions 
                SET end_date = DATE_ADD(NOW(), INTERVAL (SELECT duration_days FROM plans WHERE id = subscriptions.plan_id) DAY),
                    status = 'active',
                    payment_status = 'paid'
                WHERE id = ?
            ");
            $stmt->bind_param('i', $sub_id);
            if ($stmt->execute()) {
                $message = '✅ Subscription renewed';
                $message_type = 'success';
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $message = '⚠️ Action requires subscriptions table. This feature is coming soon.';
        $message_type = 'warning';
    }
}

// Get active subscriptions
$subscriptions = [];
$expiring = [];
$table_error = false;

try {
    // Check if subscriptions table exists
    $result = $conn->query("
        SELECT s.*, p.name as plan_name, p.price, eb.name as business_name
        FROM subscriptions s
        JOIN plans p ON s.plan_id = p.id
        JOIN extracted_businesses eb ON s.business_id = eb.id
        WHERE s.status = 'active'
        ORDER BY s.end_date ASC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $subscriptions[] = $row;
        }
    }

    // Get expiring soon (within 7 days)
    $result = $conn->query("
        SELECT s.*, p.name as plan_name, p.price, eb.name as business_name
        FROM subscriptions s
        JOIN plans p ON s.plan_id = p.id
        JOIN extracted_businesses eb ON s.business_id = eb.id
        WHERE s.status = 'active' AND s.end_date <= DATE_ADD(NOW(), INTERVAL 7 DAY) AND s.end_date > NOW()
        ORDER BY s.end_date ASC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $expiring[] = $row;
        }
    }
} catch (Exception $e) {
    $table_error = true;
    // Use sample data for demo
    $subscriptions = [
        [
            'id' => 1,
            'business_id' => 1,
            'business_name' => 'ABC Electronics',
            'plan_id' => 2,
            'plan_name' => 'Premium Plan',
            'price' => 499,
            'start_date' => '2026-01-15',
            'end_date' => '2026-04-15',
            'status' => 'active',
            'payment_status' => 'paid'
        ]
    ];
    $expiring = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Subscriptions</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .subs-container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .subs-header { color: #0B1C3D; margin: 30px 0; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid; }
        .message.success { background: #d4edda; color: #155724; border-left-color: #28a745; }
        .message.warning { background: #fff3cd; color: #856404; border-left-color: #ffc107; }
        .subs-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; margin-bottom: 30px; }
        .subs-table th { background: #0B1C3D; color: white; padding: 15px; text-align: left; }
        .subs-table td { padding: 15px; border-bottom: 1px solid #ddd; }
        .subs-table tr:hover { background: #f9f9f9; }
        .plan-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; color: white; }
        .plan-free { background: #999; }
        .plan-basic { background: #FF6A00; }
        .plan-premium { background: #FFD700; color: #333; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-paid { background: #D4EDDA; color: #155724; }
        .status-pending { background: #FFF3CD; color: #856404; }
        .status-expiring { background: #FFE5E5; color: #C3180C; }
        .actions { display: flex; gap: 10px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 12px; background: #FF6A00; color: white; }
        .btn:hover { background: #E55A00; }
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; color: #FF6A00; }
        .stat-label { color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="subs-container">
        <h1 class="subs-header">💳 Manage Subscriptions</h1>
        
        <?php if ($table_error): ?>
            <div class="message warning">
                <strong>ℹ️ Note:</strong> Subscriptions table not found in database. Showing sample data for demonstration. To enable real subscriptions, create the subscriptions table in your database.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- STATS -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($subscriptions); ?></div>
                <div class="stat-label">Active Subscriptions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($expiring); ?></div>
                <div class="stat-label">Expiring Soon (7 days)</div>
            </div>
        </div>
        
        <!-- EXPIRING SOON -->
        <?php if (!empty($expiring)): ?>
            <h2>⚠️ Expiring Soon (<?php echo count($expiring); ?>)</h2>
            <table class="subs-table">
                <thead>
                    <tr>
                        <th>Business Name</th>
                        <th>Plan</th>
                        <th>End Date</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiring as $sub): ?>
                        <tr>
                            <td><strong><?php echo esc($sub['business_name']); ?></strong></td>
                            <td>
                                <span class="plan-badge plan-<?php echo strtolower($sub['plan_name']); ?>">
                                    <?php echo ucfirst($sub['plan_name']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($sub['end_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $sub['payment_status']; ?>">
                                    <?php echo ucfirst($sub['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="renew">
                                    <input type="hidden" name="sub_id" value="<?php echo $sub['id']; ?>">
                                    <button type="submit" class="btn">🔄 Renew</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <!-- ACTIVE SUBSCRIPTIONS -->
        <h2>✅ Active Subscriptions (<?php echo count($subscriptions); ?>)</h2>
        
        <?php if (empty($subscriptions)): ?>
            <div class="empty-state">
                <p>No active subscriptions yet.</p>
            </div>
        <?php else: ?>
            <table class="subs-table">
                <thead>
                    <tr>
                        <th>Business Name</th>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td><strong><?php echo esc($sub['business_name']); ?></strong></td>
                            <td>
                                <span class="plan-badge plan-<?php echo strtolower($sub['plan_name']); ?>">
                                    <?php echo ucfirst($sub['plan_name']); ?>
                                </span>
                            </td>
                            <td>₹<?php echo $sub['price']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($sub['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($sub['end_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $sub['payment_status']; ?>">
                                    <?php echo ucfirst($sub['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($sub['payment_status'] === 'pending'): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="sub_id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="btn">💰 Mark Paid</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
