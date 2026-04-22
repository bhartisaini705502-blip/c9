<?php
/**
 * Mobile Header - Mobile-Only Navigation
 * Include this conditionally for mobile devices
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

<style>
/* ====== MOBILE HEADER STYLES ====== */
.mobile-header {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
    color: white;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.mobile-header.active {
    display: flex;
}

.mobile-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    min-height: 50px;
}

.mobile-logo {
    height: 40px;
}

.mobile-logo img {
    height: 100%;
    width: auto;
}

.mobile-menu-toggle {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mobile-menu-toggle.open::before {
    content: '✕';
}

.mobile-menu-toggle::before {
    content: '☰';
}

.mobile-nav-menu {
    display: none;
    flex-direction: column;
    background: white;
    color: #333;
    max-height: calc(100vh - 60px);
    overflow-y: auto;
    border-top: 1px solid #eee;
}

.mobile-nav-menu.active {
    display: flex;
}

.mobile-nav-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.2s;
}

.mobile-nav-item:active {
    background: #f5f5f5;
}

.mobile-nav-item a {
    color: #333;
    text-decoration: none;
    display: block;
    font-weight: 500;
}

.mobile-nav-item.group > a::after {
    content: ' ▶';
    font-size: 12px;
}

.mobile-nav-submenu {
    display: none;
    background: #f9f9f9;
    flex-direction: column;
}

.mobile-nav-submenu.active {
    display: flex;
}

.mobile-nav-submenu a {
    padding: 12px 40px;
    border-bottom: 1px solid #e0e0e0;
    color: #666;
    text-decoration: none;
    display: block;
    font-size: 14px;
}

.mobile-nav-submenu a:active {
    background: #e8e8e8;
}

.mobile-header-footer {
    padding: 10px 20px;
    border-top: 1px solid #E0E0E0;
    background: linear-gradient(180deg, #FAFAFA 0%, #F5F5F5 100%);
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    align-items: center;
}

.mobile-header-footer a {
    padding: 8px 14px;
    text-align: center;
    text-decoration: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.2px;
    transition: all 0.3s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 36px;
    white-space: nowrap;
}

.mobile-header-footer .btn-secondary {
    background: #1E3A8A;
    color: white;
    box-shadow: 0 2px 6px rgba(30, 58, 138, 0.15);
}

.mobile-header-footer .btn-secondary:active {
    background: #152856;
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.2);
    transform: translateY(0.5px);
}

