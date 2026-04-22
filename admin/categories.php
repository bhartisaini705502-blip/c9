<?php
/**
 * Manage Categories
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

// Get all businesses with their types (not just distinct)
$categories_raw = getRows("SELECT types FROM extracted_businesses WHERE types IS NOT NULL AND types != '' ORDER BY types", [], '') ?? [];

// Process categories - split by comma and count accurately
$categories_set = [];
foreach ($categories_raw as $row) {
    $types = explode(',', $row['types']);
    foreach ($types as $type) {
        $type = trim($type);
        if (!empty($type)) {
            if (!isset($categories_set[$type])) {
                $categories_set[$type] = 0;
            }
            $categories_set[$type]++;
        }
    }
}

// Sort by count descending, then by name
arsort($categories_set);
$total_categories = count($categories_set);
$grid_columns = $total_categories >= 9 ? 4 : ($total_categories >= 3 ? 3 : 1);

// Category icons mapping
$category_icons = [
    'Hotel' => '🏨', 'Restaurant' => '🍽️', 'Cafe' => '☕', 'Bar' => '🍺',
    'Shop' => '🛍️', 'Grocery' => '🛒', 'Bakery' => '🥐', 'Pizza' => '🍕',
    'Pizza Restaurant' => '🍕', 'Burger' => '🍔', 'Chicken' => '🍗', 'Desserts' => '🍰',
    'Health' => '🏥', 'Medical' => '⚕️', 'Pharmacy' => '💊', 'Gym' => '💪',
    'Salon' => '💇', 'Spa' => '💆', 'Beauty' => '💄', 'Hair' => '✂️',
    'Education' => '🎓', 'School' => '🏫', 'College' => '📚', 'Tuition' => '📖',
    'Bank' => '🏦', 'Finance' => '💰', 'Insurance' => '🛡️', 'Real Estate' => '🏠',
    'Travel' => '✈️', 'Tour' => '🗺️', 'Car Rental' => '🚗',
    'Auto' => '🔧', 'Car' => '🚙', 'Garage' => '🔩', 'Gas Station' => '⛽',
    'Clothing' => '👕', 'Fashion' => '👗', 'Shoe' => '👞', 'Jewelry' => '💎',
    'Electronics' => '📱', 'Computer' => '💻', 'Mobile' => '📞', 'Gaming' => '🎮',
    'Sports' => '⚽', 'Fitness' => '🏋️', 'Yoga' => '🧘', 'Dance' => '💃',
    'Entertainment' => '🎭', 'Movie' => '🎬', 'Music' => '🎵', 'Theater' => '🎪',
    'Garden' => '🌱', 'Nursery' => '🌿', 'Landscape' => '🏞️', 'Plant' => '🌾',
    'Wedding' => '💒', 'Event' => '🎉', 'Catering' => '🍽️', 'Photography' => '📸',
    'Cleaning' => '🧹', 'Laundry' => '🧺', 'Pest' => '🐛', 'Plumbing' => '🔨',
    'Electrical' => '⚡', 'Construction' => '🏗️', 'Painting' => '🎨', 'Repair' => '🔧',
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #f9fafb;
            color: #2c3e50;
        }

        .admin-header {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            padding: 28px 40px;
            box-shadow: 0 8px 24px rgba(11, 28, 61, 0.15);
        }

        .admin-header h1 {
            font-size: 30px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .admin-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #0B1C3D;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            padding: 10px 16px;
            border-radius: 8px;
            background: white;
            border: 1px solid #e5e7eb;
        }

        .back-link:hover {
            color: #FF6A00;
            border-color: #FF6A00;
            transform: translateX(-3px);
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .stat-badge {
            background: white;
            padding: 12px 24px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .stat-label {
            color: #7a8a9e;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            color: #0B1C3D;
            font-size: 24px;
            font-weight: 800;
        }

        .stat-value.orange {
            color: #FF6A00;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            align-items: stretch;
        }

        .category-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            min-height: 100%;
        }

        .category-card:hover {
            border-color: #FF6A00;
            box-shadow: 0 12px 24px rgba(11, 28, 61, 0.1);
            transform: translateY(-4px);
        }

        .card-header {
            padding: 28px 24px;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
        }

        .card-icon {
            font-size: 44px;
            line-height: 1;
        }

        .card-name {
            font-size: 16px;
            font-weight: 700;
            color: #0B1C3D;
            letter-spacing: -0.3px;
        }

        .card-count {
            font-size: 28px;
            font-weight: 800;
            color: #FF6A00;
        }

        .card-footer {
            padding: 16px 24px;
            border-top: 1px solid #f3f4f6;
            background: #fafafa;
        }

        .card-footer a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 10px 12px;
            background: linear-gradient(135deg, #FF6A00, #FFB347);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            gap: 6px;
            border: none;
            cursor: pointer;
        }

        .card-footer a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 106, 0, 0.2);
            background: linear-gradient(135deg, #E55A00, #FF9A00);
        }

        .no-categories {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
        }

        .no-categories p {
            font-size: 18px;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .no-categories small {
            color: #d1d5db;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-header {
                padding: 24px 20px;
            }

            .admin-header h1 {
                font-size: 24px;
            }

            .admin-container {
                padding: 30px 20px;
            }

            .header-section {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 30px;
                gap: 15px;
            }

            .stats-bar {
                width: 100%;
            }

            .stat-badge {
                flex: 1;
                min-width: 140px;
            }

            .category-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }

            .card-header {
                padding: 20px 16px;
            }

            .card-icon {
                font-size: 36px;
            }

            .card-name {
                font-size: 14px;
            }

            .card-count {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .admin-header {
                padding: 20px 16px;
            }

            .admin-header h1 {
                font-size: 20px;
            }

            .admin-container {
                padding: 20px 14px;
            }

            .back-link {
                width: 100%;
                justify-content: center;
            }

            .stats-bar {
                width: 100%;
                gap: 12px;
            }

            .stat-badge {
                flex: 1;
                padding: 10px 12px;
            }

            .stat-label {
                font-size: 11px;
            }

            .stat-value {
                font-size: 20px;
            }

            .category-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .card-header {
                padding: 18px 14px;
                gap: 8px;
            }

            .card-icon {
                font-size: 32px;
            }

            .card-name {
                font-size: 13px;
            }

            .card-count {
                font-size: 20px;
            }

            .card-footer {
                padding: 12px 14px;
            }

            .card-footer a {
                padding: 9px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🏷️ Manage Categories</h1>
    </div>

    <div class="admin-container">
        <div class="header-section">
            <a href="dashboard.php" class="back-link">← Back</a>
            <div class="stats-bar">
                <div class="stat-badge">
                    <span class="stat-label">Total Categories</span>
                    <span class="stat-value"><?php echo count($categories_set); ?></span>
                </div>
                <div class="stat-badge">
                    <span class="stat-label">Total Businesses</span>
                    <span class="stat-value orange"><?php echo array_sum($categories_set); ?></span>
                </div>
            </div>
        </div>

        <div class="category-grid">
            <?php if (empty($categories_set)): ?>
                <div class="no-categories">
                    <p>📭 No categories found</p>
                    <small>Start by importing businesses to populate categories</small>
                </div>
            <?php else: ?>
                <?php foreach ($categories_set as $category => $count): ?>
                    <div class="category-card">
                        <div class="card-header">
                            <div class="card-icon"><?php echo $category_icons[$category] ?? '🏢'; ?></div>
                            <div class="card-name"><?php echo esc($category); ?></div>
                            <div class="card-count"><?php echo $count; ?></div>
                        </div>
                        <div class="card-footer">
                            <a href="/pages/search.php?category=<?php echo urlencode($category); ?>" target="_blank">View →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
