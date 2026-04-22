<?php
/**
 * Google Places Business Detail Page
 * Handles viewing details for businesses from Google Places API
 */

// Start auth FIRST to handle sessions before any output
require_once '../config/auth.php';
require_once '../config/db.php';
require_once '../config/google-api.php';
require_once '../includes/tracking.php';
require '../includes/functions.php';

$placeId = $_GET['place_id'] ?? $_GET['id'] ?? null;

if (!$placeId) {
    redirect('/');
}

// Fetch place details from Google Places API
$apiKey = GOOGLE_PLACES_API_KEY;

if (empty($apiKey)) {
    redirect('/');
}

$url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
    'place_id' => $placeId,
    'key' => $apiKey,
    'fields' => 'place_id,name,rating,user_ratings_total,formatted_address,formatted_phone_number,website,opening_hours,types,photos,geometry,reviews'
]);

$response = @file_get_contents($url);
if (!$response) {
    redirect('/');
}

$data = json_decode($response, true);

if (!isset($data['result']) || empty($data['result'])) {
    redirect('/');
}

$business = $data['result'];

// Parse opening hours
$hours = [];
if (isset($business['opening_hours']['weekday_text'])) {
    $hours = $business['opening_hours']['weekday_text'];
}

// Parse photos
$photoUrls = [];
if (isset($business['photos']) && is_array($business['photos'])) {
    foreach (array_slice($business['photos'], 0, 10) as $photo) {
        if (isset($photo['photo_reference'])) {
            $photoUrl = getGooglePlacesPhotoUrl($photo['photo_reference'], 600);
            if ($photoUrl) {
                $photoUrls[] = $photoUrl;
            }
        }
    }
}

// Parse reviews
$reviews = $business['reviews'] ?? [];

// Business info
$name = $business['name'] ?? 'Business';
$rating = $business['rating'] ?? 0;
$reviewCount = $business['user_ratings_total'] ?? 0;
$address = $business['formatted_address'] ?? '';
$phone = $business['formatted_phone_number'] ?? '';
$website = $business['website'] ?? '';
$allTypes = $business['types'] ?? [];
$firstCategory = !empty($allTypes) ? str_replace('_', ' ', ucwords($allTypes[0])) : 'Business';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($name); ?> - ConnectWith9</title>
    <meta name="description" content="<?php echo htmlspecialchars($name); ?> - <?php echo htmlspecialchars($address); ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .hero-section {
            background: linear-gradient(135deg, #1E3A8A 0%, #0B1C3D 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .hero-section h1 {
            margin: 0 0 15px 0;
            font-size: 32px;
        }
        .hero-section .meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rating {
            background: #FFD700;
            color: #333;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
        }
        .google-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .left-col {}
        .right-col {}
        .info-box {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-label {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #FF6A00;
            color: white;
        }
        .btn-primary:hover {
            background: #e55a00;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }
        .btn-secondary:hover {
            background: #e8e8e8;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 30px;
        }
        .gallery-item {
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 8px;
            background: #f0f0f0;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .reviews-section {
            margin-top: 30px;
        }
        .review {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .review-author {
            font-weight: 600;
        }
        .review-rating {
            color: #FFB800;
        }
        .google-source {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="main-content">
        <div class="detail-container">
            <!-- Hero Section -->
            <div class="hero-section">
                <h1><?php echo htmlspecialchars($name); ?></h1>
                <div class="meta">
                    <div class="meta-item">
                        <span class="rating"><?php echo $rating; ?> ⭐</span>
                        <span><?php echo $reviewCount; ?> reviews</span>
                    </div>
                    <div class="meta-item">
                        <span><?php echo htmlspecialchars($firstCategory); ?></span>
                    </div>
                    <div class="meta-item google-badge">
                        📍 From Google Places
                    </div>
                </div>
            </div>

            <!-- Gallery -->
            <?php if (!empty($photoUrls)): ?>
            <div class="gallery">
                <?php foreach ($photoUrls as $photo): ?>
                <div class="gallery-item">
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($name); ?>" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Content Grid -->
            <div class="content-grid">
                <div class="left-col">
                    <!-- Address -->
                    <div class="info-box">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($address); ?></div>
                    </div>

                    <!-- Contact -->
                    <div class="info-box">
                        <div class="info-label">Contact</div>
                        <div class="info-value">
                            <?php if ($phone): ?>
                                <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>" style="color: #FF6A00; text-decoration: none;">
                                    📞 <?php echo htmlspecialchars($phone); ?>
                                </a>
                            <?php else: ?>
                                Phone not available
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Website -->
                    <?php if ($website): ?>
                    <div class="info-box">
                        <div class="info-label">Website</div>
                        <div class="info-value">
                            <a href="<?php echo htmlspecialchars($website); ?>" target="_blank" style="color: #FF6A00; text-decoration: none;">
                                🌐 Visit Website
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Opening Hours -->
                    <?php if (!empty($hours)): ?>
                    <div class="info-box">
                        <div class="info-label">Opening Hours</div>
                        <div class="info-value">
                            <?php foreach ($hours as $hour): ?>
                                <div><?php echo htmlspecialchars($hour); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Reviews -->
                    <?php if (!empty($reviews)): ?>
                    <div class="reviews-section">
                        <h3>Customer Reviews</h3>
                        <?php foreach (array_slice($reviews, 0, 5) as $review): ?>
                        <div class="review">
                            <div class="review-header">
                                <span class="review-author"><?php echo htmlspecialchars($review['author_name']); ?></span>
                                <span class="review-rating"><?php echo str_repeat('⭐', $review['rating']); ?></span>
                            </div>
                            <div><?php echo htmlspecialchars($review['text']); ?></div>
                            <div style="color: #999; font-size: 12px; margin-top: 8px;">
                                <?php echo date('M d, Y', strtotime($review['time'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="right-col">
                    <!-- Action Buttons -->
                    <div class="info-box">
                        <div class="action-buttons">
                            <?php if ($phone): ?>
                            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>" class="btn btn-primary">📞 Call Now</a>
                            <?php endif; ?>
                            <?php if ($website): ?>
                            <a href="<?php echo htmlspecialchars($website); ?>" target="_blank" class="btn btn-secondary">🌐 Website</a>
                            <?php else: ?>
                            <button class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;">No Website</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Info Summary -->
                    <div class="info-box">
                        <div class="info-label">Rating Summary</div>
                        <div style="font-size: 32px; color: #FFB800; margin-bottom: 10px;">
                            <?php echo $rating; ?>⭐
                        </div>
                        <div style="color: #666; font-size: 14px;">
                            Based on <?php echo number_format($reviewCount); ?> reviews
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-label">Business Type</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($firstCategory); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="google-source">
                This business information is sourced from Google Places. For the most up-to-date information, please visit Google Maps.
            </div>
        </div>
    </main>

    <script>
        // Track view
        fetch('/api/track-event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'business_id=google_<?php echo md5($placeId); ?>&event=view'
        }).catch(e => console.log('Tracking:', e));
    </script>
</body>
</html>
