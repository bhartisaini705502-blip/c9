<?php
/**
 * Admin - Lead Analytics Dashboard
 * Advanced analytics for lead scoring, sources, and conversions
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';

// Get analytics data
$total_leads = $conn->query("SELECT COUNT(*) as count FROM leads")->fetch_assoc()['count'];
$today_leads = $conn->query("SELECT COUNT(*) as count FROM leads WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['count'];
$avg_score = $conn->query("SELECT AVG(score) as avg FROM leads")->fetch_assoc()['avg'];
$high_value_leads = $conn->query("SELECT COUNT(*) as count FROM leads WHERE score >= 10")->fetch_assoc()['count'];

// Leads by service
$service_data = $conn->query("SELECT service, COUNT(*) as count FROM leads GROUP BY service ORDER BY count DESC LIMIT 10");
$services = [];
$service_counts = [];
while ($row = $service_data->fetch_assoc()) {
    $services[] = $row['service'];
    $service_counts[] = $row['count'];
}

// Daily leads (last 7 days)
$daily_data = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM leads WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
$dates = [];
$daily_counts = [];
while ($row = $daily_data->fetch_assoc()) {
    $dates[] = $row['date'];
    $daily_counts[] = $row['count'];
}

// Leads by source
$source_data = $conn->query("SELECT source, COUNT(*) as count FROM leads GROUP BY source ORDER BY count DESC");
$sources = [];
$source_counts = [];
while ($row = $source_data->fetch_assoc()) {
    $sources[] = $row['source'];
    $source_counts[] = $row['count'];
}

// Conversion rate
$contacted = $conn->query("SELECT COUNT(*) as count FROM leads WHERE status='contacted'")->fetch_assoc()['count'];
$conversion_rate = $total_leads > 0 ? round(($contacted / $total_leads) * 100, 1) : 0;

// Score distribution
$score_dist = $conn->query("SELECT 
    SUM(CASE WHEN score >= 15 THEN 1 ELSE 0 END) as high,
    SUM(CASE WHEN score >= 8 AND score < 15 THEN 1 ELSE 0 END) as medium,
    SUM(CASE WHEN score < 8 THEN 1 ELSE 0 END) as low
FROM leads");
$scores = $score_dist->fetch_assoc();

$page_title = "Lead Analytics - Admin";
require_once '../includes/header.php';
?>

<style>
    .analytics-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #0B1C3D;
        margin: 10px 0;
    }

    .stat-label {
        font-size: 13px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-icon {
        font-size: 28px;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    .chart-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .chart-card h3 {
        margin: 0 0 15px 0;
        color: #0B1C3D;
        border-bottom: 2px solid #FF6A00;
        padding-bottom: 10px;
    }

    canvas {
        max-height: 280px;
    }

    .conversion-section {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 40px;
    }

    .conversion-bar {
        background: #f0f0f0;
        border-radius: 20px;
        height: 35px;
        margin: 15px 0;
        overflow: hidden;
    }

    .conversion-fill {
        background: linear-gradient(90deg, #FF6A00, #FF8A20);
        height: 100%;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
    }

    .score-distribution {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 20px;
    }

    .score-box {
        padding: 15px;
        border-radius: 6px;
        text-align: center;
    }

    .score-high {
        background: rgba(37, 211, 102, 0.15);
        border-left: 4px solid #25D366;
    }

    .score-medium {
        background: rgba(255, 154, 0, 0.15);
        border-left: 4px solid #FF9A00;
    }

    .score-low {
        background: rgba(244, 67, 54, 0.15);
        border-left: 4px solid #F44336;
    }

    .score-number {
        font-size: 28px;
        font-weight: 700;
        margin: 10px 0 5px 0;
    }

    .score-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }
</style>

<div class="analytics-container">
    <div class="admin-header">
        <h1>📊 Lead Analytics Dashboard</h1>
        <a href="index.php" style="color: #0B1C3D; text-decoration: none;">← Back to Dashboard</a>
    </div>

    <!-- KPI Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📞</div>
            <div class="stat-number"><?php echo $total_leads; ?></div>
            <div class="stat-label">Total Leads</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✨</div>
            <div class="stat-number"><?php echo $today_leads; ?></div>
            <div class="stat-label">Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-number"><?php echo round($avg_score ?? 0); ?></div>
            <div class="stat-label">Avg Score</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💎</div>
            <div class="stat-number"><?php echo $high_value_leads; ?></div>
            <div class="stat-label">High Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-number"><?php echo $contacted; ?></div>
            <div class="stat-label">Contacted</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-number"><?php echo $conversion_rate; ?>%</div>
            <div class="stat-label">Conversion</div>
        </div>
    </div>

    <!-- Conversion Rate -->
    <div class="conversion-section">
        <h3 style="margin-top: 0;">Lead Conversion Progress</h3>
        <div class="conversion-bar">
            <div class="conversion-fill" style="width: <?php echo min($conversion_rate, 100); ?>%;">
                <?php echo $conversion_rate; ?>% Contacted (<?php echo $contacted; ?>/<?php echo $total_leads; ?>)
            </div>
        </div>

        <!-- Score Distribution -->
        <h3 style="margin-top: 30px;">Score Distribution</h3>
        <div class="score-distribution">
            <div class="score-box score-high">
                <div class="score-number"><?php echo $scores['high'] ?? 0; ?></div>
                <div class="score-label">High (15+)</div>
            </div>
            <div class="score-box score-medium">
                <div class="score-number"><?php echo $scores['medium'] ?? 0; ?></div>
                <div class="score-label">Medium (8-14)</div>
            </div>
            <div class="score-box score-low">
                <div class="score-number"><?php echo $scores['low'] ?? 0; ?></div>
                <div class="score-label">Low (<8)</div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <!-- Leads by Service -->
        <div class="chart-card">
            <h3>📊 Leads by Service</h3>
            <canvas id="serviceChart"></canvas>
        </div>

        <!-- Daily Leads -->
        <div class="chart-card">
            <h3>📈 Daily Leads (7 Days)</h3>
            <canvas id="dailyChart"></canvas>
        </div>

        <!-- Leads by Source -->
        <div class="chart-card">
            <h3>🔗 Leads by Source</h3>
            <canvas id="sourceChart"></canvas>
        </div>

        <!-- Score Distribution Chart -->
        <div class="chart-card">
            <h3>⭐ Lead Quality</h3>
            <canvas id="scoreChart"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Service Chart
const serviceCtx = document.getElementById('serviceChart').getContext('2d');
new Chart(serviceCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($services); ?>,
        datasets: [{
            label: 'Number of Leads',
            data: <?php echo json_encode($service_counts); ?>,
            backgroundColor: 'rgba(255, 106, 0, 0.7)',
            borderColor: 'rgba(255, 106, 0, 1)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// Daily Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: 'Leads',
            data: <?php echo json_encode($daily_counts); ?>,
            borderColor: 'rgba(11, 28, 61, 1)',
            backgroundColor: 'rgba(11, 28, 61, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: '#FF6A00'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
    }
});

// Source Chart
const sourceCtx = document.getElementById('sourceChart').getContext('2d');
new Chart(sourceCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($sources); ?>,
        datasets: [{
            data: <?php echo json_encode($source_counts); ?>,
            backgroundColor: [
                'rgba(255, 106, 0, 0.8)',
                'rgba(30, 58, 138, 0.8)',
                'rgba(37, 211, 102, 0.8)',
                'rgba(255, 154, 0, 0.8)',
                'rgba(63, 81, 181, 0.8)'
            ],
            borderColor: 'white',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Score Distribution Chart
const scoreCtx = document.getElementById('scoreChart').getContext('2d');
new Chart(scoreCtx, {
    type: 'doughnut',
    data: {
        labels: ['High (15+)', 'Medium (8-14)', 'Low (<8)'],
        datasets: [{
            data: [<?php echo $scores['high'] ?? 0; ?>, <?php echo $scores['medium'] ?? 0; ?>, <?php echo $scores['low'] ?? 0; ?>],
            backgroundColor: [
                'rgba(37, 211, 102, 0.8)',
                'rgba(255, 154, 0, 0.8)',
                'rgba(244, 67, 54, 0.8)'
            ],
            borderColor: 'white',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
