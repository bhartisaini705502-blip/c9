<?php
/**
 * Support Chat Message API
 */

header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../config/auth.php';
require '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : null;
$message_text = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$conversation_id || !$message_text) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Verify conversation belongs to user
$stmt = $conn->prepare("SELECT * FROM support_conversations WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $conversation_id, $user_id);
$stmt->execute();
$conversation = $stmt->get_result()->fetch_assoc();

if (!$conversation) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Insert message
$stmt = $conn->prepare("
    INSERT INTO support_messages (conversation_id, sender_id, sender_type, message) 
    VALUES (?, ?, 'user', ?)
");
$stmt->bind_param('iis', $conversation_id, $user_id, $message_text);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save message']);
    exit;
}

$message_id = $stmt->insert_id;

// Handle file attachments
if (!empty($_FILES['attachments'])) {
    $upload_dir = '../../uploads/support/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['attachments']['error'][$key] === 0) {
            $file_name = $_FILES['attachments']['name'][$key];
            $file_size = $_FILES['attachments']['size'][$key];
            $file_type = $_FILES['attachments']['type'][$key];

            // Validate file is image
            if (strpos($file_type, 'image') === false) {
                continue; // Skip non-image files
            }

            // Limit file size (5MB)
            if ($file_size > 5 * 1024 * 1024) {
                continue;
            }

            // Generate unique filename
            $unique_name = uniqid() . '_' . basename($file_name);
            $file_path = $upload_dir . $unique_name;

            if (move_uploaded_file($tmp_name, $file_path)) {
                // Insert attachment record
                $web_path = '/uploads/support/' . $unique_name;
                $stmt = $conn->prepare("
                    INSERT INTO support_attachments (message_id, file_name, file_path, file_size, file_type) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('issss', $message_id, $file_name, $web_path, $file_size, $file_type);
                $stmt->execute();
            }
        }
    }
}

// Check if any agents are online
$stmt = $conn->prepare("SELECT COUNT(*) as online_agents FROM support_agents WHERE status = 'online'");
$stmt->execute();
$agent_count = $stmt->get_result()->fetch_assoc()['online_agents'];

// If no agents online, create/update ticket
if ($agent_count == 0 && !$conversation['ticket_id']) {
    // Generate ticket number
    $ticket_number = 'TK' . date('YmdHis') . rand(1000, 9999);
    
    $stmt = $conn->prepare("
        INSERT INTO support_tickets (conversation_id, user_id, ticket_number, subject, description, status, priority) 
        VALUES (?, ?, ?, ?, ?, 'open', 'medium')
    ");
    $subject = "Support Request - " . date('M d, Y g:i A');
    $description = "User initiated chat support request.";
    
    $stmt->bind_param('iisss', $conversation_id, $user_id, $ticket_number, $subject, $description);
    $stmt->execute();
    $ticket_id = $stmt->insert_id;

    // Update conversation with ticket
    $stmt = $conn->prepare("UPDATE support_conversations SET ticket_id = ? WHERE id = ?");
    $stmt->bind_param('ii', $ticket_id, $conversation_id);
    $stmt->execute();
}

// Update conversation timestamp
$stmt = $conn->prepare("UPDATE support_conversations SET updated_at = NOW() WHERE id = ?");
$stmt->bind_param('i', $conversation_id);
$stmt->execute();

echo json_encode(['success' => true, 'message_id' => $message_id]);
