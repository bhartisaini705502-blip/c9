<?php
/**
 * Conversion Analytics Dashboard
 * Track calls, WhatsApp clicks, form submissions, and more
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

// Get date filter
$days = $_GET['days'] ?? 7;
$days = in_array($days, [1, 7, 30, 90]) ? $days : 7;

$start_date = date('Y-m-d', strtotime("-$days days"));

// Get conversion metrics
if ($GLOBALS['conn']) {
    // Total events by type
    $event_summary = $GLOBALS['conn']->query("
        SELECT 
            action_type,
            COUNT(*) as count
        FROM analytics_events
        WHERE created_at >= '$start_date'
        GROUP BY action_type
        ORDER BY count DESC
    ")->fetch_all(MYSQLI_ASSOC) ?? [];
    
    // Daily conversion trend
    $daily_trend = $GLOBALS['conn']->query("
        SELECT 
            DATE(created_at) as date,
            action_type,
            COUNT(*) as count
        FROM analytics_events
        WHERE created_at >= '$start_date'
        GROUP BY DATE(created_at), action_type
        ORDER BY date DESC, action_type
    ")->fetch_all(MYSQLI_ASSOC) ?? [];
    
    // Top performing businesses
    $top_businesses = $GLOBALS['conn']->query("
        SELECT 
            ae.business_id,
            eb.name,
            COUNT(*) as total_events,
            SUM(ae.action_type = 'call_click') as call_clicks,
            SUM(ae.action_type = 'whatsapp_click') as whatsapp_clicks,
            SUM(ae.action_type = 'form_submit') as form_submissions
        FROM analytics_events ae
        JOIN extracted_businesses eb ON ae.business_id = eb.id
        WHERE ae.created_at >= '$start_date'
        GROUP BY ae.business_id, eb.name
        ORDER BY total_events DESC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC) ?? [];
    
    // Conversion rate calculation
    $total_views = $GLOBALS['conn']->query("
        SELECT COUNT(*) as count FROM analytics_events 
        WHERE action_type = 'page_view' AND created_at >= '$start_date'
    ")->fetch_assoc()['count'] ?? 0;
    
    $total_conversions = $GLOBALS['conn']->query("
        SELECT COUNT(*) as count FROM analytics_events 
        WHERE action_type IN ('call_click', 'whatsapp_click', 'form_submit') 
        AND created_at >= '$start_date'
    ")->fetch_assoc()['count'] ?? 0;
    
    $conversion_rate = $total_views > 0 ? round(($total_conversions / $total_views) * 100, 2) : 0;
}

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conversion Analytics | Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
        
        .header { background: linear-gradient(135deg, #0B1C3D, #1E3A8A); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; display: flex; gap: 20px; align-items: center; }
        .filters button { padding: 10px 20px; border: none; background: #FF6A00; color: white; border-radius: 6px; cursor: pointer; font-weight: 700; }
        .filters button.active { background: #0B1C3D; }
        
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .metric-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #FF6A00; }
        .metric-label { font-size: 12px; color: #999; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
        .metric-value { font-size: 32px; font-weight: 800; color: #0B1C3D; margin-bottom: 8px; }
        .metric-subtitle { font-size: 12px; color: #666; }
        
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .card h2 { font-size: 20px; color: #0B1C3D; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #FF6A00; }
        
        .chart-container { position: relative; height: 400px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9f9f9; padding: 15px; text-align: left; font-weight: 700; color: #0B1C3D; border-bottom: 2px solid #FF6A00; }
        td { padding: 15px; border-bottom: 1px solid #f0f0f0; }
        tr:hover { background: #f9f9f9; }
        
        .badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge-primary { background: #FF6A00; color: white; }
        .badge-secondary { background: #25D366; color: white; }
        .badge-tertiary { background: #0B1C3D; color: white; }
        
        @media (max-width: 768px) {
            .metrics-grid { grid-template-columns: 1fr; }
            .filters { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="header">
    <h1>📊 Conversion Analytics</h1>
    <p>Track calls, WhatsApp clicks, form submissions, and more</p>
</div>

<div class="container">
    <!-- Filters -->
    <div class="filters">
        <span style="font-weight: 700; color: #333;">Last:</span>
        <a href="?days=1"><button <?php echo $days == 1 ? 'class="active"' : ''; ?>>24 Hours</button></a>
        <a href="?days=7"><button <?php echo $days == 7 ? 'class="active"' : ''; ?>>7 Days</button></a>
        <a href="?days=30"><button <?php echo $days == 30 ? 'class="active"' : ''; ?>>30 Days</button></a>
        <a href="?days=90"><button <?php echo $days == 90 ? 'class="active"' : ''; ?>>90 Days</button></a>
    </div>
    
    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-label">📞 Call Clicks</div>
            <div class="metric-value"><?php echo $event_summary[array_search('call_click', array_column($event_summary, 'action_type'))] ?? '0'; ?></div>
            <div class="metric-subtitle">Direct phone call attempts</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-label">💬 WhatsApp Clicks</div>
            <div class="metric-value"><?php 
                $wa_data = array_search('whatsapp_click', array_column($event_summary, 'action_type'));
                echo $event_summary[$wa_data]['count'] ?? '0'; 
            ?></div>
            <div class="metric-subtitle">WhatsApp chat initiations</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-label">📝 Form Submissions</div>
            <div class="metric-value"><?php 
                $form_data = array_search('form_submit', array_column($event_summary, 'action_type'));
                echo $event_summary[$form_data]['count'] ?? '0'; 
            ?></div>
            <div class="metric-subtitle">Enquiry form submissions</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-label">📈 Conversion Rate</div>
            <div class="metric-value"><?php echo $conversion_rate; ?>%</div>
            <div class="metric-subtitle"><?php echo $total_conversions; ?> conversions from <?php echo $total_views; ?> views</div>
        </div>
    </div>
    
    <!-- Events by Type -->
    <div class="card">
        <h2>📊 Events Summary</h2>
        <div class="chart-container">
            <canvas id="eventChart"></canvas>
        </div>
    </div>
    
    <!-- Top Performing Businesses -->
    <div class="card">
        <h2>🏆 Top Performing Businesses</h2>
        <table>
            <thead>
                <tr>
                    <th>Business Name</th>
                    <th class="text-center">📞 Calls</th>
                    <th class="text-center">💬 WhatsApp</th>
                    <th class="text-center">📝 Forms</th>
                    <th class="text-center">Total Events</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_businesses as $business): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($business['name']); ?></strong></td>
                    <td style="text-align: center;">
                        <span class="badge badge-primary"><?php echo $business['call_clicks'] ?? 0; ?></span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-secondary"><?php echo $business['whatsapp_clicks'] ?? 0; ?></span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-tertiary"><?php echo $business['form_submissions'] ?? 0; ?></span>
                    </td>
                    <td style="text-align: center; font-weight: 700;"><?php echo $business['total_events']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Daily Trend -->
    <div class="card">
        <h2>📈 Daily Conversion Trend</h2>
        <div class="chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>

<script>
// Process data for charts
const eventData = <?php echo json_encode($event_summary); ?>;
const dailyData = <?php echo json_encode($daily_trend); ?>;

// Events pie chart
const eventCtx = document.getElementById('eventChart').getContext('2d');
new Chart(eventCtx, {
    type: 'doughnut',
    data: {
        labels: eventData.map(e => e.action_type.replace(/_/g, ' ').toUpperCase()),
        datasets: [{
            data: eventData.map(e => e.count),
            backgroundColor: ['#FF6A00', '#25D366', '#0B1C3D', '#1E3A8A', '#FFB347'],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Daily trend chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
const groupedByDate = {};
dailyData.forEach(item => {
    if (!groupedByDate[item.date]) groupedByDate[item.date] = {};
    groupedByDate[item.date][item.action_type] = item.count;
});

const dates = Object.keys(groupedByDate).sort();
const callData = dates.map(d => groupedByDate[d]['call_click'] || 0);
const waData = dates.map(d => groupedByDate[d]['whatsapp_click'] || 0);
const formData = dates.map(d => groupedByDate[d]['form_submit'] || 0);

new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: dates,
        datasets: [
            { 
                label: 'Call Clicks', 
                data: callData, 
                borderColor: '#FF6A00', 
                backgroundColor: 'rgba(255, 106, 0, 0.1)',
                borderWidth: 2,
                tension: 0.3
            },
            { 
                label: 'WhatsApp Clicks', 
                data: waData, 
                borderColor: '#25D366', 
                backgroundColor: 'rgba(37, 211, 102, 0.1)',
                borderWidth: 2,
                tension: 0.3
            },
            { 
                label: 'Form Submissions', 
                data: formData, 
                borderColor: '#0B1C3D', 
                backgroundColor: 'rgba(11, 28, 61, 0.1)',
                borderWidth: 2,
                tension: 0.3
            }
        ]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
