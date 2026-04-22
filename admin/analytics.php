<?php
/**
 * Admin - Platform Analytics Dashboard
 */

require_once '../config/db.php';
require_once '../config/auth.php';

if (!isAdmin()) {
    header('Location: /auth/login.php');
    exit;
}

// Overall stats
$totalViews = $GLOBALS['conn']->query("SELECT SUM(views) as v FROM business_analytics")->fetch_assoc()['v'] ?? 0;
$totalClicks = $GLOBALS['conn']->query("SELECT SUM(clicks) as c FROM business_analytics")->fetch_assoc()['c'] ?? 0;
$totalInquiries = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM inquiries")->fetch_assoc()['c'];
$newInquiries = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM inquiries WHERE status = 'new'")->fetch_assoc()['c'];

// Top performing businesses
$topBusinesses = $GLOBALS['conn']->query("
    SELECT b.id, b.name, b.search_location, 
           COALESCE(ba.views, 0) as views, 
           COALESCE(ba.inquiries, 0) as inquiries,
           COALESCE(ba.clicks, 0) as clicks
    FROM extracted_businesses b
    LEFT JOIN business_analytics ba ON b.id = ba.business_id
    WHERE b.business_status = 'OPERATIONAL'
    ORDER BY ba.views DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Inquiry trends
$inquiryStats = $GLOBALS['conn']->query("
    SELECT inquiry_type, COUNT(*) as count 
    FROM inquiries 
    GROUP BY inquiry_type
")->fetch_all(MYSQLI_ASSOC);

// Recent inquiries
$recentInquiries = $GLOBALS['conn']->query("
    SELECT i.*, b.name as business_name 
    FROM inquiries i 
    JOIN extracted_businesses b ON i.business_id = b.id 
    ORDER BY i.created_at DESC 
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Platform Analytics';
include '../includes/header.php';
?>

<style>
.analytics-panel {
    max-width: 1300px;
    margin: 30px auto;
    padding: 0 20px;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.kpi-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.kpi-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

.kpi-value {
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 5px;
}

.kpi-label {
    color: #666;
    font-size: 13px;
    text-transform: uppercase;
    font-weight: 600;
}

.panel-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.panel-section h2 {
    margin-top: 0;
    color: #0B1C3D;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
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

.inquiry-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.badge-general { background: #E3F2FD; color: #1976D2; }
.badge-booking { background: #F3E5F5; color: #7B1FA2; }
.badge-quote { background: #E8F5E9; color: #388E3C; }
.badge-complaint { background: #FFEBEE; color: #C62828; }

.status-bar {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.status-item {
    flex: 1;
    padding: 12px;
    background: #F5F5F5;
    border-radius: 5px;
    text-align: center;
}

.status-count {
    font-size: 20px;
    font-weight: bold;
    color: #667eea;
}

.status-label {
    color: #666;
    font-size: 12px;
    margin-top: 4px;
}
</style>

<div class="analytics-panel">
    <h1>📊 Platform Analytics Dashboard</h1>
    
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon">📈</div>
            <div class="kpi-value"><?php echo number_format($totalViews); ?></div>
            <div class="kpi-label">Total Views</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🔗</div>
            <div class="kpi-value"><?php echo number_format($totalClicks); ?></div>
            <div class="kpi-label">Total Clicks</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">💬</div>
            <div class="kpi-value"><?php echo number_format($totalInquiries); ?></div>
            <div class="kpi-label">Total Inquiries</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🆕</div>
            <div class="kpi-value"><?php echo $newInquiries; ?></div>
            <div class="kpi-label">New Inquiries</div>
        </div>
    </div>
    
    <div class="panel-section">
        <h2>🏆 Top Performing Businesses</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Business Name</th>
                        <th>Location</th>
                        <th>Views</th>
                        <th>Clicks</th>
                        <th>Inquiries</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topBusinesses as $biz): ?>
                    <tr>
                        <td><strong><?php echo esc($biz['name']); ?></strong></td>
                        <td><?php echo esc($biz['search_location']); ?></td>
                        <td><?php echo number_format($biz['views']); ?></td>
                        <td><?php echo number_format($biz['clicks']); ?></td>
                        <td><?php echo $biz['inquiries']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="panel-section">
            <h2>📊 Inquiry Types</h2>
            <div class="status-bar" style="flex-wrap: wrap;">
                <?php foreach ($inquiryStats as $stat): ?>
                <div style="flex: 1; min-width: 150px; padding: 12px; background: #F5F5F5; border-radius: 5px; text-align: center;">
                    <div class="status-count"><?php echo $stat['count']; ?></div>
                    <div class="status-label"><?php echo ucfirst($stat['inquiry_type']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="panel-section">
            <h2>📬 Inquiry Status Summary</h2>
            <?php
            $newCount = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM inquiries WHERE status = 'new'")->fetch_assoc()['c'];
            $contacted = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM inquiries WHERE status = 'contacted'")->fetch_assoc()['c'];
            $closed = $GLOBALS['conn']->query("SELECT COUNT(*) as c FROM inquiries WHERE status = 'closed'")->fetch_assoc()['c'];
            ?>
            <div class="status-bar" style="flex-wrap: wrap;">
                <div style="flex: 1; min-width: 120px; padding: 12px; background: #FFD700; border-radius: 5px; text-align: center;">
                    <div class="status-count"><?php echo $newCount; ?></div>
                    <div class="status-label">New</div>
                </div>
                <div style="flex: 1; min-width: 120px; padding: 12px; background: #87CEEB; border-radius: 5px; text-align: center;">
                    <div class="status-count"><?php echo $contacted; ?></div>
                    <div class="status-label">Contacted</div>
                </div>
                <div style="flex: 1; min-width: 120px; padding: 12px; background: #90EE90; border-radius: 5px; text-align: center;">
                    <div class="status-count"><?php echo $closed; ?></div>
                    <div class="status-label">Closed</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="panel-section">
        <h2>📬 Recent Inquiries</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentInquiries as $inquiry): ?>
                    <tr>
                        <td><strong><?php echo esc($inquiry['business_name']); ?></strong></td>
                        <td><?php echo esc($inquiry['name']); ?></td>
                        <td><span class="inquiry-badge badge-<?php echo $inquiry['inquiry_type']; ?>"><?php echo ucfirst($inquiry['inquiry_type']); ?></span></td>
                        <td><span class="inquiry-badge" style="background: #F0F0F0; color: #333;"><?php echo ucfirst($inquiry['status']); ?></span></td>
                        <td><?php echo date('M d, H:i', strtotime($inquiry['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
