<?php
/**
 * Premium Business Detail Page - AI-Powered High-Conversion Design
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../config/google-api.php';
require_once '../includes/tracking.php';
require '../includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/');
}

$id = (int)$_GET['id'];
$nameSlug = isset($_GET['name']) ? trim($_GET['name']) : '';

$query = "SELECT * FROM extracted_businesses WHERE id = ? AND business_status = 'OPERATIONAL'";
$stmt = $GLOBALS['conn']->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$business = $result->fetch_assoc();

if (!$business) {
    redirect('/');
}

trackBusinessView($business['id']);

$allTypes = !empty($business['types']) ? array_map('trim', explode(',', $business['types'])) : [];
$firstCategory = !empty($allTypes) ? $allTypes[0] : 'Business';

$hours = [];
if (!empty($business['opening_hours_weekday'])) {
    $hourLines = explode('|', $business['opening_hours_weekday']);
    foreach ($hourLines as $line) {
        $hours[] = trim($line);
    }
}

$photoRefs = parsePhotoReferences($business['photo_references'] ?? '');
$photoUrls = [];
foreach ($photoRefs as $ref) {
    $photoUrl = getGooglePlacesPhotoUrl($ref, 1200);
    if ($photoUrl) {
        $photoUrls[] = $photoUrl;
    }
}

$relatedQuery = "SELECT id, name, types, formatted_address, search_location, rating, user_ratings_total FROM extracted_businesses 
          WHERE types LIKE ? AND id != ? AND business_status = 'OPERATIONAL' 
          ORDER BY rating DESC LIMIT 6";
$searchType = '%' . $firstCategory . '%';
$relStmt = $GLOBALS['conn']->prepare($relatedQuery);
$relStmt->bind_param('si', $searchType, $id);
$relStmt->execute();
$relatedResult = $relStmt->get_result();
$related = [];
while ($row = $relatedResult->fetch_assoc()) {
    $related[] = $row;
}

require_once '../includes/ai-features.php';
// Prefer the latest manager-approved description over the auto-generated one
$aiDescription = null;
try {
    $latestDescStmt = $GLOBALS['conn']->prepare(
        "SELECT description FROM business_descriptions
         WHERE business_id = ? AND status = 'approved'
         ORDER BY created_at DESC LIMIT 1"
    );
    $latestDescStmt->bind_param('i', $id);
    $latestDescStmt->execute();
    $latestDescRow = $latestDescStmt->get_result()->fetch_assoc();
    $latestDescStmt->close();
    if ($latestDescRow && !empty($latestDescRow['description'])) {
        $aiDescription = $latestDescRow['description'];
        // Keep extracted_businesses in sync
        $syncStmt = $GLOBALS['conn']->prepare("UPDATE extracted_businesses SET ai_description = ? WHERE id = ? AND (ai_description IS NULL OR ai_description != ?)");
        $syncStmt->bind_param('sis', $aiDescription, $id, $aiDescription);
        $syncStmt->execute();
        $syncStmt->close();
    }
} catch (Exception $e) {}

if (empty($aiDescription)) {
    $aiDescription = $business['ai_description'] ?? null;
}
if (empty($aiDescription)) {
    $aiDescription = getAIDescription($id, $business['name'], $firstCategory, $business['search_location'] ?? '');
}

// Load custom manager FAQs first; fall back to AI-generated FAQs if none
$customFAQs = [];
if ($GLOBALS['conn']) {
    try {
        $faqRes = $GLOBALS['conn']->query(
            "SELECT question, answer FROM business_faqs
             WHERE business_id = $id ORDER BY sort_order ASC, id ASC LIMIT 20"
        );
        if ($faqRes) {
            $customFAQs = $faqRes->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {}
}

$aiFAQs = [];
if (!empty($customFAQs)) {
    // Use manager-supplied FAQs (already in same {question, answer} shape)
    $aiFAQs = $customFAQs;
} else {
    $faqs_json = $business['ai_faqs'] ?? null;
    if (!empty($faqs_json)) {
        $aiFAQs = json_decode($faqs_json, true) ?? [];
    }
    if (empty($aiFAQs)) {
        $aiFAQs = getAIFAQs($id, $business['name'], $firstCategory, $aiDescription);
    }
}

$userReviewsQuery = "SELECT ur.*, u.username FROM user_reviews ur 
                     LEFT JOIN users u ON ur.user_id = u.id 
                     WHERE ur.business_id = ? AND ur.status = 'approved' 
                     ORDER BY ur.created_at DESC LIMIT 5";
$userReviewStmt = $GLOBALS['conn']->prepare($userReviewsQuery);
$userReviewStmt->bind_param('i', $id);
$userReviewStmt->execute();
$userReviews = $userReviewStmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

// Get approved services
$servicesQuery = "SELECT * FROM business_services WHERE business_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 10";
$servicesStmt = $GLOBALS['conn']->prepare($servicesQuery);
$servicesStmt->bind_param('i', $id);
$servicesStmt->execute();
$services = $servicesStmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

// Get approved offers
$offersQuery = "SELECT * FROM business_offers WHERE business_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 10";
$offersStmt = $GLOBALS['conn']->prepare($offersQuery);
$offersStmt->bind_param('i', $id);
$offersStmt->execute();
$offers = $offersStmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

// Get approved updates/blog posts
$updatesQuery = "SELECT * FROM business_updates WHERE business_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 10";
$updatesStmt = $GLOBALS['conn']->prepare($updatesQuery);
$updatesStmt->bind_param('i', $id);
$updatesStmt->execute();
$updates = $updatesStmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

$heroImageUrl = !empty($photoUrls) ? $photoUrls[0] : null;

$managerMedia = [
    'logo_url'        => null,
    'intro_video_url' => null,
];
try {
    $mediaStmt = $GLOBALS['conn']->prepare("SELECT logo_url, intro_video_url FROM business_media WHERE business_id = ? LIMIT 1");
    $mediaStmt->bind_param('i', $id);
    $mediaStmt->execute();
    $managerMedia = $mediaStmt->get_result()->fetch_assoc() ?: $managerMedia;
    $mediaStmt->close();
} catch (Exception $e) {}

// Helper: resolve a video URL to its embed URL + type for rendering
function resolveVideoEmbed(string $url): array {
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

    // YouTube
    if (in_array($host, ['www.youtube.com','youtube.com','m.youtube.com','youtu.be'])) {
        $ytId = '';
        if (preg_match('#[?&]v=([a-zA-Z0-9_-]{11})#', $url, $m)) $ytId = $m[1];
        elseif (preg_match('#/(shorts|embed)/([a-zA-Z0-9_-]{11})#', $url, $m)) $ytId = $m[2];
        elseif (preg_match('#youtu\.be/([a-zA-Z0-9_-]{11})#', $url, $m)) $ytId = $m[1];
        if ($ytId) {
            return ['type' => 'iframe', 'src' => "https://www.youtube.com/embed/$ytId?rel=0"];
        }
    }

    // Vimeo
    if (in_array($host, ['vimeo.com','www.vimeo.com','player.vimeo.com'])) {
        if (preg_match('#/(\d+)#', $url, $m)) {
            return ['type' => 'iframe', 'src' => "https://player.vimeo.com/video/{$m[1]}"];
        }
    }

    // Dailymotion
    if (in_array($host, ['dailymotion.com','www.dailymotion.com'])) {
        if (preg_match('#/video/([a-zA-Z0-9]+)#', $url, $m)) {
            return ['type' => 'iframe', 'src' => "https://www.dailymotion.com/embed/video/{$m[1]}"];
        }
    }
    if ($host === 'dai.ly') {
        if (preg_match('#/([a-zA-Z0-9]+)#', $url, $m)) {
            return ['type' => 'iframe', 'src' => "https://www.dailymotion.com/embed/video/{$m[1]}"];
        }
    }

    // Direct video file or local upload
    $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
    if (preg_match('#\.(mp4|webm|ogg|m3u8)$#', $path) || strpos($url, '/assets/uploads/') === 0) {
        return ['type' => 'video', 'src' => $url];
    }

    // Fallback: iframe for any other https URL
    return ['type' => 'iframe', 'src' => $url];
}

$heroVideo = null;
if (!empty($managerMedia['intro_video_url'])) {
    $heroVideo = resolveVideoEmbed($managerMedia['intro_video_url']);
}

/**
 * Resolve a business_images row into a usable URL.
 */
