<?php
/**
 * Admin Telecalling Management Dashboard
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('/auth/login.php');
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'agents';

// Get all telecaller agents
$stmt = $conn->prepare("
    SELECT ta.*, u.full_name, u.email
    FROM telecaller_agents ta
    JOIN users u ON ta.user_id = u.id
    ORDER BY ta.last_activity DESC
");
$stmt->execute();
$agents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get daily performance stats
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT 
        cl.agent_id,
        u.full_name,
        COUNT(*) as total_calls,
        SUM(CASE WHEN cl.call_status = 'interested' THEN 1 ELSE 0 END) as interested,
        SUM(CASE WHEN cl.call_status = 'not_interested' THEN 1 ELSE 0 END) as not_interested,
        SUM(CASE WHEN cl.call_status = 'call_again' THEN 1 ELSE 0 END) as callbacks
    FROM call_logs cl
    JOIN users u ON cl.agent_id = u.id
    WHERE DATE(cl.created_at) = ?
    GROUP BY cl.agent_id
    ORDER BY total_calls DESC
");
$stmt->bind_param('s', $today);
$stmt->execute();
$daily_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get callback reminders
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("
    SELECT cs.*, eb.name, u.full_name
    FROM callback_schedule cs
    JOIN extracted_businesses eb ON cs.business_id = eb.id
    JOIN users u ON cs.agent_id = u.id
    WHERE cs.status = 'pending' AND cs.scheduled_time <= DATE_ADD(?, INTERVAL 1 HOUR)
    ORDER BY cs.scheduled_time ASC
");
$stmt->bind_param('s', $now);
$stmt->execute();
$pending_callbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get call status breakdown
$stmt = $conn->prepare("
    SELECT 
        call_status,
        COUNT(*) as count
    FROM call_logs
    WHERE DATE(created_at) = ?
    GROUP BY call_status
");
$stmt->bind_param('s', $today);
$stmt->execute();
$status_breakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telecalling Management - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .admin-content {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #ddd;
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 13px;
            color: #999;
            margin-top: 8px;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            background: #f5f5f5;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }

        .status-online {
            background: #4CAF50;
            color: white;
        }

        .status-offline {
            background: #999;
            color: white;
        }

        .status-on_call {
            background: #2196F3;
            color: white;
        }

        .status-break {
            background: #FF9800;
            color: white;
        }

        .call-status-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-not_received { background: #FFC107; color: white; }
        .status-busy { background: #FF9800; color: white; }
        .status-not_interested { background: #F44336; color: white; }
        .status-irritated { background: #E91E63; color: white; }
        .status-interested { background: #4CAF50; color: white; }
        .status-call_again { background: #2196F3; color: white; }

        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .chart-title {
            font-weight: 600;
            margin-bottom: 15px;
        }

        .bar-chart {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            height: 200px;
        }

        .bar {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }

        .bar-fill {
            width: 100%;
            background: #667eea;
            border-radius: 4px 4px 0 0;
            margin-bottom: 8px;
            transition: all 0.3s;
        }

        .bar:hover .bar-fill {
            background: #764ba2;
        }

        .bar-label {
            font-size: 11px;
            color: #999;
            text-align: center;
            word-break: break-word;
        }

        .bar-value {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .callback-alert {
            background: #FFE5E5;
            border-left: 4px solid #F44336;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .callback-business {
            font-weight: 600;
            color: #333;
        }

        .callback-agent {
            font-size: 12px;
            color: #666;
        }

        .callback-time {
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <?php require '../includes/header.php'; ?>

    <div class="admin-content">
        <div class="header">
            <h2 style="margin: 0; font-size: 24px;">📞 Telecalling Management</h2>
            <p style="margin: 8px 0 0; opacity: 0.9;">Monitor agent performance and manage callbacks</p>
        </div>

        <div class="tabs">
            <button class="tab-btn <?php echo $tab == 'agents' ? 'active' : ''; ?>" onclick="switchTab('agents')">
                👥 Agents (<?php echo count($agents); ?>)
            </button>
            <button class="tab-btn <?php echo $tab == 'performance' ? 'active' : ''; ?>" onclick="switchTab('performance')">
                📊 Today's Performance
            </button>
            <button class="tab-btn <?php echo $tab == 'callbacks' ? 'active' : ''; ?>" onclick="switchTab('callbacks')">
                📅 Pending Callbacks (<?php echo count($pending_callbacks); ?>)
            </button>
        </div>

        <!-- Agents Tab -->
        <div id="agentsTab" class="tab-content <?php echo $tab == 'agents' ? 'active' : ''; ?>">
            <div class="table-container">
                <?php if (empty($agents)): ?>
                    <div class="empty-state">
                        No telecaller agents registered yet
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Calls Today</th>
                                <th>Interested</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agents as $agent): ?>
                                <tr>
                                    <td><?php echo esc($agent['full_name']); ?></td>
                                    <td><?php echo esc($agent['email']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $agent['status']; ?>">
                                            <?php echo ucfirst($agent['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $agent['calls_today']; ?></td>
                                    <td><?php echo $agent['interested_leads']; ?></td>
                                    <td><?php echo date('g:i A', strtotime($agent['last_activity'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance Tab -->
        <div id="performanceTab" class="tab-content <?php echo $tab == 'performance' ? 'active' : ''; ?>">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" style="color: #FF6B6B;">
                        <?php 
                        $total = 0;
                        foreach ($status_breakdown as $s) $total += $s['count'];
                        echo $total;
                        ?>
                    </div>
                    <div class="stat-label">Total Calls</div>
                </div>
                <?php 
                $interested = $not_interested = $callbacks = 0;
                foreach ($status_breakdown as $s) {
                    if ($s['call_status'] == 'interested') $interested = $s['count'];
                    if ($s['call_status'] == 'not_interested') $not_interested = $s['count'];
                    if ($s['call_status'] == 'call_again') $callbacks = $s['count'];
                }
                ?>
                <div class="stat-card">
                    <div class="stat-value" style="color: #4CAF50;"><?php echo $interested; ?></div>
                    <div class="stat-label">Interested</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #F44336;"><?php echo $not_interested; ?></div>
                    <div class="stat-label">Not Interested</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #2196F3;"><?php echo $callbacks; ?></div>
                    <div class="stat-label">Callbacks</div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">Agent Performance - Today</div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Total Calls</th>
                                <th>Interested</th>
                                <th>Not Interested</th>
                                <th>Callbacks</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_stats as $stat): ?>
                                <tr>
                                    <td><?php echo esc($stat['full_name']); ?></td>
                                    <td><strong><?php echo $stat['total_calls']; ?></strong></td>
                                    <td><?php echo $stat['interested']; ?></td>
                                    <td><?php echo $stat['not_interested']; ?></td>
                                    <td><?php echo $stat['callbacks']; ?></td>
                                    <td>
                                        <?php 
                                        $rate = $stat['total_calls'] > 0 ? round(($stat['interested'] / $stat['total_calls']) * 100, 1) : 0;
                                        echo $rate . '%';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Callbacks Tab -->
        <div id="callbacksTab" class="tab-content <?php echo $tab == 'callbacks' ? 'active' : ''; ?>">
            <?php if (empty($pending_callbacks)): ?>
                <div class="empty-state">
                    ✅ No pending callbacks
                </div>
            <?php else: ?>
                <?php foreach ($pending_callbacks as $cb): ?>
                    <div class="callback-alert">
                        <div class="callback-business">
                            <?php echo esc($cb['name']); ?>
                        </div>
                        <div class="callback-agent">
                            👤 Agent: <?php echo esc($cb['full_name']); ?>
                        </div>
                        <div class="callback-time">
                            📅 Scheduled: <?php echo date('M d, Y g:i A', strtotime($cb['scheduled_time'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tab + 'Tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
