<?php
/**
 * Admin: Batch Generate AI Descriptions & Tags
 * Generates once, stores in DB, never regenerates
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/ai-features.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Access Denied');
}

$action = $_GET['action'] ?? 'view';
$limit = $_GET['limit'] ?? 50;
$offset = $_GET['offset'] ?? 0;

if ($action === 'generate') {
    header('Content-Type: application/json');
    
    // Get businesses without descriptions
    $stmt = $conn->prepare("
        SELECT id, name, category, city, rating 
        FROM extracted_businesses 
        WHERE ai_description IS NULL OR ai_description = ''
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $generated = 0;
    $failed = 0;
    $businesses = [];
    
    while ($business = $result->fetch_assoc()) {
        // Generate description
        $ai = getGeminiAI();
        if ($ai) {
            $description = $ai->generateBusinessDescription(
                $business['name'],
                $business['category'],
                $business['city']
            );
            
            if ($description) {
                // Store in DB
                $updateStmt = $conn->prepare("
                    UPDATE extracted_businesses 
                    SET ai_description = ? 
                    WHERE id = ?
                ");
                $updateStmt->bind_param('si', $description, $business['id']);
                if ($updateStmt->execute()) {
                    $generated++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        } else {
            $failed++;
        }
        
        $businesses[] = [
            'id' => $business['id'],
            'name' => $business['name'],
            'status' => $description ? 'generated' : 'failed'
        ];
        
        // Rate limiting - be respectful to API
        sleep(1);
    }
    
    echo json_encode([
        'success' => true,
        'generated' => $generated,
        'failed' => $failed,
        'total' => $generated + $failed,
        'businesses' => $businesses
    ]);
    exit;
}

if ($action === 'generate-tags') {
    header('Content-Type: application/json');
    
    // Get businesses without tags
    $stmt = $conn->prepare("
        SELECT id, name, category, city, rating 
        FROM extracted_businesses 
        WHERE ai_tags IS NULL OR ai_tags = ''
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $generated = 0;
    $failed = 0;
    
    while ($business = $result->fetch_assoc()) {
        // Generate tags based on rating and category
        $tags = [];
        
        if ($business['rating'] >= 4.5) {
            $tags[] = 'highly-rated';
        } elseif ($business['rating'] >= 4.0) {
            $tags[] = 'popular';
        }
        
        // Price tier based on category
        $affordable_categories = ['fast-food', 'casual', 'budget', 'street-food'];
        $premium_categories = ['fine-dining', 'luxury', 'upscale'];
        
        if (in_array(strtolower($business['category']), $affordable_categories)) {
            $tags[] = 'affordable';
        } elseif (in_array(strtolower($business['category']), $premium_categories)) {
            $tags[] = 'premium';
        }
        
        // Family friendly tag
        if (in_array(strtolower($business['category']), ['restaurants', 'cafes', 'parks', 'entertainment'])) {
            $tags[] = 'family-friendly';
        }
        
        $tagsStr = implode(', ', array_unique($tags)) ?: 'local-business';
        
        // Store in DB
        $updateStmt = $conn->prepare("
            UPDATE extracted_businesses 
            SET ai_tags = ? 
            WHERE id = ?
        ");
        $updateStmt->bind_param('si', $tagsStr, $business['id']);
        if ($updateStmt->execute()) {
            $generated++;
        } else {
            $failed++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'generated' => $generated,
        'failed' => $failed
    ]);
    exit;
}

// View page - show stats and controls
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate AI Descriptions & Tags</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0B1C3D;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #1E3A8A 0%, #0B1C3D 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 30px;
        }
        button {
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #FF6A00;
            color: white;
        }
        .btn-primary:hover {
            background: #E55A00;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #1E3A8A;
            color: white;
        }
        .btn-secondary:hover {
            background: #0B1C3D;
            transform: translateY(-2px);
        }
        .progress {
            margin-top: 20px;
            display: none;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #FF6A00;
            width: 0%;
            transition: width 0.3s;
        }
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 AI Description & Tags Generator</h1>
        
        <div class="stats">
            <?php
            $stats = $conn->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN ai_description IS NOT NULL AND ai_description != '' THEN 1 ELSE 0 END) as with_desc,
                    SUM(CASE WHEN ai_tags IS NOT NULL AND ai_tags != '' THEN 1 ELSE 0 END) as with_tags
                FROM extracted_businesses
            ")->fetch_assoc();
            
            $pending_desc = $stats['total'] - $stats['with_desc'];
            $pending_tags = $stats['total'] - $stats['with_tags'];
            ?>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_desc; ?></div>
                <div class="stat-label">Pending Descriptions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_tags; ?></div>
                <div class="stat-label">Pending Tags</div>
            </div>
        </div>
        
        <div class="actions">
            <button class="btn-primary" onclick="generateDescriptions()">
                📝 Generate Descriptions
            </button>
            <button class="btn-secondary" onclick="generateTags()">
                🏷️ Generate Tags
            </button>
        </div>
        
        <div class="progress" id="progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <p style="margin-top: 10px; font-size: 14px;" id="progressText">Starting...</p>
        </div>
        
        <div class="message" id="message"></div>
    </div>
    
    <script>
        async function generateDescriptions() {
            const btn = event.target;
            btn.disabled = true;
            
            const progress = document.getElementById('progress');
            const message = document.getElementById('message');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            
            progress.style.display = 'block';
            message.style.display = 'none';
            
            let total = 0;
            let generated = 0;
            let offset = 0;
            const limit = 50;
            
            try {
                while (true) {
                    progressText.textContent = `Processing... (${generated} generated)`;
                    
                    const response = await fetch(`?action=generate&limit=${limit}&offset=${offset}`);
                    const data = await response.json();
                    
                    if (!data.success || data.total === 0) break;
                    
                    generated += data.generated;
                    total += data.total;
                    
                    progressFill.style.width = (generated / (generated + <?php echo $pending_desc; ?>) * 100) + '%';
                    
                    offset += limit;
                }
                
                message.className = 'message success';
                message.textContent = `✓ Generated ${generated} AI descriptions successfully!`;
                message.style.display = 'block';
                
                setTimeout(() => location.reload(), 2000);
            } catch (error) {
                message.className = 'message error';
                message.textContent = `✗ Error: ${error.message}`;
                message.style.display = 'block';
            } finally {
                btn.disabled = false;
                progress.style.display = 'none';
            }
        }
        
        async function generateTags() {
            const btn = event.target;
            btn.disabled = true;
            
            const progress = document.getElementById('progress');
            const message = document.getElementById('message');
            const progressText = document.getElementById('progressText');
            
            progress.style.display = 'block';
            message.style.display = 'none';
            
            let generated = 0;
            let offset = 0;
            const limit = 500;
            
            try {
                while (true) {
                    progressText.textContent = `Generating tags... (${generated} processed)`;
                    
                    const response = await fetch(`?action=generate-tags&limit=${limit}&offset=${offset}`);
                    const data = await response.json();
                    
                    if (!data.success || data.generated === 0) break;
                    
                    generated += data.generated;
                    offset += limit;
                }
                
                message.className = 'message success';
                message.textContent = `✓ Generated tags for ${generated} businesses!`;
                message.style.display = 'block';
                
                setTimeout(() => location.reload(), 2000);
            } catch (error) {
                message.className = 'message error';
                message.textContent = `✗ Error: ${error.message}`;
                message.style.display = 'block';
            } finally {
                btn.disabled = false;
                progress.style.display = 'none';
            }
        }
    </script>
</body>
</html>