function resolveImageRow(array $row): string {
    $imagePath = trim($row['image_path'] ?? '');
    $imageUrl  = trim($row['image_url']  ?? '');

    if ($imagePath !== '' && (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0)) {
        return $imagePath;
    }
    if ($imageUrl !== '' && (strpos($imageUrl, 'http://') === 0 || strpos($imageUrl, 'https://') === 0)) {
        return $imageUrl;
    }
    if ($imagePath !== '' && strpos($imagePath, 'photo_ref:') === 0) {
        $ref = substr($imagePath, strlen('photo_ref:'));
        $photoUrl = getGooglePlacesPhotoUrl($ref, 1200);
        return $photoUrl ?: '';
    }
    if ($imagePath !== '' && $imagePath[0] === '/') {
        $fullPath = dirname(__FILE__) . '/..' . $imagePath;
        if (file_exists($fullPath)) { return $imagePath; }
    }
    return '';
}

// Fetch infographic images (carousel) – fall back to Google photos if none uploaded
$infraImages = [];
// Fetch gallery images (photo grid)
$galleryImages = [];

if ($GLOBALS['conn']) {
    try {
        // Add photo_type column silently if it doesn't exist yet
        @$GLOBALS['conn']->query("ALTER TABLE business_images ADD COLUMN IF NOT EXISTS photo_type ENUM('gallery','infographic') NOT NULL DEFAULT 'infographic'");

        $infra_result = $GLOBALS['conn']->query("
            SELECT image_path, image_url FROM business_images
            WHERE business_id = $id AND photo_type = 'infographic'
            ORDER BY stored_at DESC
            LIMIT 20
        ");
        if ($infra_result) {
            while ($row = $infra_result->fetch_assoc()) {
                $url = resolveImageRow($row);
                if ($url) $infraImages[] = $url;
            }
        }

        $gallery_result = $GLOBALS['conn']->query("
            SELECT image_path, image_url FROM business_images
            WHERE business_id = $id AND photo_type = 'gallery'
            ORDER BY stored_at DESC
            LIMIT 100
        ");
        if ($gallery_result) {
            while ($row = $gallery_result->fetch_assoc()) {
                $url = resolveImageRow($row);
                if ($url) $galleryImages[] = $url;
            }
        }
    } catch (Exception $e) {
        // Table doesn't exist yet; will be created on first upload
    }
}

$infraImages   = array_values(array_unique(array_filter($infraImages)));
$galleryImages = array_values(array_unique(array_filter($galleryImages)));

// Infographic carousel falls back to Google photos when no infographics uploaded
if (empty($infraImages)) {
    $infraImages = $photoUrls;
}

// Keep $businessImages as alias for any legacy code
$businessImages = $infraImages;

// ── Load section visibility settings ──────────────────────────────────────
$visSettings = [];
if ($GLOBALS['conn']) {
    try {
        @$GLOBALS['conn']->query("CREATE TABLE IF NOT EXISTS business_visibility (
            business_id INT NOT NULL,
            section_key VARCHAR(60) NOT NULL,
            is_visible TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (business_id, section_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $vRes = $GLOBALS['conn']->query("SELECT section_key, is_visible FROM business_visibility WHERE business_id = $id");
        if ($vRes) {
            while ($vRow = $vRes->fetch_assoc()) {
                $visSettings[$vRow['section_key']] = (bool)$vRow['is_visible'];
            }
        }
    } catch (Exception $e) { }
}
// Helper: default true (visible) when not explicitly set
function visib(string $key, array $vis): bool {
    return $vis[$key] ?? true;
}

$page_title = "Best " . $firstCategory . " in " . ($business['search_location'] ?? 'area') . " | " . $business['name'];
$meta_description = "Find " . $business['name'] . ", top " . strtolower($firstCategory) . " in " . ($business['search_location'] ?? 'area') . ". Rating: " . ($business['rating'] ?? '0') . "/5. Call or WhatsApp now!";
$meta_keywords = implode(', ', $allTypes);
$meta_canonical = "https://" . $_SERVER['HTTP_HOST'] . "/pages/business-detail.php?id=" . $id;

$schemaData = [
    "@context" => "https://schema.org",
    "@type" => "LocalBusiness",
    "name" => $business['name'],
    "image" => !empty($heroImageUrl) ? $heroImageUrl : ($managerMedia['logo_url'] ?? null),
    "description" => $aiDescription,
    "url" => "https://" . $_SERVER['HTTP_HOST'] . "/pages/business-detail.php?id=" . $id,
    "telephone" => $business['formatted_phone_number'] ?? null,
    "address" => [
        "@type" => "PostalAddress",
        "streetAddress" => $business['formatted_address'] ?? '',
    ],
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => $business['rating'] ?? 0,
        "ratingCount" => $business['user_ratings_total'] ?? 0,
    ]
];

// Helper: render a description that may be HTML (from Quill editor) or plain text (AI-generated)
function renderDescription(string $text): string {
    if (empty(trim($text))) return '';
    if ($text !== strip_tags($text)) {
        // Content contains HTML tags — sanitise to safe subset
        return strip_tags($text, '<p><br><strong><em><u><s><ul><ol><li><h2><h3><h4><h5><a><blockquote><pre><code><span>');
    }
    // Plain text — preserve line breaks
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

include '../includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; }

/* Hero Section */
.premium-hero {
    background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
    color: white;
    padding: 60px 30px;
    position: relative;
    overflow: hidden;
}

.premium-hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 400px;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="20" fill="rgba(255,255,255,0.05)"/><circle cx="80" cy="80" r="30" fill="rgba(255,106,0,0.1)"/></svg>');
    pointer-events: none;
}

.hero-content {
    max-width: 1300px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 40px;
}

.hero-text-col {
    flex: 1;
    min-width: 0;
}

.hero-media-col {
    flex-shrink: 0;
    width: 340px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-media-col video {
    width: 100%;
    height: auto;
    aspect-ratio: 16/9;
    border-radius: 14px;
    object-fit: contain;
    background: #000;
    box-shadow: 0 8px 32px rgba(0,0,0,.35);
    display: block;
}

.hero-video-iframe-wrap {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,.35);
}

.hero-video-iframe-wrap iframe {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    border: none;
}

.hero-logo-box {
    width: 100%;
    max-height: 240px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.08);
    border: 2px solid rgba(255,255,255,0.18);
    border-radius: 14px;
    overflow: hidden;
    backdrop-filter: blur(6px);
    padding: 12px;
}

.hero-logo-box img {
    max-width: 100%;
    max-height: 216px;
    object-fit: contain;
    border-radius: 8px;
}

@media (max-width: 900px) {
    .hero-content { flex-direction: column-reverse; gap: 20px; }
    .hero-media-col { width: 100%; max-width: 360px; }
}

.hero-main-title {
    font-size: 42px;
    font-weight: 800;
    margin-bottom: 12px;
    line-height: 1.2;
    text-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.hero-subtitle {
    font-size: 18px;
    opacity: 0.95;
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.hero-rating {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 106, 0, 0.2);
    padding: 8px 16px;
    border-radius: 8px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.hero-rating-value {
    font-weight: 700;
    font-size: 16px;
}

.trust-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(76, 175, 80, 0.2);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid rgba(76, 175, 80, 0.4);
}

.hero-cta-group {
    display: flex;
    gap: 12px;
    margin-top: 28px;
    flex-wrap: wrap;
}

.hero-cta-btn {
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.hero-cta-primary {
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    color: white;
    box-shadow: 0 8px 24px rgba(255, 106, 0, 0.3);
}

.hero-cta-primary:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(255, 106, 0, 0.4);
}

.hero-cta-secondary {
    background: white;
    color: #0B1C3D;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.hero-cta-secondary:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.hero-cta-tertiary {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.hero-cta-tertiary:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Main Container */
.business-container {
    max-width: 1300px;
    margin: 0 auto;
    padding: 40px 30px;
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 30px;
}

/* Left Column - Main Content */
.main-content { }

/* Premium Cards */
.premium-card {
    background: white;
    border-radius: 12px;
    padding: 28px;
    margin-bottom: 28px;
    border: 1px solid #e8e8e8;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s;
}

.premium-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

.card-title {
    font-size: 22px;
    font-weight: 700;
    color: #0B1C3D;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #FF6A00;
}

/* About Section */
.about-text {
    font-size: 15px;
    line-height: 1.7;
    color: #555;
}
.about-text p { margin: 0 0 12px 0; }
.about-text ul, .about-text ol { padding-left: 22px; margin: 0 0 12px 0; }
.about-text li { margin-bottom: 4px; }
.about-text h2, .about-text h3, .about-text h4 { color: #0B1C3D; margin: 14px 0 6px; }
.about-text strong { color: #333; }
.about-text blockquote { border-left: 3px solid #FF6A00; padding-left: 12px; margin: 10px 0; color: #666; }
.about-text pre { background: #f5f5f5; padding: 10px 14px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
.about-text a { color: #1E3A8A; }

/* Business Details Grid */
.details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.detail-icon {
    font-size: 24px;
    min-width: 32px;
    text-align: center;
}

.detail-content {
    flex: 1;
}

.detail-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    color: #666;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.detail-value {
    font-size: 14px;
    color: #0B1C3D;
    font-weight: 600;
    word-break: break-word;
}

.detail-value a {
    color: #FF6A00;
    text-decoration: none;
}

.detail-value a:hover {
    text-decoration: underline;
}

/* Hours Section */
.hours-list {
    list-style: none;
}

.hours-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.hours-item:last-child {
    border-bottom: none;
}

.hours-day {
    font-weight: 600;
    color: #0B1C3D;
}

.hours-time {
    color: #666;
}

/* Map Section */
.map-container {
    border-radius: 12px;
    overflow: hidden;
    height: 400px;
    margin-bottom: 28px;
}

.map-container iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* FAQs */
.faq-item {
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 12px;
    overflow: hidden;
}

.faq-question {
    padding: 16px;
    background: #f0f0f0;
    font-weight: 700;
    color: #0B1C3D;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
    user-select: none;
}

.faq-question:hover {
    background: #FF6A00;
    color: white;
}

.faq-answer {
    display: none;
    padding: 16px;
    color: #666;
    font-size: 14px;
    line-height: 1.6;
}

.faq-answer.show {
    display: block;
}

/* Sticky Sidebar */
.sticky-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.contact-box {
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    border-radius: 12px;
    padding: 24px;
    color: white;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(255, 106, 0, 0.2);
}

.contact-box h3 {
    font-size: 18px;
    margin-bottom: 20px;
    font-weight: 700;
}

.contact-action {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.contact-btn {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: white;
    color: #FF6A00;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    text-decoration: none;
}

.contact-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Lead Form */
.lead-form-box {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e8e8e8;
    margin-bottom: 20px;
}

.lead-form-box h4 {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 16px;
    color: #0B1C3D;
}

.form-group {
    margin-bottom: 12px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 12px;
    font-family: inherit;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #FF6A00;
    box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
}

.form-submit {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
}

.form-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 106, 0, 0.3);
}

/* High-Conversion CTA Card */
.cta-card {
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    border-radius: 12px;
    padding: 24px;
    color: white;
    margin-bottom: 20px;
    box-shadow: 0 8px 24px rgba(255, 106, 0, 0.25);
}

.cta-title {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 16px;
    text-align: center;
    letter-spacing: 0.5px;
}

.cta-buttons-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

.cta-btn-primary,
.cta-btn-highlight {
    padding: 16px 12px;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    text-decoration: none;
    line-height: 1.2;
}

.cta-btn-primary {
    background: white;
    color: #FF6A00;
    font-size: 13px;
}

.cta-btn-primary:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.cta-btn-highlight {
    background: #0B1C3D;
    color: white;
    font-size: 13px;
}

.cta-btn-highlight:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
}

.cta-btn-secondary {
    width: 100%;
    padding: 12px;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid white;
    border-radius: 8px;
    color: white;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
}

.cta-btn-secondary:hover {
    background: white;
    color: #FF6A00;
}

.service-query-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #ff7a18 0%, #ff3d71 100%);
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 6px 18px rgba(255, 61, 113, 0.28);
}

.service-query-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(255, 61, 113, 0.36);
}

/* Trust Section */
.trust-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    border: 1px solid #e8e8e8;
}

.trust-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.trust-item span:first-child {
    min-width: 32px;
    text-align: center;
}

.trust-item span:last-child {
    flex: 1;
}

.trust-item strong {
    display: block;
    color: #0B1C3D;
    font-size: 13px;
    margin-bottom: 2px;
}

/* Lead Form Card */
.lead-form-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #FF6A00;
    box-shadow: 0 8px 20px rgba(255, 106, 0, 0.15);
}

.form-card-title {
    font-size: 16px;
    font-weight: 800;
    color: #0B1C3D;
    margin-bottom: 16px;
    text-align: center;
}

.form-submit-primary {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 800;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(255, 106, 0, 0.3);
}

.form-submit-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(255, 106, 0, 0.4);
}

.form-submit-primary:active {
    transform: translateY(-1px);
}

/* Related Businesses */
.related-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.related-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e8e8e8;
    transition: all 0.3s;
    text-decoration: none;
    color: inherit;
}

