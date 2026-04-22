
<?php
/**
 * Header - Navigation and Site Header
 */

if (!function_exists('esc')) {
    require_once dirname(__FILE__) . '/functions.php';
}

if (!function_exists('isLoggedIn')) {
    require_once dirname(__FILE__) . '/../config/db.php';
    require_once dirname(__FILE__) . '/../config/auth.php';
}

$currentUser = isLoggedIn() ? getUserData() : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? esc($page_title) . ' | ConnectWith9' : 'ConnectWith9 - Digital Marketing & Business Directory'; ?></title>
    <style>
        /* Desktop header visible by default */
        .header { display: flex !important; }
        
        /* Main content padding adjustments */
        main.main-content { padding-top: 20px; }
        
        /* Hide desktop on mobile, show mobile header */
        @media (max-width: 768px) {
            .header { display: none !important; }
            /* Add padding for mobile header (60px toggle + menu) */
            body { padding-top: 0; }
            main.main-content { padding-top: 80px; }
        }
        
        @media (min-width: 769px) {
            .mobile-header { display: none !important; }
            main.main-content { padding-top: 20px; }
        }
    </style>
    
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-17971154706">
</script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-17971154706');
</script>
    
    <meta name="description" content="<?php echo isset($meta_description) ? esc($meta_description) : 'Find best businesses, services, and local shops near you.'; ?>">
    <meta name="keywords" content="<?php echo isset($meta_keywords) ? esc($meta_keywords) : 'business directory, local services'; ?>">
    <?php if (isset($meta_canonical)): ?>
    <link rel="canonical" href="<?php echo esc($meta_canonical); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/mega-menu.css">
    <link rel="stylesheet" href="/assets/css/mobile-menu.css">
    <!-- JSON-LD Schema Markup -->
    <?php if (isset($jsonSchema)): ?>
    <script type="application/ld+json">
    <?php echo $jsonSchema; ?>
    </script>
    <?php endif; ?>
    <!-- Google Ads Conversion Tracking -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-XXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'AW-XXXXXXXXX');
    </script>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-wrapper">
                <!-- Logo -->
                <div class="header-logo">
                    <a href="/" class="logo-link">
                        <img src="/assets/images/connectwith-logo.webp" alt="ConnectWith9" class="logo-image">
                        <span class="logo-text"></span>
                    </a>
                </div>

                <!-- Mobile Toggle Button -->
                <button class="header-toggle" id="headerToggle" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <!-- Main Navigation -->
                <nav class="header-nav" id="headerNav">
                    <ul class="nav-items">
                        <li><a href="/">Home</a></li>
                        
        <li class="nav-group">
            <a href="#directory">Directory</a>
            <div class="dropdown">
                <a href="/pages/categories.php">📂 All Categories</a>
                <a href="/pages/locations.php">📍 All Locations</a>
                <a href="/pages/smart-search.php">🔍 Smart Search</a>
                <a href="/pages/hybrid-search.php">🌐 Hybrid Search</a>
                <a href="/pages/coming-soon.php">📈 Trending</a>
                <a href="/pages/coming-soon.php">📍 Nearby Me</a>
            </div>
        </li>
                        
                        <li class="nav-group">
                            <a href="#services">Services</a>
                            <div class="mega-dropdown">
                                <div class="mega-column">
                                    <h5>Website</h5>
                                    <a href="/pages/website-development.php">🌐 Website Development</a>
                                    <a href="/pages/website-development.php" class="highlight">💰 Get Website @ ₹10,000</a>
                                </div>
                                <div class="mega-column">
                                    <h5>SEO & Ads</h5>
                                    <a href="/pages/seo-services.php">🔍 SEO Services</a>
                                    <a href="/pages/ppc-services.php">📊 PPC / Google Ads</a>
                                    <a href="/pages/coming-soon.php">📈 Web Analytics</a>
                                </div>
                                <div class="mega-column">
                                    <h5>Content & Social</h5>
                                    <a href="/pages/social-media-marketing.php">📱 Social Media Marketing</a>
                                    <a href="/pages/content-marketing.php">✍️ Content Marketing</a>
                                    <a href="/pages/email-marketing.php">📧 Email Marketing</a>
                                </div>
                                <div class="mega-column">
                                    <h5>E-commerce</h5>
                                    <a href="/pages/ecommerce-marketing.php">🛍️ E-commerce</a>
                                    <a href="/pages/mobile-marketing.php">📱 Mobile Marketing</a>
                                    <a href="/pages/coming-soon.php">🎥 Video Marketing</a>
                                </div>
                                <div class="mega-column">
                                    <h5>Advanced</h5>
                                    <a href="/pages/crm-marketing.php">🤖 CRM & Automation</a>
                                    <a href="/pages/coming-soon.php">📋 Digital Strategy</a>
                                    <a href="/pages/coming-soon.php">🛡️ ORM / Reputation</a>
                                </div>
                            </div>
                        </li>
                        
                        <li class="nav-group">
                            <a href="#business">Business</a>
                            <div class="dropdown">
                                <a href="/pages/coming-soon.php">➕ Add Your Business</a>
                                <a href="/pages/coming-soon.php">✓ Claim Business</a>
                                <a href="/pages/coming-soon.php">📢 Advertise With Us</a>
                            </div>
                        </li>
                        
                        <li class="nav-group">
                            <a href="#tools">Tools</a>
                            <div class="dropdown">
                                <a href="/pages/coming-soon.php">🎯 Dashboard</a>
                                <a href="/pages/search-with-filters.php">🔍 Advanced Search</a>
                                <a href="/pages/coming-soon.php">🤖 AI Assistant</a>
                                <a href="/pages/coming-soon.php">⚖️ Compare Businesses</a>
                                <a href="/pages/coming-soon.php">🗺️ Map View</a>
                                <a href="/ai-seo-tool/" class="highlight">✨ AI-Powered SEO Tool</a>
                            </div>
                        </li>
                        
                        <li class="nav-group">
                            <a href="#company">Company</a>
                            <div class="dropdown">
                                <a href="/pages/about.php">ℹ️ About Us</a>
                                <a href="/pages/contact.php">📧 Contact Us</a>
                                <a href="/pages/static-page.php?slug=privacy-policy">🔒 Privacy Policy</a>
                                <a href="/pages/static-page.php?slug=terms">📋 Terms & Conditions</a>
                            </div>
                        </li>

                        <?php if ($currentUser): ?>
                            <li><a href="/pages/dashboard.php" class="nav-secondary">My Listings</a></li>
                            <?php if (in_array($currentUser['role'], ['admin', 'manager'])): ?>
                                <li><a href="/admin/dashboard.php" class="nav-secondary">Admin</a></li>
                                <li><a href="/admin/users.php" class="nav-secondary">Users</a></li>
                                <li><a href="/admin/categories.php" class="nav-secondary">Categories</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </nav>

                <!-- CTA Section -->
                <div class="header-cta">
                    <?php if ($currentUser): ?>
                        <div class="nav-group nav-user">
                            <a href="#" class="user-btn">👤 <?php echo esc(substr($currentUser['full_name'], 0, 10)); ?></a>
                            <div class="dropdown">
                                <a href="/pages/dashboard.php">Dashboard</a>
                                <a href="/pages/profile-settings.php">Settings</a>
                                <a href="/auth/logout.php">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/auth/login.php" class="btn-cta btn-secondary">Login</a>
                        <a href="/auth/register.php" class="btn-cta btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <script src="/assets/js/mega-menu.js"></script>
    
    <!-- Mobile Header -->
    <?php require_once __DIR__ . '/mobile-header.php'; ?>
    
    <!-- Chatbot Widget -->
    <?php require_once __DIR__ . '/chatbot-widget.php'; ?>

    <main class="main-content">
