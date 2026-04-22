<?php
/**
 * User Dashboard - Claimed Listings Management
 */

require_once '../config/db.php';
require_once '../config/auth.php';

requireLogin();

$user = getUserData();

// Get claimed listings
$query = "SELECT 
    lc.id as claim_id,
    lc.claim_status,
    lc.approved_at,
    lc.claimed_at,
    b.id as business_id,
    b.name,
    b.formatted_address,
    b.search_location,
    b.rating,
    b.user_ratings_total,
    b.formatted_phone_number,
    b.website
FROM listing_claims lc
JOIN extracted_businesses b ON lc.business_id = b.id
WHERE lc.user_id = ?
ORDER BY lc.claimed_at DESC";

$stmt = $GLOBALS['conn']->prepare($query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$claims = [];
while ($row = $result->fetch_assoc()) {
    $claims[] = $row;
}

// Get pending edits
$editQuery = "SELECT COUNT(*) as pending_count FROM business_edits WHERE editor_id = ? AND edit_status = 'pending'";
$editStmt = $GLOBALS['conn']->prepare($editQuery);
$editStmt->bind_param('i', $_SESSION['user_id']);
$editStmt->execute();
$editResult = $editStmt->get_result();
$editRow = $editResult->fetch_assoc();
$pendingEdits = $editRow['pending_count'];

// Count by status
$approved = count(array_filter($claims, fn($c) => $c['claim_status'] === 'approved'));
$pending  = count(array_filter($claims, fn($c) => $c['claim_status'] === 'pending'));

// Get lead counts per business
$totalLeads = 0;
$leadCounts = [];
foreach ($claims as $claim) {
    if ($claim['claim_status'] !== 'approved') continue;
    $bid = (int)$claim['business_id'];
    $lRow = $GLOBALS['conn']->query("SELECT COUNT(*) as cnt FROM inquiries WHERE business_id = $bid")->fetch_assoc();
    $cnt = (int)($lRow['cnt'] ?? 0);
    $leadCounts[$bid] = $cnt;
    $totalLeads += $cnt;
}

$page_title = 'Dashboard - My Listings';
$meta_description = 'Manage your claimed business listings.';

include '../includes/header.php';
?>

<style>
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 40px;
}

.dashboard-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
}

.dashboard-header p {
    margin: 0;
    opacity: 0.9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card .number {
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
}

.stat-card .label {
    color: #666;
    font-size: 14px;
    margin-top: 10px;
}

.claims-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.claims-section h2 {
    margin: 0 0 20px 0;
    font-size: 22px;
    color: #333;
}

.claims-list {
    display: grid;
    gap: 15px;
}

.claim-card {
    padding: 20px;
    border: 1px solid #EEE;
    border-radius: 8px;
    background: #F9F9F9;
    transition: all 0.3s ease;
}

.claim-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    background: white;
}

.claim-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.claim-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.claim-status {
    padding: 6px 15px;
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

.claim-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
    font-size: 14px;
}

.info-item {
    display: flex;
    gap: 10px;
}

.info-label {
    color: #666;
    font-weight: 600;
    min-width: 80px;
}

.info-value {
    color: #333;
}

.claim-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-edit {
    padding: 8px 16px;
    background: #667eea;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.3s ease;
}

.btn-edit:hover {
    background: #5568d3;
    transform: translateY(-2px);
}