.related-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    border-color: #FF6A00;
}

.related-name {
    font-weight: 700;
    font-size: 14px;
    color: #0B1C3D;
    margin-bottom: 6px;
    line-height: 1.3;
}

.related-info {
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.related-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
}

.related-card-content {
    padding: 12px;
}

/* Mobile Sticky Bar */
.mobile-sticky-bar {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #0B1C3D;
    padding: 12px;
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.15);
    z-index: 999;
}

.mobile-cta-group {
    display: flex;
    gap: 10px;
    justify-content: space-around;
}

.mobile-cta-btn {
    flex: 1;
    padding: 10px;
    background: white;
    color: #0B1C3D;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 700;
    font-size: 11px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s;
}

.mobile-cta-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.mobile-cta-enquiry {
    background: #FF6A00;
    color: white;
}

/* Carousel Styles */
.photo-carousel {
    position: relative;
    width: 100%;
    overflow: hidden;
    border-radius: 10px;
    margin-top: 15px;
    background: #111;
    aspect-ratio: 16/7;
}

.carousel-track {
    display: flex;
    height: 100%;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.carousel-slide {
    min-width: 100%;
    height: 100%;
    position: relative;
    cursor: pointer;
}

.carousel-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.carousel-slide .slide-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.3);
    opacity: 0;
    transition: opacity 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.carousel-slide:hover .slide-overlay {
    opacity: 1;
}

