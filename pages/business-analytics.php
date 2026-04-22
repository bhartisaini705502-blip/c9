<?php
/**
 * Business Analytics Page
 * Shows analytics for a specific business
 */

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/db.php';

$business_id = $_GET['id'] ?? null;

if (!$business_id) {
    redirect('/');
}

// Get business
$stmt = $conn->prepare("SELECT * FROM extracted_businesses WHERE id = ?");
$stmt->bind_param('i', $business_id);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();

if (!$business) {
    redirect('/');
}

// Get analytics
$stmt = $conn->prepare("SELECT * FROM business_analytics WHERE business_id = ?");
$stmt->bind_param('i', $business_id);
$stmt->execute();
$analytics = $stmt->get_result()->fetch_assoc();

// Get daily analytics (last 30 days)
$stmt = $conn->prepare("
    SELECT * FROM daily_analytics 
    WHERE business_id = ? 
    ORDER BY date DESC 
    LIMIT 30
");
$stmt->bind_param('i', $business_id);
$stmt->execute();
$daily = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get inquiries
$stmt = $conn->prepare("
    SELECT * FROM inquiries 
    WHERE business_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->bind_param('i', $business_id);
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $business['name']; ?> - Analytics</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .business-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e0e0e0;
        }
        .business-title {
            color: #0B1C3D;
            font-size: 24px;
            font-weight: 600;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .chart-container {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .chart-container h3 {
            color: #0B1C3D;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .inquiries-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
        }
        .inquiry-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .inquiry-item:last-child {
            border-bottom: none;
        }
        .inquiry-name {
            font-weight: 600;
            color: #0B1C3D;
        }
        .inquiry-meta {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="analytics-container">
        <div class="business-header">
            <div>
                <div class="business-title"><?php echo htmlspecialchars($business['name']); ?></div>
                <small style="color: #666;"><?php echo htmlspecialchars($business['address']); ?></small>
            </div>
            <a href="/pages/business-detail.php?id=<?php echo $business_id; ?>&name=<?php echo urlencode(slugify($business['name'] ?? '')); ?>" style="color: #FF6A00; text-decoration: none; font-weight: 600;">View Listing →</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $analytics['views'] ?? 0; ?></div>
                <div class="stat-label">Total Views</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $analytics['clicks'] ?? 0; ?></div>
                <div class="stat-label">Clicks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $analytics['phone_clicks'] ?? 0; ?></div>
                <div class="stat-label">Phone Clicks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $analytics['inquiries'] ?? 0; ?></div>
                <div class="stat-label">Inquiries</div>
            </div>
        </div>
        
        <?php if (!empty($daily)): ?>
        <div class="chart-container">
            <h3>📈 Views Over Last 30 Days</h3>
            <canvas id="viewsChart" height="50"></canvas>
        </div>
        
        <script>
            const chartData = <?php echo json_encode(array_reverse($daily)); ?>;
            const ctx = document.getElementById('viewsChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.map(d => d.date),
                    datasets: [{
                        label: 'Views',
                        data: chartData.map(d => d.views),
                        borderColor: '#FF6A00',
                        backgroundColor: 'rgba(255,106,0,0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } }
                }
            });
        </script>
        <?php endif; ?>
        
        <div class="inquiries-section">
            <h3 style="margin-bottom: 20px;">📬 Recent Inquiries</h3>
            <?php if (empty($inquiries)): ?>
            <p style="color: #666; text-align: center; padding: 40px 0;">No inquiries yet</p>
            <?php else: ?>
                <?php foreach ($inquiries as $inquiry): ?>
                <div class="inquiry-item">
                    <div class="inquiry-name"><?php echo htmlspecialchars($inquiry['name']); ?></div>
                    <div class="inquiry-meta">
                        📧 <?php echo htmlspecialchars($inquiry['email']); ?> · 📞 <?php echo htmlspecialchars($inquiry['phone']); ?>
                    </div>
                    <div class="inquiry-meta" style="margin-top: 8px;">
                        <?php echo htmlspecialchars(substr($inquiry['message'], 0, 100)); ?>...
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
