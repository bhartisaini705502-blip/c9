<?php
/**
 * Admin - Business AI Insights Page
 * Detailed analytics for individual businesses
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/ai-functions.php';

// Get business ID from URL
$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$business_id) {
    header('Location: businesses.php');
    exit;
}

// Get business data
$business = $conn->query("SELECT * FROM extracted_businesses WHERE id = $business_id")->fetch_assoc();

if (!$business) {
    $_SESSION['error'] = 'Business not found';
    header('Location: businesses.php');
    exit;
}

// Generate insights
$summary = generateBusinessSummary($business_id);
$sentiment = analyzeSentiment($business_id);
$keywords = extractTopKeywords($business_id);
$alerts = generateAlerts($business_id);
$suggestions = generateImprovementSuggestions($business_id);
$faqs = generateAIFAQs($business_id);

$page_title = "Business Insights - " . $business['name'];
require_once '../includes/header.php';
?>

<style>
    .insights-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .business-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: start;
    }

    .business-info h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
    }

    .business-meta {
        font-size: 14px;
        opacity: 0.9;
    }

    .business-meta p {
        margin: 5px 0;
    }

    .score-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    .score-value {
        font-size: 32px;
        font-weight: 700;
    }

    .score-label {
        font-size: 11px;
        text-transform: uppercase;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }

    .metric-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .metric-label {
        font-size: 12px;
        color: #999;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .metric-value {
        font-size: 28px;
        font-weight: 700;
        color: #0B1C3D;
    }

    .section {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 25px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #0B1C3D;
        margin: 0 0 20px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #FF6A00;
    }

    .sentiment-bars {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .sentiment-bar {
        text-align: center;
    }

    .sentiment-bar-label {
        font-size: 12px;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .bar {
        width: 100%;
        height: 20px;
        background: #eee;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 5px;
    }

    .bar-fill {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 11px;
        font-weight: 600;
    }

    .positive { background: #4caf50; }
    .neutral { background: #FF9A00; }
    .negative { background: #f44336; }

    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 10px;
        border-left: 4px solid;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .alert.critical {
        background: #ffebee;
        border-color: #f44336;
        color: #c62828;
    }

    .alert.warning {
        background: #fff3e0;
        border-color: #FF9A00;
        color: #e65100;
    }

    .alert.info {
        background: #e3f2fd;
        border-color: #2196F3;
        color: #1565c0;
    }

    .keyword-tag {
        display: inline-block;
        background: #0B1C3D;
        color: white;
        padding: 8px 12px;
        border-radius: 20px;
        margin: 5px 5px 5px 0;
        font-size: 12px;
    }

    .suggestion-card {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 15px;
    }

    .suggestion-title {
        font-weight: 700;
        color: #0B1C3D;
        margin-bottom: 8px;
    }

    .suggestion-priority {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .priority-high { background: #ffebee; color: #c62828; }
    .priority-medium { background: #fff3e0; color: #e65100; }
    .priority-low { background: #e3f2fd; color: #1565c0; }

    .faq-item {
        border: 1px solid #ddd;
        margin-bottom: 10px;
        border-radius: 4px;
        overflow: hidden;
    }

    .faq-question {
        background: #f5f5f5;
        padding: 15px;
        cursor: pointer;
        font-weight: 600;
        user-select: none;
    }

    .faq-answer {
        padding: 15px;
        background: white;
        color: #666;
        display: none;
    }

    .faq-item.open .faq-answer {
        display: block;
    }

    .empty-state {
        text-align: center;
        color: #999;
        padding: 30px;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .business-header {
            flex-direction: column;
        }

        .score-circle {
            margin-top: 20px;
        }
    }
</style>

<div class="insights-container">
    <!-- Back Button -->
    <div style="margin-bottom: 20px;">
        <a href="businesses.php" style="color: #0B1C3D; text-decoration: none; font-weight: 600;">← Back to Businesses</a>
    </div>

    <!-- Business Header -->
    <div class="business-header">
        <div class="business-info">
            <h1><?php echo esc($business['name']); ?></h1>
            <div class="business-meta">
                <p><strong>Category:</strong> <?php echo esc($business['types']); ?></p>
                <p><strong>Location:</strong> <?php echo esc($business['search_location']); ?></p>
                <p><strong>Rating:</strong> ⭐ <?php echo $business['rating']; ?> (<?php echo $business['user_ratings_total']; ?> reviews)</p>
                <p><strong>Verified:</strong> <?php echo $business['verified'] ? '✓ Yes' : '✗ No'; ?></p>
            </div>
        </div>
        <div class="score-circle">
            <div class="score-value"><?php echo $summary['performance_score']; ?></div>
            <div class="score-label">Performance</div>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-label">Rating</div>
            <div class="metric-value"><?php echo $business['rating']; ?>⭐</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Total Reviews</div>
            <div class="metric-value"><?php echo $business['user_ratings_total']; ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Sentiment</div>
            <div class="metric-value"><?php echo $sentiment['summary']; ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Verification</div>
            <div class="metric-value"><?php echo $business['verified'] ? '✓' : '✗'; ?></div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($alerts)): ?>
    <div class="section">
        <h2 class="section-title">⚠️ Alerts & Notifications</h2>
        <?php foreach ($alerts as $alert): ?>
        <div class="alert <?php echo $alert['type']; ?>">
            <div>
                <strong><?php echo $alert['message']; ?></strong>
                <div style="font-size: 12px; margin-top: 5px;"><?php echo $alert['action']; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Sentiment Analysis -->
    <div class="section">
        <h2 class="section-title">😊 Review Sentiment Analysis</h2>
        <div class="sentiment-bars">
            <div class="sentiment-bar">
                <div class="sentiment-bar-label">Positive</div>
                <div class="bar">
                    <div class="bar-fill positive" style="width: <?php echo $sentiment['positive']; ?>%;">
                        <?php echo $sentiment['positive']; ?>%
                    </div>
                </div>
            </div>
            <div class="sentiment-bar">
                <div class="sentiment-bar-label">Neutral</div>
                <div class="bar">
                    <div class="bar-fill neutral" style="width: <?php echo $sentiment['neutral']; ?>%;">
                        <?php echo $sentiment['neutral']; ?>%
                    </div>
                </div>
            </div>
            <div class="sentiment-bar">
                <div class="sentiment-bar-label">Negative</div>
                <div class="bar">
                    <div class="bar-fill negative" style="width: <?php echo $sentiment['negative']; ?>%;">
                        <?php echo $sentiment['negative']; ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Keywords -->
    <div class="section">
        <h2 class="section-title">🔑 Top Keywords & Topics</h2>
        <?php if (!empty($keywords)): ?>
        <div>
            <?php foreach ($keywords as $kw): ?>
            <span class="keyword-tag"><?php echo esc($kw['keyword']); ?></span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">No keywords found</div>
        <?php endif; ?>
    </div>

    <!-- Suggestions -->
    <div class="section">
        <h2 class="section-title">💡 Improvement Suggestions</h2>
        <?php foreach ($suggestions as $suggestion): ?>
        <div class="suggestion-card">
            <span class="suggestion-priority priority-<?php echo $suggestion['priority']; ?>">
                <?php echo strtoupper($suggestion['priority']); ?> PRIORITY
            </span>
            <div class="suggestion-title"><?php echo esc($suggestion['title']); ?></div>
            <p style="margin: 8px 0 5px 0; color: #666; font-size: 13px;">
                <?php echo esc($suggestion['description']); ?>
            </p>
            <p style="margin: 0; color: #25D366; font-size: 12px; font-weight: 600;">
                💰 Impact: <?php echo esc($suggestion['impact']); ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- AI FAQs -->
    <div class="section">
        <h2 class="section-title">❓ AI-Generated FAQs</h2>
        <div>
            <?php foreach ($faqs as $index => $faq): ?>
            <div class="faq-item" onclick="this.classList.toggle('open')">
                <div class="faq-question">
                    📌 <?php echo esc($faq['question']); ?>
                </div>
                <div class="faq-answer">
                    <?php echo esc($faq['answer']); ?>
                    <div style="margin-top: 10px; font-size: 11px; color: #999;">
                        👍 <?php echo $faq['helpful_count']; ?> found this helpful
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Summary -->
    <div class="section">
        <h2 class="section-title">📄 Business Summary</h2>
        <p style="font-size: 16px; line-height: 1.6; color: #333;">
            <?php echo esc($summary['summary_text']); ?>
        </p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
