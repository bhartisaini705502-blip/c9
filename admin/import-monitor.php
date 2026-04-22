<?php
/**
 * Admin: Import Monitor & Statistics
 * Track and manage Google API imports
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Access Denied');
}

// Get import stats
$total_imports = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM import_logs")->fetch_assoc()['count'];
$today_imports = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM import_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];

// Get source breakdown
$sources = $GLOBALS['conn']->query("SELECT source, COUNT(*) as count FROM import_logs GROUP BY source")->fetch_all(MYSQLI_ASSOC);

// Get top imported categories
$top_categories = $GLOBALS['conn']->query("
    SELECT category, COUNT(*) as import_count, SUM(records_fetched) as total_records
    FROM import_logs
    GROUP BY category
    ORDER BY import_count DESC
    LIMIT 15
")->fetch_all(MYSQLI_ASSOC);

// Get recent imports
$recent_imports = $GLOBALS['conn']->query("
    SELECT search_query, category, records_fetched, source, created_at
    FROM import_logs
    ORDER BY created_at DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Monitor - ConnectWith9 Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .monitor-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .monitor-header h1 {
            color: #0B1C3D;
            margin-bottom: 10px;
        }
        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #FF6A00;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .table-container {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .table-title {
            padding: 20px;
            background: #f5f5f5;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
            color: #0B1C3D;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        th {
            background: #f9f9f9;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div class="monitor-container">
        <div class="monitor-header">
            <h1>📥 Import Monitor</h1>
            <p style="color: #666;">Track all Google API imports and data sources</p>
        </div>

        <!-- Stats -->
        <div class="stat-row">
            <div class="stat-box">
                <div class="stat-value"><?php echo number_format($total_imports); ?></div>
                <div class="stat-label">Total Imports</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $today_imports; ?></div>
                <div class="stat-label">Today's Imports</div>
            </div>
        </div>

        <!-- Source Distribution -->
        <div class="table-container">
            <div class="table-title">📊 Import Sources</div>
            <table>
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sources as $source): ?>
                    <tr>
                        <td><?php echo ucfirst($source['source']); ?></td>
                        <td><?php echo number_format($source['count']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Categories -->
        <div class="table-container">
            <div class="table-title">📂 Top Imported Categories</div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Imports</th>
                        <th>Total Records</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_categories as $cat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['category']); ?></td>
                        <td><?php echo $cat['import_count']; ?></td>
                        <td><?php echo number_format($cat['total_records']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Imports -->
        <div class="table-container">
            <div class="table-title">⏱️ Recent Imports (Last 20)</div>
            <table>
                <thead>
                    <tr>
                        <th>Query</th>
                        <th>Category</th>
                        <th>Records</th>
                        <th>Source</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_imports as $import): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($import['search_query']); ?></td>
                        <td><?php echo htmlspecialchars($import['category']); ?></td>
                        <td><?php echo number_format($import['records_fetched']); ?></td>
                        <td><?php echo ucfirst($import['source']); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($import['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