.btn-view {
    padding: 8px 16px;
    background: white;
    color: #667eea;
    border: 1px solid #667eea;
    text-decoration: none;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-view:hover {
    background: #F9F9FF;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

.empty-state h3 {
    margin-top: 0;
    color: #333;
}

.btn-claim {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-top: 15px;
}

.btn-claim:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
    .claim-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .stat-card {
        min-width: 0;
    }
}
</style>

<div class="dashboard-header">
    <div class="container">
        <h1>Welcome, <?php echo esc($user['full_name']); ?>! 👋</h1>
        <p>Manage your claimed business listings</p>
    </div>
</div>

<div class="container">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?php echo count($claims); ?></div>
            <div class="label">Total Claims</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $approved; ?></div>
            <div class="label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $pending; ?></div>
            <div class="label">Pending Approval</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $pendingEdits; ?></div>
            <div class="label">Pending Edits</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $totalLeads; ?></div>
            <div class="label">Total Leads</div>
        </div>
    </div>

    <!-- Claims Section -->
    <div class="claims-section">
        <h2>My Claimed Listings</h2>

        <?php if (empty($claims)): ?>
            <div class="empty-state">
                <h3>No claimed listings yet</h3>
                <p>Start by claiming a business listing to manage its information</p>
                <a href="/pages/search.php" class="btn-claim">Browse & Claim Listings</a>
            </div>
        <?php else: ?>
            <div class="claims-list">
                <?php foreach ($claims as $claim): ?>
                    <div class="claim-card">
                        <div class="claim-header">
                            <h3 class="claim-title"><?php echo esc($claim['name']); ?></h3>
                            <span class="claim-status status-<?php echo $claim['claim_status']; ?>">
                                <?php echo ucfirst($claim['claim_status']); ?>
                            </span>
                        </div>

                        <div class="claim-info">
                            <div class="info-item">
                                <span class="info-label">📍 Location:</span>
                                <span class="info-value"><?php echo esc($claim['formatted_address'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">⭐ Rating:</span>
                                <span class="info-value"><?php echo number_format($claim['rating'], 1); ?>/5 (<?php echo $claim['user_ratings_total']; ?> reviews)</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">📞 Phone:</span>
                                <span class="info-value"><?php echo esc($claim['formatted_phone_number'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">📅 Claimed:</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($claim['claimed_at'])); ?></span>
                            </div>
                            <?php if ($claim['claim_status'] === 'approved'): ?>
                            <div class="info-item">
                                <span class="info-label">📩 Leads:</span>
                                <span class="info-value" style="font-weight:700;color:#28a745;">
                                    <?php echo $leadCounts[$claim['business_id']] ?? 0; ?> received
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($claim['claim_status'] === 'approved'): ?>
                            <div class="claim-actions">
                                <a href="/pages/edit-listing.php?id=<?php echo $claim['business_id']; ?>" class="btn-edit">✏️ Edit Details</a>
                                <a href="/pages/manage-images.php?id=<?php echo $claim['business_id']; ?>" class="btn-edit">📸 Manage Photos</a>
                                <a href="/pages/manage-media.php?id=<?php echo $claim['business_id']; ?>" class="btn-edit">🎬 Logo &amp; Video</a>
                                <a href="/pages/manage-faqs.php?id=<?php echo $claim['business_id']; ?>" class="btn-edit">❓ Manage FAQs</a>
                                <a href="/pages/manage-business-updates.php" class="btn-edit">📝 Updates</a>
                                <a href="/pages/manage-services.php" class="btn-edit">🔧 Services</a>
                                <a href="/pages/manage-offers.php" class="btn-edit">🎉 Offers</a>
                                <a href="/pages/manage-description.php" class="btn-edit">📄 Description</a>
                                <a href="/pages/business-inquiries.php?bid=<?php echo $claim['business_id']; ?>" class="btn-edit" style="background:#28a745;">📩 My Leads</a>
                                <a href="/pages/manage-visibility.php?id=<?php echo $claim['business_id']; ?>" class="btn-edit" style="background:#6366f1;">👁️ Visibility</a>
                                <a href="/pages/business-detail.php?id=<?php echo $claim['business_id']; ?>" class="btn-view">👁️ View Profile</a>
                            </div>
                        <?php else: ?>
                            <div class="claim-actions">
                                <span style="color: #666; font-size: 13px;">
                                    ⏳ Waiting for admin approval to edit this listing
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
