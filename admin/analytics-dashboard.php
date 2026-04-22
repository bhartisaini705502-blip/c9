<?php
/**
 * Enhanced Analytics Dashboard
 * Comprehensive tracking and insights for admin
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: /');
    exit;
}

// Core metrics
$total_businesses = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM extracted_businesses")->fetch_assoc()['count'];
$today_added = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM extracted_businesses WHERE DATE(imported_at) = CURDATE()")->fetch_assoc()['count'];
$total_imports = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM import_logs")->fetch_assoc()['count'];
$total_searches = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM search_logs")->fetch_assoc()['count'];

// Search behavior
$avg_results_per_search = $GLOBALS['conn']->query("SELECT AVG(results_found) as avg FROM search_logs")->fetch_assoc()['avg'];
$zero_result_searches = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM search_logs WHERE results_found = 0")->fetch_assoc()['count'];
$successful_searches = $total_searches - $zero_result_searches;

// High demand queries (no results)
$high_demand = $GLOBALS['conn']->query("
    SELECT search_query, COUNT(*) as count 
    FROM search_logs 
    WHERE results_found = 0 
    GROUP BY search_query 
    ORDER BY count DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Top searches overall
$top_searches = $GLOBALS['conn']->query("
    SELECT search_query, COUNT(*) as searches, AVG(results_found) as avg_results
    FROM search_logs 
    GROUP BY search_query 
    ORDER BY searches DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Import analytics
$import_stats = $GLOBALS['conn']->query("
    SELECT 
        COUNT(*) as total_imports,
        SUM(records_fetched) as total_records,
        AVG(records_fetched) as avg_per_import,
        MAX(records_fetched) as max_per_import
    FROM import_logs
")->fetch_assoc();

// Featured listings analytics
$featured_count = $GLOBALS['conn']->query("
    SELECT COUNT(*) as count FROM featured_listings 
    WHERE expires_at > NOW()
")->fetch_assoc()['count'];

// Growth data (30 days)
$growth_data = $GLOBALS['conn']->query("
    SELECT DATE(imported_at) as date, COUNT(*) as count 
    FROM extracted_businesses 
    WHERE imported_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(imported_at)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);

// Search volume trend (7 days)
$search_trend = $GLOBALS['conn']->query("
    SELECT DATE(created_at) as date, COUNT(*) as searches, SUM(CASE WHEN results_found = 0 THEN 1 ELSE 0 END) as zero_results
    FROM search_logs
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);

// Top categories
$categories = $GLOBALS['conn']->query("
    SELECT types as category, COUNT(*) as count 
    FROM extracted_businesses 
    GROUP BY types 
    ORDER BY count DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Top cities
$cities = $GLOBALS['conn']->query("
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
    <title>Analytics Dashboard - ConnectWith9</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .page-header {
            margin-bottom: 40px;
        }
        .page-header h1 {
            color: #0B1C3D;
            font-size: 36px;
            margin: 0 0 5px 0;
        }
        .page-header p {
            color: #666;
            margin: 0;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .metric-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .metric-card.highlight {
            background: linear-gradient(135deg, rgba(255,106,0,0.05) 0%, white 100%);
            border-color: #FF6A00;
        }
        .metric-value {
            font-size: 36px;
            font-weight: 700;
            color: #FF6A00;
            margin-bottom: 8px;
        }
        .metric-label {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .metric-detail {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .charts-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .section-title {
            color: #0B1C3D;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        .chart-box {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
        }
        .chart-label {
            color: #0B1C3D;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 15px;
        }
        .chart-canvas {
            height: 300px;
            position: relative;
        }
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .insight-box {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
        }
        .insight-title {
            color: #0B1C3D;
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .insight-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .insight-item:last-child {
            border-bottom: none;
        }
        .insight-label {
            color: #333;
            font-size: 13px;
            flex: 1;
        }
        .insight-value {
            background: #f5f5f5;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            color: #FF6A00;
            font-size: 13px;
        }
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .alert-title {
            color: #856404;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .alert-item {
            color: #856404;
            font-size: 13px;
            padding: 8px 0;
        }
        @media (max-width: 768px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
            .metric-card {
                padding: 20px;
            }
            .metric-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <?php require_once dirname(__DIR__) . '/includes/header.php'; ?>
    
    <div class="analytics-container">
        <div class="page-header">
            <h1>📊 Advanced Analytics Dashboard</h1>
            <p>Real-time insights into database growth, search behavior, and platform performance</p>
        </div>
        
        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?php echo number_format($total_businesses); ?></div>
                <div class="metric-label">Total Listings</div>
                <div class="metric-detail">+<?php echo $today_added; ?> today</div>
            </div>
            <div class="metric-card highlight">
                <div class="metric-value"><?php echo number_format($total_searches); ?></div>
                <div class="metric-label">Total Searches</div>
                <div class="metric-detail"><?php echo round($successful_searches/$total_searches*100, 1); ?>% successful</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo round($avg_results_per_search, 1); ?></div>
                <div class="metric-label">Avg Results/Search</div>
                <div class="metric-detail"><?php echo $zero_result_searches; ?> zero-result searches</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $featured_count; ?></div>
                <div class="metric-label">Featured Listings</div>
                <div class="metric-detail">Active paid promotions</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo number_format($import_stats['total_imports']); ?></div>
                <div class="metric-label">API Imports</div>
                <div class="metric-detail"><?php echo number_format($import_stats['total_records']); ?> records</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo round($import_stats['avg_per_import'], 0); ?></div>
                <div class="metric-label">Records/Import</div>
                <div class="metric-detail">Max: <?php echo number_format($import_stats['max_per_import']); ?></div>
            </div>
        </div>
        
        <!-- High Demand Opportunities -->
        <?php if (!empty($high_demand)): ?>
        <div class="alert-box">
            <div class="alert-title">⚠️ High Demand - Unmet Searches</div>
            <p style="margin: 0 0 10px 0; color: #856404; font-size: 13px;">These searches returned no results - great opportunities to add new businesses</p>
            <?php foreach ($high_demand as $query): ?>
            <div class="alert-item">
                • <strong>"<?php echo htmlspecialchars($query['search_query']); ?>"</strong> - <?php echo $query['count']; ?> searches
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Growth & Search Trends -->
        <div class="charts-section">
            <div class="section-title">📈 Trends & Growth</div>
            
            <div class="charts-row">
                <div class="chart-box">
                    <div class="chart-label">Database Growth (30 Days)</div>
                    <div class="chart-canvas">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-box">
                    <div class="chart-label">Search Volume Trend (7 Days)</div>
                    <div class="chart-canvas">
                        <canvas id="searchTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Insights Grid -->
        <div class="insights-grid">
            <!-- Top Searches -->
            <div class="insight-box">
                <div class="insight-title">🔝 Top Search Queries</div>
                <?php foreach (array_slice($top_searches, 0, 5) as $search): ?>
                <div class="insight-item">
                    <div class="insight-label"><?php echo htmlspecialchars($search['search_query']); ?></div>
                    <div class="insight-value"><?php echo $search['searches']; ?>x</div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Top Categories -->
            <div class="insight-box">
                <div class="insight-title">📂 Top Categories</div>
                <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                <div class="insight-item">
                    <div class="insight-label"><?php echo htmlspecialchars($cat['category']); ?></div>
                    <div class="insight-value"><?php echo number_format($cat['count']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Top Cities -->
            <div class="insight-box">
                <div class="insight-title">📍 Top Cities</div>
                <?php foreach (array_slice($cities, 0, 5) as $city): ?>
                <div class="insight-item">
                    <div class="insight-label"><?php echo htmlspecialchars($city['city']); ?></div>
                    <div class="insight-value"><?php echo number_format($city['count']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Search Quality Metrics -->
        <div class="insights-grid">
            <div class="insight-box">
                <div class="insight-title">📊 Search Quality Metrics</div>
                <div class="insight-item">
                    <div class="insight-label">Total Searches</div>
                    <div class="insight-value"><?php echo number_format($total_searches); ?></div>
                </div>
                <div class="insight-item">
                    <div class="insight-label">Successful Searches</div>
                    <div class="insight-value"><?php echo number_format($successful_searches); ?></div>
                </div>
                <div class="insight-item">
                    <div class="insight-label">Zero Result Searches</div>
                    <div class="insight-value"><?php echo number_format($zero_result_searches); ?></div>
                </div>
                <div class="insight-item">
                    <div class="insight-label">Success Rate</div>
                    <div class="insight-value"><?php echo round($successful_searches/$total_searches*100, 1); ?>%</div>
                </div>
                <div class="insight-item">
                    <div class="insight-label">Avg Results</div>
                    <div class="insight-value"><?php echo round($avg_results_per_search, 1); ?></div>
                </div>
            </div>
            
            <div class="insight-box">
                <div class="insight-title">🔄 Import Performance</div>
                <div class="insight-item">
                    <div class="insight-label">Total Imports</div>
                    <div class="insight-value"><?php echo number_format($import_stats['total_imports']); ?></div>
                </div>
                <div class="insight-item">
                    <div class="insight-label">Total Records</div>
                    <div class="insight-value"><?php echo number_format($import_stats['total_records']); ?></div>
                </div>
                <div class="insight-item">
                    <div class="insight-label">Avg Per Import</div>
                    <div class="insight-value"><?php echo number_format($import_stats['avg_per_import'], 0); ?></div>
                </div>
                <div class="insight-item">
                    <div class="insight-label">Max Per Import</div>
                    <div class="insight-value"><?php echo number_format($import_stats['max_per_import']); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Growth Chart
        const growthData = <?php echo json_encode($growth_data); ?>;
        const growthLabels = growthData.map(d => new Date(d.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}));
        const growthValues = growthData.map(d => parseInt(d.count));
        
        new Chart(document.getElementById('growthChart'), {
            type: 'line',
            data: {
                labels: growthLabels,
                datasets: [{
                    label: 'Listings Added',
                    data: growthValues,
                    borderColor: '#FF6A00',
                    backgroundColor: 'rgba(255,106,0,0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#FF6A00',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Search Trend Chart
        const searchData = <?php echo json_encode($search_trend); ?>;
        const searchLabels = searchData.map(d => new Date(d.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}));
        const searchValues = searchData.map(d => parseInt(d.searches));
        const zeroValues = searchData.map(d => parseInt(d.zero_results));
        
        new Chart(document.getElementById('searchTrendChart'), {
            type: 'bar',
            data: {
                labels: searchLabels,
                datasets: [
                    {
                        label: 'Total Searches',
                        data: searchValues,
                        backgroundColor: '#FF6A00'
                    },
                    {
                        label: 'Zero Results',
                        data: zeroValues,
                        backgroundColor: '#FF9A44'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
