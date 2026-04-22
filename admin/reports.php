<?php
/**
 * Admin - Advanced Reporting & Export
 */

require_once '../config/db.php';
require_once '../config/auth.php';

if (!isAdmin()) {
    header('Location: /auth/login.php');
    exit;
}

$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['to'] ?? date('Y-m-d');
$reportType = $_GET['type'] ?? 'overview';

// Generate reports based on type
$reportData = [];
switch ($reportType) {
    case 'payments':
        $reportData = generatePaymentReport($dateFrom, $dateTo);
        break;
    case 'inquiries':
        $reportData = generateInquiryReport($dateFrom, $dateTo);
        break;
    case 'businesses':
        $reportData = generateBusinessReport($dateFrom, $dateTo);
        break;
    default:
        $reportData = generateOverviewReport($dateFrom, $dateTo);
}

// Handle exports
if (isset($_GET['export'])) {
    exportReport($reportData, $_GET['export']);
    exit;
}

$page_title = 'Advanced Reports';
include '../includes/header.php';
?>

<style>
.reports-panel {
    max-width: 1300px;
    margin: 30px auto;
    padding: 0 20px;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.report-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.report-filters input,
.report-filters select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 13px;
}

.export-btns {
    display: flex;
    gap: 10px;
}

.export-btn {
    padding: 8px 15px;
    background: #FF6A00;
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s;
}

.export-btn:hover {
    background: #E55A00;
}

