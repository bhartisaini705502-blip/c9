<?php
/**
 * Categories Page
 */

require '../config/db.php';
require '../includes/functions.php';
require '../includes/smart-display.php';

// Get all unique categories from types field
$result = $GLOBALS['conn']->query("
    SELECT DISTINCT types FROM extracted_businesses 
    WHERE business_status = 'OPERATIONAL' 
    LIMIT 100
");

$categories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['types'])) {
            // Split comma-separated types and add each as a category
            $types = explode(',', $row['types']);
            foreach ($types as $type) {
                $type = trim($type);
                if ($type) {
                    if (!isset($categories[$type])) {
                        $categories[$type] = 0;
                    }
                    $categories[$type]++;
                }
            }
        }
    }
}

// Sort by count descending
arsort($categories);
$categories = array_slice($categories, 0, 50); // Show top 50

$page_title = 'Browse Business Categories';
$meta_description = 'Browse all business categories. Find services by category type.';

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/smart-display.css">
</head>
<body>

<div class="container">
    <div class="smart-header" style="margin-bottom: 40px;">
        <div class="smart-header-content">
            <h1 class="smart-title">Browse Business Categories</h1>
            <p class="smart-subtitle">Find services by category type</p>
        </div>
    </div>

    <div class="businesses-grid" style="max-width: 1400px; margin: 0 auto; padding: 0 20px 40px;">
        <?php foreach ($categories as $cat => $count): ?>
            <a href="/pages/search.php?category=<?php echo urlencode($cat); ?>" style="text-decoration: none;">
                <div class="business-card" style="cursor: pointer; text-align: center; padding: 40px 20px;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📂</div>
                    <h3 class="card-title"><?php echo esc($cat); ?></h3>
                    <p style="color: #666; margin-top: 8px;"><?php echo $count; ?> businesses</p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script src="/assets/js/smart-display.js"></script>
</body>
</html>

<?php include '../includes/footer.php'; ?>
