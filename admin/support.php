<?php
/**
 * Admin Support Dashboard - Handle Live Chat & Tickets
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require '../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('/auth/login.php');
}

$admin_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'conversations';

// Get active conversations
$stmt = $conn->prepare("
    SELECT c.*, u.full_name, u.email, COUNT(m.id) as message_count
    FROM support_conversations c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN support_messages m ON c.id = m.conversation_id
    WHERE c.status IN ('open', 'in_progress')
    GROUP BY c.id
    ORDER BY c.updated_at DESC
");
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get open tickets
$stmt = $conn->prepare("
    SELECT t.*, u.full_name, u.email
    FROM support_tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.status IN ('open', 'in_progress')
    ORDER BY t.created_at DESC
");
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get agent status
$stmt = $conn->prepare("SELECT * FROM support_agents WHERE user_id = ?");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$agent = $stmt->get_result()->fetch_assoc();

// If agent doesn't exist, create one
if (!$agent) {
    $stmt = $conn->prepare("INSERT INTO support_agents (user_id, status) VALUES (?, 'offline')");
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $agent = ['id' => $stmt->insert_id, 'status' => 'offline'];
}

$selected_conversation = null;
$selected_messages = [];

// If viewing conversation
if (isset($_GET['conversation_id'])) {
    $conversation_id = (int)$_GET['conversation_id'];
    
    $stmt = $conn->prepare("SELECT sc.*, u.full_name, u.email FROM support_conversations sc JOIN users u ON sc.user_id = u.id WHERE sc.id = ?");
    $stmt->bind_param('i', $conversation_id);
    $stmt->execute();
    $selected_conversation = $stmt->get_result()->fetch_assoc();

    if ($selected_conversation) {
        $stmt = $conn->prepare("
            SELECT m.*, u.full_name, u.role 
            FROM support_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param('i', $conversation_id);
        $stmt->execute();
        $selected_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Mark conversation as in_progress
        if ($selected_conversation['status'] === 'open') {
            $stmt = $conn->prepare("
                UPDATE support_conversations 
                SET status = 'in_progress', agent_id = ? 
                WHERE id = ?
            ");
            $stmt->bind_param('ii', $admin_id, $conversation_id);
            $stmt->execute();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Dashboard - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .admin-support {
            display: flex;
            height: calc(100vh - 100px);
            gap: 0;
        }

        .sidebar {
            width: 350px;
            background: #f5f5f5;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
        }

        .tab-btn {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-btn:hover {
            background: #f9f9f9;
        }

        .agent-status-bar {
            padding: 15px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-toggle {
            display: flex;
            gap: 10px;
        }

        .toggle-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .toggle-btn.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .conversation-item:hover {
            background: #fff3e0;
        }

        .conversation-item.active {
            background: #e3f2fd;
            border-left: 4px solid #667eea;
        }

        .conversation-name {
            font-weight: 500;
            margin-bottom: 4px;
        }

        .conversation-meta {
            font-size: 12px;
            color: #999;
        }

        .conversation-preview {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }

        .message.agent {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 60%;
            padding: 12px 16px;
            border-radius: 12px;
        }

        .message.user .message-bubble {
            background: #e0e0e0;
        }

        .message.agent .message-bubble {
            background: #667eea;
            color: white;
        }

        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }

        .reply-input {
            padding: 15px;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
        }

        .reply-input textarea {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            resize: none;
        }

        .reply-input button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .empty-state {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            font-size: 16px;
        }

        .ticket-list {
            padding: 15px;
        }

        .ticket-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .ticket-item:hover {
            background: #f0f0f0;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .ticket-number {
            font-weight: bold;
            color: #667eea;
        }

        .ticket-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .ticket-status.open {
            background: #FFC107;
            color: white;
        }

        .ticket-status.in_progress {
            background: #667eea;
            color: white;
        }

        .ticket-user {
            font-size: 13px;
            color: #666;
            margin-bottom: 4px;
        }

        .ticket-subject {
            font-weight: 500;
            margin-bottom: 4px;
        }

        .empty-sidebar {
            padding: 20px;
            text-align: center;
            color: #999;
        }

        @media (max-width: 1024px) {
            .admin-support {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: 200px;
                border-right: none;
                border-bottom: 1px solid #ddd;
            }

            .message-bubble {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <?php require '../includes/header.php'; ?>

    <div class="admin-support">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="agent-status-bar">
                <h4 style="margin: 0;">Support Agent</h4>
                <div class="status-toggle">
                    <button class="toggle-btn <?php echo $agent['status'] == 'online' ? 'active' : ''; ?>" onclick="setAgentStatus('online')">
                        🟢 Online
                    </button>
                    <button class="toggle-btn <?php echo $agent['status'] == 'offline' ? 'active' : ''; ?>" onclick="setAgentStatus('offline')">
                        ⭕ Offline
                    </button>
                </div>
            </div>

            <div class="tabs" style="border-right: none;">
                <button class="tab-btn <?php echo $tab == 'conversations' ? 'active' : ''; ?>" onclick="switchTab('conversations')">
                    Chats (<?php echo count($conversations); ?>)
                </button>
                <button class="tab-btn <?php echo $tab == 'tickets' ? 'active' : ''; ?>" onclick="switchTab('tickets')">
                    Tickets (<?php echo count($tickets); ?>)
                </button>
            </div>

            <!-- Conversations List -->
            <div id="conversationsTab" style="<?php echo $tab == 'conversations' ? 'display: block;' : 'display: none;'; ?>">
                <?php if (empty($conversations)): ?>
                    <div class="empty-sidebar">
                        No active conversations
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <div class="conversation-item <?php echo $selected_conversation && $selected_conversation['id'] == $conv['id'] ? 'active' : ''; ?>" 
                             onclick="location.href='?tab=conversations&conversation_id=<?php echo $conv['id']; ?>'">
                            <div class="conversation-name"><?php echo esc($conv['full_name']); ?></div>
                            <div class="conversation-meta"><?php echo esc($conv['email']); ?></div>
                            <div class="conversation-meta"><?php echo $conv['message_count']; ?> messages</div>
                            <div class="conversation-meta"><?php echo timeAgo($conv['updated_at']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Tickets List -->
            <div id="ticketsTab" style="<?php echo $tab == 'tickets' ? 'display: block;' : 'display: none;'; ?>">
                <div class="ticket-list">
                    <?php if (empty($tickets)): ?>
                        <div class="empty-sidebar">
                            No open tickets
                        </div>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="ticket-item">
                                <div class="ticket-header">
                                    <div>
                                        <div class="ticket-number">#<?php echo str_pad($ticket['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                        <div class="ticket-user"><?php echo esc($ticket['full_name']); ?></div>
                                    </div>
                                    <span class="ticket-status <?php echo $ticket['status']; ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </div>
                                <div class="ticket-subject"><?php echo esc($ticket['subject']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if ($selected_conversation): ?>
                <!-- Chat View -->
                <div style="flex: 1; display: flex; flex-direction: column;">
                    <div style="padding: 15px; border-bottom: 1px solid #ddd;">
                        <h3 style="margin: 0;">
                            <?php echo esc($selected_conversation['full_name']); ?>
                            <?php if ($selected_conversation['ticket_id']): ?>
                                <span style="color: #999; font-size: 14px;">
                                    • Ticket #<?php echo str_pad($selected_conversation['ticket_id'], 5, '0', STR_PAD_LEFT); ?>
                                </span>
                            <?php endif; ?>
                        </h3>
                        <p style="margin: 5px 0 0; color: #999; font-size: 13px;">
                            <?php echo esc($selected_conversation['email']); ?>
                        </p>
                    </div>

                    <div class="chat-messages">
                        <?php foreach ($selected_messages as $msg): ?>
                            <div class="message <?php echo $msg['sender_type'] == 'agent' ? 'agent' : 'user'; ?>">
                                <div>
                                    <div style="font-size: 12px; color: #666; margin-bottom: 4px;">
                                        <?php echo esc($msg['full_name']); ?>
                                    </div>
                                    <div class="message-bubble">
                                        <?php echo esc($msg['message']); ?>
                                    </div>
                                    <div class="message-time">
                                        <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="reply-input">
                        <form id="replyForm" style="display: flex; gap: 10px; width: 100%;">
                            <textarea id="replyText" placeholder="Type your reply..." rows="2" style="flex: 1;"></textarea>
                            <button type="submit">Send</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    Select a conversation or ticket to get started
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const conversationId = <?php echo $selected_conversation ? $selected_conversation['id'] : 'null'; ?>;

        // Play notification beep sound
        function playNotificationSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800; // 800 Hz beep
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.2);
            } catch (e) {
                console.log('Sound notification unavailable');
            }
        }

        function switchTab(tab) {
            document.getElementById('conversationsTab').style.display = tab === 'conversations' ? 'block' : 'none';
            document.getElementById('ticketsTab').style.display = tab === 'tickets' ? 'block' : 'none';
            
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        function setAgentStatus(status) {
            fetch('/api/support/set-agent-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: status })
            }).then(r => r.json()).then(data => {
                location.reload();
            });
        }

        document.getElementById('replyForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const replyText = document.getElementById('replyText').value.trim();
            if (!replyText || !conversationId) return;

            const formData = new FormData();
            formData.append('conversation_id', conversationId);
            formData.append('message', replyText);

            const response = await fetch('/api/support/send-agent-reply.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                // Play notification sound
                playNotificationSound();
                
                document.getElementById('replyText').value = '';
                setTimeout(() => location.reload(), 300);
            }
        });

        // Auto-scroll to latest message
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>
