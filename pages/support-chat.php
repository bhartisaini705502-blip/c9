<?php
/**
 * Support Chat Interface - User Page
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/auth/login.php?redirect=/pages/support-chat.php');
}

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : null;

// If starting a new conversation
if (!$conversation_id) {
    // Check if user already has an open conversation
    $stmt = $conn->prepare("SELECT id FROM support_conversations WHERE user_id = ? AND status IN ('open', 'in_progress') LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $conversation_id = $row['id'];
        redirect('/pages/support-chat.php?conversation_id=' . $conversation_id);
    }
    
    // Create new conversation
    $stmt = $conn->prepare("INSERT INTO support_conversations (user_id, status) VALUES (?, 'open')");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $conversation_id = $stmt->insert_id;
    redirect('/pages/support-chat.php?conversation_id=' . $conversation_id);
}

// Validate conversation belongs to user
$stmt = $conn->prepare("SELECT * FROM support_conversations WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $conversation_id, $user_id);
$stmt->execute();
$conversation = $stmt->get_result()->fetch_assoc();

if (!$conversation) {
    die('Conversation not found');
}

// Get messages
$stmt = $conn->prepare("
    SELECT m.*, u.full_name, u.email 
    FROM support_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC
");
$stmt->bind_param('i', $conversation_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if agent is online
$stmt = $conn->prepare("
    SELECT COUNT(*) as online_agents 
    FROM support_agents 
    WHERE status = 'online'
");
$stmt->execute();
$agent_count = $stmt->get_result()->fetch_assoc()['online_agents'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Chat - Business Directory</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .support-container {
            display: flex;
            height: 100vh;
            background: #f5f5f5;
        }

        .chat-wrapper {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .agent-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #4CAF50;
        }

        .status-dot.offline {
            background: #FFC107;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            display: flex;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .message.user {
            justify-content: flex-end;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-bubble {
            max-width: 60%;
            padding: 12px 16px;
            border-radius: 12px;
            word-wrap: break-word;
        }

        .message.user .message-bubble {
            background: #667eea;
            color: white;
            border-radius: 18px 18px 4px 18px;
        }

        .message.agent .message-bubble {
            background: #e0e0e0;
            color: #333;
            border-radius: 18px 18px 18px 4px;
        }

        .message-time {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }

        .message-sender {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .message.agent .message-sender {
            color: #764ba2;
        }

        .input-wrapper {
            border-top: 1px solid #ddd;
            padding: 15px;
            background: #fafafa;
        }

        .input-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .textarea-wrapper {
            flex: 1;
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        textarea {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            resize: none;
            max-height: 100px;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .file-input-label {
            cursor: pointer;
            padding: 10px;
            background: #e0e0e0;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .file-input-label:hover {
            background: #d0d0d0;
        }

        #fileInput {
            display: none;
        }

        .send-btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        .send-btn:hover {
            background: #764ba2;
        }

        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .attachment-preview {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .attachment-item {
            position: relative;
            padding: 6px 10px;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .attachment-remove {
            cursor: pointer;
            color: #999;
            font-weight: bold;
        }

        .attachment-remove:hover {
            color: #d32f2f;
        }

        .message-attachments {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .message-attachment {
            max-width: 150px;
            cursor: pointer;
        }

        .message-attachment img {
            max-width: 100%;
            border-radius: 8px;
            max-height: 200px;
        }

        .ticket-banner {
            background: #FFF3CD;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .ticket-banner strong {
            display: block;
            margin-bottom: 4px;
        }

        @media (max-width: 768px) {
            .message-bubble {
                max-width: 85%;
            }

            .chat-wrapper {
                height: 100vh;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php require '../includes/header.php'; ?>

    <div class="support-container" style="max-width: 1200px; margin: 20px auto; height: calc(100vh - 100px);">
        <div class="chat-wrapper">
            <div class="chat-header">
                <div>
                    <h3 style="margin: 0; font-size: 18px;">Support Chat</h3>
                    <?php if ($conversation['ticket_id']): ?>
                        <p style="margin: 5px 0 0; font-size: 13px; opacity: 0.9;">
                            Ticket #<?php echo str_pad($conversation['ticket_id'], 5, '0', STR_PAD_LEFT); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="agent-status">
                    <span class="status-dot <?php echo $agent_count == 0 ? 'offline' : ''; ?>"></span>
                    <span><?php echo $agent_count > 0 ? "Agent Online ($agent_count)" : "No Agents Online"; ?></span>
                </div>
            </div>

            <div class="messages-container" id="messagesContainer">
                <?php if ($agent_count == 0 && count($messages) == 0): ?>
                    <div class="ticket-banner">
                        <strong>⏱️ Support Queue Active</strong>
                        No agents are currently online. Your message will be automatically converted to a support ticket and assigned to the next available agent.
                    </div>
                <?php endif; ?>

                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_type'] == 'user' ? 'user' : 'agent'; ?>">
                        <div>
                            <div class="message-sender">
                                <?php echo esc($msg['full_name']); ?>
                            </div>
                            <div class="message-bubble">
                                <?php echo esc($msg['message']); ?>
                                <?php
                                // Get attachments for this message
                                $msg_id = $msg['id'];
                                $stmt = $conn->prepare("SELECT * FROM support_attachments WHERE message_id = ?");
                                $stmt->bind_param('i', $msg_id);
                                $stmt->execute();
                                $attachments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                ?>
                                <?php if (!empty($attachments)): ?>
                                    <div class="message-attachments">
                                        <?php foreach ($attachments as $attach): ?>
                                            <img 
                                                src="<?php echo esc($attach['file_path']); ?>" 
                                                alt="<?php echo esc($attach['file_name']); ?>"
                                                class="message-attachment"
                                                onclick="showImageModal(this.src)"
                                                style="cursor: pointer;"
                                            >
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="message-time">
                                <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="input-wrapper">
                <form id="messageForm" enctype="multipart/form-data">
                    <div id="attachmentPreview" class="attachment-preview"></div>
                    <div class="input-form">
                        <div class="textarea-wrapper">
                            <textarea 
                                id="messageInput" 
                                placeholder="Type your message..." 
                                rows="2"
                                required
                            ></textarea>
                            <label class="file-input-label" title="Attach Image">
                                📎
                                <input 
                                    type="file" 
                                    id="fileInput" 
                                    accept="image/*" 
                                    multiple
                                >
                            </label>
                        </div>
                        <button type="submit" class="send-btn" id="sendBtn">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
        <img id="modalImage" style="max-width:90%; max-height:90%;">
        <button onclick="document.getElementById('imageModal').style.display='none'" style="position:absolute; top:20px; right:30px; background:white; border:none; font-size:28px; cursor:pointer;">✕</button>
    </div>

    <script>
        let selectedFiles = [];
        const conversationId = <?php echo $conversation_id; ?>;
        const userId = <?php echo $user_id; ?>;

        // Play notification beep sound
        function playNotificationSound() {
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
        }

        // File input handler
        document.getElementById('fileInput').addEventListener('change', function(e) {
            selectedFiles = Array.from(e.target.files);
            updateAttachmentPreview();
        });

        function updateAttachmentPreview() {
            const preview = document.getElementById('attachmentPreview');
            preview.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'attachment-item';
                item.innerHTML = `
                    📄 ${file.name}
                    <span class="attachment-remove" onclick="removeFile(${index})">✕</span>
                `;
                preview.appendChild(item);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            document.getElementById('fileInput').value = '';
            updateAttachmentPreview();
        }

        // Message form submission
        document.getElementById('messageForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const messageText = document.getElementById('messageInput').value.trim();
            if (!messageText && selectedFiles.length === 0) return;

            const formData = new FormData();
            formData.append('conversation_id', conversationId);
            formData.append('message', messageText);
            selectedFiles.forEach(file => {
                formData.append('attachments[]', file);
            });

            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;

            try {
                const response = await fetch('/api/support/send-message.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    document.getElementById('messageInput').value = '';
                    selectedFiles = [];
                    document.getElementById('fileInput').value = '';
                    updateAttachmentPreview();
                    
                    // Play notification sound
                    try {
                        playNotificationSound();
                    } catch (e) {
                        console.log('Sound notification skipped');
                    }
                    
                    // Reload messages
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert('Failed to send message');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error sending message');
            } finally {
                sendBtn.disabled = false;
            }
        });

        function showImageModal(src) {
            const modal = document.getElementById('imageModal');
            document.getElementById('modalImage').src = src;
            modal.style.display = 'flex';
        }

        // Auto-scroll to latest message
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
    </script>
</body>
</html>
