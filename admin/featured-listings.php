<?php
/**
 * Featured Listings Management
 * Admin panel to manage featured and boosted listings
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: /');
    exit;
}

$message = '';
$messageType = '';

// Handle feature/unfeature actions
if ($_POST['action'] === 'feature') {
    $businessId = (int)($_POST['business_id'] ?? 0);
    $planType = $_POST['plan_type'] ?? 'featured';
    $duration = (int)($_POST['duration'] ?? 30);
    
    if ($businessId) {
        $expiryDate = date('Y-m-d H:i:s', strtotime("+$duration days"));
        
        // Check if already featured
        $checkQuery = "SELECT id FROM featured_listings WHERE business_id = ?";
        $checkStmt = $GLOBALS['conn']->prepare($checkQuery);
        $checkStmt->bind_param('i', $businessId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $updateQuery = "UPDATE featured_listings 
                           SET expires_at = ?, plan_type = ? 
                           WHERE business_id = ?";
            $updateStmt = $GLOBALS['conn']->prepare($updateQuery);
            $updateStmt->bind_param('ssi', $expiryDate, $planType, $businessId);
            $updateStmt->execute();
        } else {
            $insertQuery = "INSERT INTO featured_listings (business_id, expires_at, plan_type) 
                           VALUES (?, ?, ?)";
            $insertStmt = $GLOBALS['conn']->prepare($insertQuery);
            $insertStmt->bind_param('iss', $businessId, $expiryDate, $planType);
            $insertStmt->execute();
        }
        
        $updateBizQuery = "UPDATE extracted_businesses 
                          SET is_featured = 1, boost_expiry = ? 
                          WHERE id = ?";
        $updateBizStmt = $GLOBALS['conn']->prepare($updateBizQuery);
        $updateBizStmt->bind_param('si', $expiryDate, $businessId);
        $updateBizStmt->execute();
        
        $message = '✓ Listing featured successfully for ' . $duration . ' days';
        $messageType = 'success';
    }
} elseif ($_POST['action'] === 'unfeature') {
    $businessId = (int)($_POST['business_id'] ?? 0);
    
    if ($businessId) {
        $deleteQuery = "DELETE FROM featured_listings WHERE business_id = ?";
        $deleteStmt = $GLOBALS['conn']->prepare($deleteQuery);
        $deleteStmt->bind_param('i', $businessId);
        $deleteStmt->execute();
        
        $updateBizQuery = "UPDATE extracted_businesses 
                          SET is_featured = 0, boost_expiry = NULL 
                          WHERE id = ?";
        $updateBizStmt = $GLOBALS['conn']->prepare($updateBizQuery);
        $updateBizStmt->bind_param('i', $businessId);
        $updateBizStmt->execute();
        
        $message = '✓ Featured listing removed';
        $messageType = 'success';
    }
}

// Get active featured listings
$query = "SELECT fl.id, fl.business_id, fl.plan_type, fl.expires_at,
                 b.name, b.rating, b.types, b.formatted_address
          FROM featured_listings fl
          JOIN extracted_businesses b ON fl.business_id = b.id
          WHERE fl.expires_at > NOW()
          ORDER BY fl.expires_at DESC";

$result = $GLOBALS['conn']->query($query);
$featuredListings = [];

while ($row = $result->fetch_assoc()) {
    $featuredListings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Listings - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .admin-header {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .admin-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        .admin-header p {
            margin: 0;
            opacity: 0.9;
        }
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .control-panel {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .control-panel h2 {
            color: #0B1C3D;
            margin-top: 0;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        .form-group input,
        .form-group select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #FF6A00;
            color: white;
            align-self: flex-end;
        }
        .btn-primary:hover {
            background: #E55A00;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            font-size: 12px;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .featured-list {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .featured-list h2 {
            background: #f5f5f5;
            padding: 20px;
            margin: 0;
            color: #0B1C3D;
            font-size: 20px;
        }
        .listing-item {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .listing-item:last-child {
            border-bottom: none;
        }
        .listing-info {
            flex: 1;
        }
        .listing-name {
            font-weight: 700;
            color: #0B1C3D;
            margin-bottom: 5px;
            font-size: 16px;
        }
        .listing-details {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .listing-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            margin-right: 10px;
        }
        .badge-featured {
            background: linear-gradient(135deg, #FF6A00, #FFB84D);
            color: white;
        }
        .badge-boosted {
            background: linear-gradient(135deg, #00D4FF, #0099CC);
            color: white;
        }
        .listing-expiry {
            color: #FF6A00;
            font-weight: 600;
            margin-top: 8px;
        }
        .listing-actions {
            display: flex;
            gap: 10px;
        }
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #666;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .listing-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .listing-actions {
                margin-top: 15px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php require_once dirname(__DIR__) . '/includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>⭐ Featured Listings Management</h1>
            <p>Manage featured and boosted business listings</p>
        </div>
        
        <?php if ($message): ?>
        <div class="message <?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="control-panel">
            <h2>➕ Feature a Business</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="businessSearch">Search Business</label>
                        <input type="text" id="businessSearch" placeholder="Enter business name..." onkeyup="searchBusinesses()">
                        <div id="searchResults" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-width: 100%; max-height: 200px; overflow-y: auto; z-index: 10; margin-top: 5px; width: 200px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="planType">Plan Type</label>
                        <select id="planType" name="plan_type" required>
                            <option value="featured">Featured (★ Sponsored)</option>
                            <option value="boosted">Boosted (⚡ Fast Track)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration (Days)</label>
                        <select id="duration" name="duration">
                            <option value="7">7 Days</option>
                            <option value="30" selected>30 Days</option>
                            <option value="60">60 Days</option>
                            <option value="90">90 Days</option>
                        </select>
                    </div>
                    
                    <input type="hidden" id="businessId" name="business_id" required>
                    <input type="hidden" name="action" value="feature">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Feature Listing</button>
                </div>
            </form>
        </div>
        
        <div class="featured-list">
            <h2>Active Featured Listings (<?php echo count($featuredListings); ?>)</h2>
            
            <?php if (empty($featuredListings)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">⭐</div>
                <p>No active featured listings</p>
                <p style="font-size: 13px;">Featured listings will appear here once added</p>
            </div>
            <?php else: ?>
                <?php foreach ($featuredListings as $listing): ?>
                <div class="listing-item">
                    <div class="listing-info">
                        <div class="listing-name"><?php echo htmlspecialchars($listing['name']); ?></div>
                        <div class="listing-details">
                            <span class="listing-badge badge-<?php echo $listing['plan_type']; ?>">
                                <?php echo $listing['plan_type'] === 'boosted' ? '⚡ Boosted' : '★ Sponsored'; ?>
                            </span>
                            ⭐ <?php echo $listing['rating']; ?> | <?php echo htmlspecialchars($listing['types']); ?>
                        </div>
                        <div class="listing-details"><?php echo htmlspecialchars($listing['formatted_address']); ?></div>
                        <div class="listing-expiry">Expires: <?php echo date('M d, Y', strtotime($listing['expires_at'])); ?></div>
                    </div>
                    <div class="listing-actions">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="unfeature">
                            <input type="hidden" name="business_id" value="<?php echo $listing['business_id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Remove this featured listing?')">Remove</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let businesses = [];
        
        async function searchBusinesses() {
            const query = document.getElementById('businessSearch').value;
            if (!query || query.length < 2) {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }
            
            try {
                const response = await fetch(`/api/search-businesses.php?q=${encodeURIComponent(query)}&limit=10`);
                const data = await response.json();
                
                if (data.success && data.businesses) {
                    const html = data.businesses.map(b => 
                        `<div style="padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0;" onclick="selectBusiness(${b.id}, '${b.name.replace(/'/g, "\\'")}')">
                            <strong>${b.name}</strong><br>
                            <small style="color: #666;">⭐ ${b.rating} | ${b.formatted_address}</small>
                        </div>`
                    ).join('');
                    
                    document.getElementById('searchResults').innerHTML = html;
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }
        
        function selectBusiness(id, name) {
            document.getElementById('businessId').value = id;
            document.getElementById('businessSearch').value = name;
            document.getElementById('searchResults').innerHTML = '';
            document.getElementById('submitBtn').disabled = false;
        }
    </script>
</body>
</html>
