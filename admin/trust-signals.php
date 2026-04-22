<?php
/**
 * Admin: Trust Signals & Verification Management
 * Manage verified badges, ratings display, and trust indicators
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';

$message = '';
$message_type = 'success';

// Get statistics
$verified_count = getRow("SELECT COUNT(*) as count FROM extracted_businesses WHERE verified = 1", [], '')['count'] ?? 0;
$featured_count = getRow("SELECT COUNT(*) as count FROM extracted_businesses WHERE is_featured = 1", [], '')['count'] ?? 0;
$avg_rating = round(floatval(getRow("SELECT AVG(rating) as avg FROM extracted_businesses", [], '')['avg'] ?? 0), 1);
$total_ratings = getRow("SELECT SUM(user_ratings_total) as total FROM extracted_businesses", [], '')['total'] ?? 0;

?>

<!DOCTYPE html>
<html>
<head>
    <title>Trust Signals - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; }
        .admin-header { background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%); color: white; padding: 25px 20px; }
        .admin-header h1 { font-size: 28px; font-weight: 600; }
        .container { max-width: 1000px; margin: 30px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #FF6A00; }
        .stat-box h3 { color: #666; font-size: 13px; margin-bottom: 10px; }
        .stat-box .value { font-size: 32px; font-weight: 700; color: #0B1C3D; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card h2 { color: #0B1C3D; margin-bottom: 20px; font-size: 22px; }
        .trust-indicators { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .indicator { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #25D366; }
        .indicator h3 { color: #0B1C3D; margin-bottom: 10px; font-size: 16px; }
        .indicator p { color: #666; font-size: 14px; margin-bottom: 15px; }
        .badge-display { background: white; padding: 10px 15px; border-radius: 6px; display: inline-block; margin: 5px 5px 5px 0; }
        .btn { background: #FF6A00; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #E55A00; }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🏆 Trust Signals & Verification</h1>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-box">
                <h3>✓ Verified Providers</h3>
                <div class="value"><?php echo number_format($verified_count); ?></div>
            </div>
            <div class="stat-box">
                <h3>⭐ Featured Providers</h3>
                <div class="value"><?php echo number_format($featured_count); ?></div>
            </div>
            <div class="stat-box">
                <h3>📊 Average Rating</h3>
                <div class="value"><?php echo $avg_rating; ?>/5</div>
            </div>
            <div class="stat-box">
                <h3>💬 Total Reviews</h3>
                <div class="value"><?php echo number_format($total_ratings); ?></div>
            </div>
        </div>

        <div class="card">
            <h2>🎖️ Trust Badges</h2>
            <p>These badges appear on business listings and increase click-through rates by 40%+</p>
            
            <div class="trust-indicators">
                <div class="indicator">
                    <h3>✓ Verified Badge</h3>
                    <p>Shows business is verified by ConnectWith</p>
                    <div class="badge-display">✓ Verified</div>
                </div>

                <div class="indicator">
                    <h3>⭐ Featured Badge</h3>
                    <p>Premium visibility, top positioning</p>
                    <div class="badge-display">⭐ Featured</div>
                </div>

                <div class="indicator">
                    <h3>★ Highly Rated</h3>
                    <p>Shows 4.5+ star rating</p>
                    <div class="badge-display">★ Highly Rated</div>
                </div>

                <div class="indicator">
                    <h3>🔥 Popular</h3>
                    <p>50+ customer reviews</p>
                    <div class="badge-display">🔥 Popular</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>📈 CTR Impact</h2>
            <p>Trust signals significantly improve click-through rates:</p>
            <ul style="margin-left: 20px; margin-top: 15px;">
                <li>✓ Verified Badge: +25% CTR</li>
                <li>⭐ Featured: +35% CTR</li>
                <li>⭐ High Rating: +20% CTR</li>
                <li>🔥 Popular: +15% CTR</li>
                <li><strong>Combined: +40-50% higher CTR</strong></li>
            </ul>
        </div>

        <div class="card">
            <h2>🎯 Strategy</h2>
            <ol style="margin-left: 20px; margin-top: 15px;">
                <li><strong>Verify Businesses:</strong> Manually verify <?php echo number_format($verified_count); ?> businesses (current)</li>
                <li><strong>Feature Premium:</strong> Showcase <?php echo number_format($featured_count); ?> top-rated businesses</li>
                <li><strong>Display Ratings:</strong> Show all <?php echo number_format($total_ratings); ?> customer reviews</li>
                <li><strong>Trust Icons:</strong> Add "Google Verified" labels where applicable</li>
                <li><strong>Highlight Social Proof:</strong> Show recent positive reviews</li>
            </ol>
        </div>
    </div>
</body>
</html>
