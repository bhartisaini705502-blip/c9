<?php
/**
 * Dynamic Static Pages - About, Contact, Terms, Privacy
 */

require_once '../config/db.php';
require_once '../includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$allowed_slugs = ['about', 'contact', 'terms', 'privacy'];

if (!in_array($slug, $allowed_slugs)) {
    header('Location: /');
    exit;
}

// Get page content from database
$stmt = $conn->prepare("SELECT title, content, meta_description FROM static_pages WHERE slug = ?");
$stmt->bind_param('s', $slug);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();

if (!$page) {
    header('Location: /');
    exit;
}

$page_title = $page['title'];
$meta_description = $page['meta_description'] ?? $page['title'];

include '../includes/header.php';
?>

<style>
    .page-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 20px;
        border-radius: 8px;
        margin-bottom: 40px;
        text-align: center;
    }
    
    .page-header h1 {
        margin: 0;
        font-size: 36px;
    }
    
    .page-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        line-height: 1.8;
        color: #333;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .page-content h2 {
        color: #667eea;
        margin-top: 30px;
        margin-bottom: 15px;
    }
    
    .page-content h3 {
        color: #764ba2;
        margin-top: 20px;
        margin-bottom: 10px;
    }
    
    .page-content p {
        margin-bottom: 15px;
    }
    
    .page-content ul, .page-content ol {
        margin-left: 20px;
        margin-bottom: 15px;
    }
    
    .page-content li {
        margin-bottom: 8px;
    }
</style>

<div class="page-container">
    <div class="page-header">
        <h1><?php echo esc($page['title']); ?></h1>
    </div>
    
    <div class="page-content">
        <?php echo nl2br(esc($page['content'])); ?>
        
        <?php if ($slug === 'contact'): ?>
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            <h2 style="margin-top: 30px;">📋 Send us a Message</h2>
            
            <div id="contactForm" style="max-width: 600px; margin-top: 20px;">
                <form onsubmit="submitContactForm(event)">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Name *</label>
                        <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Email *</label>
                        <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Phone</label>
                        <input type="tel" name="phone" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Subject *</label>
                        <input type="text" name="subject" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Message *</label>
                        <textarea name="message" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; min-height: 150px; box-sizing: border-box; font-family: Arial, sans-serif;"></textarea>
                    </div>
                    
                    <button type="submit" style="background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 15px;">📧 Send Message</button>
                </form>
                
                <div id="formMessage" style="margin-top: 15px; padding: 15px; border-radius: 4px; display: none;"></div>
            </div>
            
            <script>
                function submitContactForm(e) {
                    e.preventDefault();
                    const form = e.target;
                    const formData = new FormData(form);
                    const messageDiv = document.getElementById('formMessage');
                    
                    fetch('/pages/contact-submit.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        messageDiv.style.display = 'block';
                        if (data.success) {
                            messageDiv.style.background = '#d4edda';
                            messageDiv.style.color = '#155724';
                            messageDiv.textContent = data.message;
                            form.reset();
                        } else {
                            messageDiv.style.background = '#f8d7da';
                            messageDiv.style.color = '#721c24';
                            messageDiv.textContent = data.error || (data.errors ? data.errors.join(', ') : 'Error submitting form');
                        }
                    })
                    .catch(err => {
                        messageDiv.style.display = 'block';
                        messageDiv.style.background = '#f8d7da';
                        messageDiv.style.color = '#721c24';
                        messageDiv.textContent = 'Error submitting form. Please try again.';
                    });
                }
            </script>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
