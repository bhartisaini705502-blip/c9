<?php
/**
 * Admin - View Contact Queries
 */

require_once '../config/db.php';
require_once '../config/auth.php';

if (!isAdmin()) {
    header('Location: /auth/login.php?redirect=/admin/contact-queries.php');
    exit;
}

$user = getUserData();

// Mark as read
if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $conn->query("UPDATE contact_queries SET status = 'read' WHERE id = $id");
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM contact_queries WHERE id = $id");
    $message = 'Query deleted';
}

$page_title = 'Contact Queries';
include '../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }
    
    .queries-list {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .query-item {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: start;
    }
    
    .query-item.new {
        background: #f0f8ff;
        border-left: 4px solid #667eea;
    }
    
    .query-info h3 {
        margin: 0 0 10px 0;
        color: #333;
    }
    
    .query-info p {
        margin: 5px 0;
        color: #666;
        font-size: 14px;
    }
    
    .query-message {
        margin-top: 10px;
        padding: 10px;
        background: white;
        border-left: 3px solid #667eea;
        color: #333;
        font-size: 14px;
        line-height: 1.6;
    }
    
    .query-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-sm {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        text-decoration: none;
        color: white;
    }
    
    .btn-mark {
        background: #FFC107;
        color: #333;
    }
    
    .btn-delete {
        background: #FF6B6B;
    }
    
    .btn-mark:hover, .btn-delete:hover {
        opacity: 0.8;
    }
    
    .empty-state {
        padding: 40px;
        text-align: center;
        color: #999;
    }
    
    .header-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .stat {
        padding: 15px 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .stat-number {
        font-size: 28px;
        font-weight: bold;
        color: #667eea;
    }
    
    .stat-label {
        font-size: 13px;
        color: #999;
        margin-top: 5px;
    }
</style>

<div class="admin-container">
    <h2>📧 Contact Queries</h2>
    
    <div class="header-stats">
        <?php
        $new = $conn->query("SELECT COUNT(*) as count FROM contact_queries WHERE status = 'new'")->fetch_assoc()['count'];
        $total = $conn->query("SELECT COUNT(*) as count FROM contact_queries")->fetch_assoc()['count'];
        ?>
        <div class="stat">
            <div class="stat-number"><?php echo $new; ?></div>
            <div class="stat-label">New Queries</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $total; ?></div>
            <div class="stat-label">Total Queries</div>
        </div>
    </div>
    
    <div class="queries-list">
        <?php
        $result = $conn->query("SELECT * FROM contact_queries ORDER BY status = 'new' DESC, created_at DESC");
        
        if ($result->num_rows === 0):
        ?>
            <div class="empty-state">
                📭 No contact queries yet
            </div>
        <?php else: ?>
            <?php while ($query = $result->fetch_assoc()): ?>
                <div class="query-item <?php echo $query['status'] === 'new' ? 'new' : ''; ?>">
                    <div class="query-info" style="flex: 1;">
                        <h3><?php echo esc($query['name']); ?> <?php echo $query['status'] === 'new' ? '<span style="background: #667eea; color: white; padding: 3px 8px; font-size: 12px; border-radius: 3px;">NEW</span>' : ''; ?></h3>
                        <p>📧 <strong><?php echo esc($query['email']); ?></strong></p>
                        <?php if ($query['phone']): ?>
                            <p>📱 <?php echo esc($query['phone']); ?></p>
                        <?php endif; ?>
                        <p>📌 <strong><?php echo esc($query['subject']); ?></strong></p>
                        <p style="color: #999; font-size: 12px;">📅 <?php echo date('M d, Y g:i A', strtotime($query['created_at'])); ?></p>
                        <div class="query-message">
                            <?php echo nl2br(esc($query['message'])); ?>
                        </div>
                    </div>
                    <div class="query-actions">
                        <?php if ($query['status'] === 'new'): ?>
                            <a href="?mark_read=<?php echo $query['id']; ?>" class="btn-sm btn-mark">✓ Mark Read</a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $query['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this query?')">🗑️ Delete</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
