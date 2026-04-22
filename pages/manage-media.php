<?php
/**
 * Manage Business Media – Business Logo & Intro Video
 * Allows approved listing managers to upload/replace their logo and intro video
 * (via file upload OR an external link from YouTube, Vimeo, etc.).
 */

require_once '../config/db.php';
require_once '../config/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header('Location: /pages/dashboard.php');
    exit;
}

$business_id = (int)$_GET['id'];

// Verify approved claim
$claimStmt = $GLOBALS['conn']->prepare("
    SELECT lc.id FROM listing_claims lc
    WHERE lc.business_id = ? AND lc.user_id = ? AND lc.claim_status = 'approved'
");
$claimStmt->bind_param('ii', $business_id, $user_id);
$claimStmt->execute();
if ($claimStmt->get_result()->num_rows === 0) {
    header('Location: /pages/dashboard.php');
    exit;
}
$claimStmt->close();

// Get business name
$bStmt = $GLOBALS['conn']->prepare("SELECT name FROM extracted_businesses WHERE id = ?");
$bStmt->bind_param('i', $business_id);
$bStmt->execute();
$business = $bStmt->get_result()->fetch_assoc();
$bStmt->close();
if (!$business) { header('Location: /pages/dashboard.php'); exit; }

// Ensure business_media table exists
$GLOBALS['conn']->query("CREATE TABLE IF NOT EXISTS business_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL UNIQUE,
    logo_url VARCHAR(500) DEFAULT NULL,
    intro_video_url VARCHAR(500) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bm_business (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Load existing
$media = ['logo_url' => null, 'intro_video_url' => null];
$mRow = $GLOBALS['conn']->query("SELECT logo_url, intro_video_url FROM business_media WHERE business_id = $business_id LIMIT 1");
if ($mRow && $mRow->num_rows) { $media = $mRow->fetch_assoc(); }

// ── Video link validator ──────────────────────────────────────────────────────
/**
 * Returns the canonical storage URL if valid, or false if invalid.
 * Accepts:
 *  - YouTube  (youtube.com/watch?v=, youtu.be/, youtube.com/shorts/)
 *  - Vimeo    (vimeo.com/)
 *  - Dailymotion (dailymotion.com/video/, dai.ly/)
 *  - Direct video files ending in .mp4 / .webm / .ogg / .m3u8
 *  - Any other https URL (accepted as-is, rendered in iframe)
 */
function validateVideoLink(string $raw): string|false {
    $url = trim($raw);
    if (empty($url)) return false;

    // Must be a proper http/https URL
    if (!preg_match('#^https?://#i', $url)) return false;
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;

    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

    // ── YouTube ──────────────────────────────────────────────────────────────
    if (in_array($host, ['www.youtube.com','youtube.com','m.youtube.com'])) {
        // /watch?v=ID
        if (preg_match('#[?&]v=([a-zA-Z0-9_-]{11})#', $url, $m)) return $url;
        // /shorts/ID or /embed/ID
        if (preg_match('#/(shorts|embed)/([a-zA-Z0-9_-]{11})#', $url, $m)) return $url;
        return false;
    }
    if ($host === 'youtu.be') {
        if (preg_match('#youtu\.be/([a-zA-Z0-9_-]{11})#', $url)) return $url;
        return false;
    }

    // ── Vimeo ─────────────────────────────────────────────────────────────────
    if (in_array($host, ['vimeo.com','www.vimeo.com','player.vimeo.com'])) {
        if (preg_match('#/(\d+)#', $url)) return $url;
        return false;
    }

    // ── Dailymotion ───────────────────────────────────────────────────────────
    if (in_array($host, ['dailymotion.com','www.dailymotion.com'])) {
        if (preg_match('#/video/([a-zA-Z0-9]+)#', $url)) return $url;
        return false;
    }
    if ($host === 'dai.ly') {
        if (preg_match('#dai\.ly/([a-zA-Z0-9]+)#', $url)) return $url;
        return false;
    }

    // ── Direct video file ─────────────────────────────────────────────────────
    $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
    if (preg_match('#\.(mp4|webm|ogg|m3u8)$#', $path)) return $url;

    // ── Other https URL (generic iframe fallback) ─────────────────────────────
    // Reject http (insecure) and non-URL strings
    if (strpos($url, 'https://') === 0) return $url;

    return false;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = dirname(__DIR__) . '/assets/uploads/business-media/';
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

    $newLogo  = $media['logo_url'];
    $newVideo = $media['intro_video_url'];
    $action   = $_POST['action'] ?? '';

    // ── Logo upload ───────────────────────────────────────────────────────────
    if ($action === 'save_logo') {
        if (!empty($_FILES['logo']['tmp_name'])) {
            $allowedImg = ['image/jpeg','image/png','image/webp','image/gif','image/svg+xml'];
            $mime = mime_content_type($_FILES['logo']['tmp_name']);
            if (!in_array($mime, $allowedImg)) {
                $error .= 'Logo must be JPG, PNG, WEBP, GIF or SVG. ';
            } elseif ($_FILES['logo']['size'] > 3 * 1024 * 1024) {
                $error .= 'Logo must be under 3 MB. ';
            } else {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION)) ?: 'jpg';
                $fname = 'logo_' . $business_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $fname)) {
                    if ($media['logo_url'] && strpos($media['logo_url'], '/assets/uploads/') === 0) {
                        @unlink(dirname(__DIR__) . $media['logo_url']);
                    }
                    $newLogo = '/assets/uploads/business-media/' . $fname;
                } else { $error .= 'Logo upload failed. '; }
            }
        } else {
            $error = 'Please choose a logo file to upload.';
        }
    }

    // ── Logo delete ───────────────────────────────────────────────────────────
    if ($action === 'delete_logo') {
        if ($newLogo && strpos($newLogo, '/assets/uploads/') === 0) {
            @unlink(dirname(__DIR__) . $newLogo);
        }
        $newLogo = null;
    }

    // ── Video file upload ─────────────────────────────────────────────────────
    if ($action === 'save_video_file') {
        if (!empty($_FILES['video']['tmp_name'])) {
            $allowedVid = ['video/mp4','video/webm','video/ogg','video/quicktime'];
            $mime = mime_content_type($_FILES['video']['tmp_name']);
            if (!in_array($mime, $allowedVid)) {
                $error .= 'Video must be MP4, WEBM, OGG or MOV. ';
            } elseif ($_FILES['video']['size'] > 100 * 1024 * 1024) {
                $error .= 'Video must be under 100 MB. ';
            } else {
                $ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION)) ?: 'mp4';
                $fname = 'video_' . $business_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['video']['tmp_name'], $uploadDir . $fname)) {
                    if ($media['intro_video_url'] && strpos($media['intro_video_url'], '/assets/uploads/') === 0) {
                        @unlink(dirname(__DIR__) . $media['intro_video_url']);
                    }
                    $newVideo = '/assets/uploads/business-media/' . $fname;
                } else { $error .= 'Video upload failed. '; }
            }
        } else {
            $error = 'Please choose a video file to upload.';
        }
    }

    // ── Video link save ───────────────────────────────────────────────────────
    if ($action === 'save_video_link') {
        $raw = trim($_POST['video_link'] ?? '');
        $validated = validateVideoLink($raw);
        if ($validated === false) {
            $error = 'Invalid video link. Please paste a valid YouTube, Vimeo, Dailymotion, or direct video URL (must start with https://).';
        } else {
            // Remove old local file if any
            if ($media['intro_video_url'] && strpos($media['intro_video_url'], '/assets/uploads/') === 0) {
                @unlink(dirname(__DIR__) . $media['intro_video_url']);
            }
            $newVideo = $validated;
        }
    }

    // ── Video delete ──────────────────────────────────────────────────────────
    if ($action === 'delete_video') {
        if ($newVideo && strpos($newVideo, '/assets/uploads/') === 0) {
            @unlink(dirname(__DIR__) . $newVideo);
        }
        $newVideo = null;
    }

    if (empty($error)) {
        $upsert = $GLOBALS['conn']->prepare("
            INSERT INTO business_media (business_id, logo_url, intro_video_url)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE logo_url = VALUES(logo_url), intro_video_url = VALUES(intro_video_url)
        ");
        $upsert->bind_param('iss', $business_id, $newLogo, $newVideo);
        $upsert->execute();
        $upsert->close();
        $media = ['logo_url' => $newLogo, 'intro_video_url' => $newVideo];
        $success = 'Media updated successfully!';
    }
}

