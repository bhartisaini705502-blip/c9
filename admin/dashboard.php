<?php
/**
 * Admin Dashboard
 */

session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

// Get statistics
$totalBusinesses = getRow("SELECT COUNT(*) as count FROM extracted_businesses", [], '')['count'] ?? 0;
$totalCategories = getRow("SELECT COUNT(DISTINCT types) as count FROM extracted_businesses", [], '')['count'] ?? 0;
$totalLocations = getRow("SELECT COUNT(DISTINCT search_location) as count FROM extracted_businesses", [], '')['count'] ?? 0;
$avgRating = round(floatval(getRow("SELECT AVG(rating) as avg FROM extracted_businesses", [], '')['avg'] ?? 0), 1);

// Get pending content counts
$pendingEdits = 0;
$pendingUpdates = 0;
$pendingServices = 0;
$pendingOffers = 0;
$pendingDescriptions = 0;

try {
    $pendingEdits = getRow("SELECT COUNT(*) as count FROM business_edits WHERE edit_status = 'pending'", [], '')['count'] ?? 0;
} catch (Exception $e) {}

try {
    $pendingUpdates = getRow("SELECT COUNT(*) as count FROM business_updates WHERE status = 'pending'", [], '')['count'] ?? 0;
} catch (Exception $e) {}

try {
    $pendingServices = getRow("SELECT COUNT(*) as count FROM business_services WHERE status = 'pending'", [], '')['count'] ?? 0;
} catch (Exception $e) {}

try {
    $pendingOffers = getRow("SELECT COUNT(*) as count FROM business_offers WHERE status = 'pending'", [], '')['count'] ?? 0;
} catch (Exception $e) {}

try {
    $pendingDescriptions = getRow("SELECT COUNT(*) as count FROM business_descriptions WHERE status = 'pending'", [], '')['count'] ?? 0;
} catch (Exception $e) {}

$totalPendingContent = $pendingEdits + $pendingUpdates + $pendingServices + $pendingOffers + $pendingDescriptions;

// Monetization stats (with error handling for missing tables)
$totalPendingClaims = 0;
$totalActiveSubs = 0;
$totalPlans = 0;

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM business_claims WHERE status = 'pending'");
    if ($result) {
        $totalPendingClaims = $result->fetch_assoc()['count'] ?? 0;
    }
} catch (Exception $e) {
    $totalPendingClaims = 0;
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    if ($result) {
        $totalActiveSubs = $result->fetch_assoc()['count'] ?? 0;
    }
} catch (Exception $e) {
    $totalActiveSubs = 0;
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM plans");
    if ($result) {
        $totalPlans = $result->fetch_assoc()['count'] ?? 0;
    }
} catch (Exception $e) {
    $totalPlans = 0;
}

