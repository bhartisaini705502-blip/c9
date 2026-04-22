<?php
/**
 * Dynamic Location Page - SEO Optimized
 * URL: /pages/location-page.php?city=Delhi
 */

require '../config/db.php';
require '../includes/functions.php';

$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

if (empty($city)) {
    header('Location: /pages/locations.php');
    exit;
}

// Get businesses in this location
$stmt = $GLOBALS['conn']->prepare("
    SELECT * FROM extracted_businesses 
    WHERE business_status = 'OPERATIONAL' AND search_location = ? 
    ORDER BY verified DESC, rating DESC
    LIMIT 500
");
$stmt->bind_param('s', $city);
$stmt->execute();
$all_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Pagination
$total = count($all_results);
$perPage = 12;
$pagination = paginate($total, $perPage, $page);
$results = array_slice($all_results, $pagination['offset'], $perPage);

// SEO
$page_title = 'Best Services in ' . $city . ' | ConnectWith9';
$meta_description = "Find top-rated businesses and services in $city. Browse verified listings, read reviews, and connect with the best service providers in $city.";

include '../includes/header.php';
?>

<div class="container">
    <h1>Services in <?php echo esc($city); ?></h1>
    
    <!-- SEO Content Block -->
    <div style="background: #F5F7FA; padding: 20px; border-radius: 8px; margin-bottom: 30px; line-height: 1.8;">
        <p style="margin: 0; color: #333; font-size: 15px;">
            Discover the best <strong>businesses and services in <?php echo esc($city); ?></strong> with ConnectWith9. 
            Find verified service providers, read authentic customer reviews, and compare ratings all in one place. 
            Whether you're looking for restaurants, plumbers, salons, or any other service in <?php echo esc($city); ?>, 
            our directory helps you find the perfect match. Connect instantly with top-rated businesses near you.
        </p>
    </div>
    
    <p style="color: #666; margin-bottom: 20px;">Found <?php echo $total; ?> verified businesses in <?php echo esc($city); ?></p>

    <?php if (!empty($results)): ?>
        <div class="business-grid">
            <?php foreach ($results as $business): ?>
                <?php include '../pages/business-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['has_prev']): ?>
                    <a href="?city=<?php echo urlencode($city); ?>&page=1">« First</a>
                    <a href="?city=<?php echo urlencode($city); ?>&page=<?php echo $page - 1; ?>">‹ Prev</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($pagination['total_pages'], $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?city=<?php echo urlencode($city); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['has_next']): ?>
                    <a href="?city=<?php echo urlencode($city); ?>&page=<?php echo $page + 1; ?>">Next ›</a>
                    <a href="?city=<?php echo urlencode($city); ?>&page=<?php echo $pagination['total_pages']; ?>">Last »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p style="text-align: center; padding: 40px; font-size: 16px; color: #666;">
            No businesses found in <?php echo esc($city); ?> yet.
        </p>
    <?php endif; ?>
</div>

<?php include '../includes/claim-modal.php'; ?>
<?php include '../includes/footer.php'; ?>
