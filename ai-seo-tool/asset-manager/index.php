<?php
/**
 * Asset Manager - Digital Asset Management System
 * Integrated with ConnectWith9
 */

session_start();

// Set page title
$page_title = "Asset Manager | AI-Powered SEO Tool";
$meta_description = "Organize and manage your digital assets, media files, and resources.";

require_once dirname(__FILE__) . '/../../includes/header.php';
require_once dirname(__FILE__) . '/../../config/db.php';

// Check authentication
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: /auth/login.php');
    exit;
}

// Create assets table if not exists
if ($GLOBALS['conn']) {
    try {
        $GLOBALS['conn']->query("
            CREATE TABLE IF NOT EXISTS assets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                file_type VARCHAR(50),
                file_size BIGINT,
                category VARCHAR(50),
                tags VARCHAR(255),
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_category (category)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Exception $e) {
        // Table already exists
    }
}

// Handle file upload
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['asset_file'])) {
    $file = $_FILES['asset_file'];
    $category = $_POST['category'] ?? 'uncategorized';
    $description = $_POST['description'] ?? '';
    $tags = $_POST['tags'] ?? '';
    
    if ($file['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 
                         'video/mp4', 'video/webm', 'application/pdf', 
                         'application/msword', 'text/plain', 'audio/mpeg'];
        
        if (in_array($file['type'], $allowed_types)) {
            $upload_dir = dirname(__FILE__) . '/../../uploads/assets/';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
            
            $filename = time() . '_' . basename($file['name']);
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save to database
                $rel_path = '/uploads/assets/' . $filename;
                $file_size = $file['size'];
                $file_type = $file['type'];
                
                $stmt = $GLOBALS['conn']->prepare("
                    INSERT INTO assets (user_id, filename, file_path, file_type, file_size, category, tags, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt) {
                    $stmt->bind_param('isssibss', $user_id, $file['name'], $rel_path, $file_type, $file_size, $category, $tags, $description);
                    if ($stmt->execute()) {
                        $upload_message = '✓ File uploaded successfully!';
                    }
                }
            }
        } else {
            $upload_message = '✗ File type not allowed';
        }
    }
}

