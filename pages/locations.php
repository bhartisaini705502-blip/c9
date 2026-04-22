<?php
/**
 * Locations Page
 */

require '../config/db.php';
require '../includes/functions.php';
require '../includes/smart-display.php';

// Get all locations with business count
$locations = [];
$result = $GLOBALS['conn']->query("
    SELECT search_location, COUNT(*) as count FROM extracted_businesses 
    WHERE business_status = 'OPERATIONAL' AND search_location IS NOT NULL
    GROUP BY search_location 
    ORDER BY count DESC
    LIMIT 50
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

$page_title = 'Browse by Location';
$meta_description = 'Find businesses by location. Browse services in your city.';

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
            <h1 class="smart-title">Find Businesses by Location</h1>
            <p class="smart-subtitle">Discover services in your city</p>
        </div>
    </div>

    <div class="businesses-grid" style="max-width: 1400px; margin: 0 auto; padding: 0 20px 40px;">
        <?php foreach ($locations as $loc): ?>
            <a href="/pages/search.php?location=<?php echo urlencode($loc['search_location']); ?>" style="text-decoration: none;">
                <div class="business-card" style="cursor: pointer; text-align: center; padding: 40px 20px;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📍</div>
                    <h3 class="card-title"><?php echo esc($loc['search_location']); ?></h3>
                    <p style="color: #666; margin-top: 8px;"><?php echo $loc['count']; ?> businesses</p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script src="/assets/js/smart-display.js"></script>
</body>
</html>

<?php include '../includes/footer.php'; ?>
