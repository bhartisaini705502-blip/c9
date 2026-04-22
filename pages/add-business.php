<?php
$page_title = 'Add Your Business to ConnectWith9';
$meta_description = 'List your business on ConnectWith9 for free and reach thousands of customers in your area.';

require_once dirname(__DIR__) . '/includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo '<div class="container" style="padding: 40px 0; text-align: center;">';
    echo '<h2 style="color: #0B1C3D;">Please Login or Register to Add Your Business</h2>';
    echo '<p style="color: #666;">You need to create an account to add your business.</p>';
    echo '<a href="/auth/login.php" style="background: #FF6A00; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 700; display: inline-block; margin-right: 10px;">Login</a>';
    echo '<a href="/auth/register.php" style="background: #1E3A8A; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 700; display: inline-block;">Register</a>';
    echo '</div>';
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}
?>

<div class="container">
    <div class="smart-header" style="margin-bottom: 40px;">
        <div class="smart-header-content">
            <h1 class="smart-title">➕ Add Your Business to ConnectWith9</h1>
            <p class="smart-subtitle">Get listed for free and reach thousands of potential customers</p>
        </div>
    </div>

    <div style="max-width: 800px; margin: 0 auto;">
        <div style="background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 30px;">
            <h2 style="color: #0B1C3D; margin-top: 0;">Benefits of Listing Your Business</h2>
            <ul style="color: #333; line-height: 1.8; font-size: 15px;">
                <li>✓ Free business listing</li>
                <li>✓ Increase online visibility</li>
                <li>✓ Get customer reviews</li>
                <li>✓ Drive more customers</li>
                <li>✓ Manage your business info</li>
                <li>✓ Access to analytics</li>
                <li>✓ Optional premium features</li>
            </ul>

            <h2 style="color: #0B1C3D;">How to Add Your Business</h2>
            <ol style="color: #333; line-height: 1.8;">
                <li>Complete basic business information</li>
                <li>Add photos and description</li>
                <li>Set business hours and contact details</li>
                <li>Verify your business</li>
                <li>Start getting customers!</li>
            </ol>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 30px;">
                <h3 style="color: #0B1C3D; margin-top: 0;">Ready to Get Started?</h3>
                <p style="color: #666;">Go to your dashboard to add your business listing now.</p>
                <a href="/pages/dashboard.php" style="background: #FF6A00; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 700; display: inline-block;">Go to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
