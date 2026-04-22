<?php
/**
 * AI Content Generator Admin Panel
 * Manually trigger AI content generation for businesses
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';

// Check admin access
if (!isLoggedIn() || !isAdmin()) {
    header('Location: /');
    exit;
}

$message = '';
$messageType = '';

// Handle generation request
if ($_POST['action'] === 'generate') {
    $limit = (int)($_POST['limit'] ?? 50);
    $limit = min($limit, 100);
    
    // Call API endpoint
    $apiKey = getenv('ADMIN_API_KEY');
    $url = 'http://localhost:5000/api/generate-ai-content.php?api_key=' . urlencode($apiKey) . '&limit=' . $limit;
    
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if ($data['success']) {
            $message = "✓ Generated content for {$data['generated']} businesses ({$data['failed']} failed)";
            $messageType = 'success';
        } else {
            $message = "Error: " . ($data['message'] ?? 'Unknown error');
            $messageType = 'error';
        }
    } else {
        $message = 'Error: Failed to call API';
        $messageType = 'error';
    }
}

// Get statistics
$stats = [];
$result = $GLOBALS['conn']->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ai_generated = 1 THEN 1 ELSE 0 END) as generated,
        SUM(CASE WHEN ai_generated = 0 THEN 1 ELSE 0 END) as pending
    FROM extracted_businesses
    WHERE business_status = 'OPERATIONAL'
");
if ($result) {
    $stats = $result->fetch_assoc();
}

// Get sample generated content
$samples = [];
$result = $GLOBALS['conn']->query("
    SELECT id, name, ai_short_summary, ai_tags, ai_generated
    FROM extracted_businesses
    WHERE ai_generated = 1 AND ai_short_summary IS NOT NULL
    LIMIT 10
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $samples[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Content Generator - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .admin-header {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .admin-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        .admin-header p {
            margin: 0;
            opacity: 0.9;
        }
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
        }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #FF6A00;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 30px;
            margin-top: 10px;
            overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(90deg, #FF6A00, #FFB84D);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 12px;
        }
        .control-panel {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .control-panel h2 {
            color: #0B1C3D;
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #FF6A00;
            color: white;
        }
        .btn-primary:hover {
            background: #E55A00;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #0B1C3D;
            border: 1px solid #ddd;
        }
        .btn-secondary:hover {
            border-color: #FF6A00;
            color: #FF6A00;
        }
        .samples-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
        }
        .samples-section h2 {
            color: #0B1C3D;
            margin-top: 0;
        }
        .sample-item {
            border: 1px solid #f0f0f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
        }
        .sample-item:last-child {
            margin-bottom: 0;
        }
        .sample-name {
            font-weight: 700;
            color: #0B1C3D;
            margin-bottom: 8px;
        }
        .sample-summary {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .sample-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .tag {
            background: #e3f2fd;
            color: #1E3A8A;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-generated {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        @media (max-width: 768px) {
            .admin-header {
                padding: 20px;
            }
            .admin-header h1 {
                font-size: 24px;
            }
            .btn-group {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php require_once dirname(__DIR__) . '/includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>🧠 AI Content Generator</h1>
            <p>Generate professional business descriptions, summaries, and tags using Gemini API</p>
        </div>
        
        <?php if ($message): ?>
        <div class="message <?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Businesses</div>
                <div class="stat-number"><?php echo number_format($stats['total'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Generated</div>
                <div class="stat-number" style="color: #28a745;"><?php echo number_format($stats['generated'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-number" style="color: #ffc107;"><?php echo number_format($stats['pending'] ?? 0); ?></div>
                <div class="progress-bar">
                    <?php 
                    $percentage = $stats['total'] > 0 ? round(($stats['generated'] / $stats['total']) * 100) : 0;
                    ?>
                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%;">
                        <?php echo $percentage; ?>%
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Control Panel -->
        <div class="control-panel">
            <h2>📝 Generate Content</h2>
            <form method="POST" style="display: flex; gap: 20px; flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label for="limit">Businesses to Generate (per run)</label>
                    <input type="number" id="limit" name="limit" value="50" min="1" max="100">
                </div>
                <div class="btn-group">
                    <button type="submit" name="action" value="generate" class="btn btn-primary">
                        🚀 Generate Content
                    </button>
                </div>
            </form>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <h3 style="margin-top: 0; color: #0B1C3D;">Cron Job Setup</h3>
                <p style="color: #666; margin: 10px 0;">Schedule this command to run daily at 2 AM:</p>
                <code style="background: #f5f5f5; padding: 12px; border-radius: 4px; display: block; overflow-x: auto;">
                    0 2 * * * php <?php echo dirname(__DIR__); ?>/cron/daily-ai-generation.php
                </code>
            </div>
        </div>
        
        <!-- Sample Generated Content -->
        <?php if (!empty($samples)): ?>
        <div class="samples-section">
            <h2>📊 Sample Generated Content</h2>
            <?php foreach ($samples as $sample): ?>
            <div class="sample-item">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                    <div class="sample-name"><?php echo htmlspecialchars($sample['name']); ?></div>
                    <span class="status-badge status-generated">✓ Generated</span>
                </div>
                <div class="sample-summary"><?php echo htmlspecialchars($sample['ai_short_summary']); ?></div>
                <div class="sample-tags">
                    <?php foreach (array_filter(explode(',', $sample['ai_tags'] ?? '')) as $tag): ?>
                    <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
