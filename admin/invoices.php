<?php
/**
 * Admin - Invoice Management
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';

// Handle create invoice
if (isset($_POST['action']) && $_POST['action'] === 'create' && isset($_POST['client_id'])) {
    $client_id = (int)$_POST['client_id'];
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $total = $amount + $tax;
    $due_date = $_POST['due_date'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO invoices (client_id, description, amount, tax, total, due_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isddds', $client_id, $description, $amount, $tax, $total, $due_date);
    $stmt->execute();
    $stmt->close();
    
    header('Location: invoices.php');
    exit;
}

// Handle mark paid
if (isset($_POST['action']) && $_POST['action'] === 'mark_paid' && isset($_POST['invoice_id'])) {
    $invoice_id = (int)$_POST['invoice_id'];
    $payment_method = trim($_POST['payment_method'] ?? 'bank_transfer');
    
    $stmt = $conn->prepare("UPDATE invoices SET status = 'paid', payment_method = ?, paid_date = NOW() WHERE id = ?");
    $stmt->bind_param('si', $payment_method, $invoice_id);
    $stmt->execute();
    $stmt->close();
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

// Get all invoices, clients, and stats (with error handling for missing table)
$invoices = [];
$clients = [];
$total_invoiced = 0;
$total_paid = 0;
$total_pending = 0;
$table_error = null;

try {
    $invoices = $conn->query("
        SELECT i.*, c.name as client_name, c.phone, c.email 
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        ORDER BY i.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC) ?? [];

    // Get clients for dropdown
    $clients = $conn->query("SELECT id, name, email FROM clients ORDER BY name")->fetch_all(MYSQLI_ASSOC) ?? [];

    // Calculate stats
    $total_invoiced = $conn->query("SELECT SUM(total) as amount FROM invoices")->fetch_assoc()['amount'] ?? 0;
    $total_paid = $conn->query("SELECT SUM(total) as amount FROM invoices WHERE status = 'paid'")->fetch_assoc()['amount'] ?? 0;
    $total_pending = $conn->query("SELECT SUM(total) as amount FROM invoices WHERE status = 'pending'")->fetch_assoc()['amount'] ?? 0;
} catch (Exception $e) {
    $table_error = "Invoice system is not available yet. The invoices table needs to be created.";
}

$page_title = "Invoice Management - Admin";
require_once '../includes/header.php';
?>

<style>
    .invoice-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
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
        font-size: 28px;
        font-weight: 700;
        color: #FF6A00;
        margin: 10px 0;
    }

    .stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }

    .create-btn {
        background: #FF6A00;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
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

    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    .invoice-table thead {
        background: #0B1C3D;
        color: white;
    }

    .invoice-table th,
    .invoice-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .invoice-table tbody tr:hover {
        background: #f8f9fa;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-paid {
        background: #e8f5e9;
        color: #25D366;
    }

    .badge-pending {
        background: #fff3e0;
        color: #FF9A00;
    }

    .action-btns {
        display: flex;
        gap: 10px;
    }

    .action-btns button {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        background: #FF6A00;
        color: white;
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
        background: #FF6A00;
        color: white;
    }

    .btn-cancel {
        background: #ddd;
        color: #333;
    }
</style>

<div class="invoice-container">
    <div class="admin-header">
        <h1>💰 Invoice Management</h1>
        <button class="create-btn" onclick="openModal()" <?php echo $table_error ? 'disabled' : ''; ?>>+ Create Invoice</button>
    </div>

    <?php if ($table_error): ?>
    <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
        <strong>⚠️ Notice:</strong> <?php echo $table_error; ?>
    </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Invoiced</div>
            <div class="stat-number">₹<?php echo number_format($total_invoiced, 0); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Paid</div>
            <div class="stat-number">₹<?php echo number_format($total_paid, 0); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-number">₹<?php echo number_format($total_pending, 0); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Invoices</div>
            <div class="stat-number"><?php echo count($invoices); ?></div>
        </div>
    </div>

    <!-- Invoices Table -->
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Invoice ID</th>
                <th>Client</th>
                <th>Email</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td><strong>#<?php echo $inv['id']; ?></strong></td>
                <td><?php echo esc($inv['client_name']); ?></td>
                <td><?php echo esc($inv['email']); ?></td>
                <td><strong>₹<?php echo number_format($inv['total'], 2); ?></strong></td>
                <td><span class="badge badge-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                <td><?php echo $inv['due_date'] ? date('M d, Y', strtotime($inv['due_date'])) : '-'; ?></td>
                <td>
                    <div class="action-btns">
                        <?php if ($inv['status'] === 'pending'): ?>
                        <button onclick="markPaid(<?php echo $inv['id']; ?>)">✓ Mark Paid</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Create Invoice Modal -->
<div class="modal" id="createModal">
    <div class="modal-content">
        <h2>Create Invoice</h2>
        <form method="POST">
            <div class="form-group">
                <label>Client</label>
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo esc($c['name']); ?> (<?php echo esc($c['email']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Invoice description/items"></textarea>
            </div>

            <div class="form-group">
                <label>Amount (₹)</label>
                <input type="number" name="amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label>Tax (₹)</label>
                <input type="number" name="tax" step="0.01" value="0">
            </div>

            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date">
            </div>

            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="action" value="create" class="btn-submit">Create Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('createModal').classList.add('active');
}

function closeModal() {
    document.getElementById('createModal').classList.remove('active');
}

function markPaid(invoiceId) {
    const method = prompt('Payment method (bank_transfer/upi/cash):', 'bank_transfer');
    if (method) {
        fetch('invoices.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=mark_paid&invoice_id=' + invoiceId + '&payment_method=' + method
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) location.reload();
        });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