.zoom-icon {
    font-size: 36px;
    color: white;
}

.carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.9);
    border: none;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    font-size: 18px;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.carousel-btn:hover {
    background: white;
    box-shadow: 0 4px 16px rgba(0,0,0,0.25);
    transform: translateY(-50%) scale(1.1);
}

.carousel-prev { left: 12px; }
.carousel-next { right: 12px; }

.carousel-dots {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
    z-index: 10;
}

.carousel-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0;
}

.carousel-dot.active {
    background: white;
    transform: scale(1.3);
}

.carousel-counter {
    position: absolute;
    top: 10px;
    right: 12px;
    background: rgba(0,0,0,0.55);
    color: white;
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 20px;
    z-index: 10;
}

/* Gallery Modal */
.gallery-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.95);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s;
}

.gallery-modal.show {
    display: flex;
}

.gallery-modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90vh;
}

.gallery-modal-image {
    width: 100%;
    max-height: 80vh;
    object-fit: contain;
}

.gallery-modal-close {
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 32px;
    color: white;
    cursor: pointer;
    background: none;
    border: none;
    transition: all 0.3s;
}

.gallery-modal-close:hover {
    transform: scale(1.2);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Photo Gallery Grid */
.photo-gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
    margin-top: 15px;
}

.gallery-grid-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 4/3;
    background: #f0f0f0;
    cursor: pointer;
}

.gallery-grid-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.3s ease;
}

.gallery-grid-item:hover img {
    transform: scale(1.05);
}

.gallery-grid-item .grid-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.3);
    opacity: 0;
    transition: opacity 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gallery-grid-item:hover .grid-overlay {
    opacity: 1;
}

.gallery-grid-item .grid-zoom {
    font-size: 28px;
    color: white;
}

@media (max-width: 480px) {
    .photo-gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 8px;
    }
}

