<?php
/**
 * Client Dashboard
 */

session_start();

// Check if client is logged in
if (!isset($_SESSION['client_logged_in']) || !$_SESSION['client_logged_in']) {
    header('Location: /auth/client-login.php');
    exit;
}

require_once '../config/db.php';

$client_id = $_SESSION['client_id'];
$client_name = $_SESSION['client_name'];

// Get client details
$client = $conn->query("SELECT * FROM clients WHERE id = $client_id")->fetch_assoc();

// Get client's leads
$leads = $conn->query("SELECT * FROM leads WHERE client_id = $client_id ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Get client's invoices
$invoices = $conn->query("SELECT * FROM invoices WHERE client_id = $client_id ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Calculate stats
$total_leads = $conn->query("SELECT COUNT(*) as count FROM leads WHERE client_id = $client_id")->fetch_assoc()['count'];
$active_leads = $conn->query("SELECT COUNT(*) as count FROM leads WHERE client_id = $client_id AND pipeline_stage != 'closed'")->fetch_assoc()['count'];
$invoices_pending = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE client_id = $client_id AND status = 'pending'")->fetch_assoc()['count'];
$invoices_paid = $conn->query("SELECT SUM(total) as amount FROM invoices WHERE client_id = $client_id AND status = 'paid'")->fetch_assoc()['amount'] ?? 0;

$page_title = "Client Dashboard - ConnectWith";
require_once '../includes/header.php';
?>

<style>
    .client-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .client-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 2px solid #eee;
    }

    .client-header h1 {
        margin: 0;
        color: #0B1C3D;
    }

    .logout-btn {
        background: #f44336;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        font-weight: 600;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
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
        color: #FF6A00;
        margin: 10px 0;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .section-box {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .section-box h2 {
        margin-top: 0;
        color: #0B1C3D;
        border-bottom: 2px solid #FF6A00;
        padding-bottom: 15px;
    }

    .item-list {
        list-style: none;
        padding: 0;
    }

    .item-list li {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .item-list li:last-child {
        border-bottom: none;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-new {
        background: #e3f2fd;
        color: #1976d2;
    }

    .badge-interested {
        background: #fff3e0;
        color: #f57c00;
    }

    .badge-converted {
        background: #e8f5e9;
        color: #388e3c;
    }

    .badge-pending {
        background: #fff3e0;
        color: #f57c00;
    }

    .badge-paid {
        background: #e8f5e9;
        color: #388e3c;
    }

    .empty-state {
        text-align: center;
        color: #999;
        padding: 20px;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .content-grid {
            grid-template-columns: 1fr;
        }

        .client-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
    }
</style>

<div class="client-container">
    <div class="client-header">
        <div>
            <h1>Welcome, <?php echo htmlspecialchars($client_name); ?>!</h1>
            <p style="color: #666; margin: 5px 0;">Company: <?php echo htmlspecialchars($client['company'] ?? 'N/A'); ?></p>
        </div>
        <a href="/auth/client-logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Leads</div>
            <div class="stat-number"><?php echo $total_leads; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Leads</div>
            <div class="stat-number"><?php echo $active_leads; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending Invoices</div>
            <div class="stat-number"><?php echo $invoices_pending; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Paid Amount</div>
            <div class="stat-number">₹<?php echo number_format($invoices_paid, 0); ?></div>
        </div>
    </div>

    <!-- Content -->
    <div class="content-grid">
        <!-- Recent Leads -->
        <div class="section-box">
            <h2>📞 Your Leads</h2>
            <?php if (!empty($leads)): ?>
            <ul class="item-list">
                <?php foreach ($leads as $lead): ?>
                <li>
                    <div>
                        <strong><?php echo htmlspecialchars($lead['name']); ?></strong>
                        <p style="margin: 5px 0; font-size: 13px; color: #666;">
                            <?php echo htmlspecialchars($lead['service']); ?>
                        </p>
                    </div>
                    <span class="badge badge-<?php echo $lead['pipeline_stage']; ?>">
                        <?php echo ucfirst($lead['pipeline_stage']); ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="empty-state">No leads yet</div>
            <?php endif; ?>
        </div>

        <!-- Recent Invoices -->
        <div class="section-box">
            <h2>💰 Your Invoices</h2>
            <?php if (!empty($invoices)): ?>
            <ul class="item-list">
                <?php foreach ($invoices as $invoice): ?>
                <li>
                    <div>
                        <strong>Invoice #<?php echo $invoice['id']; ?></strong>
                        <p style="margin: 5px 0; font-size: 13px; color: #666;">
                            ₹<?php echo number_format($invoice['total'], 2); ?>
                        </p>
                    </div>
                    <span class="badge badge-<?php echo $invoice['status']; ?>">
                        <?php echo ucfirst($invoice['status']); ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="empty-state">No invoices yet</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
