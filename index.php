<?php
/**
 * Homepage - Business Directory
 */

require 'config/db.php';
require 'includes/functions.php';

// SEO Meta Tags
$page_title = 'Find Best Business Services Near You';
$meta_description = 'Search and discover top-rated businesses and services in your area. Browse by category and location. Expert local business directory.';
$meta_keywords = 'business directory, local services, find businesses, top rated services';

include 'includes/header.php';

// Get unique categories for dropdown
$categories = [];
$result = $GLOBALS['conn']->query("
    SELECT DISTINCT types FROM extracted_businesses 
    WHERE business_status = 'OPERATIONAL'
    LIMIT 100
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['types'])) {
            $types = explode(',', $row['types']);
            foreach ($types as $type) {
                $type = trim($type);
                if ($type && !in_array($type, $categories)) {
                    $categories[] = $type;
                }
            }
        }
    }
}
sort($categories);
$categories = array_slice($categories, 0, 20);

// Get unique locations for dropdown
$locations = [];
$result = $GLOBALS['conn']->query("
    SELECT DISTINCT search_location FROM extracted_businesses 
    WHERE business_status = 'OPERATIONAL' AND search_location IS NOT NULL
    ORDER BY search_location
    LIMIT 30
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['search_location'])) {
            $locations[] = $row['search_location'];
        }
    }
}

// Get featured/popular businesses
$featured = [];
$result = $GLOBALS['conn']->query("
    SELECT id, name, types, formatted_address, search_location, formatted_phone_number, website, rating, user_ratings_total 
    FROM extracted_businesses 
    WHERE business_status = 'OPERATIONAL' AND rating IS NOT NULL
    ORDER BY rating DESC 
    LIMIT 12
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $featured[] = $row;
    }
}
?>

