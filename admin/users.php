<?php
/**
 * Admin - User Management
 */

require_once '../config/db.php';
require_once '../config/auth.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($userId > 0) {
        if ($_POST['action'] === 'update_user') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $companyName = trim($_POST['company_name'] ?? '');
            $role = trim($_POST['role'] ?? 'user');
            $status = trim($_POST['status'] ?? 'active');

            $stmt = $GLOBALS['conn']->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, company_name = ?, role = ?, status = ? WHERE id = ?");
            $stmt->bind_param('ssssssi', $fullName, $email, $username, $companyName, $role, $status, $userId);
            $stmt->execute();
        }

        if ($_POST['action'] === 'update_password') {
            $password = trim($_POST['password'] ?? '');
            if ($password !== '') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $GLOBALS['conn']->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param('si', $hashedPassword, $userId);
                $stmt->execute();
            }
        }
    }

    header('Location: /admin/users.php');
    exit;
}

// Get all users
$query = "SELECT id, username, email, full_name, company_name, role, status, created_at FROM users ORDER BY created_at DESC";
$result = $GLOBALS['conn']->query($query);
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$page_title = 'Admin - User Management';
include '../includes/header.php';
?>

<style>
.admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px 0;
    margin-bottom: 30px;
}

.admin-header h1 {
    margin: 0;
    font-size: 28px;
}

.users-container {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #F9F9F9;
    border-bottom: 2px solid #DDD;
}

th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #333;
    font-size: 13px;
    text-transform: uppercase;
}

td {
    padding: 15px;
    border-bottom: 1px solid #EEE;
    font-size: 14px;
}

tbody tr:hover {
    background: #F9F9F9;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #D4EDDA;
    color: #155724;
}

.status-inactive {
    background: #E2E3E5;
    color: #383D41;
}

.status-suspended {
    background: #F8D7DA;
    color: #721C24;
}

.role-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.role-user {
    background: #E7F3FF;
    color: #004085;
}

.role-manager {
    background: #E2E3E5;
    color: #383D41;
}

.role-admin {
    background: #FFE5E5;
    color: #721C24;
}

.user-name {
    font-weight: 600;
    color: #333;
}

.user-email {
    font-size: 12px;
    color: #999;
}

.empty-state {
    padding: 40px;
    text-align: center;
    color: #999;
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
}

.user-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}

.user-card h3 {
    margin-bottom: 12px;
    color: #0B1C3D;
}

.user-field {
    width: 100%;
    margin-bottom: 10px;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.user-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    border: none;
    border-radius: 8px;
    padding: 10px 14px;
    cursor: pointer;
    font-weight: 600;
}

.btn-primary {
    background: #FF6A00;
    color: white;
}

.btn-secondary {
    background: #0B1C3D;
    color: white;
}
</style>

<div class="admin-header">
    <div class="container">
        <h1>👥 User Management</h1>
    </div>
</div>

<div class="container">
    <div class="users-container">
        <div class="users-grid">
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <h3><?php echo esc($user['full_name']); ?></h3>
                    <form method="post">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                        <input class="user-field" name="full_name" value="<?php echo esc($user['full_name']); ?>" placeholder="Full name">
                        <input class="user-field" name="username" value="<?php echo esc($user['username']); ?>" placeholder="Username">
                        <input class="user-field" name="email" value="<?php echo esc($user['email']); ?>" placeholder="Email">
                        <input class="user-field" name="company_name" value="<?php echo esc($user['company_name'] ?? ''); ?>" placeholder="Company name">
                        <input class="user-field" name="role" value="<?php echo esc($user['role']); ?>" placeholder="Role">
                        <input class="user-field" name="status" value="<?php echo esc($user['status']); ?>" placeholder="Status">
                        <div class="user-actions">
                            <button class="btn btn-primary" type="submit">Save Details</button>
                        </div>
                    </form>
                    <form method="post" style="margin-top:12px;">
                        <input type="hidden" name="action" value="update_password">
                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                        <input class="user-field" type="password" name="password" placeholder="New password">
                        <div class="user-actions">
                            <button class="btn btn-secondary" type="submit">Update Password</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
