<?php
/**
 * Admin: Manage Pricing Plans
 */
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';

$message = '';
$message_type = 'success';

// Handle plan actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $name = $conn->real_escape_string($_POST['name']);
            $price = floatval($_POST['price']);
            $features = $conn->real_escape_string($_POST['features']);
            $duration = intval($_POST['duration']);
            
            $stmt = $conn->prepare("
                INSERT INTO premium_plans (name, price, features, duration_days)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param('sdsi', $name, $price, $features, $duration);
            
            if ($stmt->execute()) {
                $message = '✅ Plan added successfully!';
            } else {
                $message = '❌ Error adding plan';
                $message_type = 'error';
            }
            $stmt->close();
        }
        elseif ($action === 'update') {
            $plan_id = intval($_POST['plan_id']);
            $name = $conn->real_escape_string($_POST['name']);
            $price = floatval($_POST['price']);
            $features = $conn->real_escape_string($_POST['features']);
            
            $stmt = $conn->prepare("
                UPDATE premium_plans SET name = ?, price = ?, features = ? WHERE id = ?
            ");
            $stmt->bind_param('sdsi', $name, $price, $features, $plan_id);
            
            if ($stmt->execute()) {
                $message = '✅ Plan updated successfully!';
            } else {
                $message = '❌ Error updating plan';
                $message_type = 'error';
            }
            $stmt->close();
        }
        elseif ($action === 'delete') {
            $plan_id = intval($_POST['plan_id']);
            
            // Check if plan is in use
            $check = $conn->query("SELECT COUNT(*) as count FROM featured_listings WHERE business_id IN (SELECT id FROM extracted_businesses WHERE is_featured = 1)");
            $count = $check->fetch_assoc()['count'];
            
            if ($count == 0) {
                if ($conn->query("DELETE FROM premium_plans WHERE id = $plan_id")) {
                    $message = '✅ Plan deleted successfully!';
                } else {
                    $message = '❌ Error deleting plan';
                    $message_type = 'error';
                }
            } else {
                $message = '⚠️ Cannot delete plan - it has featured listings';
                $message_type = 'warning';
            }
        }
    }
}

// Get all plans
$plans = [];
$result = $conn->query("SELECT * FROM premium_plans ORDER BY price ASC");
while ($row = $result->fetch_assoc()) {
    $plans[] = $row;
}

// Get featured listing stats
$stats = [];
foreach ($plans as $plan) {
    $count = $conn->query("SELECT COUNT(*) as count FROM featured_listings WHERE expires_at > NOW()")->fetch_assoc()['count'];
    $stats[$plan['id']] = $count;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Plans</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .plans-container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .plans-header { color: #0B1C3D; margin: 30px 0; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .message.warning { background: #fff3cd; color: #856404; }
        .add-plan-form { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 5px; color: #333; }
        .form-group input, .form-group textarea { padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #FF6A00; }
        .btn-add { grid-column: 1 / -1; padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-add:hover { background: #218838; }
        .plans-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        .plans-table th { background: #0B1C3D; color: white; padding: 15px; text-align: left; }
        .plans-table td { padding: 15px; border-bottom: 1px solid #ddd; }
        .plans-table tr:hover { background: #f9f9f9; }
        .plan-name { font-weight: bold; color: #0B1C3D; }
        .plan-price { font-size: 18px; font-weight: bold; color: #FF6A00; }
        .plan-stats { font-size: 12px; color: #666; }
        .actions { display: flex; gap: 10px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 12px; }
        .btn-edit { background: #007BFF; color: white; }
        .btn-edit:hover { background: #0056b3; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="plans-container">
        <h1 class="plans-header">💰 Manage Pricing Plans</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- ADD NEW PLAN -->
        <div class="add-plan-form">
            <h2>➕ Add New Plan</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Plan Name</label>
                        <input type="text" id="name" name="name" required placeholder="e.g., Premium">
                    </div>
                    <div class="form-group">
                        <label for="price">Price (₹)</label>
                        <input type="number" id="price" name="price" step="0.01" required placeholder="999">
                    </div>
                    <div class="form-group">
                        <label for="duration">Duration (days)</label>
                        <input type="number" id="duration" name="duration" required placeholder="30">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" required placeholder="Brief description">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="features">Features (comma separated)</label>
                        <textarea id="features" name="features" placeholder="Feature 1|Feature 2|Feature 3" rows="3"></textarea>
                    </div>
                    <input type="hidden" name="action" value="add">
                    <button type="submit" class="btn-add">➕ Add Plan</button>
                </div>
            </form>
        </div>
        
        <!-- PLANS TABLE -->
        <h2>📋 Existing Plans</h2>
        <table class="plans-table">
            <thead>
                <tr>
                    <th>Plan Name</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Active Subscriptions</th>
                    <th>Features</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td class="plan-name"><?php echo $plan['name']; ?></td>
                        <td class="plan-price">₹<?php echo number_format($plan['price']); ?></td>
                        <td><?php echo $plan['duration_days']; ?> days</td>
                        <td class="plan-stats"><?php echo $stats[$plan['id']]; ?> active</td>
                        <td><?php echo substr(str_replace('|', ', ', $plan['features']), 0, 40) . '...'; ?></td>
                        <td>
                            <div class="actions">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                    <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this plan?')">🗑️ Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