// Determine current video type for preview
$currentVideoType = 'none';
$currentVideo = $media['intro_video_url'] ?? '';
if ($currentVideo) {
    $host = strtolower(parse_url($currentVideo, PHP_URL_HOST) ?? '');
    if (in_array($host, ['www.youtube.com','youtube.com','m.youtube.com','youtu.be'])) {
        $currentVideoType = 'youtube';
    } elseif (in_array($host, ['vimeo.com','www.vimeo.com','player.vimeo.com'])) {
        $currentVideoType = 'vimeo';
    } elseif (strpos($currentVideo, '/assets/uploads/') === 0) {
        $currentVideoType = 'file';
    } else {
        $path = strtolower(parse_url($currentVideo, PHP_URL_PATH) ?? '');
        $currentVideoType = preg_match('#\.(mp4|webm|ogg)$#', $path) ? 'file' : 'link';
    }
}

$page_title = 'Manage Logo & Intro Video – ' . $business['name'];
include '../includes/header.php';
?>

<style>
.media-wrap { max-width: 820px; margin: 40px auto; padding: 0 20px 60px; }
.media-card { background: #fff; border-radius: 14px; padding: 28px; box-shadow: 0 4px 16px rgba(0,0,0,.08); margin-bottom: 28px; }
.media-card h2 { margin: 0 0 6px; color: #0B1C3D; font-size: 20px; }
.media-card > p { margin: 0 0 20px; color: #666; font-size: 13px; }

/* Tabs */
.vtabs { display: flex; gap: 0; margin-bottom: 20px; border-bottom: 2px solid #eee; }
.vtab-btn { padding: 10px 20px; background: none; border: none; cursor: pointer; font-size: 14px; font-weight: 600; color: #888; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all .2s; }
.vtab-btn.active { color: #FF6A00; border-bottom-color: #FF6A00; }
.vtab-pane { display: none; }
.vtab-pane.active { display: block; }

.upload-preview { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 18px; flex-wrap: wrap; }
.preview-box { width: 200px; height: 130px; border-radius: 10px; overflow: hidden; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 2px dashed #ccc; flex-shrink: 0; }
.preview-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
.preview-box video { width: 100%; height: 100%; object-fit: cover; }
.preview-box iframe { width: 100%; height: 100%; border: none; }
.preview-box span { color: #aaa; font-size: 13px; text-align: center; padding: 8px; }

.upload-controls { flex: 1; min-width: 0; }
.upload-controls label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; font-size: 14px; }
.upload-controls input[type=file] { display: block; margin-bottom: 12px; }
.upload-controls input[type=url] { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 14px; margin-bottom: 12px; }
.upload-controls input[type=url]:focus { outline: none; border-color: #FF6A00; }

.link-platforms { font-size: 12px; color: #888; margin-bottom: 12px; line-height: 1.6; }
.link-platforms span { display: inline-block; background: #f3f4f6; border-radius: 4px; padding: 2px 8px; margin: 2px 3px 2px 0; }

.btn-save { padding: 10px 20px; border: none; border-radius: 8px; background: linear-gradient(135deg, #FF6A00, #FF8533); color: #fff; font-weight: 700; cursor: pointer; font-size: 14px; }
.btn-save:hover { opacity: .9; }
.btn-del { padding: 7px 14px; border: none; border-radius: 8px; background: #fee2e2; color: #b91c1c; font-weight: 600; cursor: pointer; font-size: 13px; margin-top: 8px; }
.btn-del:hover { background: #fecaca; }

.current-video-wrap { margin-bottom: 20px; }
.current-video-label { font-size: 13px; font-weight: 600; color: #555; margin-bottom: 8px; }
.current-video-url { font-size: 12px; color: #888; word-break: break-all; margin-bottom: 8px; padding: 6px 10px; background: #f9f9f9; border-radius: 6px; border: 1px solid #eee; }

.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
.back-link { display: inline-block; margin-bottom: 24px; color: #667eea; text-decoration: none; font-weight: 600; }
.back-link:hover { text-decoration: underline; }
</style>

<div class="media-wrap">
    <a href="/pages/dashboard.php" class="back-link">← Back to Dashboard</a>
    <h1 style="color:#0B1C3D;margin-bottom:24px;">Logo &amp; Intro Video — <?php echo htmlspecialchars($business['name']); ?></h1>

    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- ── Logo ─────────────────────────────────────────────────────────── -->
    <div class="media-card">
        <h2>📷 Business Logo</h2>
        <p>Shown in the hero section when no intro video is set. JPG, PNG, WEBP, GIF or SVG — max 3 MB.</p>

        <?php if ($media['logo_url']): ?>
        <div class="upload-preview" style="margin-bottom:12px;">
            <div class="preview-box"><img src="<?php echo htmlspecialchars($media['logo_url']); ?>" alt="Current logo"></div>
            <div>
                <div style="font-size:13px;color:#555;font-weight:600;margin-bottom:6px;">Current Logo</div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_logo">
                    <button type="submit" class="btn-del" onclick="return confirm('Remove current logo?')">🗑️ Remove Logo</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_logo">
            <div class="upload-controls">
                <label>Upload <?php echo $media['logo_url'] ? 'New' : 'a'; ?> Logo</label>
                <input type="file" name="logo" accept="image/*">
                <button type="submit" class="btn-save">💾 Save Logo</button>
            </div>
        </form>
    </div>

    <!-- ── Intro Video ───────────────────────────────────────────────────── -->
    <div class="media-card">
        <h2>🎬 Business Intro Video</h2>
        <p>Displayed on the right side of the hero section, overriding the logo. Upload a file <strong>or</strong> paste a link from YouTube, Vimeo, etc.</p>

        <?php if ($currentVideo): ?>
        <div class="current-video-wrap">
            <div class="current-video-label">Current Video</div>
            <div class="current-video-url"><?php echo htmlspecialchars($currentVideo); ?></div>
            <div class="upload-preview" style="margin-bottom:12px;">
                <div class="preview-box">
                    <?php if ($currentVideoType === 'file'): ?>
                        <video src="<?php echo htmlspecialchars($currentVideo); ?>" muted loop playsinline autoplay></video>
                    <?php elseif ($currentVideoType === 'youtube'): ?>
                        <?php
                        // Build embed URL
                        $ytUrl = $currentVideo;
                        $ytId = '';
                        if (preg_match('#[?&]v=([a-zA-Z0-9_-]{11})#', $ytUrl, $m)) $ytId = $m[1];
                        elseif (preg_match('#/(shorts|embed)/([a-zA-Z0-9_-]{11})#', $ytUrl, $m)) $ytId = $m[2];
                        elseif (preg_match('#youtu\.be/([a-zA-Z0-9_-]{11})#', $ytUrl, $m)) $ytId = $m[1];
                        $embedYt = $ytId ? "https://www.youtube.com/embed/$ytId?autoplay=1&mute=1&loop=1&playlist=$ytId" : $ytUrl;
                        ?>
                        <iframe src="<?php echo htmlspecialchars($embedYt); ?>" allowfullscreen allow="autoplay"></iframe>
                    <?php elseif ($currentVideoType === 'vimeo'): ?>
                        <?php
                        preg_match('#/(\d+)#', $currentVideo, $vm);
                        $embedVimeo = isset($vm[1]) ? "https://player.vimeo.com/video/{$vm[1]}?autoplay=1&muted=1&loop=1" : $currentVideo;
                        ?>
                        <iframe src="<?php echo htmlspecialchars($embedVimeo); ?>" allowfullscreen allow="autoplay"></iframe>
                    <?php else: ?>
                        <span>🎬 External video link</span>
                    <?php endif; ?>
                </div>
                <div>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_video">
                        <button type="submit" class="btn-del" onclick="return confirm('Remove current video?')">🗑️ Remove Video</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="vtabs">
            <button class="vtab-btn active" onclick="switchTab('upload',this)">⬆️ Upload File</button>
            <button class="vtab-btn" onclick="switchTab('link',this)">🔗 Paste Link</button>
        </div>

        <!-- Tab: Upload -->
        <div id="vtab-upload" class="vtab-pane active">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_video_file">
                <div class="upload-controls">
                    <label>Choose Video File</label>
                    <div class="link-platforms">MP4, WEBM, OGG or MOV &nbsp;·&nbsp; Max 100 MB</div>
                    <input type="file" name="video" accept="video/*">
                    <button type="submit" class="btn-save">💾 Save Video</button>
                </div>
            </form>
        </div>

        <!-- Tab: Link -->
        <div id="vtab-link" class="vtab-pane">
            <form method="POST" id="linkForm">
                <input type="hidden" name="action" value="save_video_link">
                <div class="upload-controls">
                    <label>Paste Video Link</label>
                    <div class="link-platforms">
                        Accepted platforms:
                        <span>YouTube</span>
                        <span>Vimeo</span>
                        <span>Dailymotion</span>
                        <span>Direct .mp4 / .webm</span>
                        <span>Any HTTPS video link</span>
                    </div>
                    <input type="url" name="video_link" id="videoLinkInput"
                           placeholder="https://www.youtube.com/watch?v=..."
                           value="<?php echo ($currentVideoType === 'youtube' || $currentVideoType === 'vimeo' || $currentVideoType === 'link') ? htmlspecialchars($currentVideo) : ''; ?>">
                    <div id="linkPreviewWrap" style="margin-bottom:12px;display:none;">
                        <div style="font-size:12px;color:#555;margin-bottom:6px;">Link Preview:</div>
                        <iframe id="linkPreviewFrame" style="width:100%;height:180px;border-radius:8px;border:1px solid #eee;" allowfullscreen allow="autoplay"></iframe>
                    </div>
                    <button type="button" class="btn-save" style="background:linear-gradient(135deg,#667eea,#764ba2);margin-right:8px;" onclick="previewLink()">👁️ Preview</button>
                    <button type="submit" class="btn-save">💾 Save Link</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.vtab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.vtab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('vtab-' + name).classList.add('active');
    btn.classList.add('active');
}

function getEmbedUrl(raw) {
    var url = raw.trim();
    if (!url) return null;

    // YouTube watch
    var m = url.match(/[?&]v=([a-zA-Z0-9_-]{11})/);
    if (m) return 'https://www.youtube.com/embed/' + m[1] + '?autoplay=1&mute=1&loop=1&playlist=' + m[1];

    // YouTube shorts / embed
    m = url.match(/\/(shorts|embed)\/([a-zA-Z0-9_-]{11})/);
    if (m) return 'https://www.youtube.com/embed/' + m[2] + '?autoplay=1&mute=1&loop=1&playlist=' + m[2];

    // youtu.be
    m = url.match(/youtu\.be\/([a-zA-Z0-9_-]{11})/);
    if (m) return 'https://www.youtube.com/embed/' + m[1] + '?autoplay=1&mute=1&loop=1&playlist=' + m[1];

    // Vimeo
    m = url.match(/vimeo\.com\/(\d+)/);
    if (m) return 'https://player.vimeo.com/video/' + m[1] + '?autoplay=1&muted=1&loop=1';

    // Dailymotion
    m = url.match(/dailymotion\.com\/video\/([a-zA-Z0-9]+)/);
    if (m) return 'https://www.dailymotion.com/embed/video/' + m[1] + '?autoplay=1&mute=1';
    m = url.match(/dai\.ly\/([a-zA-Z0-9]+)/);
    if (m) return 'https://www.dailymotion.com/embed/video/' + m[1] + '?autoplay=1&mute=1';

    // Generic https — show as-is
    if (url.startsWith('https://')) return url;

    return null;
}

function previewLink() {
    var raw = document.getElementById('videoLinkInput').value;
    var embed = getEmbedUrl(raw);
    var wrap = document.getElementById('linkPreviewWrap');
    var frame = document.getElementById('linkPreviewFrame');
    if (!embed) {
        alert('Could not recognise this as a valid video link. Please check the URL and try again.');
        wrap.style.display = 'none';
        return;
    }
    frame.src = embed;
    wrap.style.display = 'block';
}

// Auto-open link tab if current video is a remote link
<?php if (in_array($currentVideoType, ['youtube','vimeo','link'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    switchTab('link', document.querySelectorAll('.vtab-btn')[1]);
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>
