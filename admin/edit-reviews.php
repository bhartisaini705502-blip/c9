<?php
/**
 * Admin Panel - Review User Edits
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';

// Handle edit approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = (int)$_POST['edit_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    
    if ($action === 'approve') {
        // Get the edit details
        $editQuery = "SELECT business_id, field_name, new_value FROM business_edits WHERE id = ?";
        $editStmt = $GLOBALS['conn']->prepare($editQuery);
        $editStmt->bind_param('i', $edit_id);
        $editStmt->execute();
        $editResult = $editStmt->get_result();
        $editData = $editResult->fetch_assoc();
        
        if ($editData) {
            // Update the business
            $updateQuery = "UPDATE extracted_businesses SET " . $editData['field_name'] . " = ? WHERE id = ?";
            $updateStmt = $GLOBALS['conn']->prepare($updateQuery);
            $updateStmt->bind_param('si', $editData['new_value'], $editData['business_id']);
            $updateStmt->execute();
        }
        
        $status = 'approved';
    } else {
        $status = 'rejected';
    }
    
    // Update edit status (reviewed_by is NULL for admin panel)
    $updateEditQuery = "UPDATE business_edits SET edit_status = ?, reviewed_by = NULL, reviewed_at = NOW() WHERE id = ?";
    $updateEditStmt = $GLOBALS['conn']->prepare($updateEditQuery);
    $updateEditStmt->bind_param('si', $status, $edit_id);
    $updateEditStmt->execute();
}

// Get pending edits
$query = "SELECT 
    be.id,
    be.business_id,
    be.field_name,
    be.old_value,
    be.new_value,
    be.created_at,
    be.edit_status,
    u.username,
    u.full_name,
    b.name as business_name
FROM business_edits be
JOIN users u ON be.editor_id = u.id
JOIN extracted_businesses b ON be.business_id = b.id
ORDER BY 
    CASE WHEN be.edit_status = 'pending' THEN 0 ELSE 1 END,
    be.created_at DESC";

$result = $GLOBALS['conn']->query($query);
$edits = [];
while ($row = $result->fetch_assoc()) {
    $edits[] = $row;
}

$pendingCount = count(array_filter($edits, fn($e) => $e['edit_status'] === 'pending'));

$page_title = 'Admin - Review Edits';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
    .main-content {
        max-width: 1100px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .main-content h1 {
        margin-top: 20px;
        margin-bottom: 30px;
        font-size: 28px;
    }

.edits-container {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-top: 20px;
}

.edit-item {
    padding: 20px;
    border-bottom: 1px solid #EEE;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 20px;
    align-items: center;
}

.edit-item:last-child {
    border-bottom: none;
}

.edit-details h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.edit-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.info-item {
    font-size: 13px;
}

.info-label {
    color: #999;
    font-weight: 600;
    text-transform: uppercase;
}

.info-value {
    color: #333;
    margin-top: 3px;
}

.field-change {
    background: #F9F9F9;
    padding: 12px;
    border-radius: 5px;
    margin-top: 10px;
}

.field-change-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 5px;
    font-weight: 600;
}

.old-value {
    background: #FFE8E8;
    color: #721C24;
    padding: 8px;
    border-radius: 3px;
    margin-bottom: 8px;
    font-family: monospace;
    font-size: 12px;
}

.new-value {
    background: #E8F5E9;
    color: #155724;
    padding: 8px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 12px;
}

.edit-actions {
    display: flex;
    gap: 10px;
    flex-direction: column;
}

.btn-approve, .btn-reject {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-approve {
    background: #D4EDDA;
    color: #155724;
}

.btn-approve:hover {
    background: #28a745;
    color: white;
}

.btn-reject {
    background: #F8D7DA;
    color: #721C24;
}

.btn-reject:hover {
    background: #dc3545;
    color: white;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #FFF3CD;
    color: #856404;
}

.status-approved {
    background: #D4EDDA;
    color: #155724;
}

.status-rejected {
    background: #F8D7DA;
    color: #721C24;
}

.empty-state {
    padding: 40px;
    text-align: center;
    color: #999;
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
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
}

.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #DDD;
    border-radius: 5px;
    font-family: inherit;
    resize: vertical;
    min-height: 80px;
}

.modal-actions {
    display: flex;
    gap: 10px;
}

.btn-modal {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
}

.btn-modal-approve {
    background: #28a745;
    color: white;
}

.btn-modal-reject {
    background: #dc3545;
    color: white;
}

.btn-cancel {
    background: #E9ECEF;
    color: #333;
}

@media (max-width: 768px) {
    .edit-item {
        grid-template-columns: 1fr;
    }
    
    .edit-actions {
        flex-direction: row;
    }
}
</style>
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="main-content">
        <h1>✏️ Review Business Edits</h1>
        <p style="color: #666;">Pending edits to review: <strong><?php echo $pendingCount; ?></strong></p>
        
        <div class="edits-container">
        <?php if (empty($edits)): ?>
            <div class="empty-state">
                <p>No edits to review</p>
            </div>
        <?php else: ?>
            <?php foreach ($edits as $edit): ?>
                <div class="edit-item">
                    <div class="edit-details">
                        <h3><?php echo esc($edit['business_name']); ?></h3>
                        <div class="edit-info">
                            <div class="info-item">
                                <div class="info-label">Edited By</div>
                                <div class="info-value"><?php echo esc($edit['full_name']); ?> (@<?php echo esc($edit['username']); ?>)</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Field</div>
                                <div class="info-value"><?php echo ucwords(str_replace('_', ' ', $edit['field_name'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="status-badge status-<?php echo $edit['edit_status']; ?>">
                                        <?php echo ucfirst($edit['edit_status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Date</div>
                                <div class="info-value"><?php echo date('M d, Y H:i', strtotime($edit['created_at'])); ?></div>
                            </div>
                        </div>

                        <div class="field-change">
                            <div class="field-change-label">Old Value:</div>
                            <div class="old-value"><?php echo esc($edit['old_value'] ?? '(empty)'); ?></div>
                            
                            <div class="field-change-label" style="margin-top: 10px;">New Value:</div>
                            <div class="new-value"><?php echo esc($edit['new_value'] ?? '(empty)'); ?></div>
                        </div>
                    </div>

                    <?php if ($edit['edit_status'] === 'pending'): ?>
                        <div class="edit-actions">
                            <button onclick="openModal('approve', <?php echo $edit['id']; ?>)" class="btn-approve">✅ Approve</button>
                            <button onclick="openModal('reject', <?php echo $edit['id']; ?>)" class="btn-reject">❌ Reject</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Decision Modal -->
<div id="decisionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
        </div>
        <form method="POST" action="">
            <input type="hidden" id="editId" name="edit_id">
            <input type="hidden" id="actionInput" name="action">

            <div class="form-group">
                <label for="notes">Notes (Optional)</label>
                <textarea id="notes" name="notes" placeholder="Add notes about this decision..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="submit" id="submitBtn" class="btn-modal"></button>
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(action, editId) {
    const modal = document.getElementById('decisionModal');
    const title = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const editInput = document.getElementById('editId');
    const actionInput = document.getElementById('actionInput');
    
    editInput.value = editId;
    actionInput.value = action;
    
    if (action === 'approve') {
        title.textContent = '✅ Approve Edit';
        submitBtn.className = 'btn-modal btn-modal-approve';
        submitBtn.textContent = 'Approve';
    } else {
        title.textContent = '❌ Reject Edit';
        submitBtn.className = 'btn-modal btn-modal-reject';
        submitBtn.textContent = 'Reject';
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('decisionModal').classList.remove('active');
}

document.getElementById('decisionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
        </div>
    </div>
</body>
</html>