/* Responsive */
@media (max-width: 1024px) {
    .business-container {
        grid-template-columns: 1fr;
    }

    .sticky-sidebar {
        position: static;
    }

    .related-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .premium-hero {
        padding: 40px 20px;
    }

    .hero-main-title {
        font-size: 28px;
    }

    .hero-subtitle {
        font-size: 14px;
    }

    .hero-cta-group {
        flex-direction: column;
    }

    .hero-cta-btn {
        width: 100%;
        justify-content: center;
    }

    .business-container {
        padding: 20px;
        gap: 20px;
    }

    .premium-card {
        padding: 16px;
        margin-bottom: 16px;
    }

    .card-title {
        font-size: 18px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .map-container {
        height: 300px;
    }

    .related-grid {
        grid-template-columns: 1fr;
    }

    .mobile-sticky-bar {
        display: block;
    }

    body {
        padding-bottom: 80px;
    }
}

@media (max-width: 480px) {
    .hero-main-title {
        font-size: 22px;
    }

    .hero-cta-group {
        gap: 8px;
    }

    .hero-cta-btn {
        padding: 10px 16px;
        font-size: 12px;
    }

    .business-container {
        padding: 16px;
    }

    .premium-card {
        padding: 14px;
    }

    .mobile-cta-btn {
        font-size: 10px;
        padding: 8px;
    }

    /* Review Tabs */
    .review-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e0e0e0;
    }

    .review-tab {
        padding: 12px 16px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .review-tab.active {
        color: #FF6A00;
        border-bottom-color: #FF6A00;
    }

    .review-tab:hover {
        color: #FF6A00;
    }

    .review-tab-content {
        display: none;
    }

    .review-tab-content.active {
        display: block;
    }

    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .review-item {
        padding: 16px;
        background: #f9f9f9;
        border-radius: 8px;
        border-left: 4px solid #FF6A00;
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .review-user {
        flex: 1;
    }

    .review-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }

    .review-rating {
        font-size: 14px;
        color: #FF6A00;
    }

    .review-date {
        font-size: 12px;
        color: #999;
    }

    .review-text {
        color: #555;
        line-height: 1.6;
        margin: 0;
    }
}
</style>

<div class="premium-hero">
    <div class="hero-content">
        <!-- Left: Business info -->
        <div class="hero-text-col">
            <h1 class="hero-main-title"><?php echo esc($business['name']); ?></h1>
            
            <div class="hero-subtitle">
                <span>🏷️ <?php echo esc($firstCategory); ?> • <?php echo esc($business['search_location']); ?></span>
                
                <?php if ($business['verified']): ?>
                <span class="trust-badge">✔ Verified Business</span>
                <?php else: ?>
                <span class="trust-badge" style="background: rgba(52, 168, 83, 0.2); border-color: rgba(52, 168, 83, 0.4);">📍 Data: Google</span>
                <?php endif; ?>
            </div>

            <?php if ($business['rating']): ?>
            <div class="hero-rating">
                <span>⭐</span>
                <span class="hero-rating-value"><?php echo number_format($business['rating'], 1); ?>/5</span>
                <span>(<?php echo $business['user_ratings_total'] ?? 0; ?> reviews)</span>
            </div>
            <?php endif; ?>

            <div class="hero-cta-group">
                <?php if (!empty($business['formatted_phone_number'])): ?>
                <a href="tel:<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>" class="hero-cta-btn hero-cta-primary">📞 Call Now</a>
                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>?text=Hi, I'm interested" target="_blank" rel="noopener" class="hero-cta-btn hero-cta-secondary">💬 WhatsApp</a>
                <?php endif; ?>
                <?php if (visib('popup_query', $visSettings)): ?>
                <a href="javascript:void(0)" class="hero-cta-btn hero-cta-secondary" onclick="openQueryModal()">📩 Send Query</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Intro video or logo -->
        <?php if ($heroVideo): ?>
        <div class="hero-media-col">
            <?php if ($heroVideo['type'] === 'video'): ?>
                <video src="<?php echo htmlspecialchars($heroVideo['src']); ?>"
                       controls playsinline preload="metadata"></video>
            <?php else: ?>
                <div class="hero-video-iframe-wrap">
                    <iframe src="<?php echo htmlspecialchars($heroVideo['src']); ?>"
                            allow="fullscreen; picture-in-picture"
                            allowfullscreen
                            loading="lazy"
                            title="Business intro video"></iframe>
                </div>
            <?php endif; ?>
        </div>
        <?php elseif (!empty($managerMedia['logo_url'])): ?>
        <div class="hero-media-col">
            <div class="hero-logo-box">
                <img src="<?php echo htmlspecialchars($managerMedia['logo_url']); ?>"
                     alt="<?php echo esc($business['name']); ?> logo">
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="business-container">
    <div class="main-content">
        <!-- Business Infographics Carousel -->
        <?php if (!empty($infraImages) && visib('infographics', $visSettings)): 
            $infraSlides = array_values(array_slice($infraImages, 0, 20));
            $totalSlides = count($infraSlides);
        ?>
        <div class="premium-card">
            <h2 class="card-title">Business Infographics</h2>
            <div class="photo-carousel" id="photoCarousel">
                <div class="carousel-track" id="carouselTrack">
                    <?php foreach ($infraSlides as $img_url): ?>
                    <div class="carousel-slide" onclick="openGalleryModal('<?php echo esc($img_url); ?>')">
                        <img src="<?php echo esc($img_url); ?>" alt="Business infographic" loading="lazy"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 200%22%3E%3Crect fill=%22%23e0e0e0%22 width=%22400%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2218%22 fill=%22%23999%22%3EImage unavailable%3C/text%3E%3C/svg%3E'">
                        <div class="slide-overlay"><span class="zoom-icon">🔍</span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($totalSlides > 1): ?>
                <button class="carousel-btn carousel-prev" onclick="carouselMove(-1)" aria-label="Previous">&#8249;</button>
                <button class="carousel-btn carousel-next" onclick="carouselMove(1)"  aria-label="Next">&#8250;</button>
                <div class="carousel-counter" id="carouselCounter">1 / <?php echo $totalSlides; ?></div>
                <div class="carousel-dots" id="carouselDots">
                    <?php for ($d = 0; $d < $totalSlides; $d++): ?>
                    <button class="carousel-dot <?php echo $d === 0 ? 'active' : ''; ?>"
                            onclick="carouselGoTo(<?php echo $d; ?>)" aria-label="Slide <?php echo $d+1; ?>"></button>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
            <script>
            (function() {
                var current = 0;
                var total   = <?php echo $totalSlides; ?>;
                var track   = document.getElementById('carouselTrack');
                var counter = document.getElementById('carouselCounter');
                var dots    = document.querySelectorAll('#carouselDots .carousel-dot');
                var autoTimer;

                function updateCarousel() {
                    track.style.transform = 'translateX(-' + (current * 100) + '%)';
                    if (counter) counter.textContent = (current + 1) + ' / ' + total;
                    dots.forEach(function(d, i) { d.classList.toggle('active', i === current); });
                }

                window.carouselMove = function(dir) {
                    current = (current + dir + total) % total;
                    updateCarousel();
                    resetAuto();
                };

                window.carouselGoTo = function(idx) {
                    current = idx;
                    updateCarousel();
                    resetAuto();
                };

                function resetAuto() {
                    clearInterval(autoTimer);
                    if (total > 1) autoTimer = setInterval(function() { current = (current + 1) % total; updateCarousel(); }, 4000);
                }

                var startX = 0;
                var el = document.getElementById('photoCarousel');
                el.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; }, { passive: true });
                el.addEventListener('touchend', function(e) {
                    var diff = startX - e.changedTouches[0].clientX;
                    if (Math.abs(diff) > 40) window.carouselMove(diff > 0 ? 1 : -1);
                }, { passive: true });

                resetAuto();
            })();
            </script>
        </div>
        <?php endif; ?>

        <!-- Business Gallery Photos Grid -->
        <?php if (!empty($galleryImages) && visib('gallery', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Photo Gallery <span style="font-size:14px;font-weight:500;color:#888;">(<?php echo count($galleryImages); ?> photos)</span></h2>
            <div class="photo-gallery-grid">
                <?php foreach ($galleryImages as $img_url): ?>
                <div class="gallery-grid-item" onclick="openGalleryModal('<?php echo esc($img_url); ?>')">
                    <img src="<?php echo esc($img_url); ?>" alt="Gallery photo" loading="lazy"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 150%22%3E%3Crect fill=%22%23e0e0e0%22 width=%22200%22 height=%22150%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2213%22 fill=%22%23999%22%3EPhoto unavailable%3C/text%3E%3C/svg%3E'">
                    <div class="grid-overlay"><span class="grid-zoom">🔍</span></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Services Section -->
        <?php if (!empty($services) && visib('services', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Services Offered</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <?php foreach ($services as $service): ?>
                <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 18px; background: #fafafa; transition: all 0.3s ease;">
                    <div style="margin-bottom: 12px;">
                        <h3 style="margin: 0 0 8px 0; color: #1E3A8A; font-size: 18px;"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <?php if (!empty($service['duration'])): ?>
                        <span style="display: inline-block; background: #f0f7ff; color: #1E3A8A; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">⏱️ <?php echo htmlspecialchars($service['duration']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($service['description'])): ?>
                    <div class="about-text" style="font-size:14px;margin:10px 0;"><?php echo renderDescription($service['description']); ?></div>
                    <?php endif; ?>
                        <?php if (!empty($service['price']) && (float)$service['price'] > 0): ?>
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e0e0e0;">
                        <span style="font-size: 20px; color: #FF6A00; font-weight: bold;">₹<?php echo number_format($service['price'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                        <button type="button" class="service-query-btn" style="margin-top:12px;width:100%;" onclick='openServiceQueryModal(<?php echo json_encode([
                            "service_name" => $service["service_name"] ?? "",
                            "phone" => preg_replace("/\\D/", "", $business["formatted_phone_number"] ?? ""),
                            "customer_name" => $_SESSION["full_name"] ?? ($_SESSION["username"] ?? "")
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG); ?>)'>Send Query</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- About Business -->
        <?php if (!empty($aiDescription) && visib('about', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">About This Business</h2>
            <div class="about-text"><?php echo renderDescription($aiDescription); ?></div>
        </div>
        <?php endif; ?>

        <!-- Business Details -->
        <?php if (visib('details', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Business Details</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-icon">📞</div>
                    <div class="detail-content">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value" style="display: flex; gap: 8px; align-items: center;">
                            <a href="tel:<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>"><?php echo esc($business['formatted_phone_number']); ?></a>
                            <button onclick="copyPhone('<?php echo esc($business['formatted_phone_number']); ?>')" style="background: #FF6A00; color: white; border: none; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.3s;">📋 Copy</button>
                        </div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon">📍</div>
                    <div class="detail-content">
                        <div class="detail-label">Address</div>
                        <div class="detail-value"><?php echo esc($business['formatted_address']); ?></div>
                    </div>
                </div>

                <?php if (!empty($business['website'])): ?>
                <div class="detail-item">
                    <div class="detail-icon">🌐</div>
                    <div class="detail-content">
                        <div class="detail-label">Website</div>
                        <div class="detail-value"><a href="<?php echo esc($business['website']); ?>" target="_blank" rel="noopener">Visit Website →</a></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="detail-item">
                    <div class="detail-icon">🏷️</div>
                    <div class="detail-content">
                        <div class="detail-label">Category</div>
                        <div class="detail-value"><?php echo esc(implode(', ', array_slice($allTypes, 0, 2))); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Hours -->
        <?php if (!empty($hours) && visib('hours', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Business Hours</h2>
            <ul class="hours-list">
                <?php foreach ($hours as $hour): ?>
                <li class="hours-item">
                    <span class="hours-day"><?php echo esc(strtok($hour, ':')); ?></span>
                    <span class="hours-time"><?php echo esc(substr($hour, strlen(strtok($hour, ':')) + 1)); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Map -->
        <?php if (!empty($business['lat']) && !empty($business['lng']) && visib('map', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Location</h2>
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3684.4363400000003!2d<?php echo $business['lng']; ?>!3d<?php echo $business['lat']; ?>!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2z<?php echo urlencode($business['formatted_address']); ?>!5e0!3m2!1sen!2sin!4v1" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
        <?php endif; ?>

        <!-- FAQs -->
        <?php if (!empty($aiFAQs) && visib('faqs', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Frequently Asked Questions</h2>
            <?php foreach ($aiFAQs as $index => $faq): 
                $question = '';
                $answer = '';
                if (is_array($faq)) {
                    $question = $faq['question'] ?? $faq['q'] ?? '';
                    $answer = $faq['answer'] ?? $faq['a'] ?? '';
                } else {
                    $question = $faq;
                }
            ?>
            <div class="faq-item">
                <div class="faq-question" onclick="document.querySelector('.faq-answer-<?php echo $index; ?>').classList.toggle('show')">
                    <span><?php echo esc($question); ?></span>
                    <span>▼</span>
                </div>
                <div class="faq-answer faq-answer-<?php echo $index; ?>">
                    <?php echo esc($answer); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Services Section -->
        <!-- Offers Section -->
        <?php if (!empty($offers) && visib('offers', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Special Offers & Promotions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <?php foreach ($offers as $offer): 
                    $discountDisplay = $offer['discount_type'] === 'percentage' ? $offer['discount_value'] . '%' : '₹' . number_format($offer['discount_value'], 2);
                ?>
                <div style="background: linear-gradient(135deg, #FF6A00 0%, #FF8533 100%); border-radius: 8px; padding: 20px; color: white; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -10px; right: -10px; width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                    <h3 style="margin: 0 0 8px 0; font-size: 20px;"><?php echo htmlspecialchars($offer['offer_title']); ?></h3>
                    <?php if (!empty($offer['description'])): ?>
                    <div style="margin:8px 0;font-size:14px;opacity:.9;line-height:1.6;"><?php echo renderDescription($offer['description']); ?></div>
                    <?php endif; ?>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3);">
                        <div style="font-size: 32px; font-weight: bold; margin-bottom: 8px;">-<?php echo $discountDisplay; ?></div>
                        <?php if (!empty($offer['valid_from']) || !empty($offer['valid_until'])): ?>
                        <div style="font-size: 12px; opacity: 0.85;">
                            <?php 
                            if (!empty($offer['valid_from']) && !empty($offer['valid_until'])) {
                                echo 'Valid: ' . date('M d', strtotime($offer['valid_from'])) . ' - ' . date('M d, Y', strtotime($offer['valid_until']));
                            } elseif (!empty($offer['valid_until'])) {
                                echo 'Until: ' . date('M d, Y', strtotime($offer['valid_until']));
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Business Updates/Blog Section -->
        <?php if (!empty($updates) && visib('updates', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Latest Updates</h2>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($updates as $update): ?>
                <div style="border-left: 4px solid #1E3A8A; padding-left: 18px; padding-top: 12px; padding-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                        <h3 style="margin: 0; color: #1E3A8A; font-size: 17px;"><?php echo htmlspecialchars($update['title']); ?></h3>
                        <span style="font-size: 12px; color: #999; white-space: nowrap; margin-left: 12px;"><?php echo date('M d, Y', strtotime($update['created_at'])); ?></span>
                    </div>
                    <p style="margin: 8px 0 0 0; color: #555; font-size: 14px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars(substr($update['content'], 0, 200))); ?><?php echo strlen($update['content']) > 200 ? '...' : ''; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reviews Section -->
        <?php if (visib('reviews', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Reviews</h2>
            
            <!-- Google & Local Reviews Tabs -->
            <div class="review-tabs">
                <button class="review-tab active" onclick="showReviewTab('local', this)">Local Reviews (<?php echo count($userReviews); ?>)</button>
                <button class="review-tab" onclick="showReviewTab('google', this)">Google Reviews (<?php echo $business['user_ratings_total'] ?? 0; ?>)</button>
            </div>
            
            <!-- Local Reviews Tab -->
            <div id="local-reviews-tab" class="review-tab-content active">
                <?php if (!empty($userReviews)): ?>
                    <div class="reviews-list">
                        <?php foreach ($userReviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-user">
                                    <div class="review-name"><?php echo esc($review['username'] ?? 'Anonymous'); ?></div>
                                    <div class="review-rating">
                                        <?php 
                                        $rating = (int)($review['rating'] ?? 0);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '⭐' : '☆';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'] ?? 'now')); ?></div>
                            </div>
                            <p class="review-text"><?php echo esc($review['review_text'] ?? ''); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 30px; text-align: center; background: #f9f9f9; border-radius: 8px;">
                        <p style="color: #999; margin: 0;">No reviews yet. Be the first to review!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Google Reviews Tab -->
            <div id="google-reviews-tab" class="review-tab-content">
                <div style="padding: 20px; background: #f9f9f9; border-radius: 8px; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">⭐ <?php echo number_format($business['rating'] ?? 0, 1); ?>/5</div>
                    <p style="margin: 5px 0; color: #666;">Based on <?php echo number_format($business['user_ratings_total'] ?? 0); ?> Google reviews</p>
                    <p style="font-size: 12px; color: #999; margin: 10px 0 0 0;">Google reviews are loaded directly from Google Places</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Related Businesses -->
        <?php if (!empty($related) && visib('related', $visSettings)): ?>
        <div class="premium-card">
            <h2 class="card-title">Similar Businesses</h2>
            <div class="related-grid">
                <?php foreach ($related as $rel): ?>
                <a href="/pages/business-detail.php?id=<?php echo $rel['id']; ?>" class="related-card">
                    <div class="related-card-content">
                        <div class="related-name"><?php echo esc($rel['name']); ?></div>
                        <div class="related-info">📍 <?php echo esc($rel['search_location']); ?></div>
                        <?php if ($rel['rating']): ?>
                        <div class="related-rating">
                            <span>⭐</span>
                            <span><?php echo number_format($rel['rating'], 1); ?></span>
                            <span>(<?php echo $rel['user_ratings_total'] ?? 0; ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar - High-Conversion Panel -->
    <div class="sticky-sidebar">
        <!-- Premium CTA Section -->
        <?php if (visib('sidebar_cta', $visSettings)): ?>
        <div class="cta-card">
            <div class="cta-title">Connect Now</div>
            <?php if (!empty($business['formatted_phone_number'])): ?>
            <div class="cta-buttons-group">
                <a href="tel:<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>" class="cta-btn-primary">
                    📞<br>Call Now
                </a>
                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>" target="_blank" rel="noopener" class="cta-btn-highlight">
                    💬<br>WhatsApp
                </a>
            </div>
            <button onclick="shareWhatsApp('<?php echo esc($business['name']); ?>')" class="cta-btn-secondary" style="width: 100%; margin-top: 10px;">📤 Share Business</button>
            <?php endif; ?>
        </div>

        <!-- Trust Section -->
        <div class="trust-card">
            <div class="trust-item">
                <?php if ($business['verified']): ?>
                <span style="font-size: 20px;">✔️</span>
                <span><strong>Verified Business</strong><br><span style="font-size: 11px; color: #888;">Confirmed & Authentic</span></span>
                <?php else: ?>
                <span style="font-size: 20px;">📍</span>
                <span><strong>From Google</strong><br><span style="font-size: 11px; color: #888;">Data Source: Google Maps</span></span>
                <?php endif; ?>
            </div>
            <div class="trust-item" style="border-top: 1px solid #f0f0f0; padding-top: 12px; margin-top: 12px;">
                <span style="font-size: 20px;">⚡</span>
                <span><strong>Quick Response</strong><br><span style="font-size: 11px; color: #888;">Usually replies within 2 hours</span></span>
            </div>
        </div>

        <?php endif; ?>

        <!-- Lead Capture Form -->
        <?php if (visib('sidebar_form', $visSettings)): ?>
        <div class="lead-form-card" id="enquiry-form">
            <div class="form-card-title">Get Callback</div>
            <form method="POST" onsubmit="handleEnquiry(event)">
                <div class="form-group">
                    <input type="text" name="name" id="query-name" placeholder="Your Name" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" id="query-phone" placeholder="Phone Number" required>
                </div>
                <div class="form-group">
                    <input type="text" name="service" id="query-service" placeholder="What service do you need?" required>
                </div>
                <button type="submit" class="form-submit-primary">🚀 Get Callback Now</button>
                <div id="form-msg" style="margin-top: 12px; font-size: 12px; text-align: center; min-height: 20px;"></div>
            </form>
            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 11px; color: #999;">
                ✓ Free • No Spam • 100% Secure
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Gallery Modal -->
<div id="gallery-modal" class="gallery-modal" onclick="closeGalleryModal(event)">
    <button class="gallery-modal-close" onclick="closeGalleryModal()">✕</button>
    <div class="gallery-modal-content" onclick="event.stopPropagation()">
        <img id="gallery-modal-image" src="" alt="Large photo" class="gallery-modal-image">
    </div>
</div>

<!-- Mobile Sticky Bar -->
<?php if (visib('popup_mobile_bar', $visSettings)): ?>
<div class="mobile-sticky-bar">
    <div class="mobile-cta-group">
        <?php if (!empty($business['formatted_phone_number'])): ?>
        <a href="tel:<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>" class="mobile-cta-btn">📞 Call</a>
        <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>?text=Hi, interested in your services" target="_blank" rel="noopener" class="mobile-cta-btn">💬 Chat</a>
        <?php endif; ?>
        <?php if (visib('popup_query', $visSettings)): ?>
        <a href="javascript:void(0)" class="mobile-cta-btn mobile-cta-enquiry" onclick="openQueryModal()">📩 Enquiry</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (visib('popup_query', $visSettings)): ?>
<div id="query-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; max-width:520px; width:100%; border-radius:14px; padding:24px; position:relative;">
        <button type="button" onclick="closeServiceQueryModal()" style="position:absolute; right:16px; top:16px; border:none; background:#f3f4f6; width:34px; height:34px; border-radius:50%; cursor:pointer;">✕</button>
        <h3 style="margin-top:0;">Send Query</h3>
        <form method="POST" onsubmit="handleEnquiry(event)">
            <div class="form-group">
                <input type="text" id="modal-query-name" name="name" placeholder="Your Name" required>
            </div>
            <div class="form-group">
                <input type="tel" id="modal-query-phone" name="phone" placeholder="Phone Number" required>
            </div>
            <div class="form-group">
                <input type="text" id="modal-query-service" name="service" placeholder="Service Name" required>
            </div>
            <button type="submit" class="form-submit-primary">🚀 Send Query</button>
            <div id="modal-form-msg" style="margin-top: 12px; font-size: 12px; text-align: center; min-height: 20px;"></div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function handleEnquiry(e) {
    e.preventDefault();
    const form = e.target;
    const msgEl = form.closest('#query-modal') ? document.getElementById('modal-form-msg') : document.getElementById('form-msg');
    const submitBtn = form.querySelector('.form-submit-primary') || form.querySelector('.form-submit') || form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent : '';
    
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Sending...';
    msgEl.textContent = '';

    const formData = new FormData(form);
    formData.append('business_id', <?php echo $id; ?>);

    fetch('/api/send-enquiry.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json())
    .then(data => {
        if (data.success) {
            msgEl.textContent = '✓ Enquiry sent successfully!';
            msgEl.style.color = '#4CAF50';
            form.reset();
            setTimeout(() => { msgEl.textContent = ''; }, 3000);
        } else {
            msgEl.textContent = '✗ ' + (data.error || 'Failed');
            msgEl.style.color = '#FF6A00';
        }
    }).catch(err => {
        msgEl.textContent = '✗ Error: ' + err.message;
        msgEl.style.color = '#FF6A00';
    }).finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function openQueryModal() {
    document.getElementById('query-modal').style.display = 'flex';
}

function closeServiceQueryModal() {
    document.getElementById('query-modal').style.display = 'none';
}

function openServiceQueryModal(data) {
    document.getElementById('query-modal').style.display = 'flex';
    document.getElementById('modal-query-name').value = data.customer_name || '';
    document.getElementById('modal-query-phone').value = data.phone || '';
    document.getElementById('modal-query-service').value = data.service_name || '';
}

// Show mobile bar on mobile
window.addEventListener('DOMContentLoaded', () => {
    const bar = document.querySelector('.mobile-sticky-bar');
    const updateBar = () => {
        if (window.innerWidth <= 768) {
            bar.style.display = 'block';
        } else {
            bar.style.display = 'none';
        }
    };
    updateBar();
    window.addEventListener('resize', updateBar);
});

// Copy phone number to clipboard
function copyPhone(phone) {
    const cleanPhone = phone.replace(/\D/g, '');
    navigator.clipboard.writeText(cleanPhone).then(() => {
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = '✓ Copied!';
        setTimeout(() => { btn.textContent = originalText; }, 2000);
        trackAction('copy_phone');
    });
}

// Share on WhatsApp
function shareWhatsApp(businessName, message) {
    const text = `Check out ${businessName}: ${message || 'I found this business on ConnectWith9'}`;
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(text)} ${window.location.href}`;
    window.open(whatsappUrl, '_blank');
    trackAction('share_whatsapp');
}

// Click tracking
function trackAction(actionType, actionValue) {
    const phone = '<?php echo preg_replace('/\D/', '', $business['formatted_phone_number'] ?? ''); ?>';
    const businessId = <?php echo $id; ?>;
    
    if (!phone || !businessId) return;
    
    fetch('/api/track-action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            business_id: businessId,
            phone: phone,
            action_type: actionType,
            action_value: actionValue,
            timestamp: new Date().toISOString()
        })
    }).catch(() => {}); // Fail silently
}

// Track call clicks
document.querySelectorAll('a[href^="tel:"]').forEach(link => {
    link.addEventListener('click', () => trackAction('call_click'));
});

// Track WhatsApp clicks
document.querySelectorAll('a[href^="https://wa.me"]').forEach(link => {
    link.addEventListener('click', () => trackAction('whatsapp_click'));
});

// Lazy load images
document.querySelectorAll('img[data-src]').forEach(img => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    observer.observe(img);
});

// Gallery Modal Functions
function openGalleryModal(imgUrl) {
    const modal = document.getElementById('gallery-modal');
    const modalImg = document.getElementById('gallery-modal-image');
    modalImg.src = imgUrl;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeGalleryModal(event) {
    if (event && event.target.id !== 'gallery-modal') return;
    const modal = document.getElementById('gallery-modal');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Close gallery on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeGalleryModal();
});

// Store business images locally on page load
window.addEventListener('DOMContentLoaded', () => {
    const businessId = <?php echo $id; ?>;
    const hasImages = document.querySelector('.gallery-item') !== null;
    
    if (businessId && !hasImages) {
        // Trigger image storage in background
        fetch('/api/store-business-images.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'business_id=' + businessId
        }).catch(() => {}); // Fail silently
    }
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
        const href = link.getAttribute('href');
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Review Tab Switching
function showReviewTab(tabName, button) {
    // Hide all tabs
    document.querySelectorAll('.review-tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.review-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    const tabElement = document.getElementById(tabName + '-reviews-tab');
    if (tabElement) {
        tabElement.classList.add('active');
    }
    
    // Add active class to clicked button
    button.classList.add('active');
}
</script>

<!-- Exit Intent Popup -->
<?php if (visib('popup_exit_intent', $visSettings)): ?>
<div id="exit-intent-popup" class="cro-popup cro-popup-hidden">
    <div class="cro-popup-overlay"></div>
    <div class="cro-popup-content">
        <button class="cro-popup-close" onclick="closeExitPopup()">✕</button>
        <div class="cro-popup-icon">🚀</div>
        <h3 class="cro-popup-title">Wait! Get Best Deals Instantly</h3>
        <p class="cro-popup-text">Share your details and get a callback within minutes.</p>
        <form onsubmit="handleExitPopupSubmit(event)">
            <input type="text" name="name" placeholder="Your Name" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <button type="submit" class="cro-popup-btn">🚀 Get Callback Now</button>
        </form>
        <p class="cro-popup-guarantee">✓ No Spam • 100% Secure</p>
    </div>
</div>
<?php endif; ?>

<!-- Timed Popup -->
<?php if (visib('popup_timed', $visSettings)): ?>
<div id="timed-popup" class="cro-popup cro-popup-hidden">
    <div class="cro-popup-overlay"></div>
    <div class="cro-popup-content cro-popup-small">
        <button class="cro-popup-close" onclick="closeTimedPopup()">✕</button>
        <h3 class="cro-popup-title">Need Help?</h3>
        <p class="cro-popup-text">Get in touch with us instantly</p>
        <div class="cro-popup-actions">
            <?php if (!empty($business['formatted_phone_number'])): ?>
            <a href="tel:<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>" class="cro-action-btn cro-action-call">📞 Call Now</a>
            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>" target="_blank" rel="noopener" class="cro-action-btn cro-action-whatsapp">💬 WhatsApp</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Floating CTA Buttons -->
<?php if (visib('popup_floating_cta', $visSettings)): ?>
<div class="floating-cta-buttons">
    <?php if (!empty($business['formatted_phone_number'])): ?>
    <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>" target="_blank" rel="noopener" class="floating-btn floating-whatsapp" title="Chat on WhatsApp">
        <span>💬</span>
    </a>
    <a href="tel:<?php echo preg_replace('/\D/', '', $business['formatted_phone_number']); ?>" class="floating-btn floating-call" title="Call Now">
        <span>📞</span>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Urgency Badges -->
<div id="urgency-badges" style="display: none;">
    <div class="urgency-badge">🔥 Limited Time Offer</div>
    <div class="urgency-badge">⚡ Fast Response Guaranteed</div>
</div>

<style>
/* CRO Popup Styles */
.cro-popup {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.cro-popup-hidden {
    opacity: 0;
    pointer-events: none;
}

.cro-popup.show {
    opacity: 1;
    pointer-events: auto;
}

.cro-popup-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    cursor: pointer;
}

.cro-popup-content {
    position: relative;
    background: white;
    border-radius: 16px;
    padding: 40px 32px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.cro-popup-small {
    max-width: 380px;
    padding: 32px 24px;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.cro-popup-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    transition: all 0.3s;
}

.cro-popup-close:hover {
    color: #0B1C3D;
    transform: scale(1.2);
}

.cro-popup-icon {
    font-size: 48px;
    text-align: center;
    margin-bottom: 16px;
}

.cro-popup-title {
    font-size: 24px;
    font-weight: 800;
    color: #0B1C3D;
    margin-bottom: 12px;
    text-align: center;
}

.cro-popup-text {
    font-size: 14px;
    color: #666;
    margin-bottom: 20px;
    text-align: center;
    line-height: 1.6;
}

.cro-popup-content form {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

.cro-popup-content input {
    padding: 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
    font-family: inherit;
}

.cro-popup-content input:focus {
    outline: none;
    border-color: #FF6A00;
    box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
}

.cro-popup-btn {
    padding: 14px;
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 800;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(255, 106, 0, 0.3);
}

.cro-popup-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(255, 106, 0, 0.4);
}

.cro-popup-guarantee {
    font-size: 12px;
    color: #999;
    text-align: center;
    margin: 0;
}

.cro-popup-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.cro-action-btn {
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
    transition: all 0.3s;
    cursor: pointer;
}

.cro-action-call {
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    color: white;
}

.cro-action-call:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(255, 106, 0, 0.3);
}

.cro-action-whatsapp {
    background: linear-gradient(135deg, #25D366, #34A853);
    color: white;
}

.cro-action-whatsapp:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
}

/* Floating CTA Buttons */
.floating-cta-buttons {
    position: fixed;
    bottom: 30px;
    right: 30px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    z-index: 100;
}

.floating-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: bounce 2s infinite;
}

.floating-whatsapp {
    background: linear-gradient(135deg, #25D366, #34A853);
    color: white;
    animation-delay: 0s;
}

.floating-call {
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    color: white;
    animation-delay: 0.2s;
}

.floating-btn:hover {
    transform: scale(1.15);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.urgency-badge {
    background: linear-gradient(135deg, #FF6A00, #FFB347);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 12px;
    text-align: center;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .cro-popup-content {
        width: 85%;
        padding: 28px 20px;
        max-width: calc(100% - 40px);
    }

    .floating-cta-buttons {
        bottom: 90px;
        right: 20px;
    }

    .floating-btn {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }

    .cro-popup-title {
        font-size: 20px;
    }

    .cro-popup-text {
        font-size: 13px;
    }

    .cro-popup-btn,
    .cro-action-btn {
        font-size: 12px;
        padding: 12px;
    }

    .cro-popup-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Exit Intent Detection
let exitIntentShown = false;
document.addEventListener('mouseout', function(e) {
    if (e.clientY < 50 && !exitIntentShown && window.innerWidth > 768) {
        showExitPopup();
        exitIntentShown = true;
    }
});

function showExitPopup() {
    const popup = document.getElementById('exit-intent-popup');
    popup.classList.remove('cro-popup-hidden');
    popup.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeExitPopup() {
    const popup = document.getElementById('exit-intent-popup');
    popup.classList.remove('show');
    popup.classList.add('cro-popup-hidden');
    document.body.style.overflow = 'auto';
}

// Timed Popup (15-20 seconds)
let timedPopupShown = false;
setTimeout(() => {
    if (!timedPopupShown && window.innerWidth > 768) {
        const popup = document.getElementById('timed-popup');
        popup.classList.remove('cro-popup-hidden');
        popup.classList.add('show');
        document.body.style.overflow = 'hidden';
        timedPopupShown = true;
    }
}, 15000);

function closeTimedPopup() {
    const popup = document.getElementById('timed-popup');
    popup.classList.remove('show');
    popup.classList.add('cro-popup-hidden');
    document.body.style.overflow = 'auto';
}

// Close popup on overlay click
document.querySelectorAll('.cro-popup-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        const popup = e.target.closest('.cro-popup');
        if (popup.id === 'exit-intent-popup') closeExitPopup();
        if (popup.id === 'timed-popup') closeTimedPopup();
    });
});

// Exit Popup Form Submission
function handleExitPopupSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const name = form.querySelector('input[name="name"]').value;
    const phone = form.querySelector('input[name="phone"]').value;

    const formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('email', '');
    formData.append('message', 'Lead from exit intent popup');
    formData.append('business_id', <?php echo $id; ?>);

    fetch('/api/send-enquiry.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json())
    .then(data => {
        if (data.success) {
            const whatsappUrl = `https://wa.me/${phone.replace(/\D/g, '')}?text=Hi, you requested a callback`;
            window.open(whatsappUrl, '_blank');
            closeExitPopup();
        }
    }).catch(() => {
        closeExitPopup();
    });
}

// Track view count (simulated - replace with actual count from DB)
const viewCount = Math.floor(Math.random() * 20) + 5;
const contactCount = Math.floor(Math.random() * 8) + 2;

// Show urgency badges occasionally
if (Math.random() > 0.3) {
    const badges = document.getElementById('urgency-badges');
    if (badges) badges.style.display = 'block';
}
</script>

<script type="application/ld+json">
<?php echo json_encode($schemaData, JSON_UNESCAPED_SLASHES); ?>
</script>

<?php include '../includes/claim-modal.php'; ?>
<?php include '../includes/footer.php'; ?>
