<?php
/**
 * Manage Businesses
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

// Get all businesses
$businesses = getRows("SELECT id, name, types as category, search_location as city, rating, formatted_phone_number, lat, lng FROM extracted_businesses ORDER BY id DESC LIMIT 100") ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Businesses - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #f9fafb;
            color: #2c3e50;
            min-height: 100vh;
        }

        .admin-header {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            padding: 30px 40px;
            box-shadow: 0 10px 30px rgba(11, 28, 61, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header-stat {
            background: rgba(255, 255, 255, 0.15);
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            flex-wrap: wrap;
            gap: 15px;
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

        .business-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .business-card {
            background: white;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .business-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FF6A00, #FFB347);
        }

        .business-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.12);
            border-color: #FF6A00;
        }

        .card-header {
            padding: 24px;
            flex-grow: 1;
        }

        .business-name {
            font-size: 18px;
            font-weight: 700;
            color: #0B1C3D;
            margin-bottom: 12px;
            line-height: 1.3;
            letter-spacing: -0.3px;
        }

        .business-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #7a8a9e;
        }

        .detail-label {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5a6c7d;
        }

        .detail-value {
            color: #2c3e50;
            font-weight: 600;
        }

        .rating-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            width: fit-content;
        }

        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e0f2fe;
            color: #0B1C3D;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            width: fit-content;
        }

        .city-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0fdf4;
            color: #166534;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            width: fit-content;
        }

        .card-footer {
            padding: 16px 24px;
            border-top: 1px solid #f3f4f6;
            background: #fafafa;
            display: flex;
            gap: 12px;
        }

        .action-btn {
            flex: 1;
            padding: 10px 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .action-btn.view {
            background: linear-gradient(135deg, #FF6A00, #FFB347);
            color: white;
        }

        .action-btn.view:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 106, 0, 0.3);
            background: linear-gradient(135deg, #E55A00, #FF9A00);
        }

        .action-btn.delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-btn.delete:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        .no-businesses {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: 14px;
            border: 2px dashed #e5e7eb;
        }

        .no-businesses p {
            font-size: 18px;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .no-businesses small {
            color: #d1d5db;
            font-size: 14px;
        }

        .stats-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* Responsive */
        @media (max-width: 1024px) {
            .admin-header {
                padding: 24px 30px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .admin-header h1 {
                font-size: 26px;
            }

            .business-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .admin-header {
                padding: 20px 24px;
            }

            .admin-container {
                padding: 30px 20px;
            }

            .business-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 18px;
            }

            .business-name {
                font-size: 16px;
            }

            .card-header {
                padding: 20px;
            }

            .card-footer {
                flex-direction: column;
                gap: 10px;
            }

            .action-btn {
                width: 100%;
            }

            .stats-bar {
                flex-direction: column;
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .admin-header {
                padding: 16px 18px;
            }

            .admin-header h1 {
                font-size: 20px;
            }

            .admin-container {
                padding: 20px 14px;
            }

            .page-header {
                flex-direction: column;
                margin-bottom: 25px;
            }

            .back-link {
                width: 100%;
                justify-content: center;
            }

            .business-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .business-name {
                font-size: 15px;
            }

            .card-header {
                padding: 18px;
            }

            .detail-row {
                font-size: 12px;
                flex-direction: column;
                gap: 4px;
            }

            .card-footer {
                padding: 12px 18px;
            }

            .action-btn {
                padding: 9px 10px;
                font-size: 12px;
            }

            .no-businesses {
                padding: 60px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div>
            <h1>📂 Manage Businesses</h1>
        </div>
        <div class="header-stat">Total Businesses: <?php echo count($businesses); ?></div>
    </div>

    <div class="admin-container">
        <div class="page-header">
            <a href="dashboard.php" class="back-link">← Back</a>
        </div>

        <?php if (!empty($businesses)): ?>
        <div class="stats-bar">
            <div class="stat-item">
                <div>
                    <div class="stat-label">Total Businesses</div>
                    <div class="stat-value"><?php echo count($businesses); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="business-grid">
            <?php if (empty($businesses)): ?>
                <div class="no-businesses">
                    <p>📭 No businesses found</p>
                    <small>Start by importing businesses from the import page</small>
                </div>
            <?php else: ?>
                <?php foreach ($businesses as $b): ?>
                    <div class="business-card">
                        <div class="card-header">
                            <div class="business-name"><?php echo esc($b['name'] ?? 'N/A'); ?></div>
                            <div class="business-details">
                                <div class="detail-row">
                                    <span class="category-badge">🏷️ <?php echo esc($b['category'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="city-badge">📍 <?php echo esc($b['city'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="rating-badge">⭐ <?php echo number_format(floatval($b['rating'] ?? 0), 1); ?>/5</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Phone</span>
                                    <span class="detail-value"><?php echo esc($b['formatted_phone_number'] ?? '—'); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">ID</span>
                                    <span class="detail-value">#<?php echo $b['id'] ?? '—'; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="business-insights.php?id=<?php echo $b['id']; ?>" class="action-btn view">📊 View</a>
                            <a href="delete-business.php?id=<?php echo $b['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this business?');">🗑 Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