// Get user's assets
$assets = [];
try {
    $result = $GLOBALS['conn']->query("
        SELECT id, filename, file_path, file_type, file_size, category, tags, description, created_at
        FROM assets
        WHERE user_id = $user_id
        ORDER BY created_at DESC
        LIMIT 100
    ");
    
    if ($result) {
        $assets = $result->fetch_all(MYSQLI_ASSOC) ?? [];
    }
} catch (Exception $e) {
    // Query failed
}

// Count assets by category
$category_counts = [
    'images' => count(array_filter($assets, fn($a) => strpos($a['file_type'], 'image') !== false)),
    'videos' => count(array_filter($assets, fn($a) => strpos($a['file_type'], 'video') !== false)),
    'documents' => count(array_filter($assets, fn($a) => strpos($a['file_type'], 'pdf') !== false || strpos($a['file_type'], 'document') !== false)),
    'audio' => count(array_filter($assets, fn($a) => strpos($a['file_type'], 'audio') !== false)),
];
?>

<div class="container" style="padding: 40px 20px;">
    <div class="premium-card">
        <a href="/ai-seo-tool/" style="text-decoration: none; color: #FF6A00;">← Back to Dashboard</a>
        
        <h1 style="margin-top: 20px;">📦 Asset Manager</h1>
        <p style="color: #666;">Organize and manage your digital assets, media files, and resources</p>
        
        <?php if ($upload_message): ?>
        <div style="padding: 12px 16px; background: <?php echo strpos($upload_message, '✓') !== false ? '#e8f5e9' : '#ffebee'; ?>; 
                    border-radius: 4px; margin: 20px 0; color: <?php echo strpos($upload_message, '✓') !== false ? '#2e7d32' : '#c62828'; ?>;">
            <?php echo $upload_message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Section -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 30px 0;">
            <div style="padding: 15px; background: #fff3e0; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">📦</div>
                <h4 style="margin: 0 0 5px 0;">Total Assets</h4>
                <p style="margin: 0; font-size: 20px; color: #FF6A00; font-weight: bold;"><?php echo count($assets); ?></p>
            </div>
            <div style="padding: 15px; background: #e8f5e9; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">🖼️</div>
                <h4 style="margin: 0 0 5px 0;">Images</h4>
                <p style="margin: 0; font-size: 20px; color: #4caf50; font-weight: bold;"><?php echo $category_counts['images']; ?></p>
            </div>
            <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">🎥</div>
                <h4 style="margin: 0 0 5px 0;">Videos</h4>
                <p style="margin: 0; font-size: 20px; color: #2196f3; font-weight: bold;"><?php echo $category_counts['videos']; ?></p>
            </div>
            <div style="padding: 15px; background: #f3e5f5; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">📄</div>
                <h4 style="margin: 0 0 5px 0;">Documents</h4>
                <p style="margin: 0; font-size: 20px; color: #9c27b0; font-weight: bold;"><?php echo $category_counts['documents']; ?></p>
            </div>
        </div>
        
        <!-- Upload Section -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 30px 0;">
            <h3 style="margin-top: 0;">📤 Upload New Asset</h3>
            <form method="POST" enctype="multipart/form-data" style="display: grid; gap: 15px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">File</label>
                    <div style="border: 2px dashed #ddd; padding: 20px; border-radius: 8px; text-align: center; cursor: pointer;" 
                         onclick="document.getElementById('fileInput').click()"
                         ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                        <input type="file" id="fileInput" name="asset_file" style="display: none;" onchange="updateFileName(this)">
                        <div style="font-size: 32px; margin-bottom: 10px;">📁</div>
                        <p style="margin: 0 0 10px 0; font-weight: 600;">Drag & drop or click to select file</p>
                        <p style="margin: 0; font-size: 12px; color: #999;" id="fileName">No file selected</p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Category</label>
                        <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <option>images</option>
                            <option>videos</option>
                            <option>documents</option>
                            <option>audio</option>
                            <option>other</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Tags (comma separated)</label>
                        <input type="text" name="tags" placeholder="e.g., marketing, social, blog" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Description</label>
                    <textarea name="description" placeholder="Add a description for this asset..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; min-height: 80px;"></textarea>
                </div>
                
                <button type="submit" style="padding: 12px 20px; background: linear-gradient(135deg, #FF6A00, #ff8c00); color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">
                    ⬆️ Upload Asset
                </button>
            </form>
        </div>
        
        <!-- Assets List -->
        <div style="margin-top: 30px;">
            <h3>📚 Your Assets</h3>
            <?php if (count($assets) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px; text-align: left;">Name</th>
                            <th style="padding: 12px; text-align: left;">Category</th>
                            <th style="padding: 12px; text-align: left;">Size</th>
                            <th style="padding: 12px; text-align: left;">Added</th>
                            <th style="padding: 12px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;"><strong><?php echo esc($asset['filename']); ?></strong></td>
                            <td style="padding: 12px;">
                                <span style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo esc($asset['category']); ?>
                                </span>
                            </td>
                            <td style="padding: 12px;"><?php echo round($asset['file_size'] / 1024, 2); ?> KB</td>
                            <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($asset['created_at'])); ?></td>
                            <td style="padding: 12px; text-align: center;">
                                <a href="<?php echo esc($asset['file_path']); ?>" target="_blank" style="color: #FF6A00; text-decoration: none; margin-right: 10px;">📥 Download</a>
                                <a href="javascript:deleteAsset(<?php echo $asset['id']; ?>)" style="color: #d32f2f; text-decoration: none;">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 40px; text-align: center; background: #f5f5f5; border-radius: 8px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 15px;">📁</div>
                <p style="margin: 0;">No assets yet. Upload your first asset to get started!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dnd-hover {
    border-color: #FF6A00 !important;
    background: #fff8f0 !important;
}
</style>

<script>
function updateFileName(input) {
    const fileName = input.files[0]?.name || 'No file selected';
    document.getElementById('fileName').textContent = fileName;
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('dnd-hover');
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('dnd-hover');
}

function handleDrop(e) {
    e.preventDefault();
    const files = e.dataTransfer.files;
    document.getElementById('fileInput').files = files;
    updateFileName(document.getElementById('fileInput'));
    e.currentTarget.classList.remove('dnd-hover');
}

function deleteAsset(id) {
    if (confirm('Are you sure you want to delete this asset?')) {
        // AJAX call to delete
        fetch('/ai-seo-tool/asset-manager/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
        });
    }
}
</script>

<?php require_once dirname(__FILE__) . '/../../includes/footer.php'; ?>
