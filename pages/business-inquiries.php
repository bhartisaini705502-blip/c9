<?php
/**
 * Business Owner - Manage Inquiries & Leads
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$user = getUserData();

// Get businesses claimed by this user (via listing_claims)
$businesses = $GLOBALS['conn']->prepare("
    SELECT b.id, b.name, b.types, b.search_location
    FROM extracted_businesses b
    JOIN listing_claims lc ON lc.business_id = b.id
    WHERE lc.user_id = ? AND lc.claim_status = 'approved' AND b.business_status = 'OPERATIONAL'
");
$businesses->bind_param('i', $user['id']);
$businesses->execute();
$userBusinesses = $businesses->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($userBusinesses)) {
    redirect('/pages/dashboard.php');
}

$selectedBusinessId = isset($_GET['bid']) ? intval($_GET['bid']) : $userBusinesses[0]['id'];

// Verify user owns this business via listing_claims
$verify = $GLOBALS['conn']->prepare("
    SELECT lc.id FROM listing_claims lc
    WHERE lc.business_id = ? AND lc.user_id = ? AND lc.claim_status = 'approved'
");
$verify->bind_param('ii', $selectedBusinessId, $user['id']);
$verify->execute();
if ($verify->get_result()->num_rows === 0) {
    redirect('/pages/dashboard.php');
}

// Get inquiries for this business
$inquiries = $GLOBALS['conn']->query("
    SELECT * FROM inquiries 
    WHERE business_id = $selectedBusinessId 
    ORDER BY created_at DESC 
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

// Get analytics
$analytics = $GLOBALS['conn']->query("
    SELECT views, clicks, phone_clicks, direction_clicks, website_clicks, inquiries 
    FROM business_analytics 
    WHERE business_id = $selectedBusinessId
")->fetch_assoc() ?? [
    'views' => 0, 'clicks' => 0, 'phone_clicks' => 0, 
    'direction_clicks' => 0, 'website_clicks' => 0, 'inquiries' => 0
];

// Get daily stats for chart
$dailyStats = $GLOBALS['conn']->query("
    SELECT date, views, clicks, inquiries 
    FROM daily_analytics 
    WHERE business_id = $selectedBusinessId 
    ORDER BY date DESC 
    LIMIT 30
")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Business Inquiries & Analytics';
include '../includes/header.php';
?>

<style>
.inquiries-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px 40px;
}

.business-selector {
    margin-bottom: 30px;
}

.business-selector select {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    width: 100%;
    max-width: 400px;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.metric-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
}

.metric-value {
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 8px;
}

.metric-label {
    color: #666;
    font-size: 13px;
    text-transform: uppercase;
    font-weight: 600;
}

.inquiries-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    overflow: hidden;
}

.inquiries-header {
    padding: 20px;
    background: #667eea;
    color: white;
}

.inquiries-header h2 {
    margin: 0;
}

.inquiries-table {
    width: 100%;
    border-collapse: collapse;
}

.inquiries-table th {
    background: #F5F5F5;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    border-bottom: 1px solid #ddd;
}

.inquiries-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.inquiries-table tr:hover {
    background: #F9F9F9;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-new {
    background: #FFD700;
    color: #333;
}

.status-contacted {
    background: #87CEEB;
    color: #333;
}

.status-closed {
    background: #90EE90;
    color: #333;
}

.status-spam {
    background: #FFB6C6;
    color: #333;
}

.inquiry-type {
    font-size: 12px;
    color: #666;
    background: #F0F0F0;
    padding: 3px 8px;
    border-radius: 3px;
    display: inline-block;
}

.mark-btn {
    padding: 5px 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.3s;
}

.mark-btn:hover {
    background: #5568d3;
}

.no-inquiries {
    text-align: center;
    padding: 40px;
    color: #999;
}
</style>

<div class="inquiries-container">
    <h1>📊 Business Inquiries & Analytics</h1>
    
    <div class="business-selector">
        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Select Business:</label>
        <select onchange="window.location.href='/pages/business-inquiries.php?bid=' + this.value">
            <?php foreach ($userBusinesses as $biz): ?>
            <option value="<?php echo $biz['id']; ?>" <?php echo $biz['id'] == $selectedBusinessId ? 'selected' : ''; ?>>
                <?php echo esc($biz['name']); ?> (<?php echo esc($biz['search_location']); ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="analytics-grid">
        <div class="metric-card">
            <div class="metric-value"><?php echo $analytics['views']; ?></div>
            <div class="metric-label">📈 Total Views</div>
        </div>
        <div class="metric-card">
            <div class="metric-value"><?php echo $analytics['clicks']; ?></div>
            <div class="metric-label">🔗 Profile Clicks</div>
        </div>
        <div class="metric-card">
            <div class="metric-value"><?php echo $analytics['phone_clicks']; ?></div>
            <div class="metric-label">☎️ Phone Clicks</div>
        </div>
        <div class="metric-card">
            <div class="metric-value"><?php echo $analytics['inquiries']; ?></div>
            <div class="metric-label">💬 Inquiries</div>
        </div>
    </div>
    
    <div class="inquiries-section">
        <div class="inquiries-header">
            <h2>📬 Recent Inquiries (<?php echo count($inquiries); ?>)</h2>
        </div>
        
        <?php if (!empty($inquiries)): ?>
        <table class="inquiries-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inquiries as $inquiry): ?>
                <tr>
                    <td><strong><?php echo esc($inquiry['name']); ?></strong></td>
                    <td>
                        <?php if ($inquiry['email']): ?>
                        <div style="font-size: 12px;">✉️ <?php echo esc($inquiry['email']); ?></div>
                        <?php endif; ?>
                        <?php if ($inquiry['phone']): ?>
                        <div style="font-size: 12px;">☎️ <?php echo esc($inquiry['phone']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="inquiry-type"><?php echo ucfirst($inquiry['inquiry_type']); ?></span></td>
                    <td style="max-width: 250px; word-break: break-word;">
                        <?php echo esc(substr($inquiry['message'], 0, 50)); ?>...
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $inquiry['status']; ?>">
                            <?php echo ucfirst($inquiry['status']); ?>
                        </span>
                    </td>
                    <td style="font-size: 12px;">
                        <?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?>
                    </td>
                    <td>
                        <button class="mark-btn" onclick="markInquiry(<?php echo $inquiry['id']; ?>, 'contacted')">Mark Contacted</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-inquiries">
            <p>No inquiries yet. When customers contact you, they'll appear here.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function markInquiry(inquiryId, status) {
    fetch('/api/update-inquiry.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ inquiry_id: inquiryId, status: status })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            location.reload();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
