<?php
/**
 * Telecaller Agent Dashboard
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$agent_id = $_SESSION['user_id'];

// Check if user is telecaller agent
$stmt = $conn->prepare("SELECT * FROM telecaller_agents WHERE user_id = ?");
$stmt->bind_param('i', $agent_id);
$stmt->execute();
$agent = $stmt->get_result()->fetch_assoc();

if (!$agent) {
    die('Access denied. You are not registered as a telecaller agent.');
}

$user = getUserData();

// Get filters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$website_status = isset($_GET['website_status']) ? $_GET['website_status'] : '';
$listing_complete = isset($_GET['listing_complete']) ? $_GET['listing_complete'] : '';
$call_status_filter = isset($_GET['call_status']) ? $_GET['call_status'] : 'all'; // all, called, not_called

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query for available businesses
$where = ["eb.business_status = 'OPERATIONAL'"];
$params = [];
$types = '';

// Get distinct categories for filter
$categories = [];
$result = $conn->query("SELECT DISTINCT types FROM extracted_businesses WHERE business_status = 'OPERATIONAL' AND types IS NOT NULL");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $types_arr = array_map('trim', explode(',', $row['types']));
        foreach ($types_arr as $t) {
            if (!empty($t) && !in_array($t, $categories)) {
                $categories[] = $t;
            }
        }
    }
}
sort($categories);

if (!empty($category)) {
    $where[] = "eb.types LIKE ?";
    $params[] = '%' . $category . '%';
    $types .= 's';
}

if (!empty($website_status) && $website_status !== 'all') {
    $where[] = "(bcs.website_status = ? OR bcs.website_status IS NULL)";
    $params[] = $website_status;
    $types .= 's';
}

if (!empty($listing_complete)) {
    $where[] = "(bcs.listing_complete = ? OR bcs.listing_complete IS NULL)";
    $params[] = $listing_complete;
    $types .= 's';
}

// Filter by call status
if ($call_status_filter === 'called') {
    $where[] = "bcs.last_call_status IS NOT NULL AND bcs.last_call_status != 'not_called'";
} elseif ($call_status_filter === 'not_called') {
    $where[] = "(bcs.last_call_status IS NULL OR bcs.last_call_status = 'not_called')";
}

$whereClause = implode(' AND ', $where);

// Get total count for pagination
$countSql = "
    SELECT COUNT(DISTINCT eb.id) as total
    FROM extracted_businesses eb
    LEFT JOIN business_call_status bcs ON eb.id = bcs.business_id
    LEFT JOIN call_logs cl ON eb.id = cl.business_id AND cl.agent_id = ?
    WHERE $whereClause
";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    array_unshift($params, $agent_id);
    $types = 'i' . $types;
    $paramRefs = [];
    foreach ($params as &$param) {
        $paramRefs[] = &$param;
    }
    $countStmt->bind_param($types, ...$paramRefs);
} else {
    $countStmt->bind_param('i', $agent_id);
}
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$total_records = $countResult['total'];
$total_pages = ceil($total_records / $per_page);

// Reset params for main query
$params = [];
$types = '';

// Rebuild params for main query
if (!empty($category)) {
    $params[] = '%' . $category . '%';
    $types .= 's';
}

if (!empty($website_status) && $website_status !== 'all') {
    $params[] = $website_status;
    $types .= 's';
}

if (!empty($listing_complete)) {
    $params[] = $listing_complete;
    $types .= 's';
}

// Get businesses for this agent to call
$sql = "
    SELECT eb.*, 
           COALESCE(bcs.last_call_status, 'not_called') as last_call_status,
           COALESCE(bcs.website_status, 'not_built') as website_status,
           COALESCE(bcs.listing_complete, 'incomplete') as listing_complete,
           COALESCE(bcs.call_count, 0) as call_count,
           bcs.last_call_date,
           COUNT(cl.id) as agent_calls_on_this
    FROM extracted_businesses eb
    LEFT JOIN business_call_status bcs ON eb.id = bcs.business_id
    LEFT JOIN call_logs cl ON eb.id = cl.business_id AND cl.agent_id = ?
    WHERE $whereClause
    GROUP BY eb.id
    ORDER BY eb.rating DESC, eb.user_ratings_total DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
array_unshift($params, $agent_id);
$types = 'i' . $types . 'ii';

$paramRefs = [];
foreach ($params as &$param) {
    $paramRefs[] = &$param;
}
$stmt->bind_param($types, ...$paramRefs);

$stmt->execute();
$businesses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get today's stats
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN call_status = 'interested' THEN 1 ELSE 0 END) as interested,
        SUM(CASE WHEN call_status = 'call_again' THEN 1 ELSE 0 END) as call_again
    FROM call_logs
    WHERE agent_id = ? AND DATE(created_at) = ?
");
$stmt->bind_param('is', $agent_id, $today);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telecaller Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 500;
            color: #666;
        }

        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
        }

        .filter-btn {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .filter-btn:hover {
            background: #764ba2;
        }

        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            color: #333;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-block;
        }

        .pagination-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .business-list {
            display: grid;
            gap: 15px;
        }

        .business-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .business-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .business-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .business-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .business-meta {
            display: flex;
            gap: 10px;
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge.website-not-built {
            background: #FFC107;
            color: white;
        }

        .badge.website-built {
            background: #4CAF50;
            color: white;
        }

        .badge.listing-incomplete {
            background: #FF6B6B;
            color: white;
        }

        .badge.listing-complete {
            background: #4CAF50;
            color: white;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-not_received { background: #FFC107; color: white; }
        .status-busy { background: #FF9800; color: white; }
        .status-not_interested { background: #F44336; color: white; }
        .status-irritated { background: #E91E63; color: white; }
        .status-interested { background: #4CAF50; color: white; }
        .status-call_again { background: #2196F3; color: white; }

        .call-action {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            align-items: center;
        }

        .call-btn {
            flex: 1;
            padding: 10px 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
        }

        .call-btn:hover {
            background: #764ba2;
        }

        .call-now-btn {
            padding: 12px 30px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.2s;
            width: 100%;
        }

        .call-now-btn:hover:not(:disabled) {
            background: #45a049;
            transform: scale(1.02);
        }

        .call-now-btn:disabled {
            background: #999;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .call-log {
            font-size: 12px;
            color: #999;
        }

        .view-toggle-btn {
            padding: 8px 16px;
            background: #764ba2;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .view-toggle-btn:hover {
            background: #667eea;
        }

        .business-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: none;
        }

        .business-table.active {
            display: table;
        }

        .business-table thead {
            background: #667eea;
            color: white;
        }

        .business-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        .business-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .business-table tbody tr:hover {
            background: #f9f9f9;
        }

        .business-table td.business-name {
            font-weight: 600;
            color: #333;
        }

        .business-table td.phone-no {
            color: #667eea;
            font-weight: 500;
        }

        .business-table td.address {
            color: #666;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .map-btn {
            padding: 6px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 12px;
        }

        .map-btn:hover {
            background: #45a049;
        }

        .call-table-btn {
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 12px;
        }

        .call-table-btn:hover {
            background: #764ba2;
        }

        .call-status-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .call-status-tab {
            padding: 10px 20px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 13px;
        }

        .call-status-tab:hover {
            border-color: #667eea;
            background: #f0f0f0;
        }

        .call-status-tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .call-history-item {
            padding: 10px;
            background: white;
            border-left: 3px solid #667eea;
            margin-bottom: 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .call-history-status {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }

        .call-history-time {
            color: #999;
            font-size: 11px;
        }

        .call-history-message {
            color: #666;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid #eee;
            font-style: italic;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            padding: 25px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .modal-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .status-options {
            display: grid;
            gap: 10px;
            margin-bottom: 20px;
        }

        .status-btn {
            padding: 12px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s;
        }

        .status-btn:hover {
            border-color: #667eea;
            background: #f0f0f0;
        }

        .status-btn.selected {
            border-color: #667eea;
            background: #f0f0f0;
            font-weight: bold;
        }

        .textarea-group {
            margin-bottom: 15px;
        }

        .textarea-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 13px;
        }

        .datetime-group {
            margin-bottom: 15px;
            display: none;
        }

        .datetime-group.active {
            display: block;
        }

        .datetime-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-footer button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .cancel-btn {
            background: #eee;
            color: #333;
        }

        .submit-btn {
            background: #667eea;
            color: white;
        }

        .submit-btn:hover {
            background: #764ba2;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .quick-access-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            border-left: 4px solid #667eea;
        }

        .quick-access-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .quick-access-card.all-listings {
            border-left-color: #667eea;
        }

        .quick-access-card.not-called {
            border-left-color: #FFC107;
        }

        .quick-access-card.called {
            border-left-color: #4CAF50;
        }

        .quick-access-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .quick-access-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }

        .quick-access-desc {
            font-size: 12px;
            color: #999;
        }

        .quick-access-count {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            margin-top: 10px;
        }

        .agent-status-toggle {
            display: flex;
            gap: 10px;
        }

        .toggle-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .toggle-btn.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }

            .business-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php require '../includes/header.php'; ?>

    <div style="max-width: 1200px; margin: 20px auto; padding: 0 15px;">
        <!-- Telecaller Dashboard Menu -->
        <nav style="background: white; padding: 15px 25px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; gap: 30px; align-items: center;">
                <li><a href="/pages/telecaller-dashboard.php" style="color: #667eea; text-decoration: none; font-weight: 600; font-size: 15px;">📊 Dashboard</a></li>
                <li><a href="/pages/telecaller-dashboard.php?call_status=not_called" style="color: #666; text-decoration: none; font-weight: 500; font-size: 14px;">📭 Not Called</a></li>
                <li><a href="/pages/telecaller-dashboard.php?call_status=called" style="color: #666; text-decoration: none; font-weight: 500; font-size: 14px;">✅ Called</a></li>
                <li style="margin-left: auto;"><a href="/auth/logout.php" style="color: #FF6B6B; text-decoration: none; font-weight: 500; font-size: 14px;">🚪 Logout</a></li>
            </ul>
        </nav>

        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <h2 style="margin: 0; font-size: 24px;">📞 Telecaller Dashboard</h2>
                    <p style="margin: 5px 0 0; opacity: 0.9; font-size: 14px;">Welcome, <?php echo esc($user['full_name'] ?? 'Telecaller'); ?></p>
                </div>
                <div class="agent-status-toggle">
                    <button class="toggle-btn <?php echo $agent['status'] == 'online' ? 'active' : ''; ?>" onclick="setAgentStatus('online')">
                        🟢 Online
                    </button>
                    <button class="toggle-btn <?php echo $agent['status'] == 'offline' ? 'active' : ''; ?>" onclick="setAgentStatus('offline')">
                        ⭕ Offline
                    </button>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_calls'] ?? 0; ?></div>
                <div class="stat-label">Calls Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #4CAF50;"><?php echo $stats['interested'] ?? 0; ?></div>
                <div class="stat-label">Interested</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #2196F3;"><?php echo $stats['call_again'] ?? 0; ?></div>
                <div class="stat-label">Callback</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($businesses); ?></div>
                <div class="stat-label">Available</div>
            </div>
        </div>

        <!-- Quick Access Cards -->
        <div class="quick-access-grid">
            <a href="/pages/telecaller-dashboard.php?call_status=all" class="quick-access-card all-listings">
                <div class="quick-access-icon">📊</div>
                <div class="quick-access-title">All Listings</div>
                <div class="quick-access-desc">View all available businesses</div>
                <div class="quick-access-count"><?php echo count($businesses); ?></div>
            </a>
            <a href="/pages/telecaller-dashboard.php?call_status=not_called" class="quick-access-card not-called">
                <div class="quick-access-icon">📭</div>
                <div class="quick-access-title">Not Called</div>
                <div class="quick-access-desc">Fresh prospects to reach out</div>
                <div class="quick-access-count">
                    <?php 
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM extracted_businesses eb LEFT JOIN business_call_status bcs ON eb.id = bcs.business_id WHERE eb.business_status = 'OPERATIONAL' AND (bcs.last_call_status IS NULL OR bcs.last_call_status = 'not_called')");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $count_not_called = $result->fetch_assoc()['count'];
                        echo $count_not_called;
                    ?>
                </div>
            </a>
            <a href="/pages/telecaller-dashboard.php?call_status=called" class="quick-access-card called">
                <div class="quick-access-icon">✅</div>
                <div class="quick-access-title">Already Called</div>
                <div class="quick-access-desc">Follow-ups and conversions</div>
                <div class="quick-access-count">
                    <?php 
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM extracted_businesses eb LEFT JOIN business_call_status bcs ON eb.id = bcs.business_id WHERE eb.business_status = 'OPERATIONAL' AND bcs.last_call_status IS NOT NULL AND bcs.last_call_status != 'not_called'");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $count_called = $result->fetch_assoc()['count'];
                        echo $count_called;
                    ?>
                </div>
            </a>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label>Category</label>
                <select id="categoryFilter" onchange="applyFilters()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                            <?php echo esc($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Website Status</label>
                <select id="websiteFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $website_status == 'all' || $website_status == '' ? 'selected' : ''; ?>>All Websites</option>
                    <option value="not_built" <?php echo $website_status == 'not_built' ? 'selected' : ''; ?>>No Website</option>
                    <option value="under_construction" <?php echo $website_status == 'under_construction' ? 'selected' : ''; ?>>Under Construction</option>
                    <option value="built" <?php echo $website_status == 'built' ? 'selected' : ''; ?>>Website Built</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Listing Status</label>
                <select id="listingFilter" onchange="applyFilters()">
                    <option value="">All</option>
                    <option value="incomplete" <?php echo $listing_complete == 'incomplete' ? 'selected' : ''; ?>>Incomplete</option>
                    <option value="complete" <?php echo $listing_complete == 'complete' ? 'selected' : ''; ?>>Complete</option>
                </select>
            </div>

            <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
            <button class="view-toggle-btn" onclick="toggleView()">📋 Switch to List View</button>
        </div>

        <!-- Call Status Filter Tabs -->
        <div class="call-status-tabs">
            <button class="call-status-tab <?php echo $call_status_filter === 'all' ? 'active' : ''; ?>" onclick="filterByCallStatus('all')">
                📊 All Listings
            </button>
            <button class="call-status-tab <?php echo $call_status_filter === 'not_called' ? 'active' : ''; ?>" onclick="filterByCallStatus('not_called')">
                📭 Not Called
            </button>
            <button class="call-status-tab <?php echo $call_status_filter === 'called' ? 'active' : ''; ?>" onclick="filterByCallStatus('called')">
                ✅ Called
            </button>
        </div>

        <div id="businessList" class="business-list">
            <?php if (empty($businesses)): ?>
                <div class="empty-state">
                    📭 No businesses available with selected filters
                </div>
            <?php else: ?>
                <?php foreach ($businesses as $biz): ?>
                    <div class="business-card">
                        <div class="business-header">
                            <div>
                                <div class="business-name"><?php echo esc($biz['name']); ?></div>
                                <div class="business-meta">
                                    <span>⭐ <?php echo $biz['rating']; ?> (<?php echo $biz['user_ratings_total']; ?> reviews)</span>
                                    <span>📍 <?php echo esc(explode(',', $biz['formatted_address'])[0]); ?></span>
                                </div>
                            </div>
                            <div>
                                <?php if ($biz['last_call_status'] != 'not_called'): ?>
                                    <span class="status-badge status-<?php echo $biz['last_call_status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $biz['last_call_status'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                            <span class="badge badge-website-<?php echo $biz['website_status']; ?>">
                                🌐 <?php echo ucwords(str_replace('_', ' ', $biz['website_status'])); ?>
                            </span>
                            <span class="badge badge-listing-<?php echo $biz['listing_complete']; ?>">
                                📋 <?php echo ucwords($biz['listing_complete']); ?>
                            </span>
                            <?php if ($biz['call_count'] > 0): ?>
                                <span class="badge" style="background: #999; color: white;">
                                    📞 Called <?php echo $biz['call_count']; ?>x
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="business-meta">
                            <?php if ($biz['formatted_phone_number']): ?>
                                <strong>📞 <?php echo esc($biz['formatted_phone_number']); ?></strong>
                            <?php endif; ?>
                            <?php if ($biz['website']): ?>
                                <a href="<?php echo esc($biz['website']); ?>" target="_blank" style="color: #667eea;">🔗 Website</a>
                            <?php endif; ?>
                        </div>

                        <div class="call-action">
                            <button class="call-btn" onclick="openCallModal(<?php echo $biz['id']; ?>, '<?php echo esc(addslashes($biz['name'])); ?>', '<?php echo esc($biz['formatted_phone_number']); ?>')">
                                📞 Call Now
                            </button>
                        </div>

                        <?php if ($biz['last_call_date']): ?>
                            <div class="call-log">
                                Last call: <?php echo date('M d, Y g:i A', strtotime($biz['last_call_date'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin: 30px 0; flex-wrap: wrap;">
                <?php if ($page > 1): ?>
                    <a href="?page=1&category=<?php echo urlencode($category); ?>&website_status=<?php echo urlencode($website_status); ?>&listing_complete=<?php echo urlencode($listing_complete); ?>&call_status=<?php echo urlencode($call_status_filter); ?>" class="pagination-btn">« First</a>
                    <a href="?page=<?php echo $page - 1; ?>&category=<?php echo urlencode($category); ?>&website_status=<?php echo urlencode($website_status); ?>&listing_complete=<?php echo urlencode($listing_complete); ?>&call_status=<?php echo urlencode($call_status_filter); ?>" class="pagination-btn">‹ Previous</a>
                <?php endif; ?>
                
                <div style="display: flex; gap: 5px;">
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($p = $start_page; $p <= $end_page; $p++):
                    ?>
                        <a href="?page=<?php echo $p; ?>&category=<?php echo urlencode($category); ?>&website_status=<?php echo urlencode($website_status); ?>&listing_complete=<?php echo urlencode($listing_complete); ?>&call_status=<?php echo urlencode($call_status_filter); ?>" 
                           class="pagination-btn <?php echo $p == $page ? 'active' : ''; ?>" 
                           style="<?php echo $p == $page ? 'background: #667eea; color: white; font-weight: bold;' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&category=<?php echo urlencode($category); ?>&website_status=<?php echo urlencode($website_status); ?>&listing_complete=<?php echo urlencode($listing_complete); ?>&call_status=<?php echo urlencode($call_status_filter); ?>" class="pagination-btn">Next ›</a>
                    <a href="?page=<?php echo $total_pages; ?>&category=<?php echo urlencode($category); ?>&website_status=<?php echo urlencode($website_status); ?>&listing_complete=<?php echo urlencode($listing_complete); ?>&call_status=<?php echo urlencode($call_status_filter); ?>" class="pagination-btn">Last »</a>
                <?php endif; ?>
                
                <span style="color: #666; font-size: 14px; margin-left: 20px;">Page <?php echo $page; ?> of <?php echo $total_pages; ?> | Total: <?php echo $total_records; ?> businesses</span>
            </div>
        <?php endif; ?>

        <!-- Business List Table View -->
        <table class="business-table" id="businessTable">
            <thead>
                <tr>
                    <th>Listing Name</th>
                    <th>Phone No.</th>
                    <th>Address</th>
                    <th>Website Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($businesses as $biz): ?>
                    <tr>
                        <td class="business-name"><?php echo esc($biz['name']); ?></td>
                        <td class="phone-no"><?php echo esc($biz['formatted_phone_number']); ?></td>
                        <td class="address" title="<?php echo esc($biz['formatted_address']); ?>">
                            <?php echo esc(explode(',', $biz['formatted_address'])[0]); ?>
                        </td>
                        <td>
                            <span class="badge badge-website-<?php echo $biz['website_status']; ?>">
                                🌐 <?php echo ucwords(str_replace('_', ' ', $biz['website_status'])); ?>
                            </span>
                        </td>
                        <td style="display: flex; gap: 8px;">
                            <button class="map-btn" onclick="openMap(<?php echo $biz['lat']; ?>, <?php echo $biz['lng']; ?>, '<?php echo esc(addslashes($biz['name'])); ?>')">
                                🗺️ Map
                            </button>
                            <button class="call-table-btn" onclick="openCallModal(<?php echo $biz['id']; ?>, '<?php echo esc(addslashes($biz['name'])); ?>', '<?php echo esc($biz['formatted_phone_number']); ?>')">
                                📞 Call
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Call Status Modal -->
    <div id="callModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">
                Call - <span id="modalBizName"></span>
                <div id="callPhoneDisplay" style="font-size: 14px; color: #667eea; margin-top: 8px; font-weight: normal;">
                    <span id="modalPhoneNumber"></span>
                </div>
            </div>

            <!-- Call Timer Section -->
            <div id="callTimerSection" style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 15px; display: none;">
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #667eea; margin-bottom: 10px;">
                        <span id="callTimer">00:00</span>
                    </div>
                    <button id="callNowBtn" class="call-now-btn" onclick="initiateCall()">
                        📞 Click to Call
                    </button>
                    <p style="font-size: 12px; color: #999; margin-top: 8px;">Timer starts when you click the call button</p>
                </div>
            </div>

            <!-- Status Options Section -->
            <div id="statusSection" style="display: none;">
                <label style="font-size: 13px; font-weight: 500; display: block; margin-bottom: 10px;">Call Result</label>
                <div class="status-options" id="statusOptions">
                    <button class="status-btn" onclick="selectStatus('not_received')">📵 Not Received</button>
                    <button class="status-btn" onclick="selectStatus('busy')">📱 Busy</button>
                    <button class="status-btn" onclick="selectStatus('not_interested')">👎 Not Interested</button>
                    <button class="status-btn" onclick="selectStatus('irritated')">😠 Irritated/Angry</button>
                    <button class="status-btn" onclick="selectStatus('interested')">👍 Interested</button>
                    <button class="status-btn" onclick="selectStatus('call_again')">📅 Call Again Later</button>
                </div>
            </div>

            <div class="datetime-group" id="datetimeGroup">
                <label style="font-size: 13px; font-weight: 500; display: block; margin-bottom: 8px;">Callback Time</label>
                <input type="datetime-local" id="callbackTime">
            </div>

            <div class="textarea-group">
                <label style="font-size: 13px; font-weight: 500; display: block; margin-bottom: 8px;">💬 Custom Message (Optional)</label>
                <textarea id="callNotes" rows="3" placeholder="Add your custom notes about this call..."></textarea>
                <p style="font-size: 11px; color: #999; margin-top: 5px;">
                    This message will be saved along with the status. Every status update creates a new record with timestamp.
                </p>
            </div>

            <!-- Call History Section -->
            <div id="callHistorySection" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee;">
                <label style="font-size: 13px; font-weight: 500; display: block; margin-bottom: 10px;">📞 Call History</label>
                <div id="callHistoryContainer" style="max-height: 200px; overflow-y: auto; background: #f9f9f9; border-radius: 6px; padding: 10px;">
                    <p style="color: #999; font-size: 12px;">Loading call history...</p>
                </div>
            </div>

            <div class="modal-footer">
                <button class="cancel-btn" onclick="closeCallModal()">Cancel</button>
                <button class="submit-btn" onclick="submitCall()">Save Call Status</button>
            </div>
        </div>
    </div>

    <script>
        let selectedBusinessId = null;
        let selectedStatus = null;
        let callStartTime = null;
        let callTimerInterval = null;
        let phoneNumber = null;
        let isCardView = true;

        function toggleView() {
            isCardView = !isCardView;
            const cardView = document.querySelector('.business-list');
            const tableView = document.getElementById('businessTable');
            const toggleBtn = document.querySelector('.view-toggle-btn');

            if (isCardView) {
                cardView.style.display = 'grid';
                tableView.classList.remove('active');
                toggleBtn.textContent = '📋 Switch to List View';
            } else {
                cardView.style.display = 'none';
                tableView.classList.add('active');
                toggleBtn.textContent = '🃏 Switch to Card View';
            }
        }

        function filterByCallStatus(status) {
            // Update active tab
            document.querySelectorAll('.call-status-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Build URL with current filters
            const params = new URLSearchParams(window.location.search);
            params.set('call_status', status);
            window.location.href = window.location.pathname + '?' + params.toString();
        }

        function openMap(lat, lng, name) {
            if (!lat || !lng) {
                alert('Location data not available');
                return;
            }
            const mapsUrl = `https://www.google.com/maps/?q=${lat},${lng}`;
            window.open(mapsUrl, '_blank');
        }

        function openCallModal(businessId, bizName, phone) {
            selectedBusinessId = businessId;
            selectedStatus = null;
            phoneNumber = phone;
            document.getElementById('modalBizName').textContent = bizName;
            document.getElementById('modalPhoneNumber').textContent = phone || 'No phone number';
            document.getElementById('callNotes').value = '';
            document.getElementById('callbackTime').value = '';
            document.getElementById('datetimeGroup').classList.remove('active');
            document.getElementById('statusOptions').querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('selected'));
            
            // Show call timer section
            document.getElementById('callTimerSection').style.display = 'block';
            document.getElementById('statusSection').style.display = 'none';
            document.getElementById('callNowBtn').style.display = 'block';
            document.getElementById('callNowBtn').textContent = '📞 Click to Call';
            
            // Reset timer
            callStartTime = null;
            clearInterval(callTimerInterval);
            document.getElementById('callTimer').textContent = '00:00';
            
            // Fetch call history
            fetchCallHistory(businessId);
            
            document.getElementById('callModal').classList.add('active');
        }

        function fetchCallHistory(businessId) {
            fetch('/api/telecaller/get-call-history.php?business_id=' + businessId)
                .then(response => response.json())
                .then(data => {
                    const historyContainer = document.getElementById('callHistoryContainer');
                    
                    if (!data.calls || data.calls.length === 0) {
                        historyContainer.innerHTML = '<p style="color: #999; font-size: 12px;">No call history yet</p>';
                        return;
                    }
                    
                    let html = '';
                    data.calls.forEach((call, index) => {
                        const timestamp = new Date(call.created_at).toLocaleString();
                        const status = call.call_status.replace(/_/g, ' ').toUpperCase();
                        const statusColors = {
                            'NOT RECEIVED': '#FFC107',
                            'BUSY': '#FF9800',
                            'NOT INTERESTED': '#F44336',
                            'IRRITATED': '#E91E63',
                            'INTERESTED': '#4CAF50',
                            'CALL AGAIN': '#2196F3'
                        };
                        const color = statusColors[status] || '#667eea';
                        
                        html += `<div class="call-history-item">
                            <div class="call-history-status" style="color: ${color};">● ${status}</div>
                            <div class="call-history-time">📅 ${timestamp}</div>
                            ${call.notes ? '<div class="call-history-message">"' + escapeHtml(call.notes) + '"</div>' : ''}
                        </div>`;
                    });
                    
                    historyContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('callHistoryContainer').innerHTML = '<p style="color: #999; font-size: 12px;">Error loading call history</p>';
                });
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function initiateCall() {
            if (!phoneNumber) {
                alert('No phone number available');
                return;
            }

            // Start the timer
            if (!callStartTime) {
                callStartTime = Date.now();
                startCallTimer();
                document.getElementById('callNowBtn').textContent = '☎️ Call in Progress...';
                document.getElementById('callNowBtn').disabled = true;
                
                // Open dialer in new tab/window (won't actually call without carrier support)
                setTimeout(() => {
                    window.open('tel:' + phoneNumber.replace(/\D/g, ''));
                }, 100);
                
                // Allow them to return and record after 5 seconds
                setTimeout(() => {
                    document.getElementById('callTimerSection').style.display = 'none';
                    document.getElementById('statusSection').style.display = 'block';
                }, 2000);
            }
        }

        function startCallTimer() {
            callTimerInterval = setInterval(() => {
                const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
                const minutes = Math.floor(elapsed / 60);
                const seconds = elapsed % 60;
                document.getElementById('callTimer').textContent = 
                    String(minutes).padStart(2, '0') + ':' + 
                    String(seconds).padStart(2, '0');
            }, 1000);
        }

        function closeCallModal() {
            document.getElementById('callModal').classList.remove('active');
            // Clear timer when modal closes
            clearInterval(callTimerInterval);
            callStartTime = null;
        }

        function selectStatus(status) {
            selectedStatus = status;
            document.getElementById('statusOptions').querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('selected'));
            event.target.classList.add('selected');
            
            // Show datetime picker for call_again
            const datetimeGroup = document.getElementById('datetimeGroup');
            if (status === 'call_again') {
                datetimeGroup.classList.add('active');
            } else {
                datetimeGroup.classList.remove('active');
            }
        }

        async function submitCall() {
            if (!selectedStatus) {
                alert('Please select a call status');
                return;
            }

            const formData = new FormData();
            formData.append('business_id', selectedBusinessId);
            formData.append('status', selectedStatus);
            formData.append('notes', document.getElementById('callNotes').value);

            if (selectedStatus === 'call_again') {
                const callbackTime = document.getElementById('callbackTime').value;
                if (!callbackTime) {
                    alert('Please set a callback time');
                    return;
                }
                formData.append('callback_time', callbackTime);
            }

            try {
                const response = await fetch('/api/telecaller/record-call.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    closeCallModal();
                    alert('Call recorded successfully!');
                    location.reload();
                } else {
                    alert('Failed to record call');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error recording call');
            }
        }

        function applyFilters() {
            const category = document.getElementById('categoryFilter').value;
            const website = document.getElementById('websiteFilter').value;
            const listing = document.getElementById('listingFilter').value;

            let url = '?';
            if (category) url += 'category=' + encodeURIComponent(category) + '&';
            if (website) url += 'website_status=' + encodeURIComponent(website) + '&';
            if (listing) url += 'listing_complete=' + encodeURIComponent(listing);

            location.href = url;
        }

        function setAgentStatus(status) {
            fetch('/api/telecaller/set-agent-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: status })
            }).then(r => r.json()).then(data => {
                location.reload();
            });
        }

        // Close modal when clicking outside
        document.getElementById('callModal').addEventListener('click', function(e) {
            if (e.target === this) closeCallModal();
        });
    </script>
</body>
</html>
