<?php
/**
 * Admin - AI Content Generator (Gemini API)
 * Uses Google Gemini API for content generation
 */

session_start();

require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$generated_content = '';
$error_message = '';

// Handle content generation
if (isset($_POST['action']) && $_POST['action'] === 'generate' && isset($_POST['prompt_id'])) {
    $prompt_id = (int)$_POST['prompt_id'];
    $business_type = trim($_POST['business_type'] ?? '');
    $keyword = trim($_POST['keyword'] ?? '');
    $service = trim($_POST['service'] ?? '');
    
    // Get prompt template
    $prompt_row = $conn->query("SELECT prompt FROM ai_prompts WHERE id = $prompt_id")->fetch_assoc();
    
    if ($prompt_row) {
        $template = $prompt_row['prompt'];
        
        // Replace variables
        $final_prompt = $template;
        $final_prompt = str_replace('{business_type}', $business_type, $final_prompt);
        $final_prompt = str_replace('{keyword}', $keyword, $final_prompt);
        $final_prompt = str_replace('{service}', $service, $final_prompt);
        
        // Call Gemini API
        $api_key = getenv('GEMINI_API_KEY');
        
        if (empty($api_key)) {
            $error_message = "⚠️ Gemini API key not configured. Set GEMINI_API_KEY environment variable.";
        } else {
            $response = callGeminiAPI($final_prompt, $api_key);
            
            if ($response['success']) {
                $generated_content = $response['content'];
            } else {
                $error_message = "Error: " . ($response['error'] ?? 'API call failed');
            }
        }
    }
}

// Call Gemini API
function callGeminiAPI($prompt, $api_key) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $api_key;
    
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 1024
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $json = json_decode($response, true);
        
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => true,
                'content' => $json['candidates'][0]['content']['parts'][0]['text']
            ];
        }
    }
    
    return [
        'success' => false,
        'error' => 'Failed to generate content'
    ];
}

// Get all prompts
$prompts = [];
$prompts_by_category = [];
$table_error = null;

