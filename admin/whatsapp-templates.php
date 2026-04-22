<?php
/**
 * Admin - WhatsApp Templates Management
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !in_array(getUserData()['role'] ?? '', ['admin', 'manager'])) {
    header('Location: login.php');
    exit;
}

// Handle create template
if ($_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($name) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO whatsapp_templates (name, message) VALUES (?, ?)");
        $stmt->bind_param('ss', $name, $message);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: whatsapp-templates.php');
    exit;
}

// Handle delete template
if ($_GET['action'] === 'delete' && $_GET['id']) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM whatsapp_templates WHERE id = $id");
    header('Location: whatsapp-templates.php');
    exit;
}

// Handle send template
if ($_POST['action'] === 'send' && $_POST['lead_id'] && $_POST['template_id']) {
    $lead_id = (int)$_POST['lead_id'];
    $template_id = (int)$_POST['template_id'];
    
    // Get lead details
    $lead = $conn->query("SELECT name, phone FROM leads WHERE id = $lead_id")->fetch_assoc();
    
    // Get template
    $template = $conn->query("SELECT message FROM whatsapp_templates WHERE id = $template_id")->fetch_assoc();
    
    if ($lead && $template) {
        // Replace variables
        $message = str_replace('{name}', $lead['name'], $template['message']);
        $wa_link = "https://wa.me/919068899033?text=" . urlencode($message);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'link' => $wa_link,
            'message' => $message
        ]);
        exit;
    }
}

// Get all templates
$templates = $conn->query("SELECT * FROM whatsapp_templates ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get recent leads for dropdown
$leads = $conn->query("SELECT id, name, phone, service FROM leads ORDER BY created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

$page_title = "WhatsApp Templates - Admin";
require_once '../includes/header.php';
?>

<style>
    .template-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .create-btn {
        background: #25D366;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .template-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid #25D366;
    }

    .template-card h3 {
        margin-top: 0;
        color: #0B1C3D;
        font-size: 16px;
    }

    .template-card p {
        color: #666;
        font-size: 14px;
        line-height: 1.6;
        margin: 15px 0;
    }

    .template-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .template-actions button,
    .template-actions a {
        flex: 1;
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s;
    }

    .btn-use {
        background: #25D366;
        color: white;
    }

    .btn-use:hover {
        background: #20B759;
    }

    .btn-delete {
        background: #f44336;
        color: white;
    }

    .btn-delete:hover {
        background: #d32f2f;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
    }

    .modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
    }

    .modal-content h2 {
        margin-top: 0;
        color: #0B1C3D;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 600;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-family: inherit;
        box-sizing: border-box;
    }

    .modal-btns {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .modal-btns button {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-submit {
        background: #25D366;
        color: white;
    }

    .btn-cancel {
        background: #ddd;
        color: #333;
    }

    .variables-info {
        background: #e8f5e9;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
        color: #2e7d32;
    }

    .info-header {
        font-weight: 600;
        margin-bottom: 8px;
    }

    .send-template-form {
        background: #f5f5f5;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .send-template-form h3 {
        margin-top: 0;
        color: #0B1C3D;
    }
</style>

<div class="template-container">
    <div class="admin-header">
        <h1>💬 WhatsApp Templates</h1>
        <button class="create-btn" onclick="openCreateModal()">+ Create Template</button>
    </div>

    <!-- Send Template Form -->
    <div class="send-template-form">
        <h3>📤 Send Template to Lead</h3>
        <form id="sendForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px;">
                <select id="leadSelect" required>
                    <option value="">Select Lead</option>
                    <?php foreach ($leads as $l): ?>
                    <option value="<?php echo $l['id']; ?>">
                        <?php echo esc($l['name']); ?> (<?php echo esc($l['service']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>

                <select id="templateSelect" required>
                    <option value="">Select Template</option>
                    <?php foreach ($templates as $t): ?>
                    <option value="<?php echo $t['id']; ?>"><?php echo esc($t['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="hidden" id="messagePreview">

                <button type="button" onclick="sendTemplate()" style="background: #25D366; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Send</button>
            </div>
        </form>
    </div>

    <!-- Templates Grid -->
    <div class="template-grid">
        <?php foreach ($templates as $template): ?>
        <div class="template-card">
            <h3><?php echo esc($template['name']); ?></h3>
            <p><?php echo esc($template['message']); ?></p>
            <div class="template-actions">
                <button class="btn-use" onclick="selectTemplate(<?php echo $template['id']; ?>)">✓ Use</button>
                <a href="?action=delete&id=<?php echo $template['id']; ?>" class="btn-delete" onclick="return confirm('Delete this template?')">🗑 Delete</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Create Template Modal -->
<div class="modal" id="createModal">
    <div class="modal-content">
        <h2>Create New Template</h2>

        <div class="variables-info">
            <div class="info-header">Available Variables:</div>
            {name} - Customer name<br>
            {service} - Service interested in<br>
            {phone} - Customer phone
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Template Name</label>
                <input type="text" name="name" placeholder="e.g., Initial Contact" required>
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="6" placeholder="Enter your WhatsApp message. Use {name}, {service}, {phone} for variables." required></textarea>
            </div>

            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" name="action" value="create" class="btn-submit">Create Template</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.add('active');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('active');
}

function selectTemplate(templateId) {
    document.getElementById('templateSelect').value = templateId;
}

function sendTemplate() {
    const leadId = document.getElementById('leadSelect').value;
    const templateId = document.getElementById('templateSelect').value;

    if (!leadId || !templateId) {
        alert('Please select both lead and template');
        return;
    }

    fetch('whatsapp-templates.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=send&lead_id=' + leadId + '&template_id=' + templateId
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert('Opening WhatsApp...\n\nMessage:\n' + d.message);
            window.open(d.link, '_blank');
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
