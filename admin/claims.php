<?php
/**
 * Admin Panel - Manage Listing Claims
 */

require_once '../config/db.php';
require_once '../config/auth.php';

requireAdmin();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claim_id = (int)$_POST['claim_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    $admin_id = $_SESSION['user_id'];
    
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $updateQuery = "UPDATE listing_claims SET claim_status = ?, approval_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?";
    $updateStmt = $GLOBALS['conn']->prepare($updateQuery);
    $updateStmt->bind_param('ssii', $status, $notes, $admin_id, $claim_id);
    $updateStmt->execute();
}

// Get pending claims
$query = "SELECT 
    lc.id,
    lc.business_id,
    lc.claim_status,
    lc.claimed_at,
    u.username,
    u.full_name,
    u.email,
    u.phone,
    u.company_name,
    b.name as business_name,
    b.formatted_address
FROM listing_claims lc
JOIN users u ON lc.user_id = u.id
JOIN extracted_businesses b ON lc.business_id = b.id
ORDER BY 
    CASE WHEN lc.claim_status = 'pending' THEN 0 ELSE 1 END,
    lc.claimed_at DESC";

$result = $GLOBALS['conn']->query($query);
$claims = [];
while ($row = $result->fetch_assoc()) {
    $claims[] = $row;
}

// Count stats
$pendingCount = count(array_filter($claims, fn($c) => $c['claim_status'] === 'pending'));
$approvedCount = count(array_filter($claims, fn($c) => $c['claim_status'] === 'approved'));

$page_title = 'Admin - Manage Claims';
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
    margin: 0 0 5px 0;
    font-size: 28px;
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.stat-box {
    background: rgba(255,255,255,0.2);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.stat-box .number {
    font-size: 24px;
    font-weight: bold;
}

.stat-box .label {
    font-size: 12px;
    margin-top: 5px;
    opacity: 0.9;
}

.claims-container {
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
    letter-spacing: 0.5px;
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

.user-info {
    font-weight: 600;
    color: #333;
}

.user-email {
    font-size: 12px;
    color: #999;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-approve, .btn-reject {
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
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

.modal-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #EEE;
}

.modal-header h2 {
    margin: 0;
    color: #333;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
    font-size: 14px;
}

.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #DDD;
    border-radius: 5px;
    font-size: 14px;
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
    transition: all 0.3s ease;
}

.btn-modal-approve {
    background: #28a745;
    color: white;
}

.btn-modal-approve:hover {
    background: #218838;
}

.btn-modal-reject {
    background: #dc3545;
    color: white;
}

.btn-modal-reject:hover {
    background: #c82333;
}

.btn-cancel {
    background: #E9ECEF;
    color: #333;
}

.btn-cancel:hover {
    background: #DDD;
}
</style>

<div class="admin-header">
    <div class="container">
        <h1>📋 Manage Listing Claims</h1>
        <div class="admin-stats">
            <div class="stat-box">
                <div class="number"><?php echo count($claims); ?></div>
                <div class="label">Total Claims</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $pendingCount; ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $approvedCount; ?></div>
                <div class="label">Approved</div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="claims-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Business Name</th>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Company</th>
                        <th>Claimed Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($claims as $claim): ?>
                        <tr>
                            <td>
                                <span class="status-badge status-<?php echo $claim['claim_status']; ?>">
                                    <?php echo ucfirst($claim['claim_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="/pages/business-detail.php?id=<?php echo $claim['business_id']; ?>" style="color: #667eea; text-decoration: none;">
                                    <?php echo esc($claim['business_name']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="user-info"><?php echo esc($claim['full_name']); ?></div>
                                <div class="user-email"><?php echo esc($claim['username']); ?></div>
                            </td>
                            <td>
                                📧 <?php echo esc($claim['email']); ?><br>
                                📞 <?php echo esc($claim['phone'] ?? 'N/A'); ?>
                            </td>
                            <td><?php echo esc($claim['company_name'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($claim['claimed_at'])); ?></td>
                            <td>
                                <?php if ($claim['claim_status'] === 'pending'): ?>
                                    <div class="action-buttons">
                                        <button onclick="openModal('approve', <?php echo $claim['id']; ?>)" class="btn-approve">Approve</button>
                                        <button onclick="openModal('reject', <?php echo $claim['id']; ?>)" class="btn-reject">Reject</button>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Decision Modal -->
<div id="decisionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
        </div>
        <form method="POST" action="">
            <input type="hidden" id="claimId" name="claim_id">
            <input type="hidden" id="actionInput" name="action">

            <div class="form-group">
                <label for="notes">Notes (Optional)</label>
                <textarea id="notes" name="notes" placeholder="Add any notes about this decision..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="submit" id="submitBtn" class="btn-modal"></button>
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(action, claimId) {
    const modal = document.getElementById('decisionModal');
    const title = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const claimInput = document.getElementById('claimId');
    const actionInput = document.getElementById('actionInput');
    
    claimInput.value = claimId;
    actionInput.value = action;
    
    if (action === 'approve') {
        title.textContent = '✅ Approve Claim';
        submitBtn.className = 'btn-modal btn-modal-approve';
        submitBtn.textContent = 'Approve';
    } else {
        title.textContent = '❌ Reject Claim';
        submitBtn.className = 'btn-modal btn-modal-reject';
        submitBtn.textContent = 'Reject';
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('decisionModal').classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('decisionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