// Handle chatbot toggle
$chatbotEnabled = true;
try {
    $result = $conn->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'chatbot_enabled'");
    if ($result && $result->num_rows > 0) {
        $chatbotEnabled = $result->fetch_assoc()['setting_value'] == '1';
    }
} catch (Exception $e) {
    $chatbotEnabled = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_chatbot') {
        $newStatus = isset($_POST['chatbot_enabled']) ? '1' : '0';
        try {
            // Ensure table exists
            $createTableSQL = "CREATE TABLE IF NOT EXISTS admin_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $conn->query($createTableSQL);
            
            // Update setting
            $conn->query("INSERT INTO admin_settings (setting_key, setting_value) VALUES ('chatbot_enabled', '$newStatus') ON DUPLICATE KEY UPDATE setting_value = '$newStatus'");
            $chatbotEnabled = $newStatus == '1';
            $message = 'Chatbot status updated successfully!';
        } catch (Exception $e) {
            $message = 'Error updating chatbot status: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Business Directory</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: #2c3e50;
        }

        .admin-header {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(11, 28, 61, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-header h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .logout-btn {
            background: #FF6A00;
            color: white;
            padding: 11px 26px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(255, 106, 0, 0.2);
        }

        .logout-btn:hover {
            background: #E55A00;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 106, 0, 0.3);
        }

        .logout-btn:active {
            transform: translateY(0);
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        .admin-container > h2 {
            color: #0B1C3D;
            margin-bottom: 30px;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 50px;
        }

        .stat-card {
            background: white;
            padding: 28px;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #f0f0f0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FF6A00, #FFB347);
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
            border-color: #FF6A00;
        }

        .stat-card h3 {
            color: #7a8a9e;
            font-size: 12px;
            margin-bottom: 14px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 38px;
            font-weight: 800;
            color: #0B1C3D;
            line-height: 1;
        }

        /* Section Headers */
        h3[style*="margin-top: 30px"] {
            color: #0B1C3D !important;
            font-size: 20px !important;
            font-weight: 700 !important;
            margin-bottom: 20px !important;
            margin-top: 45px !important;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Navigation Grid */
        .admin-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 40px;
        }

        .admin-nav a {
            background: white;
            padding: 16px 20px;
            border-radius: 10px;
            text-decoration: none;
            color: #2c3e50;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            border: 2px solid #f0f0f0;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: left;
        }

        .admin-nav a:hover {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(11, 28, 61, 0.2);
        }

        .admin-nav a:active {
            transform: translateY(-1px);
        }

        /* Info Cards */
        .card {
            background: white;
            padding: 32px;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 28px;
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-color: #e0e0e0;
        }

        .card h3 {
            color: #0B1C3D;
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: -0.3px;
        }

        .card ul {
            list-style: none;
        }

        .card ul li {
            padding: 11px 0;
            color: #5a6c7d;
            line-height: 1.7;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #f5f5f5;
        }

        .card ul li:last-child {
            border-bottom: none;
        }

        .card ul li::before {
            content: '→';
            color: #FF6A00;
            font-weight: bold;
        }

        .card a {
            color: #FF6A00 !important;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .card a:hover {
            color: #E55A00 !important;
            transform: translateX(3px);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .admin-header {
                padding: 18px 24px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .admin-header h1 {
                font-size: 24px;
            }

            .admin-container {
                padding: 30px 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 18px;
                margin-bottom: 40px;
            }

            .stat-card {
                padding: 22px;
            }

            .stat-card .value {
                font-size: 32px;
            }

            .admin-nav {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 14px;
            }

            .card {
                padding: 24px;
            }
        }

        @media (max-width: 768px) {
            .admin-header {
                padding: 16px 20px;
            }

            .admin-header h1 {
                font-size: 20px;
            }

            .logout-btn {
                padding: 10px 20px;
                font-size: 13px;
            }

            .admin-container {
                padding: 24px 16px;
            }

            .admin-container > h2 {
                font-size: 22px;
                margin-bottom: 24px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 16px;
                margin-bottom: 32px;
            }

            .stat-card {
                padding: 18px;
            }

            .stat-card h3 {
                font-size: 11px;
                margin-bottom: 10px;
            }

            .stat-card .value {
                font-size: 28px;
            }

            h3[style*="margin-top: 30px"] {
                font-size: 18px !important;
                margin-top: 30px !important;
                margin-bottom: 15px !important;
            }

            .admin-nav {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .admin-nav a {
                padding: 14px 16px;
                font-size: 13px;
            }

            .card {
                padding: 18px;
                margin-bottom: 20px;
            }

            .card h3 {
                font-size: 16px;
                margin-bottom: 16px;
            }

            .card ul li {
                padding: 9px 0;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .admin-header h1 {
                font-size: 18px;
            }

            .admin-container {
                padding: 18px 12px;
            }

            .admin-container > h2 {
                font-size: 19px;
                margin-bottom: 18px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-bottom: 24px;
            }

            .stat-card {
                padding: 14px;
            }

            .stat-card h3 {
                font-size: 10px;
                margin-bottom: 8px;
            }

            .stat-card .value {
                font-size: 24px;
            }

            h3[style*="margin-top: 30px"] {
                font-size: 16px !important;
                margin-top: 24px !important;
                margin-bottom: 12px !important;
            }

            .admin-nav a {
                padding: 12px 14px;
                font-size: 12px;
            }

            .card {
                padding: 14px;
                margin-bottom: 16px;
            }

            .card h3 {
                font-size: 15px;
                margin-bottom: 12px;
            }

            .card ul li {
                padding: 8px 0;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>

    <div class="admin-container">
        <h2 style="margin-top: 30px;">📊 Dashboard - Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></h2>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📂 Total Businesses</h3>
                <div class="value"><?php echo $totalBusinesses; ?></div>
            </div>
            <div class="stat-card">
                <h3>🏷️ Total Categories</h3>
                <div class="value"><?php echo $totalCategories; ?></div>
            </div>
            <div class="stat-card">
                <h3>📍 Total Locations</h3>
                <div class="value"><?php echo $totalLocations; ?></div>
            </div>
            <div class="stat-card">
                <h3>⭐ Average Rating</h3>
                <div class="value"><?php echo $avgRating; ?>/5</div>
            </div>
            <div class="stat-card">
                <h3>📋 Pending Claims</h3>
                <div class="value"><?php echo $totalPendingClaims; ?></div>
            </div>
            <div class="stat-card">
                <h3>💳 Active Subscriptions</h3>
                <div class="value"><?php echo $totalActiveSubs; ?></div>
            </div>
            <div class="stat-card">
                <h3>💰 Pricing Plans</h3>
                <div class="value"><?php echo $totalPlans; ?></div>
            </div>
        </div>

        <!-- Content Review Section -->
        <?php if ($totalPendingContent > 0): ?>
        <h3 style="margin-top: 30px; color: #d32f2f; font-weight: bold;">⚠️ PENDING CONTENT REVIEW (<?php echo $totalPendingContent; ?>)</h3>
        <div class="admin-nav" style="background: #fff3e0; padding: 20px; border-radius: 10px; border-left: 4px solid #FF6A00;">
            <?php if ($pendingEdits > 0): ?>
                <a href="edit-reviews.php" style="background: #FFE082; color: #F57F17; border-color: #FFB300;">
                    ✏️ Review Field Edits <span style="background: #F57F17; color: white; border-radius: 50%; padding: 2px 6px; font-weight: bold;"><?php echo $pendingEdits; ?></span>
                </a>
            <?php endif; ?>
            <?php if ($pendingUpdates > 0): ?>
                <a href="review-updates.php" style="background: #FFE082; color: #F57F17; border-color: #FFB300;">
                    📝 Review Updates <span style="background: #F57F17; color: white; border-radius: 50%; padding: 2px 6px; font-weight: bold;"><?php echo $pendingUpdates; ?></span>
                </a>
            <?php endif; ?>
            <?php if ($pendingServices > 0): ?>
                <a href="review-services.php" style="background: #FFE082; color: #F57F17; border-color: #FFB300;">
                    🔧 Review Services <span style="background: #F57F17; color: white; border-radius: 50%; padding: 2px 6px; font-weight: bold;"><?php echo $pendingServices; ?></span>
                </a>
            <?php endif; ?>
            <?php if ($pendingOffers > 0): ?>
                <a href="review-offers.php" style="background: #FFE082; color: #F57F17; border-color: #FFB300;">
                    🎉 Review Offers <span style="background: #F57F17; color: white; border-radius: 50%; padding: 2px 6px; font-weight: bold;"><?php echo $pendingOffers; ?></span>
                </a>
            <?php endif; ?>
            <?php if ($pendingDescriptions > 0): ?>
                <a href="review-descriptions.php" style="background: #FFE082; color: #F57F17; border-color: #FFB300;">
                    📄 Review Descriptions <span style="background: #F57F17; color: white; border-radius: 50%; padding: 2px 6px; font-weight: bold;"><?php echo $pendingDescriptions; ?></span>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Directory Management -->
        <h3 style="margin-top: 30px; color: #333;">📂 Directory Management</h3>
        <div class="admin-nav">
            <a href="businesses.php">📂 Manage Businesses</a>
            <a href="categories.php">🏷️ Manage Categories</a>
            <a href="locations.php">📍 Manage Locations</a>
            <a href="import-businesses.php">📥 Import Businesses</a>
        </div>

        <!-- Monetization Management -->
        <h3 style="margin-top: 30px; color: #333;">💰 Monetization Management</h3>
        <div class="admin-nav">
            <a href="manage-claims.php">📋 Manage Claims</a>
            <a href="manage-subscriptions.php">💳 Manage Subscriptions</a>
            <a href="manage-plans.php">💰 Manage Plans</a>
        </div>

        <!-- SaaS Features -->
        <h3 style="margin-top: 30px; color: #333;">🚀 SaaS Features</h3>
        <div class="admin-nav">
            <a href="leads-management.php">📞 Leads Management</a>
            <a href="lead-analytics.php">📊 Lead Analytics</a>
            <a href="crm-pipeline.php">🎯 CRM Pipeline</a>
            <a href="invoices.php">💰 Invoices</a>
            <a href="chat-logs.php">💬 Chat Logs</a>
            <a href="ai-insights.php">🤖 AI Insights</a>
            <a href="ai-content-generator.php">✨ AI Content Gen</a>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <h3>🔗 Quick Links</h3>
            <ul style="list-style: none; margin-top: 15px;">
                <li><a href="/" style="color: #667eea; text-decoration: none; display: block; padding: 8px 0;">👉 View Homepage</a></li>
                <li><a href="/pages/search-with-filters.php" style="color: #667eea; text-decoration: none; display: block; padding: 8px 0;">🔍 Advanced Search</a></li>
                <li><a href="/admin/index.php?action=logout" style="color: #667eea; text-decoration: none; display: block; padding: 8px 0;">🚪 Logout</a></li>
            </ul>
        </div>

        <!-- System Status & Settings -->
        <div class="card">
            <h3>✅ System Status & Settings</h3>
            <ul style="list-style: none; margin-top: 15px;">
                <li>✅ Database: Connected</li>
                <li>✅ Admin Panel: Active</li>
                <li><?php echo $chatbotEnabled ? '✅' : '❌'; ?> Voice AI Chatbot: <?php echo $chatbotEnabled ? 'Enabled' : 'Disabled'; ?></li>
                <li>✅ Monetization System: Active</li>
                <li>✅ Import System: Ready</li>
                <li>✅ SMTP: smtp.hostinger.com:465 (SSL)</li>
            </ul>
            <a href="/admin/test-smtp.php" style="display: inline-block; margin-top: 14px; background: #FF6A00; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;">📧 Test SMTP Email</a>
        </div>

        <!-- AI Chatbot Settings -->
        <div class="card" style="background: linear-gradient(135deg, #f0f4ff 0%, #f9f9ff 100%); border-left: 4px solid #667eea;">
            <h3>🤖 AI Assistant Settings</h3>
            <?php if (isset($message)): ?>
                <div style="background: #c8e6c9; color: #2e7d32; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #2e7d32;">
                    ✅ <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" style="margin-top: 15px;">
                <input type="hidden" name="action" value="toggle_chatbot">
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; flex: 1;">
                        <input type="checkbox" name="chatbot_enabled" value="1" <?php echo $chatbotEnabled ? 'checked' : ''; ?> style="width: 20px; height: 20px; cursor: pointer;">
                        <span style="font-weight: 600; color: #333;">Enable AI Assistant Chatbot</span>
                        <span style="font-size: 12px; color: #999;">(Displays chatbot widget on all pages)</span>
                    </label>
                    <button type="submit" style="padding: 10px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s;">
                        💾 Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