try {
    $result = $conn->query("SELECT * FROM ai_prompts ORDER BY category, title");
    if ($result) {
        $prompts = $result->fetch_all(MYSQLI_ASSOC) ?? [];
    }
} catch (Exception $e) {
    // Try to create the table with sample prompts
    try {
        $conn->query("
            CREATE TABLE IF NOT EXISTS ai_prompts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                category VARCHAR(100) NOT NULL,
                prompt TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert sample prompts
        $sample_prompts = [
            ['Blog Post Introduction', 'content', 'Write an engaging blog post introduction for a {business_type} about {keyword}. Keep it under 150 words. Make it compelling and SEO-friendly.'],
            ['Meta Description', 'seo', 'Create a compelling meta description for a {business_type} website focused on {keyword}. Keep it under 160 characters.'],
            ['Social Media Post', 'social', 'Write an engaging social media post for {business_type} about {service}. Use relevant emojis and hashtags. Keep it under 280 characters.'],
            ['Product Description', 'content', 'Write a persuasive product description for a {business_type} offering {service} with focus on {keyword}. Include benefits and call-to-action.'],
            ['Email Subject Line', 'email', 'Create 5 compelling email subject lines for a {business_type} newsletter about {keyword}. Make them clickable and relevant.'],
            ['Landing Page Copy', 'content', 'Write compelling landing page copy for a {business_type} offering {service}. Include headline, subheading, benefits, and CTA.'],
            ['FAQ Answer', 'content', 'Create a helpful FAQ answer for customers of a {business_type} asking about {keyword}. Be clear, concise, and helpful.'],
            ['Google Business Description', 'seo', 'Write a compelling Google Business Profile description for a {business_type} specializing in {service}. Keep it under 750 characters.'],
        ];
        
        foreach ($sample_prompts as $prompt) {
            $title = $prompt[0];
            $category = $prompt[1];
            $template = $prompt[2];
            
            $stmt = $conn->prepare("INSERT INTO ai_prompts (title, category, prompt) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $title, $category, $template);
            $stmt->execute();
            $stmt->close();
        }
        
        // Fetch the newly created prompts
        $result = $conn->query("SELECT * FROM ai_prompts ORDER BY category, title");
        if ($result) {
            $prompts = $result->fetch_all(MYSQLI_ASSOC) ?? [];
        }
    } catch (Exception $create_error) {
        $table_error = "Could not create AI Prompts table. Please check database permissions.";
        $prompts = [];
    }
}

// Group by category
foreach ($prompts as $p) {
    $category = $p['category'] ?? 'other';
    if (!isset($prompts_by_category[$category])) {
        $prompts_by_category[$category] = [];
    }
    $prompts_by_category[$category][] = $p;
}

$page_title = "AI Content Generator - Admin";
require_once '../includes/header.php';
?>

<style>
    .generator-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .admin-header {
        margin-bottom: 30px;
    }

    .admin-header h1 {
        margin: 0;
        color: #0B1C3D;
    }

    .generator-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 30px;
        align-items: start;
    }

    .prompts-panel {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        height: fit-content;
    }

    .prompts-panel h3 {
        margin-top: 0;
        color: #0B1C3D;
        font-size: 16px;
        border-bottom: 2px solid #FF6A00;
        padding-bottom: 12px;
    }

    .prompt-category {
        margin-bottom: 20px;
    }

    .category-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #999;
        display: block;
        margin-bottom: 10px;
    }

    .prompt-btn {
        display: block;
        width: 100%;
        padding: 12px;
        margin-bottom: 8px;
        background: #f5f5f5;
        border: 2px solid transparent;
        border-radius: 6px;
        cursor: pointer;
        text-align: left;
        font-size: 13px;
        font-weight: 500;
        color: #333;
        transition: all 0.3s;
    }

    .prompt-btn:hover {
        background: #e8f5e9;
        border-color: #25D366;
    }

    .prompt-btn.active {
        background: #e8f5e9;
        border-color: #25D366;
        color: #25D366;
        font-weight: 600;
    }

    .generator-form {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .generator-form h3 {
        margin-top: 0;
        color: #0B1C3D;
        font-size: 16px;
        border-bottom: 2px solid #FF6A00;
        padding-bottom: 12px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 600;
        font-size: 14px;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        box-sizing: border-box;
    }

    .form-group input:focus {
        outline: none;
        border-color: #FF6A00;
        box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
    }

    .generate-btn {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
    }

    .generate-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .output-section {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-top: 30px;
    }

    .output-section h3 {
        margin-top: 0;
        color: #0B1C3D;
        font-size: 16px;
        border-bottom: 2px solid #25D366;
        padding-bottom: 12px;
    }

    .generated-text {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        line-height: 1.7;
        color: #333;
        margin: 15px 0;
        border-left: 4px solid #25D366;
    }

    .copy-btn {
        background: #25D366;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
    }

    .copy-btn:hover {
        background: #20B759;
    }

    .error-message {
        background: #ffebee;
        color: #c62828;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 4px solid #c62828;
    }

    .info-box {
        background: #e3f2fd;
        color: #1976d2;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 4px solid #1976d2;
        font-size: 13px;
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .generator-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="generator-container">
    <div class="admin-header">
        <h1>✨ AI Content Generator</h1>
        <p style="color: #666; margin-top: 5px;">Powered by Google Gemini API</p>
    </div>

    <?php if (!empty($error_message)): ?>
    <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($table_error)): ?>
    <div class="error-message"><?php echo $table_error; ?></div>
    <?php endif; ?>

    <div class="info-box">
        💡 <strong>How to use:</strong> Select a prompt template, fill in the details (business type, keyword, service), and click Generate. AI will create custom content for you.
    </div>

    <div class="generator-grid">
        <!-- Prompts Panel -->
        <div class="prompts-panel">
            <h3>📋 Templates</h3>
            
            <?php foreach ($prompts_by_category as $category => $category_prompts): ?>
            <div class="prompt-category">
                <span class="category-label"><?php echo ucfirst($category); ?></span>
                <?php foreach ($category_prompts as $prompt): ?>
                <button type="button" class="prompt-btn" onclick="selectPrompt(<?php echo $prompt['id']; ?>, '<?php echo esc($prompt['title']); ?>')">
                    <?php echo esc($prompt['title']); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Generator Form -->
        <div class="generator-form">
            <h3 id="promptTitle">Select a Template</h3>

            <form method="POST" id="generatorForm">
                <input type="hidden" name="action" value="generate">
                <input type="hidden" name="prompt_id" id="promptId" value="">

                <div class="form-group">
                    <label>Business Type / Industry</label>
                    <input type="text" name="business_type" placeholder="e.g., Software Company, Salon, Gym" id="businessType">
                </div>

                <div class="form-group">
                    <label>Keyword / Focus</label>
                    <input type="text" name="keyword" placeholder="e.g., Digital Marketing, Fitness" id="keyword">
                </div>

                <div class="form-group">
                    <label>Service (Optional)</label>
                    <input type="text" name="service" placeholder="e.g., SEO, Website Development" id="service">
                </div>

                <button type="submit" class="generate-btn" id="generateBtn" disabled>🤖 Generate Content</button>
            </form>
        </div>
    </div>

    <!-- Output Section -->
    <?php if (!empty($generated_content)): ?>
    <div class="output-section">
        <h3>✅ Generated Content</h3>
        <div class="generated-text"><?php echo nl2br(htmlspecialchars($generated_content)); ?></div>
        <button class="copy-btn" onclick="copyToClipboard()">📋 Copy to Clipboard</button>
    </div>
    <?php endif; ?>
</div>

<script>
function selectPrompt(promptId, promptTitle) {
    document.getElementById('promptId').value = promptId;
    document.getElementById('promptTitle').textContent = promptTitle;
    document.getElementById('generateBtn').disabled = false;
    
    // Update active button
    document.querySelectorAll('.prompt-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

function copyToClipboard() {
    const text = document.querySelector('.generated-text').textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('Content copied to clipboard!');
    });
}

// Validate form before submit
document.getElementById('generatorForm').addEventListener('submit', function(e) {
    if (!document.getElementById('promptId').value) {
        alert('Please select a template first');
        e.preventDefault();
    }
    if (!document.getElementById('businessType').value) {
        alert('Please enter a business type');
        e.preventDefault();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
