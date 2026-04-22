<?php
/**
 * Admin: Generate SEO Pages (Enhanced)
 * Creates category and location pages with AI content
 */

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/ai-features.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Access Denied');
}

$action = $_GET['action'] ?? 'view';

if ($action === 'generate') {
    header('Content-Type: application/json');
    
    $type = $_GET['type'] ?? 'category';
    $limit = $_GET['limit'] ?? 20;
    $offset = $_GET['offset'] ?? 0;
    
    if ($type === 'category') {
        // Get unique categories
        $stmt = $conn->prepare("
            SELECT DISTINCT LOWER(TRIM(category)) as category 
            FROM extracted_businesses 
            WHERE business_status = 'OPERATIONAL' AND category IS NOT NULL
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $generated = 0;
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $category = $row['category'];
            $slug = sanitizeSlug($category);
            
            // Get count
            $catStmt = $conn->prepare("
                SELECT COUNT(*) as count FROM extracted_businesses 
                WHERE LOWER(category) LIKE ? AND business_status = 'OPERATIONAL'
            ");
            $catParam = '%' . $category . '%';
            $catStmt->bind_param('s', $catParam);
            $catStmt->execute();
            $catCount = $catStmt->get_result()->fetch_assoc()['count'];
            
            // Generate content
            $ai = getGeminiAI();
            if ($ai) {
                $content = $ai->generateBusinessDescription($category, 'Business Directory', 'SEO');
                
                if ($content) {
                    $insertStmt = $conn->prepare("
                        INSERT INTO seo_pages (type, slug, title, content, meta_description, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()
                    ");
                    
                    $type_val = 'category';
                    $title = "Best " . ucwords($category);
                    $meta = "Find and compare the best $category in your area.";
                    
                    $insertStmt->bind_param('sssss', $type_val, $slug, $title, $content, $meta);
                    if ($insertStmt->execute()) {
                        $generated++;
                        $items[] = ['name' => $category, 'status' => 'generated'];
                    }
                }
            }
            
            sleep(1);
        }
        
    } else {
        // Location pages
        $stmt = $conn->prepare("
            SELECT DISTINCT LOWER(TRIM(city)) as city 
            FROM extracted_businesses 
            WHERE business_status = 'OPERATIONAL' AND city IS NOT NULL
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $generated = 0;
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $city = $row['city'];
            $slug = sanitizeSlug($city);
            
            $ai = getGeminiAI();
            if ($ai) {
                $content = $ai->generateBusinessDescription($city, 'Location Directory', 'SEO');
                
                if ($content) {
                    $insertStmt = $conn->prepare("
                        INSERT INTO seo_pages (type, slug, title, content, meta_description, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()
                    ");
                    
                    $type_val = 'location';
                    $title = "Businesses in " . ucwords($city);
                    $meta = "Find local businesses and services in $city.";
                    
                    $insertStmt->bind_param('sssss', $type_val, $slug, $title, $content, $meta);
                    if ($insertStmt->execute()) {
                        $generated++;
                        $items[] = ['name' => $city, 'status' => 'generated'];
                    }
                }
            }
            
            sleep(1);
        }
    }
    
    echo json_encode([
        'success' => true,
        'type' => $type,
        'generated' => $generated,
        'items' => $items
    ]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate SEO Pages</title>
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
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 SEO Pages Generator</h1>
        
        <div class="stats">
            <?php
            $catCount = $conn->query("SELECT COUNT(DISTINCT LOWER(category)) as count FROM extracted_businesses WHERE business_status = 'OPERATIONAL' AND category IS NOT NULL")->fetch_assoc()['count'] ?? 0;
            $locCount = $conn->query("SELECT COUNT(DISTINCT city) as count FROM extracted_businesses WHERE business_status = 'OPERATIONAL' AND city != ''")->fetch_assoc()['count'] ?? 0;
            ?>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $catCount; ?></div>
                <div class="stat-label">Categories</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $locCount; ?></div>
                <div class="stat-label">Locations</div>
            </div>
        </div>
        
        <div class="actions">
            <button class="btn-primary" onclick="generatePages('category')">
                📑 Generate Category Pages
            </button>
            <button class="btn-secondary" onclick="generatePages('location')">
                📍 Generate Location Pages
            </button>
        </div>
        
        <div class="message" id="message"></div>
    </div>
    
    <script>
        async function generatePages(type) {
            const btn = event.target;
            btn.disabled = true;
            
            const message = document.getElementById('message');
            message.style.display = 'none';
            
            try {
                const response = await fetch(`?action=generate&type=${type}&limit=20`);
                const data = await response.json();
                
                message.className = 'message success';
                message.textContent = `✓ Generated ${data.generated} ${type} pages!`;
                message.style.display = 'block';
                
                setTimeout(() => location.reload(), 2000);
            } catch (error) {
                message.className = 'message error';
                message.textContent = `Error: ${error.message}`;
                message.style.display = 'block';
            } finally {
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
