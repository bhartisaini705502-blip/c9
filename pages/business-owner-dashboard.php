<?php
/**
 * Business Owner Dashboard
 * Track views, inquiries, and manage listing
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/db.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getUserData();

// Get claimed businesses
$stmt = $conn->prepare("
    SELECT lc.business_id, eb.name, eb.rating, eb.review_count, lc.status
    FROM listing_claims lc
    JOIN extracted_businesses eb ON lc.business_id = eb.id
    WHERE lc.email = ? OR lc.phone = ?
    ORDER BY lc.created_at DESC
");
$stmt->bind_param('ss', $user['email'], $user['phone'] ?? '');
$stmt->execute();
$result = $stmt->get_result();
$claimed_businesses = [];
while ($row = $result->fetch_assoc()) {
    $claimed_businesses[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Dashboard - ConnectWith9</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .dashboard-header {
            margin-bottom: 40px;
        }
        .dashboard-header h1 {
            color: #0B1C3D;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .dashboard-header p {
            color: #666;
            font-size: 16px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #FF6A00;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .businesses-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
        }
        .section-title {
            color: #0B1C3D;
            font-size: 22px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .business-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        .business-name {
            font-weight: 600;
            color: #0B1C3D;
            font-size: 16px;
        }
        .business-meta {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .business-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .business-actions {
            display: flex;
            gap: 10px;
        }
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .action-btn-primary {
            background: #FF6A00;
            color: white;
        }
        .action-btn-primary:hover {
            background: #E55A00;
        }
        .action-btn-secondary {
            background: white;
            border: 1px solid #ddd;
            color: #0B1C3D;
        }
        .action-btn-secondary:hover {
            border-color: #FF6A00;
            color: #FF6A00;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>📊 Your Business Dashboard</h1>
            <p>Manage your claimed listings and track performance</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($claimed_businesses); ?></div>
                <div class="stat-label">Claimed Businesses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $approved = array_filter($claimed_businesses, fn($b) => $b['status'] === 'approved');
                    echo count($approved);
                    ?>
                </div>
                <div class="stat-label">Verified Listings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $total_views = 0;
                    foreach ($claimed_businesses as $b) {
                        $viewStmt = $conn->prepare("SELECT views FROM business_analytics WHERE business_id = ?");
                        $viewStmt->bind_param('i', $b['business_id']);
                        $viewStmt->execute();
                        $viewResult = $viewStmt->get_result()->fetch_assoc();
                        if ($viewResult) {
                            $total_views += $viewResult['views'];
                        }
                    }
                    echo $total_views;
                    ?>
                </div>
                <div class="stat-label">Total Views</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $total_inquiries = 0;
                    foreach ($claimed_businesses as $b) {
                        $inquiryStmt = $conn->prepare("SELECT COUNT(*) as count FROM inquiries WHERE business_id = ?");
                        $inquiryStmt->bind_param('i', $b['business_id']);
                        $inquiryStmt->execute();
                        $inquiryResult = $inquiryStmt->get_result()->fetch_assoc();
                        if ($inquiryResult) {
                            $total_inquiries += $inquiryResult['count'];
                        }
                    }
                    echo $total_inquiries;
                    ?>
                </div>
                <div class="stat-label">Inquiries</div>
            </div>
        </div>
        
        <div class="businesses-section">
            <div class="section-title">🏢 Your Businesses</div>
            
            <?php if (empty($claimed_businesses)): ?>
            <div class="empty-state">
                <div class="empty-icon">🏪</div>
                <h3>No claimed businesses yet</h3>
                <p>Claim your business to start tracking performance</p>
                <a href="/pages/claim-business.php" style="color: #FF6A00; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 15px;">
                    Claim Your Business →
                </a>
            </div>
            <?php else: ?>
                <?php foreach ($claimed_businesses as $business): ?>
                <div class="business-item">
                    <div style="flex: 1;">
                        <div class="business-name"><?php echo htmlspecialchars($business['name']); ?></div>
                        <div class="business-meta">⭐ <?php echo $business['rating']; ?> · <?php echo $business['review_count']; ?> reviews</div>
                    </div>
                    
                    <span class="business-status <?php echo $business['status'] === 'approved' ? 'status-approved' : 'status-pending'; ?>">
                        <?php echo ucfirst($business['status']); ?>
                    </span>
                    
                    <div class="business-actions">
                        <a href="/pages/business-detail.php?id=<?php echo $business['business_id']; ?>&name=<?php echo urlencode(slugify($business['name'] ?? '')); ?>" class="action-btn action-btn-primary">
                            View Listing
                        </a>
                        <a href="/pages/business-analytics.php?id=<?php echo $business['business_id']; ?>" class="action-btn action-btn-secondary">
                            Analytics
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
