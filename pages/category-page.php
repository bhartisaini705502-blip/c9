<?php
/**
 * Dynamic Category Page - SEO Optimized
 * URL: /pages/category-page.php?slug=restaurant
 */

require '../config/db.php';
require '../includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

if (empty($slug)) {
    header('Location: /pages/categories.php');
    exit;
}

// Get category name from slug
$category_name = str_replace('-', ' ', $slug);
$category_name = ucwords($category_name);

// Get businesses in this category
$stmt = $GLOBALS['conn']->prepare("
    SELECT * FROM extracted_businesses 
    WHERE business_status = 'OPERATIONAL' AND types LIKE ? 
    ORDER BY verified DESC, rating DESC
    LIMIT 500
");
$search_term = '%' . $category_name . '%';
$stmt->bind_param('s', $search_term);
$stmt->execute();
$all_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Pagination
$total = count($all_results);
$perPage = 12;
$pagination = paginate($total, $perPage, $page);
$results = array_slice($all_results, $pagination['offset'], $perPage);

// SEO
$page_title = $category_name . ' - Find Best Services | ConnectWith9';
$meta_description = "Find top-rated $category_name services in your area. Browse verified listings, compare ratings, and connect with the best $category_name providers.";

include '../includes/header.php';
?>

<div class="container">
    <h1><?php echo esc($category_name); ?></h1>
    
    <!-- SEO Content Block -->
    <div style="background: #F5F7FA; padding: 20px; border-radius: 8px; margin-bottom: 30px; line-height: 1.8;">
        <p style="margin: 0; color: #333; font-size: 15px;">
            Looking for the best <strong><?php echo esc($category_name); ?></strong> in your area? 
            ConnectWith9 helps you discover top-rated <?php echo esc($category_name); ?> providers with verified reviews, 
            ratings, and instant contact options. Compare the best services, read customer reviews, and connect with 
            trusted <strong><?php echo esc($category_name); ?></strong> businesses near you. All listings are verified and 
            updated regularly to ensure you get the most accurate information.
        </p>
    </div>
    
    <p style="color: #666; margin-bottom: 20px;">Found <?php echo $total; ?> verified <?php echo esc($category_name); ?> services</p>

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
                    <a href="?slug=<?php echo urlencode($slug); ?>&page=1">« First</a>
                    <a href="?slug=<?php echo urlencode($slug); ?>&page=<?php echo $page - 1; ?>">‹ Prev</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($pagination['total_pages'], $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?slug=<?php echo urlencode($slug); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['has_next']): ?>
                    <a href="?slug=<?php echo urlencode($slug); ?>&page=<?php echo $page + 1; ?>">Next ›</a>
                    <a href="?slug=<?php echo urlencode($slug); ?>&page=<?php echo $pagination['total_pages']; ?>">Last »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p style="text-align: center; padding: 40px; font-size: 16px; color: #666;">
            No <?php echo esc($category_name); ?> services found yet.
        </p>
    <?php endif; ?>
</div>

<?php include '../includes/claim-modal.php'; ?>
<?php include '../includes/footer.php'; ?>
