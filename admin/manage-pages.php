<?php
/**
 * Admin - Manage Static Pages
 */

require_once '../config/db.php';
require_once '../config/auth.php';

if (!isAdmin()) {
    header('Location: /auth/login.php?redirect=/admin/manage-pages.php');
    exit;
}

$user = getUserData();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$page_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    
    if (empty($slug) || empty($title) || empty($content)) {
        $error = 'All fields are required';
    } else {
        $stmt = $conn->prepare("UPDATE static_pages SET title = ?, content = ?, meta_description = ? WHERE slug = ?");
        $stmt->bind_param('ssss', $title, $content, $meta_description, $slug);
        
        if ($stmt->execute()) {
            $success = 'Page updated successfully';
            $action = 'list';
        } else {
            $error = 'Failed to update page';
        }
    }
}

$page_title = 'Manage Pages';
include '../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }
    
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .admin-header h2 {
        color: #333;
        margin: 0;
    }
    
    .pages-list {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .pages-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .pages-table th {
        background: #f5f5f5;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 1px solid #ddd;
    }
    
    .pages-table td {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .pages-table tr:hover {
        background: #f9f9f9;
    }
    
    .edit-btn {
        background: #667eea;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
    }
    
    .edit-btn:hover {
        background: #764ba2;
    }
    
    .edit-form {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        max-width: 900px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-family: Arial, sans-serif;
        box-sizing: border-box;
    }
    
    .form-group textarea {
        min-height: 400px;
        resize: vertical;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn-save {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }
    
    .btn-save:hover {
        background: #45a049;
    }
    
    .btn-cancel {
        background: #999;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-cancel:hover {
        background: #777;
    }
    
    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="admin-container">
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo esc($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo esc($error); ?></div>
    <?php endif; ?>
    
    <?php if ($action === 'list'): ?>
        <div class="admin-header">
            <h2>📄 Manage Pages</h2>
        </div>
        
        <div class="pages-list">
            <table class="pages-table">
                <thead>
                    <tr>
                        <th>Page Title</th>
                        <th>Slug</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT id, slug, title, updated_at FROM static_pages ORDER BY slug");
                    while ($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo esc($row['title']); ?></td>
                            <td><code><?php echo esc($row['slug']); ?></code></td>
                            <td><?php echo date('M d, Y g:i A', strtotime($row['updated_at'])); ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $row['id']; ?>" class="edit-btn">✏️ Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    
    <?php elseif ($action === 'edit'): ?>
        <?php
        $stmt = $conn->prepare("SELECT id, slug, title, content, meta_description FROM static_pages WHERE id = ?");
        $stmt->bind_param('i', $page_id);
        $stmt->execute();
        $page = $stmt->get_result()->fetch_assoc();
        
        if (!$page) {
            header('Location: ?action=list');
            exit;
        }
        ?>
        
        <div class="admin-header">
            <h2>✏️ Edit: <?php echo esc($page['title']); ?></h2>
        </div>
        
        <div class="edit-form">
            <form method="POST">
                <div class="form-group">
                    <label>Page Title</label>
                    <input type="text" name="title" value="<?php echo esc($page['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Page Slug (URL)</label>
                    <input type="text" name="slug" value="<?php echo esc($page['slug']); ?>" readonly style="background: #f5f5f5;">
                </div>
                
                <div class="form-group">
                    <label>Meta Description (for SEO)</label>
                    <input type="text" name="meta_description" value="<?php echo esc($page['meta_description']); ?>" placeholder="Brief description for search engines">
                </div>
                
                <div class="form-group">
                    <label>Page Content</label>
                    <textarea name="content" required><?php echo esc($page['content']); ?></textarea>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn-save">💾 Save Changes</button>
                    <a href="?action=list" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
