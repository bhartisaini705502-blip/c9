<?php
/**
 * Admin: Data Insights Dashboard
 * Tracks database growth, user behavior, and search demand
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Access Denied');
}

// Get summary stats
$stats = [];
$stats['total_businesses'] = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM extracted_businesses")->fetch_assoc()['count'];
$stats['today_added'] = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM extracted_businesses WHERE DATE(imported_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$stats['total_imports'] = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM import_logs")->fetch_assoc()['count'];
$stats['verified_listings'] = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM extracted_businesses WHERE source = 'claimed'")->fetch_assoc()['count'];
$stats['claimed_listings'] = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM listing_claims WHERE status = 'approved'")->fetch_assoc()['count'];

// Get search logs
$search_logs = $GLOBALS['conn']->query("SELECT search_query, COUNT(*) as count FROM search_logs GROUP BY search_query ORDER BY count DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Get zero result searches
$zero_results = $GLOBALS['conn']->query("SELECT search_query, COUNT(*) as count FROM search_logs WHERE results_found = 0 GROUP BY search_query ORDER BY count DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Get source distribution
$sources = $GLOBALS['conn']->query("SELECT source, COUNT(*) as count FROM extracted_businesses GROUP BY source")->fetch_all(MYSQLI_ASSOC);

// Get growth data (last 30 days)
$growth_data = $GLOBALS['conn']->query("
    SELECT DATE(imported_at) as date, COUNT(*) as count 
    FROM extracted_businesses 
    WHERE imported_at IS NOT NULL AND imported_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(imported_at)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);

// Get category growth
$category_growth = $GLOBALS['conn']->query("
    SELECT types as category, COUNT(*) as count 
    FROM extracted_businesses 
    GROUP BY types 
    ORDER BY count DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get location growth (top cities)
$location_growth = $GLOBALS['conn']->query("
    SELECT vicinity as city, COUNT(*) as count 
    FROM extracted_businesses 
    WHERE vicinity IS NOT NULL AND vicinity != ''
    GROUP BY vicinity 
    ORDER BY count DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Insights Dashboard - ConnectWith9</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .dashboard-header {
            margin-bottom: 30px;
        }
        .dashboard-header h1 {
            color: #0B1C3D;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #FF6A00;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        .chart-container {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .chart-title {
            color: #0B1C3D;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 16px;
        }
        .chart-small {
            height: 300px;
        }
        .list-container {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .list-item:last-child {
            border-bottom: none;
        }
        .list-label {
            color: #333;
            font-weight: 500;
        }
        .list-value {
            background: #f5f5f5;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: 600;
            color: #FF6A00;
        }
        .highlight-red {
            background: #ffe8e8;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #d32f2f;
        }
        .highlight-red .chart-title {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>📊 Data Insights Dashboard</h1>
            <p style="color: #666;">Track database growth, user behavior, and search demand</p>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_businesses']); ?></div>
                <div class="stat-label">Total Businesses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['today_added']; ?></div>
                <div class="stat-label">Added Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_imports']); ?></div>
                <div class="stat-label">Total Imports</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['claimed_listings']; ?></div>
                <div class="stat-label">Verified Listings</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <!-- Growth Chart -->
            <div class="chart-container">
                <div class="chart-title">📈 Database Growth (30 Days)</div>
                <canvas id="growthChart" class="chart-small"></canvas>
            </div>

            <!-- Source Distribution -->
            <div class="chart-container">
                <div class="chart-title">📦 Source Distribution</div>
                <canvas id="sourceChart" class="chart-small"></canvas>
            </div>
        </div>

        <!-- Top Search Queries -->
        <div class="list-container">
            <div class="chart-title">🔍 Top Search Queries</div>
            <?php foreach (array_slice($search_logs, 0, 10) as $log): ?>
            <div class="list-item">
                <div class="list-label"><?php echo htmlspecialchars($log['search_query']); ?></div>
                <div class="list-value"><?php echo $log['count']; ?> searches</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Zero Result Searches (High Demand) -->
        <?php if (!empty($zero_results)): ?>
        <div class="highlight-red">
            <div class="chart-title">⚠️ High Demand - No Data</div>
            <p style="color: #666; margin-bottom: 15px;">These searches have 0 results - opportunity to add more businesses</p>
            <?php foreach (array_slice($zero_results, 0, 10) as $search): ?>
            <div class="list-item">
                <div class="list-label"><?php echo htmlspecialchars($search['search_query']); ?></div>
                <div class="list-value"><?php echo $search['count']; ?> searches</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Category Growth -->
        <div class="list-container">
            <div class="chart-title">📂 Top Categories</div>
            <?php foreach (array_slice($category_growth, 0, 10) as $cat): ?>
            <div class="list-item">
                <div class="list-label"><?php echo htmlspecialchars($cat['category']); ?></div>
                <div class="list-value"><?php echo number_format($cat['count']); ?> listings</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Location Growth -->
        <div class="list-container">
            <div class="chart-title">📍 Top Cities</div>
            <?php foreach (array_slice($location_growth, 0, 10) as $loc): ?>
            <div class="list-item">
                <div class="list-label"><?php echo htmlspecialchars($loc['city']); ?></div>
                <div class="list-value"><?php echo number_format($loc['count']); ?> listings</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Growth Chart
        const growthData = <?php echo json_encode($growth_data); ?>;
        const ctx1 = document.getElementById('growthChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: growthData.map(d => d.date),
                datasets: [{
                    label: 'Listings Added',
                    data: growthData.map(d => d.count),
                    borderColor: '#FF6A00',
                    backgroundColor: 'rgba(255,106,0,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });

        // Source Distribution
        const sourceData = <?php echo json_encode($sources); ?>;
        const ctx2 = document.getElementById('sourceChart').getContext('2d');
        new Chart(ctx2, {
            type: 'pie',
            data: {
                labels: sourceData.map(s => s.source),
                datasets: [{
                    data: sourceData.map(s => s.count),
                    backgroundColor: ['#FF6A00', '#1E3A8A', '#0B1C3D']
                }]
            },
            options: { responsive: true }
        });
    </script>
</body>
</html>