<!-- ===== HERO SECTION ===== -->
<section class="hero-section">
    <div class="container">
        <div class="hero-wrapper">
            <!-- Left Side -->
            <div class="hero-left">
                <h1 class="hero-title">Find the Best Businesses Near You & Grow Your Own</h1>
                <p class="hero-subtitle">Discover trusted local services or grow your business with expert digital marketing and website solutions.</p>
                
                <!-- Search Form -->
                <form class="hero-search" method="GET" action="/pages/search.php">
                    <div class="search-field">
                        <input type="text" name="business" placeholder="Business name or service..." value="<?php echo isset($_GET['business']) ? esc($_GET['business']) : ''; ?>" required>
                    </div>
                    <div class="search-field">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc($cat); ?>" <?php echo isset($_GET['category']) && $_GET['category'] === $cat ? 'selected' : ''; ?>>
                                    <?php echo esc($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-field">
                        <select name="location">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo esc($loc); ?>" <?php echo isset($_GET['location']) && $_GET['location'] === $loc ? 'selected' : ''; ?>>
                                    <?php echo esc($loc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Search Businesses</button>
                </form>

                <!-- CTA Buttons -->
                <div class="hero-buttons">
                    <a href="/pages/search.php" class="btn btn-outline">Explore Businesses</a>
                    <a href="/pages/website-offer.php" class="btn btn-orange">Get Website @ ₹10,000</a>
                </div>
            </div>

            <!-- Right Side -->
            <div class="hero-right">
                <div class="hero-illustration">
                    <div class="illustration-icon">🏢</div>
                    <p>Connecting Businesses & Customers</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== QUICK CATEGORIES ===== -->
<section class="categories-section">
    <div class="container">
        <h2 class="section-title">Browse Popular Categories</h2>
        <p class="section-subtitle">Find services and businesses by category</p>
        
        <div class="categories-grid">
            <?php 
            $categoryIcons = [
                'restaurant' => '🍽️',
                'cafe' => '☕',
                'hotel' => '🏨',
                'bank' => '🏦',
                'hospital' => '🏥',
                'pharmacy' => '💊',
                'salon' => '💇',
                'gym' => '💪',
                'school' => '🎓',
                'store' => '🛍️'
            ];
            
            for ($i = 0; $i < min(8, count($categories)); $i++): 
                $cat = $categories[$i];
                $icon = $categoryIcons[strtolower($cat)] ?? '📌';
            ?>
                <a href="/pages/categories.php?category=<?php echo urlencode($cat); ?>" class="category-card">
                    <span class="category-icon"><?php echo $icon; ?></span>
                    <span class="category-name"><?php echo esc($cat); ?></span>
                </a>
            <?php endfor; ?>
            <a href="/pages/categories.php" class="category-card view-all">
                <span class="category-icon">➕</span>
                <span class="category-name">View All</span>
            </a>
        </div>
    </div>
</section>

<!-- ===== TOP BUSINESSES ===== -->
<section class="featured-section">
    <div class="container">
        <h2 class="section-title">Top Recommended Businesses</h2>
        <p class="section-subtitle">Highly-rated businesses trusted by thousands</p>
        
        <?php if (!empty($featured)): ?>
            <div class="business-grid">
                <?php foreach (array_slice($featured, 0, 6) as $item): ?>
                    <a href="/pages/business-detail.php?id=<?php echo $item['id']; ?>&slug=<?php echo generateSlug($item['name']); ?>" class="business-card-link">
                        <div class="business-card">
                            <div class="business-card-header">
                                <div class="business-icon">🏢</div>
                                <div class="business-rating">⭐ <?php echo isset($item['rating']) && $item['rating'] ? number_format($item['rating'], 1) : '0.0'; ?></div>
                            </div>
                            <div class="business-card-body">
                                <h3 class="business-name"><?php echo esc($item['name'] ?? 'N/A'); ?></h3>
                                <?php 
                                    $firstCategory = '';
                                    if (!empty($item['types'])) {
                                        $typeArray = explode(',', $item['types']);
                                        $firstCategory = trim($typeArray[0]);
                                    }
                                ?>
                                <p class="business-category"><?php echo esc($firstCategory ?? 'Business'); ?></p>
                                <p class="business-location">📍 <?php echo esc($item['search_location'] ?? 'Location'); ?></p>
                                <p class="business-reviews"><?php echo $item['user_ratings_total'] ?? 0; ?> reviews</p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-40">
                <a href="/pages/search.php" class="btn btn-primary">View All Businesses</a>
            </div>
        <?php else: ?>
            <p class="text-center p-30">No businesses found yet. Check back soon!</p>
        <?php endif; ?>
    </div>
</section>

<!-- ===== SERVICES SECTION ===== -->
<section class="services-section">
    <div class="container">
        <h2 class="section-title">Grow Your Business with Our Services</h2>
        <p class="section-subtitle">Complete solutions for digital success</p>
        
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">🌐</div>
                <h3>Website Development</h3>
                <p>Professional websites designed to convert visitors into customers</p>
                <a href="/pages/website-development.php" class="service-link">Learn More →</a>
            </div>
            <div class="service-card">
                <div class="service-icon">🔍</div>
                <h3>SEO Services</h3>
                <p>Rank higher on Google and drive organic traffic to your site</p>
                <a href="/pages/seo-services.php" class="service-link">Learn More →</a>
            </div>
            <div class="service-card">
                <div class="service-icon">📱</div>
                <h3>Social Media Marketing</h3>
                <p>Build your brand presence on social platforms effectively</p>
                <a href="/pages/social-media.php" class="service-link">Learn More →</a>
            </div>
            <div class="service-card">
                <div class="service-icon">📊</div>
                <h3>Google Ads (PPC)</h3>
                <p>Targeted advertising to reach customers actively searching for you</p>
                <a href="/pages/ppc-services.php" class="service-link">Learn More →</a>
            </div>
        </div>
    </div>
</section>

<!-- ===== PROMOTION SECTION ===== -->
<section class="promotion-section">
    <div class="container">
        <div class="promotion-wrapper">
            <div class="promotion-content">
                <h2>Get Your Business Website with 4 Years Validity</h2>
                <p class="promotion-subtitle">Professional website setup + maintenance included</p>
                <div class="promotion-price">Only <span class="price-highlight">₹10,000</span></div>
                <div class="promotion-buttons">
                    <a href="tel:+919876543210" class="btn btn-primary">📞 Call Now</a>
                    <a href="https://wa.me/919876543210" target="_blank" class="btn btn-success">💬 WhatsApp</a>
                </div>
                <p class="promotion-note">Limited time offer - Limited slots available!</p>
            </div>
            <div class="promotion-image">
                <div class="promo-illustration">
                    💼
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== WHY CHOOSE US ===== -->
<section class="whychoose-section">
    <div class="container">
        <h2 class="section-title">Why Choose ConnectWith9?</h2>
        <p class="section-subtitle">Your partner in business growth</p>
        
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-number">✓</div>
                <h3>Trusted Business Directory</h3>
                <p>Verified and authenticated business listings with real reviews from genuine customers</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">✓</div>
                <h3>Affordable Digital Solutions</h3>
                <p>Professional digital marketing services starting from just ₹10,000</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">✓</div>
                <h3>AI-Powered Smart Search</h3>
                <p>Intelligent algorithms that find exactly what customers are looking for</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">✓</div>
                <h3>Verified Listings</h3>
                <p>All businesses are verified and regularly updated with latest information</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">✓</div>
                <h3>Complete Growth Support</h3>
                <p>From listing to lead generation to website - we support your entire growth journey</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">✓</div>
                <h3>24/7 Customer Support</h3>
                <p>Dedicated support team ready to help you succeed at every step</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="howitworks-section">
    <div class="container">
        <h2 class="section-title">How It Works</h2>
        <p class="section-subtitle">Two simple paths to success</p>
        
        <div class="howitworks-wrapper">
            <!-- For Customers -->
            <div class="howitworks-path">
                <h3>For Customers</h3>
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Search Businesses</h4>
                            <p>Find businesses by category, location, or name</p>
                        </div>
                    </div>
                    <div class="step-arrow">→</div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Compare Options</h4>
                            <p>Check ratings, reviews, and details</p>
                        </div>
                    </div>
                    <div class="step-arrow">→</div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Connect Instantly</h4>
                            <p>Call, WhatsApp, or visit directly</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- For Business Owners -->
            <div class="howitworks-path">
                <h3>For Business Owners</h3>
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>List Your Business</h4>
                            <p>Create a detailed business profile</p>
                        </div>
                    </div>
                    <div class="step-arrow">→</div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Get Leads</h4>
                            <p>Start receiving customer inquiries</p>
                        </div>
                    </div>
                    <div class="step-arrow">→</div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Grow Revenue</h4>
                            <p>Convert leads and expand your business</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FINAL CTA ===== -->
<section class="final-cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Grow Your Business?</h2>
        <p class="cta-subtitle">Join thousands of businesses already succeeding with ConnectWith9</p>
        
        <div class="cta-buttons">
            <a href="/pages/website-offer.php" class="btn btn-primary btn-large">Get Website @ ₹10,000</a>
            <a href="/pages/static-page.php?slug=contact" class="btn btn-outline btn-large">Contact Us</a>
            <a href="/pages/add-business.php" class="btn btn-orange btn-large">Add Your Business</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
