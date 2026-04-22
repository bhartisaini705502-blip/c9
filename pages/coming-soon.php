<?php
/**
 * Coming Soon Page - Placeholder for features in development
 */

session_start();

$page_title = 'Coming Soon';
$meta_description = 'This page is coming soon. Please check back later.';
$meta_keywords = 'coming soon';

include '../includes/header.php';
?>

<div style="background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%); color: white; padding: 100px 20px; text-align: center; min-height: 70vh; display: flex; align-items: center; justify-content: center;">
    <div style="max-width: 600px;">
        <h1 style="font-size: 48px; margin-bottom: 20px; font-weight: 700;">🚀 Coming Soon</h1>
        <p style="font-size: 20px; margin-bottom: 30px; opacity: 0.9;">This exciting feature is under development. We're working hard to bring it to you!</p>
        
        <div style="background: rgba(255,106,0,0.1); padding: 30px; border-radius: 12px; margin-bottom: 30px; border: 2px solid #FF6A00;">
            <h3 style="margin-bottom: 15px;">What's Coming?</h3>
            <ul style="text-align: left; display: inline-block;">
                <li style="margin-bottom: 10px;">✓ Advanced features for your business</li>
                <li style="margin-bottom: 10px;">✓ Enhanced tools and services</li>
                <li style="margin-bottom: 10px;">✓ Better performance and speed</li>
                <li>✓ More ways to grow your presence</li>
            </ul>
        </div>

        <a href="/" style="display: inline-block; background: #FF6A00; color: white; padding: 15px 40px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-right: 15px;">← Back to Home</a>
        <a href="/pages/contact.php" style="display: inline-block; background: white; color: #0B1C3D; padding: 15px 40px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 10px;">Contact Us</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
