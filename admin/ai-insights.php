<?php
/**
 * Admin - AI Business Insights Dashboard
 */

session_start();

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/ai-functions.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

// Get insights
$insights = getBusinessInsights();

// Get recommended leads
$recommended = getRecommendedLeads(5);

// Get follow-up required leads
$followup_required = getFollowupRequiredLeads();

// Recalculate scores if requested
if (isset($_POST['action']) && $_POST['action'] === 'recalculate_scores') {
    $updated = recalculateAllAIScores();
    $_SESSION['success_message'] = "Recalculated AI scores for $updated leads";
}

$page_title = "AI Insights - Admin";
require_once '../includes/header.php';
?>

<style>
    .ai-container {
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

    .recalc-btn {
        background: #9C27B0;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
    }

    .success-message {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 4px solid #2e7d32;
    }

    .insights-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .insight-card {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        text-align: center;
    }

    .insight-icon {
        font-size: 32px;
        margin-bottom: 10px;
    }

    .insight-number {
        font-size: 32px;
        font-weight: 700;
        color: #0B1C3D;
        margin: 10px 0;
    }

    .insight-label {
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
    }

    .insight-detail {
        font-size: 13px;
        color: #999;
        margin-top: 8px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #0B1C3D;
        margin-top: 30px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #FF6A00;
    }

    .lead-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    .lead-table thead {
        background: #0B1C3D;
        color: white;
    }

    .lead-table th,
    .lead-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .lead-table tbody tr:hover {
        background: #f8f9fa;
    }

    .score-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 12px;
    }

    .score-high {
        background: #e8f5e9;
        color: #25D366;
    }

    .score-medium {
        background: #fff3e0;
        color: #FF9A00;
    }

    .score-low {
        background: #ffebee;
        color: #f44336;
    }

    .empty-state {
        text-align: center;
        color: #999;
        padding: 40px 20px;
        font-style: italic;
    }

    .insight-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .insight-summary h3 {
        margin: 0 0 10px 0;
    }

    .insight-summary p {
        margin: 5px 0;
        opacity: 0.9;
    }

    .recommended-badge {
        background: #FFD700;
        color: #333;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 11px;
        display: inline-block;
        margin-left: 10px;
    }
</style>

<div class="ai-container">
    <div class="admin-header">
        <h1>🤖 AI Insights Dashboard</h1>
        <form method="POST" style="display: inline;">
            <button type="submit" name="action" value="recalculate_scores" class="recalc-btn">↻ Recalculate Scores</button>
        </form>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="success-message"><?php echo $_SESSION['success_message']; ?></div>
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Key Insights -->
    <div class="insights-grid">
        <div class="insight-card">
            <div class="insight-icon">📊</div>
            <div class="insight-label">Total Leads</div>
            <div class="insight-number"><?php echo $insights['total_leads']; ?></div>
        </div>

        <div class="insight-card">
            <div class="insight-icon">⭐</div>
            <div class="insight-label">Avg AI Score</div>
            <div class="insight-number"><?php echo $insights['avg_score']; ?>/100</div>
        </div>

        <div class="insight-card">
            <div class="insight-icon">📈</div>
            <div class="insight-label">Conversion Rate</div>
            <div class="insight-number"><?php echo $insights['conversion_rate']; ?>%</div>
        </div>

        <div class="insight-card">
            <div class="insight-icon">🔥</div>
            <div class="insight-label">Today's Leads</div>
            <div class="insight-number"><?php echo $insights['today_leads']; ?></div>
        </div>
    </div>

    <!-- Best Performing Service -->
    <?php if ($insights['best_service']): ?>
    <div class="insight-summary">
        <h3>🎯 Best Performing Service</h3>
        <p><?php echo htmlspecialchars($insights['best_service']['service']); ?> is generating the most leads</p>
        <p style="font-size: 20px; margin-top: 10px;"><strong><?php echo $insights['best_service']['count']; ?> leads</strong></p>
    </div>
    <?php endif; ?>

    <!-- Best Traffic Source -->
    <?php if ($insights['best_source']): ?>
    <div class="insight-summary" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <h3>🌐 Best Traffic Source</h3>
        <p><?php echo htmlspecialchars($insights['best_source']['source']); ?> is your top-performing source</p>
        <p style="font-size: 20px; margin-top: 10px;"><strong><?php echo $insights['best_source']['count']; ?> leads</strong></p>
    </div>
    <?php endif; ?>

    <!-- Recommended Leads Section -->
    <div class="section-title">🔥 Recommended Leads (Call These First)</div>
    
    <?php if (!empty($recommended)): ?>
    <table class="lead-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Service</th>
                <th>Phone</th>
                <th>Score</th>
                <th>AI Score</th>
                <th>Status</th>
                <th>Age</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recommended as $lead): ?>
            <tr>
                <td><strong><?php echo esc($lead['name']); ?></strong></td>
                <td><?php echo esc($lead['service']); ?></td>
                <td><a href="tel:<?php echo esc($lead['phone']); ?>"><?php echo esc($lead['phone']); ?></a></td>
                <td>
                    <span class="score-badge score-high">⭐ <?php echo $lead['score']; ?></span>
                </td>
                <td>
                    <span class="score-badge score-high">🤖 <?php echo $lead['ai_score']; ?>/100</span>
                </td>
                <td><?php echo ucfirst($lead['status']); ?></td>
                <td><?php echo date('M d', strtotime($lead['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">No leads available</div>
    <?php endif; ?>

    <!-- Follow-up Required Section -->
    <div class="section-title">⏰ Follow-up Required (24+ Hours)</div>
    
    <?php if (!empty($followup_required)): ?>
    <table class="lead-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Service</th>
                <th>Phone</th>
                <th>Days Since Contact</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($followup_required as $lead): ?>
            <tr>
                <td><strong><?php echo esc($lead['name']); ?></strong></td>
                <td><?php echo esc($lead['service']); ?></td>
                <td><a href="tel:<?php echo esc($lead['phone']); ?>"><?php echo esc($lead['phone']); ?></a></td>
                <td><strong><?php echo $lead['days_since_contact']; ?> days</strong></td>
                <td>
                    <a href="crm-pipeline.php" style="color: #FF6A00; text-decoration: none; font-weight: 600;">View →</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">All leads are up to date! ✓</div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