.mobile-header-footer .btn-primary {
    background: linear-gradient(135deg, #FF6A00 0%, #E55A00 100%);
    color: white;
    box-shadow: 0 2px 6px rgba(255, 106, 0, 0.15);
}

.mobile-header-footer .btn-primary:active {
    background: linear-gradient(135deg, #E55A00 0%, #D04B00 100%);
    box-shadow: 0 2px 8px rgba(255, 106, 0, 0.2);
    transform: translateY(0.5px);
}

/* Show mobile header only on mobile */
@media (max-width: 768px) {
    .mobile-header.active {
        display: flex;
    }
    
    .desktop-header {
        display: none !important;
    }
}

@media (min-width: 769px) {
    .mobile-header {
        display: none !important;
    }
}
</style>

<!-- Mobile Header -->
<header class="mobile-header active">
    <div class="mobile-header-top">
        <div class="mobile-logo">
            <a href="/">
                <img src="/assets/images/connectwith-logo.webp" alt="ConnectWith9">
            </a>
        </div>
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()"></button>
    </div>
    
    <nav class="mobile-nav-menu" id="mobile-nav-menu">
        <!-- Home -->
        <div class="mobile-nav-item">
            <a href="/">🏠 Home</a>
        </div>
        
        <!-- Directory -->
        <div class="mobile-nav-item group" onclick="toggleMobileSubmenu(this)">
            <a href="#directory">📂 Directory</a>
            <div class="mobile-nav-submenu">
                <a href="/pages/categories.php">All Categories</a>
                <a href="/pages/locations.php">All Locations</a>
                <a href="/pages/smart-search.php">Smart Search</a>
                <a href="/pages/hybrid-search.php">Hybrid Search</a>
                <a href="/pages/coming-soon.php">Trending</a>
            </div>
        </div>
        
        <!-- Services -->
        <div class="mobile-nav-item group" onclick="toggleMobileSubmenu(this)">
            <a href="#services">🔧 Services</a>
            <div class="mobile-nav-submenu">
                <a href="/pages/website-development.php">Website Development</a>
                <a href="/pages/seo-services.php">SEO Services</a>
                <a href="/pages/ppc-services.php">Google Ads / PPC</a>
                <a href="/pages/social-media-marketing.php">Social Media Marketing</a>
                <a href="/pages/content-marketing.php">Content Marketing</a>
                <a href="/ai-seo-tool/">AI-Powered SEO Tool</a>
            </div>
        </div>
        
        <!-- Business -->
        <div class="mobile-nav-item group" onclick="toggleMobileSubmenu(this)">
            <a href="#business">🏢 Business</a>
            <div class="mobile-nav-submenu">
                <a href="/pages/coming-soon.php">Add Your Business</a>
                <a href="/pages/coming-soon.php">Claim Business</a>
                <a href="/pages/coming-soon.php">Advertise With Us</a>
            </div>
        </div>
        
        <!-- Tools -->
        <div class="mobile-nav-item group" onclick="toggleMobileSubmenu(this)">
            <a href="#tools">🛠️ Tools</a>
            <div class="mobile-nav-submenu">
                <a href="/pages/search-with-filters.php">Advanced Search</a>
                <a href="/pages/coming-soon.php">AI Assistant</a>
                <a href="/pages/coming-soon.php">Compare Businesses</a>
                <a href="/ai-seo-tool/">SEO Tool</a>
            </div>
        </div>
        
        <!-- Company -->
        <div class="mobile-nav-item group" onclick="toggleMobileSubmenu(this)">
            <a href="#company">ℹ️ Company</a>
            <div class="mobile-nav-submenu">
                <a href="/pages/about.php">About Us</a>
                <a href="/pages/contact.php">Contact Us</a>
                <a href="/pages/static-page.php?slug=privacy-policy">Privacy Policy</a>
                <a href="/pages/static-page.php?slug=terms">Terms & Conditions</a>
            </div>
        </div>
        
        <?php if ($currentUser && in_array($currentUser['role'], ['admin', 'manager'])): ?>
        <div class="mobile-nav-item group" onclick="toggleMobileSubmenu(this)">
            <a href="#admin">⚙️ Admin</a>
            <div class="mobile-nav-submenu">
                <a href="/admin/">Dashboard</a>
                <a href="/admin/analytics-dashboard.php">Analytics</a>
                <a href="/admin/manage-claims.php">Manage Claims</a>
                <a href="/admin/review-services.php">Review Services</a>
                <a href="/admin/import-businesses.php">Import Businesses</a>
                <a href="/admin/support.php">Support Hub</a>
            </div>
        </div>
        <?php endif; ?>
    </nav>
    
    <div class="mobile-header-footer">
        <?php if ($currentUser): ?>
            <a href="/pages/dashboard.php" class="btn-secondary">My Listings</a>
            <a href="/auth/logout.php" class="btn-primary">Logout</a>
        <?php else: ?>
            <a href="/auth/login.php" class="btn-secondary">Login</a>
            <a href="/auth/register.php" class="btn-primary">Sign Up</a>
        <?php endif; ?>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobile-nav-menu');
    const toggle = document.querySelector('.mobile-menu-toggle');
    menu.classList.toggle('active');
    toggle.classList.toggle('open');
}

function toggleMobileSubmenu(item) {
    const submenu = item.querySelector('.mobile-nav-submenu');
    if (submenu) {
        submenu.classList.toggle('active');
    }
}

// Close mobile menu on link click
document.querySelectorAll('.mobile-nav-submenu a').forEach(link => {
    link.addEventListener('click', () => {
        document.getElementById('mobile-nav-menu').classList.remove('active');
        document.querySelector('.mobile-menu-toggle').classList.remove('open');
    });
});

// Show mobile header only on mobile devices
function checkAndShowMobileHeader() {
    const mobileHeader = document.querySelector('.mobile-header');
    if (window.innerWidth <= 768) {
        mobileHeader.classList.add('active');
    } else {
        mobileHeader.classList.remove('active');
    }
}

window.addEventListener('resize', checkAndShowMobileHeader);
document.addEventListener('DOMContentLoaded', checkAndShowMobileHeader);
</script>
