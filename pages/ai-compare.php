<?php
/**
 * AI Business Comparison Tool
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

$page_title = 'Compare Businesses | ConnectWith9';
$meta_description = 'Compare 2-3 businesses side-by-side to make the best choice.';

$id1 = $_GET['id1'] ?? '';
$id2 = $_GET['id2'] ?? '';
$id3 = $_GET['id3'] ?? '';

$business1 = null;
$business2 = null;
$business3 = null;

if (!empty($id1)) {
    $business1 = getBusinessForComparison((int)$id1);
}
if (!empty($id2)) {
    $business2 = getBusinessForComparison((int)$id2);
}
if (!empty($id3)) {
    $business3 = getBusinessForComparison((int)$id3);
}

include '../includes/header.php';
?>

<style>
.compare-container {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 20px 40px;
}

.compare-header {
    text-align: center;
    margin-bottom: 30px;
}

.compare-header h1 {
    color: #0B1C3D;
    margin: 0 0 10px 0;
}

.search-box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.search-inputs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    align-items: flex-end;
}

.search-inputs select {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    width: 100%;
}

.search-inputs button {
    padding: 12px 25px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.comparison-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.business-compare-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.compare-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    text-align: center;
}

.compare-card-header h2 {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.compare-card-body {
    padding: 20px;
}

.compare-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.compare-row:last-child {
    border-bottom: none;
}

.compare-label {
    font-weight: 600;
    color: #666;
    font-size: 13px;
}

.compare-value {
    color: #0B1C3D;
    font-weight: 600;
    text-align: right;
}

.compare-rating {
    color: #FFB800;
    font-size: 14px;
}

.verdict {
    background: #E3F2FD;
    padding: 20px;
    border-radius: 10px;
    margin-top: 30px;
    border-left: 4px solid #667eea;
}

.verdict h3 {
    margin: 0 0 10px 0;
    color: #0B1C3D;
}

.verdict p {
    margin: 0;
    color: #333;
    line-height: 1.6;
}

.feature-comparison {
    margin-top: 30px;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.feature-comparison h3 {
    color: #0B1C3D;
    margin: 0 0 20px 0;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.feature-item {
    background: #F9F9F9;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.feature-item h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 14px;
}

.feature-item p {
    margin: 0;
    color: #666;
    font-size: 13px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state h3 {
    color: #666;
    margin: 0 0 10px 0;
}
</style>

<div class="compare-container">
    <div class="compare-header">
        <h1>🔄 Compare Businesses</h1>
        <p>Select 2-3 businesses to compare features and ratings</p>
    </div>

    <div class="search-box">
        <form method="GET" class="search-inputs">
            <select name="id1" onchange="this.form.submit()">
                <option value="">Select Business 1</option>
                <?php listBusinessOptions($id1); ?>
            </select>
            <select name="id2" onchange="this.form.submit()">
                <option value="">Select Business 2</option>
                <?php listBusinessOptions($id2); ?>
            </select>
            <select name="id3" onchange="this.form.submit()">
                <option value="">Select Business 3 (Optional)</option>
                <?php listBusinessOptions($id3); ?>
            </select>
        </form>
    </div>

    <?php if ($business1 && $business2): ?>
    <div class="comparison-grid">
        <?php if ($business1): ?>
        <div class="business-compare-card">
            <div class="compare-card-header">
                <h2><?php echo esc($business1['name']); ?></h2>
            </div>
            <div class="compare-card-body">
                <div class="compare-row">
                    <span class="compare-label">Rating</span>
                    <span class="compare-value compare-rating">
                        <?php echo str_repeat('★', (int)$business1['rating']); ?> <?php echo number_format($business1['rating'], 1); ?>/5
                    </span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Reviews</span>
                    <span class="compare-value"><?php echo $business1['reviews']; ?></span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Category</span>
                    <span class="compare-value"><?php echo esc($business1['category']); ?></span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Location</span>
                    <span class="compare-value"><?php echo esc($business1['location']); ?></span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Price Level</span>
                    <span class="compare-value">
                        <?php 
                        $symbols = ['?', '₹', '₹₹', '₹₹₹', '₹₹₹₹'];
                        echo $symbols[$business1['price_level'] ?? 0];
                        ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($business2): ?>
        <div class="business-compare-card">
            <div class="compare-card-header">
                <h2><?php echo esc($business2['name']); ?></h2>
            </div>
            <div class="compare-card-body">
                <div class="compare-row">
                    <span class="compare-label">Rating</span>
                    <span class="compare-value compare-rating">
                        <?php echo str_repeat('★', (int)$business2['rating']); ?> <?php echo number_format($business2['rating'], 1); ?>/5
                    </span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Reviews</span>
                    <span class="compare-value"><?php echo $business2['reviews']; ?></span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Category</span>
                    <span class="compare-value"><?php echo esc($business2['category']); ?></span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Location</span>
                    <span class="compare-value"><?php echo esc($business2['location']); ?></span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Price Level</span>
                    <span class="compare-value">
                        <?php 
                        $symbols = ['?', '₹', '₹₹', '₹₹₹', '₹₹₹₹'];
                        echo $symbols[$business2['price_level'] ?? 0];
                        ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($business3): ?>
        <div class="business-compare-card">
            <div class="compare-card-header">
                <h2><?php echo esc($business3['name']); ?></h2>
            </div>
            <div class="compare-card-body">
                <div class="compare-row">
                    <span class="compare-label">Rating</span>
                    <span class="compare-value compare-rating">
                        <?php echo str_repeat('★', (int)$business3['rating']); ?> <?php echo number_format($business3['rating'], 1); ?>/5
                    </span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Reviews</span>
                    <span class="compare-value"><?php echo $business3['reviews']; ?></span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Category</span>
                    <span class="compare-value"><?php echo esc($business3['category']); ?></span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Location</span>
                    <span class="compare-value"><?php echo esc($business3['location']); ?></span>
                </div>
                <div class="compare-row">
                    <span class="compare-label">Price Level</span>
                    <span class="compare-value">
                        <?php 
                        $symbols = ['?', '₹', '₹₹', '₹₹₹', '₹₹₹₹'];
                        echo $symbols[$business3['price_level'] ?? 0];
                        ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($business1 && $business2): ?>
    <div class="verdict">
        <h3>🎯 Verdict</h3>
        <p>
            <?php
            $winner = $business1['rating'] >= $business2['rating'] ? $business1 : $business2;
            echo esc($winner['name']) . " has the higher rating (" . number_format($winner['rating'], 1) . "/5) and is the better choice based on customer reviews.";
            ?>
        </p>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <h3>📊 Select Businesses to Compare</h3>
        <p>Choose 2 or more businesses from the dropdowns above to see a detailed comparison.</p>
    </div>
    <?php endif; ?>
</div>

<?php
function listBusinessOptions($selectedId = null) {
    global $conn;
    
    $result = $conn->query("SELECT id, name FROM extracted_businesses WHERE business_status = 'OPERATIONAL' ORDER BY rating DESC LIMIT 50");
    
    while ($row = $result->fetch_assoc()) {
        $selected = ($row['id'] == $selectedId) ? 'selected' : '';
        echo "<option value=\"{$row['id']}\" $selected>" . esc($row['name']) . "</option>";
    }
}

function getBusinessForComparison($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, name, types, search_location, rating, user_ratings_total as reviews, price_level FROM extracted_businesses WHERE id = ? AND business_status = 'OPERATIONAL'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $types = array_map('trim', explode(',', $result['types']));
        $result['category'] = $types[0] ?? 'Business';
        $result['location'] = $result['search_location'];
    }
    
    return $result;
}
?>

<?php include '../includes/footer.php'; ?>
