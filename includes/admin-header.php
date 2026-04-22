<?php
/**
 * Admin Panel Navigation Header
 */

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    die('Unauthorized access');
}
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .admin-navbar {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            padding: 0;
            margin: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .admin-navbar-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            color: white;
        }
        
        .admin-navbar-top h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        
        .admin-navbar-top a {
            color: white;
            text-decoration: none;
            background: #FF6A00;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .admin-navbar-top a:hover {
            background: #E55A00;
            transform: translateY(-2px);
        }
        
        .admin-navbar-menu {
            background: #f8f9fa;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            border-top: 1px solid #ddd;
            overflow-x: auto;
        }
        
        .admin-navbar-menu a {
            padding: 12px 18px;
            text-decoration: none;
            color: #2c3e50;
            font-size: 13px;
            font-weight: 500;
            border-right: 1px solid #ddd;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .admin-navbar-menu a:hover {
            background: #e8eef7;
            color: #1E3A8A;
        }
        
        .admin-navbar-menu a.active {
            background: #1E3A8A;
            color: white;
            border-bottom: 3px solid #FF6A00;
        }
        
        .admin-navbar-menu a:last-child {
            border-right: none;
        }
        
        @media (max-width: 768px) {
            .admin-navbar-top {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .admin-navbar-menu a {
                flex: 1 1 auto;
                border-right: none;
                border-bottom: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="admin-navbar">
        <div class="admin-navbar-top">
            <h2>🛠️ Admin Panel</h2>
            <a href="logout.php">🚪 Logout</a>
        </div>
        
        <div class="admin-navbar-menu">
            <a href="/admin/dashboard.php">📊 Dashboard</a>
            
            <a href="/admin/manage-claims.php">📋 Claims</a>
            <a href="/admin/edit-reviews.php">✏️ Field Edits</a>
            <a href="/admin/review-updates.php">📝 Updates</a>
            <a href="/admin/review-services.php">🔧 Services</a>
            <a href="/admin/review-offers.php">🎉 Offers</a>
            <a href="/admin/review-descriptions.php">📄 Descriptions</a>
            
            <a href="/admin/import-businesses.php">📥 Import Businesses</a>
            <a href="/admin/import-google-listing.php">🔗 Import Google</a>
            
            <a href="/admin/manage-subscriptions.php">💳 Subscriptions</a>
            <a href="/admin/manage-plans.php">💰 Plans</a>
            
            <a href="/admin/leads-management.php">📞 Leads</a>
            <a href="/admin/crm-pipeline.php">🎯 CRM</a>
        </div>
    </div>