.report-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.report-card h2 {
    margin-top: 0;
    color: #0B1C3D;
    border-bottom: 2px solid #667eea;
    padding-bottom: 12px;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.metric-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.metric-value {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 5px;
}

.metric-label {
    font-size: 12px;
    opacity: 0.9;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.data-table th {
    background: #F5F5F5;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 1px solid #ddd;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.data-table tr:hover {
    background: #F9F9F9;
}
</style>

<div class="reports-panel">
    <div class="report-header">
        <h1>📊 Advanced Reports</h1>
        
        <div class="report-filters">
            <input type="date" id="dateFrom" value="<?php echo $dateFrom; ?>" onchange="updateReport()">
            <span style="color: #999;">to</span>
            <input type="date" id="dateTo" value="<?php echo $dateTo; ?>" onchange="updateReport()">
            
            <select onchange="updateReport()" id="reportType" style="width: 150px;">
                <option value="overview" <?php echo $reportType === 'overview' ? 'selected' : ''; ?>>Overview Report</option>
                <option value="payments" <?php echo $reportType === 'payments' ? 'selected' : ''; ?>>Payment Report</option>
                <option value="inquiries" <?php echo $reportType === 'inquiries' ? 'selected' : ''; ?>>Inquiry Report</option>
                <option value="businesses" <?php echo $reportType === 'businesses' ? 'selected' : ''; ?>>Business Report</option>
            </select>
            
            <div class="export-btns">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="export-btn">📥 CSV</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="export-btn">📄 PDF</a>
            </div>
        </div>
    </div>
    
    <!-- Overview Report -->
    <?php if ($reportType === 'overview'): ?>
    <div class="report-card">
        <h2>📈 Platform Overview</h2>
        <div class="metric-grid">
            <div class="metric-box">
                <div class="metric-value"><?php echo number_format($reportData['total_views'] ?? 0); ?></div>
                <div class="metric-label">Total Views</div>
            </div>
            <div class="metric-box">
                <div class="metric-value"><?php echo number_format($reportData['total_clicks'] ?? 0); ?></div>
                <div class="metric-label">Total Clicks</div>
            </div>
            <div class="metric-box">
                <div class="metric-value"><?php echo number_format($reportData['total_inquiries'] ?? 0); ?></div>
                <div class="metric-label">Total Inquiries</div>
            </div>
            <div class="metric-box">
                <div class="metric-value">$<?php echo number_format($reportData['total_revenue'] ?? 0, 0); ?></div>
                <div class="metric-label">Revenue</div>
            </div>
        </div>
    </div>
    
    <div class="report-card">
        <h2>🏆 Top Performing Businesses</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Views</th>
                    <th>Clicks</th>
                    <th>Inquiries</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['top_businesses'] ?? [] as $biz): ?>
                <tr>
                    <td><?php echo esc($biz['name']); ?></td>
                    <td><?php echo number_format($biz['views']); ?></td>
                    <td><?php echo number_format($biz['clicks']); ?></td>
                    <td><?php echo $biz['inquiries']; ?></td>
                    <td>$<?php echo number_format($biz['revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Payment Report -->
    <?php if ($reportType === 'payments'): ?>
    <div class="report-card">
        <h2>💳 Payment Report</h2>
        <div class="metric-grid">
            <div class="metric-box">
                <div class="metric-value"><?php echo count($reportData['payments'] ?? []); ?></div>
                <div class="metric-label">Total Transactions</div>
            </div>
            <div class="metric-box">
                <div class="metric-value">$<?php echo number_format($reportData['total_amount'] ?? 0, 2); ?></div>
                <div class="metric-label">Total Amount</div>
            </div>
            <div class="metric-box">
                <div class="metric-value"><?php echo $reportData['completed_count'] ?? 0; ?></div>
                <div class="metric-label">Completed</div>
            </div>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['payments'] ?? [] as $payment): ?>
                <tr>
                    <td><?php echo esc($payment['business_name']); ?></td>
                    <td><?php echo esc($payment['plan_name']); ?></td>
                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                    <td><?php echo ucfirst($payment['status']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Inquiry Report -->
    <?php if ($reportType === 'inquiries'): ?>
    <div class="report-card">
        <h2>💬 Inquiry Report</h2>
        <div class="metric-grid">
            <div class="metric-box">
                <div class="metric-value"><?php echo count($reportData['inquiries'] ?? []); ?></div>
                <div class="metric-label">Total Inquiries</div>
            </div>
            <div class="metric-box">
                <div class="metric-value"><?php echo $reportData['new_count'] ?? 0; ?></div>
                <div class="metric-label">New</div>
            </div>
            <div class="metric-box">
                <div class="metric-value"><?php echo $reportData['contacted_count'] ?? 0; ?></div>
                <div class="metric-label">Contacted</div>
            </div>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['inquiries'] ?? [] as $inquiry): ?>
                <tr>
                    <td><?php echo esc($inquiry['business_name']); ?></td>
                    <td><?php echo esc($inquiry['name']); ?></td>
                    <td><?php echo ucfirst($inquiry['inquiry_type']); ?></td>
                    <td><?php echo ucfirst($inquiry['status']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function updateReport() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const reportType = document.getElementById('reportType').value;
    window.location.href = `?type=${reportType}&from=${dateFrom}&to=${dateTo}`;
}
</script>

<?php
function generateOverviewReport($from, $to) {
    global $conn;
    
    $totalViews = $conn->query("SELECT SUM(views) as v FROM business_analytics")->fetch_assoc()['v'] ?? 0;
    $totalClicks = $conn->query("SELECT SUM(clicks) as c FROM business_analytics")->fetch_assoc()['c'] ?? 0;
    $totalInquiries = $conn->query("SELECT COUNT(*) as c FROM inquiries WHERE created_at BETWEEN '$from' AND DATE_ADD('$to', INTERVAL 1 DAY)")->fetch_assoc()['c'];
    $totalRevenue = $conn->query("SELECT SUM(amount) as a FROM payments WHERE status = 'completed' AND created_at BETWEEN '$from' AND DATE_ADD('$to', INTERVAL 1 DAY)")->fetch_assoc()['a'] ?? 0;
    
    $topBusinesses = $conn->query("
        SELECT b.name, COALESCE(ba.views, 0) as views, COALESCE(ba.clicks, 0) as clicks, COALESCE(ba.inquiries, 0) as inquiries,
               COALESCE(SUM(p.amount), 0) as revenue
        FROM extracted_businesses b
        LEFT JOIN business_analytics ba ON b.id = ba.business_id
        LEFT JOIN payments p ON b.id = p.business_id AND p.status = 'completed'
        WHERE b.business_status = 'OPERATIONAL'
        GROUP BY b.id
        ORDER BY ba.views DESC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);
    
    return [
        'total_views' => $totalViews,
        'total_clicks' => $totalClicks,
        'total_inquiries' => $totalInquiries,
        'total_revenue' => $totalRevenue,
        'top_businesses' => $topBusinesses
    ];
}

function generatePaymentReport($from, $to) {
    global $conn;
    
    $payments = $conn->query("
        SELECT p.*, b.name as business_name, pp.name as plan_name
        FROM payments p
        JOIN extracted_businesses b ON p.business_id = b.id
        LEFT JOIN premium_plans pp ON p.plan_id = pp.id
        WHERE p.created_at BETWEEN '$from' AND DATE_ADD('$to', INTERVAL 1 DAY)
        ORDER BY p.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
    
    $total = array_sum(array_column($payments, 'amount'));
    $completed = count(array_filter($payments, fn($p) => $p['status'] === 'completed'));
    
    return [
        'payments' => $payments,
        'total_amount' => $total,
        'completed_count' => $completed
    ];
}

function generateInquiryReport($from, $to) {
    global $conn;
    
    $inquiries = $conn->query("
        SELECT i.*, b.name as business_name
        FROM inquiries i
        JOIN extracted_businesses b ON i.business_id = b.id
        WHERE i.created_at BETWEEN '$from' AND DATE_ADD('$to', INTERVAL 1 DAY)
        ORDER BY i.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
    
    $newCount = $conn->query("SELECT COUNT(*) as c FROM inquiries WHERE status = 'new' AND created_at BETWEEN '$from' AND DATE_ADD('$to', INTERVAL 1 DAY)")->fetch_assoc()['c'];
    $contactedCount = $conn->query("SELECT COUNT(*) as c FROM inquiries WHERE status = 'contacted' AND created_at BETWEEN '$from' AND DATE_ADD('$to', INTERVAL 1 DAY)")->fetch_assoc()['c'];
    
    return [
        'inquiries' => $inquiries,
        'new_count' => $newCount,
        'contacted_count' => $contactedCount
    ];
}

function generateBusinessReport($from, $to) {
    global $conn;
    return generateOverviewReport($from, $to);
}

function exportReport($data, $format) {
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="report-' . date('Y-m-d') . '.csv"');
        
        echo "ConnectWith9 Report Export\n";
        echo date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                echo "$key\n";
                foreach ($value as $row) {
                    if (is_array($row)) {
                        echo implode(',', $row) . "\n";
                    }
                }
                echo "\n";
            }
        }
    } elseif ($format === 'pdf') {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="report-' . date('Y-m-d') . '.pdf"');
        // Simple text-based PDF fallback (in production, use TCPDF or similar)
        echo "Report export to PDF requires TCPDF library";
    }
}
?>

<?php include '../includes/footer.php'; ?>
