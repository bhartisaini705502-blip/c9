<?php
/**
 * Admin - Lead CRM Pipeline
 * Kanban-style pipeline view
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !in_array(getUserData()['role'] ?? '', ['admin', 'manager'])) {
    header('Location: login.php');
    exit;
}

// Handle pipeline stage update
if ($_POST['action'] === 'update_stage' && $_POST['lead_id'] && $_POST['stage']) {
    $lead_id = (int)$_POST['lead_id'];
    $stage = trim($_POST['stage']);
    
    $allowed_stages = ['new', 'contacted', 'interested', 'converted', 'closed'];
    if (in_array($stage, $allowed_stages)) {
        $stmt = $conn->prepare("UPDATE leads SET pipeline_stage = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $stage, $lead_id);
        $stmt->execute();
        $stmt->close();
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

// Get leads grouped by pipeline stage
$stages = ['new', 'contacted', 'interested', 'converted', 'closed'];
$pipeline = [];

foreach ($stages as $stage) {
    $result = $conn->query("SELECT id, name, phone, service, score, created_at FROM leads WHERE pipeline_stage = '$stage' ORDER BY updated_at DESC");
    $pipeline[$stage] = $result->fetch_all(MYSQLI_ASSOC);
}

// Get overall stats
$total_leads = $conn->query("SELECT COUNT(*) as count FROM leads")->fetch_assoc()['count'];
$converted = $conn->query("SELECT COUNT(*) as count FROM leads WHERE pipeline_stage = 'converted'")->fetch_assoc()['count'];
$conversion_rate = $total_leads > 0 ? round(($converted / $total_leads) * 100, 1) : 0;

$page_title = "CRM Pipeline - Admin";
require_once '../includes/header.php';
?>

<style>
    .pipeline-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 20px;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }

    .stat-mini {
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-mini-number {
        font-size: 24px;
        font-weight: 700;
        color: #FF6A00;
    }

    .stat-mini-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }

    .pipeline-board {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .pipeline-column {
        background: #f5f5f5;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 600px;
    }

    .column-header {
        background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
        color: white;
        padding: 15px;
        font-weight: 600;
        font-size: 16px;
    }

    .column-header.stage-new { background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%); }
    .column-header.stage-contacted { background: linear-gradient(135deg, #f57c00 0%, #f57f17 100%); }
    .column-header.stage-interested { background: linear-gradient(135deg, #388e3c 0%, #33691e 100%); }
    .column-header.stage-converted { background: linear-gradient(135deg, #5e35b1 0%, #4527a0 100%); }
    .column-header.stage-closed { background: linear-gradient(135deg, #757575 0%, #616161 100%); }

    .column-count {
        background: rgba(255,255,255,0.3);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        display: inline-block;
    }

    .column-body {
        padding: 15px;
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .lead-card {
        background: white;
        padding: 15px;
        border-radius: 6px;
        cursor: grab;
        transition: all 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6A00;
    }

    .lead-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }

    .lead-card-name {
        font-weight: 600;
        color: #0B1C3D;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .lead-card-details {
        font-size: 12px;
        color: #666;
        margin-bottom: 8px;
    }

    .lead-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11px;
    }

    .lead-score {
        background: #e8f5e9;
        color: #25D366;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 600;
    }

    .lead-menu {
        position: relative;
    }

    .lead-menu-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 16px;
        padding: 0;
    }

    .dropdown-menu {
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-width: 150px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        z-index: 10;
        display: none;
    }

    .dropdown-menu.show {
        display: block;
    }

    .dropdown-menu button {
        display: block;
        width: 100%;
        padding: 10px 15px;
        border: none;
        background: none;
        text-align: left;
        cursor: pointer;
        font-size: 13px;
        color: #333;
        transition: all 0.2s;
    }

    .dropdown-menu button:hover {
        background: #f5f5f5;
    }

    .column-empty {
        text-align: center;
        color: #999;
        padding: 40px 20px;
        font-style: italic;
        font-size: 14px;
    }
</style>

<div class="pipeline-container">
    <div class="admin-header">
        <h1>🎯 CRM Pipeline</h1>
        <a href="index.php" style="color: #0B1C3D; text-decoration: none;">← Back</a>
    </div>

    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="stat-mini-number"><?php echo $total_leads; ?></div>
            <div class="stat-mini-label">Total Leads</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-number"><?php echo $converted; ?></div>
            <div class="stat-mini-label">Converted</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-number"><?php echo $conversion_rate; ?>%</div>
            <div class="stat-mini-label">Conversion Rate</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-number"><?php echo count($pipeline['new']); ?></div>
            <div class="stat-mini-label">New Leads</div>
        </div>
    </div>

    <!-- Pipeline Board -->
    <div class="pipeline-board">
        <?php foreach ($stages as $stage): ?>
        <div class="pipeline-column">
            <div class="column-header stage-<?php echo $stage; ?>">
                <?php 
                    $icons = [
                        'new' => '✨',
                        'contacted' => '📞',
                        'interested' => '👂',
                        'converted' => '✅',
                        'closed' => '🏁'
                    ];
                    echo $icons[$stage] . ' ' . ucfirst($stage);
                ?>
                <span class="column-count"><?php echo count($pipeline[$stage]); ?></span>
            </div>

            <div class="column-body">
                <?php if (empty($pipeline[$stage])): ?>
                    <div class="column-empty">No leads</div>
                <?php else: ?>
                    <?php foreach ($pipeline[$stage] as $lead): ?>
                    <div class="lead-card">
                        <div class="lead-card-name"><?php echo esc($lead['name']); ?></div>
                        <div class="lead-card-details">
                            📱 <?php echo esc($lead['phone']); ?><br>
                            🎯 <?php echo esc($lead['service']); ?>
                        </div>
                        <div class="lead-card-footer">
                            <span class="lead-score">⭐ <?php echo $lead['score']; ?></span>
                            <div class="lead-menu">
                                <button class="lead-menu-btn" onclick="toggleMenu(event, <?php echo $lead['id']; ?>)">⋮</button>
                                <div class="dropdown-menu" id="menu-<?php echo $lead['id']; ?>">
                                    <?php foreach ($stages as $s): ?>
                                        <?php if ($s !== $stage): ?>
                                        <button onclick="moveLead(<?php echo $lead['id']; ?>, '<?php echo $s; ?>')">→ <?php echo ucfirst($s); ?></button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleMenu(event, leadId) {
    event.stopPropagation();
    const menu = document.getElementById('menu-' + leadId);
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m !== menu) m.classList.remove('show');
    });
    menu.classList.toggle('show');
}

document.addEventListener('click', function() {
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
});

function moveLead(leadId, stage) {
    fetch('crm-pipeline.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update_stage&lead_id=' + leadId + '&stage=' + stage
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            location.reload();
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
