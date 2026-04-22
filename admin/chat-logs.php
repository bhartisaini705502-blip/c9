<?php
/**
 * Admin - Chat Logs
 * View all chatbot conversations
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !in_array(getUserData()['role'] ?? '', ['admin', 'manager'])) {
    header('Location: login.php');
    exit;
}

// Handle delete
if ($_GET['action'] === 'delete' && $_GET['id']) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM chat_logs WHERE id = $id");
    header('Location: chat-logs.php');
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = "1=1";
if ($filter === 'leads') {
    $where = "lead_captured = TRUE";
} elseif ($filter === 'today') {
    $where = "DATE(created_at) = CURDATE()";
}

// Get total count
$total = $conn->query("SELECT COUNT(*) as count FROM chat_logs WHERE $where")->fetch_assoc()['count'];
$total_pages = ceil($total / $per_page);

// Get logs
$logs = $conn->query("
    SELECT * FROM chat_logs 
    WHERE $where
    ORDER BY created_at DESC 
    LIMIT $offset, $per_page
")->fetch_all(MYSQLI_ASSOC);

// Get stats
$total_chats = $conn->query("SELECT COUNT(*) as count FROM chat_logs")->fetch_assoc()['count'];
$leads_captured = $conn->query("SELECT COUNT(*) as count FROM chat_logs WHERE lead_captured = TRUE")->fetch_assoc()['count'];
$today_chats = $conn->query("SELECT COUNT(*) as count FROM chat_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];

$page_title = "Chat Logs - Admin";
require_once '../includes/header.php';
?>

<style>
    .chat-logs-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .admin-header {
        margin-bottom: 30px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #667eea;
        margin: 10px 0;
    }

    .stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }

    .filter-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .filter-btn {
        padding: 10px 20px;
        border: 2px solid #ddd;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s;
    }

    .filter-btn:hover {
        border-color: #667eea;
        color: #667eea;
    }

    .filter-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .chat-log-item {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }

    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .chat-time {
        font-size: 13px;
        color: #999;
        font-weight: 600;
    }

    .chat-badges {
        display: flex;
        gap: 8px;
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-lead {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .chat-messages {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 15px;
    }

    .chat-message {
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eee;
    }

    .chat-message:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .message-sender {
        font-weight: 600;
        color: #333;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .sender-user {
        color: #667eea;
    }

    .sender-bot {
        color: #666;
    }

    .message-text {
        font-size: 13px;
        color: #555;
        line-height: 1.5;
        word-wrap: break-word;
    }

    .chat-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        color: #999;
    }

    .lead-info {
        background: #e8f5e9;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
    }

    .lead-info strong {
        color: #2e7d32;
    }

    .delete-btn {
        background: #f44336;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .delete-btn:hover {
        background: #d32f2f;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 30px;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #333;
    }

    .pagination a:hover {
        background: #f5f5f5;
    }

    .pagination .current {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }
</style>

<div class="chat-logs-container">
    <div class="admin-header">
        <h1>💬 Chat Logs</h1>
        <p style="color: #666; margin-top: 5px;">View all chatbot conversations</p>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Chats</div>
            <div class="stat-number"><?php echo $total_chats; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Leads Captured</div>
            <div class="stat-number"><?php echo $leads_captured; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Today</div>
            <div class="stat-number"><?php echo $today_chats; ?></div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All Chats</a>
        <a href="?filter=leads" class="filter-btn <?php echo $filter === 'leads' ? 'active' : ''; ?>">With Leads</a>
        <a href="?filter=today" class="filter-btn <?php echo $filter === 'today' ? 'active' : ''; ?>">Today</a>
    </div>

    <!-- Chat Logs -->
    <?php if (!empty($logs)): ?>
        <?php foreach ($logs as $log): ?>
        <div class="chat-log-item">
            <div class="chat-header">
                <div class="chat-time">📅 <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></div>
                <div class="chat-badges">
                    <?php if ($log['lead_captured']): ?>
                    <span class="badge badge-lead">✓ Lead Captured</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($log['lead_captured'] && ($log['user_name'] || $log['user_phone'])): ?>
            <div class="lead-info">
                👤 <strong><?php echo esc($log['user_name'] ?? 'N/A'); ?></strong> | 
                📱 <strong><?php echo esc($log['user_phone'] ?? 'N/A'); ?></strong>
                <?php if ($log['user_email']): ?>
                | 📧 <strong><?php echo esc($log['user_email']); ?></strong>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="chat-messages">
                <div class="chat-message">
                    <div class="message-sender sender-user">👤 User:</div>
                    <div class="message-text"><?php echo esc(substr($log['user_message'], 0, 300)); ?><?php echo strlen($log['user_message']) > 300 ? '...' : ''; ?></div>
                </div>
                <div class="chat-message">
                    <div class="message-sender sender-bot">🤖 Bot:</div>
                    <div class="message-text"><?php echo esc(substr($log['bot_response'], 0, 300)); ?><?php echo strlen($log['bot_response']) > 300 ? '...' : ''; ?></div>
                </div>
            </div>

            <div class="chat-footer">
                <span>📄 Page: <?php echo esc(basename($log['page_url'])); ?></span>
                <a href="?action=delete&id=<?php echo $log['id']; ?>" class="delete-btn" onclick="return confirm('Delete this chat?')">Delete</a>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?filter=<?php echo $filter; ?>&page=1">« First</a>
            <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">‹ Prev</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">Next ›</a>
            <a href="?filter=<?php echo $filter; ?>&page=<?php echo $total_pages; ?>">Last »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <p>No chat logs found.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
