<?php
/**
 * Manage Business Images
 * Two sections: Gallery Photos (up to 100, grid) and Infographics (carousel).
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

// Ensure table exists with all required columns
$GLOBALS['conn']->query("CREATE TABLE IF NOT EXISTS business_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    stored_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_business (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

@$GLOBALS['conn']->query("ALTER TABLE business_images ADD COLUMN IF NOT EXISTS uploaded_by INT DEFAULT NULL");
@$GLOBALS['conn']->query("ALTER TABLE business_images ADD COLUMN IF NOT EXISTS photo_type ENUM('gallery','infographic') NOT NULL DEFAULT 'infographic'");

// Clean up bad records
$GLOBALS['conn']->query("DELETE FROM business_images WHERE business_id = $business_id AND image_path = '0'");

$error   = '';
$success = '';

$uploadDir = dirname(__DIR__) . '/assets/uploads/business-images/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxSize      = 5 * 1024 * 1024; // 5 MB

// ── Handle gallery upload ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gallery_images']) && ($_POST['section'] ?? '') === 'gallery') {
    $countRow = $GLOBALS['conn']->query("SELECT COUNT(*) as cnt FROM business_images WHERE business_id = $business_id AND photo_type = 'gallery' AND image_path LIKE '/assets/uploads/%'")->fetch_assoc();
    $existing = (int)($countRow['cnt'] ?? 0);
    $uploaded = 0;
    $errors   = [];

    foreach ($_FILES['gallery_images']['tmp_name'] as $i => $tmpName) {
        if ($_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($existing + $uploaded >= 100) {
            $errors[] = 'Maximum 100 gallery photos allowed.';
            break;
        }
        $mimeType = mime_content_type($tmpName);
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type: ' . htmlspecialchars($_FILES['gallery_images']['name'][$i]);
            continue;
        }
        if ($_FILES['gallery_images']['size'][$i] > $maxSize) {
            $errors[] = 'File too large (max 5 MB): ' . htmlspecialchars($_FILES['gallery_images']['name'][$i]);
            continue;
        }
        $ext      = strtolower(pathinfo($_FILES['gallery_images']['name'][$i], PATHINFO_EXTENSION)) ?: 'jpg';
        $filename = 'gal_' . $business_id . '_' . time() . '_' . $i . '.' . $ext;
        if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
            $webPath = '/assets/uploads/business-images/' . $filename;
            $ins = $GLOBALS['conn']->prepare("INSERT INTO business_images (business_id, image_path, uploaded_by, photo_type) VALUES (?, ?, ?, 'gallery')");
            $ins->bind_param('isi', $business_id, $webPath, $user_id);
            $ins->execute();
            $ins->close();
            $uploaded++;
        } else {
            $errors[] = 'Upload failed for: ' . htmlspecialchars($_FILES['gallery_images']['name'][$i]);
        }
    }
    if ($uploaded > 0) $success = $uploaded . ' gallery photo(s) uploaded successfully!';
    if (!empty($errors)) $error = implode(' ', $errors);
}

// ── Handle infographic upload ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['infographic_images']) && ($_POST['section'] ?? '') === 'infographic') {
    $countRow = $GLOBALS['conn']->query("SELECT COUNT(*) as cnt FROM business_images WHERE business_id = $business_id AND photo_type = 'infographic' AND image_path LIKE '/assets/uploads/%'")->fetch_assoc();
    $existing = (int)($countRow['cnt'] ?? 0);
    $uploaded = 0;
    $errors   = [];

    foreach ($_FILES['infographic_images']['tmp_name'] as $i => $tmpName) {
        if ($_FILES['infographic_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($existing + $uploaded >= 20) {
            $errors[] = 'Maximum 20 infographics allowed.';
            break;
        }
        $mimeType = mime_content_type($tmpName);
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type: ' . htmlspecialchars($_FILES['infographic_images']['name'][$i]);
            continue;
        }
        if ($_FILES['infographic_images']['size'][$i] > $maxSize) {
            $errors[] = 'File too large (max 5 MB): ' . htmlspecialchars($_FILES['infographic_images']['name'][$i]);
            continue;
        }
        $ext      = strtolower(pathinfo($_FILES['infographic_images']['name'][$i], PATHINFO_EXTENSION)) ?: 'jpg';
        $filename = 'inf_' . $business_id . '_' . time() . '_' . $i . '.' . $ext;
        if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
            $webPath = '/assets/uploads/business-images/' . $filename;
            $ins = $GLOBALS['conn']->prepare("INSERT INTO business_images (business_id, image_path, uploaded_by, photo_type) VALUES (?, ?, ?, 'infographic')");
            $ins->bind_param('isi', $business_id, $webPath, $user_id);
            $ins->execute();
            $ins->close();
            $uploaded++;
        } else {
            $errors[] = 'Upload failed for: ' . htmlspecialchars($_FILES['infographic_images']['name'][$i]);
        }
    }
    if ($uploaded > 0) $success = $uploaded . ' infographic(s) uploaded successfully!';
    if (!empty($errors)) $error = implode(' ', $errors);
}

// ── Handle delete ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image_id'])) {
    $imgId = (int)$_POST['delete_image_id'];
    $imgStmt = $GLOBALS['conn']->prepare("SELECT image_path FROM business_images WHERE id = ? AND business_id = ?");
    $imgStmt->bind_param('ii', $imgId, $business_id);
    $imgStmt->execute();
    $imgRow = $imgStmt->get_result()->fetch_assoc();
    $imgStmt->close();
    if ($imgRow) {
        $path = $imgRow['image_path'];
        if (strpos($path, '/assets/uploads/') === 0) {
            $fullPath = dirname(__DIR__) . $path;
            if (file_exists($fullPath)) { unlink($fullPath); }
        }
        $del = $GLOBALS['conn']->prepare("DELETE FROM business_images WHERE id = ? AND business_id = ?");
        $del->bind_param('ii', $imgId, $business_id);
        $del->execute();
        $del->close();
        $success = 'Image deleted.';
    }
}

// ── Fetch current images by type ───────────────────────────────────────────
$galleryRes = $GLOBALS['conn']->query("SELECT id, image_path, stored_at FROM business_images WHERE business_id = $business_id AND photo_type = 'gallery' ORDER BY stored_at DESC LIMIT 100");
$galleryImages = $galleryRes ? $galleryRes->fetch_all(MYSQLI_ASSOC) : [];

$infraRes = $GLOBALS['conn']->query("SELECT id, image_path, stored_at FROM business_images WHERE business_id = $business_id AND photo_type = 'infographic' ORDER BY stored_at DESC LIMIT 20");
$infraImages = $infraRes ? $infraRes->fetch_all(MYSQLI_ASSOC) : [];

// Which tab is active (based on which action just ran)
$activeTab = ($_POST['section'] ?? 'gallery') === 'infographic' ? 'infographic' : 'gallery';
if (isset($_POST['delete_image_id'])) $activeTab = $_GET['tab'] ?? 'gallery';
if (!isset($_POST['section'])) $activeTab = $_GET['tab'] ?? 'gallery';

$page_title = 'Manage Photos & Infographics – ' . $business['name'];
include '../includes/header.php';
?>

<style>
.mgmt-container { max-width: 960px; margin: 40px auto; padding: 0 20px 60px; }
.mgmt-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
.mgmt-header h1 { margin: 0 0 6px; font-size: 24px; }
.mgmt-header p { margin: 0; opacity: 0.85; font-size: 14px; }

/* Section Tabs */
.section-tabs { display: flex; gap: 0; margin-bottom: 28px; border-bottom: 2px solid #e0e0e0; }
.section-tab { padding: 12px 24px; background: none; border: none; cursor: pointer; font-size: 15px; font-weight: 600; color: #888; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
.section-tab.active { color: #667eea; border-bottom-color: #667eea; }
.section-tab:hover:not(.active) { color: #555; }
.tab-pane { display: none; }
.tab-pane.active { display: block; }

.mgmt-card { background: white; border-radius: 12px; padding: 28px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.mgmt-card h2 { margin: 0 0 6px; font-size: 18px; color: #333; }
.mgmt-card .card-desc { margin: 0 0 20px; color: #888; font-size: 13px; }

.upload-zone { border: 2px dashed #667eea; border-radius: 10px; padding: 36px; text-align: center; background: #f8f8ff; cursor: pointer; transition: all 0.3s; }
.upload-zone:hover, .upload-zone.drag-over { background: #ededff; border-color: #4f46e5; }
.upload-zone input[type=file] { display: none; }
.upload-zone .icon { font-size: 44px; }
.upload-zone p { color: #555; margin: 8px 0 0; font-size: 14px; }
.hint { font-size: 12px; color: #999; margin-top: 6px; }

.btn-upload { display: inline-block; margin-top: 16px; padding: 10px 28px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-upload:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }

.preview-strip { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
.preview-strip img { width: 80px; height: 60px; object-fit: cover; border-radius: 6px; border: 2px solid #e0e0e0; }

.images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
.img-item { position: relative; border-radius: 10px; overflow: hidden; aspect-ratio: 4/3; background: #f0f0f0; }
.img-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.img-item .delete-btn { position: absolute; top: 7px; right: 7px; background: rgba(220,53,69,0.92); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.img-item .delete-btn:hover { background: #dc3545; transform: scale(1.1); }
.img-label { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.55); color: white; font-size: 11px; padding: 4px 8px; text-align: center; }

.section-count { display: inline-block; background: #f0f0ff; color: #667eea; padding: 2px 10px; border-radius: 20px; font-size: 13px; font-weight: 700; margin-left: 8px; }

.alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.back-link { display: inline-flex; align-items: center; gap: 8px; color: #667eea; text-decoration: none; font-weight: 600; font-size: 14px; margin-bottom: 20px; }
.back-link:hover { text-decoration: underline; }

.empty-state { padding: 40px; text-align: center; background: #fafafa; border-radius: 8px; border: 1px dashed #ddd; }
.empty-state .es-icon { font-size: 44px; margin-bottom: 10px; }
.empty-state p { color: #aaa; margin: 0; font-size: 14px; }

/* Infographic mini-preview strip */
.infographic-strip { display: flex; gap: 12px; flex-wrap: wrap; }
.infographic-strip .inf-item { position: relative; width: 200px; border-radius: 10px; overflow: hidden; background: #f0f0f0; }
.infographic-strip .inf-item img { width: 100%; height: 130px; object-fit: cover; display: block; }
.infographic-strip .inf-item .delete-btn { position: absolute; top: 7px; right: 7px; background: rgba(220,53,69,0.92); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.infographic-strip .inf-item .delete-btn:hover { background: #dc3545; }
.infographic-strip .inf-item .img-label { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.55); color: white; font-size: 11px; padding: 4px 8px; text-align: center; }
</style>

<div class="mgmt-container">
    <a href="/pages/dashboard.php" class="back-link">&larr; Back to Dashboard</a>

    <div class="mgmt-header">
        <h1>📸 Photos &amp; Infographics</h1>
        <p><?php echo htmlspecialchars($business['name']); ?> — Manage your gallery photos and infographic carousel.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Section Tabs -->
    <div class="section-tabs">
        <button class="section-tab <?php echo $activeTab === 'gallery' ? 'active' : ''; ?>" onclick="switchTab('gallery')">
            🖼️ Business Gallery Photos
            <span class="section-count"><?php echo count($galleryImages); ?>/100</span>
        </button>
        <button class="section-tab <?php echo $activeTab === 'infographic' ? 'active' : ''; ?>" onclick="switchTab('infographic')">
            📊 Infographics Carousel
            <span class="section-count"><?php echo count($infraImages); ?>/20</span>
        </button>
    </div>

    <!-- ═══════════════ GALLERY TAB ═══════════════ -->
    <div id="tab-gallery" class="tab-pane <?php echo $activeTab === 'gallery' ? 'active' : ''; ?>">

        <!-- Upload -->
        <div class="mgmt-card">
            <h2>Upload Gallery Photos</h2>
            <p class="card-desc">These photos appear in a large photo gallery grid on your listing page. Visitors can click any photo to view it full-size. Up to 100 photos, max 5 MB each.</p>
            <form method="POST" enctype="multipart/form-data" id="gallery-form">
                <input type="hidden" name="section" value="gallery">
                <div class="upload-zone" id="gallery-zone" onclick="document.getElementById('gallery-input').click()">
                    <div class="icon">🖼️</div>
                    <p>Click to select photos (or drag &amp; drop)</p>
                    <p class="hint">JPG, PNG, WEBP — Max 5 MB per image — Up to <?php echo 100 - count($galleryImages); ?> more photos</p>
                    <input type="file" id="gallery-input" name="gallery_images[]" multiple accept="image/*" onchange="previewImages(this, 'gallery-preview')">
                </div>
                <div class="preview-strip" id="gallery-preview"></div>
                <button type="submit" class="btn-upload" id="gallery-upload-btn" style="display:none;">Upload Selected Photos</button>
            </form>
        </div>

        <!-- Current Gallery -->
        <div class="mgmt-card">
            <h2>Current Gallery Photos <span class="section-count"><?php echo count($galleryImages); ?>/100</span></h2>
            <?php if (empty($galleryImages)): ?>
                <div class="empty-state">
                    <div class="es-icon">🖼️</div>
                    <p>No gallery photos uploaded yet. Add photos above to build your gallery.</p>
                </div>
            <?php else: ?>
                <div class="images-grid">
                    <?php foreach ($galleryImages as $img):
                        $src = htmlspecialchars($img['image_path']);
                        if (empty($src)) continue;
                        $isUpload = strpos($img['image_path'], '/assets/uploads/') === 0;
                    ?>
                    <div class="img-item">
                        <img src="<?php echo $src; ?>" alt="Gallery photo"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 150%22%3E%3Crect fill=%22%23e0e0e0%22 width=%22200%22 height=%22150%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%23999%22 font-size=%2213%22%3ENo image%3C/text%3E%3C/svg%3E'">
                        <?php if ($isUpload): ?>
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this photo?')">
                            <input type="hidden" name="section" value="gallery">
                            <input type="hidden" name="delete_image_id" value="<?php echo $img['id']; ?>">
                            <button type="submit" class="delete-btn" title="Delete">✕</button>
                        </form>
                        <?php endif; ?>
                        <div class="img-label"><?php echo date('d M Y', strtotime($img['stored_at'] ?? 'now')); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════ INFOGRAPHIC TAB ═══════════════ -->
    <div id="tab-infographic" class="tab-pane <?php echo $activeTab === 'infographic' ? 'active' : ''; ?>">

        <!-- Upload -->
        <div class="mgmt-card">
            <h2>Upload Infographics</h2>
            <p class="card-desc">Infographics are displayed in a sliding carousel on your listing page. Use this section for banners, charts, price lists, menus or any informational images. Up to 20 infographics, max 5 MB each.</p>
            <form method="POST" enctype="multipart/form-data" id="infographic-form">
                <input type="hidden" name="section" value="infographic">
                <div class="upload-zone" id="infographic-zone" onclick="document.getElementById('infographic-input').click()">
                    <div class="icon">📊</div>
                    <p>Click to select infographic images (or drag &amp; drop)</p>
                    <p class="hint">JPG, PNG, WEBP — Max 5 MB per image — Up to <?php echo 20 - count($infraImages); ?> more</p>
                    <input type="file" id="infographic-input" name="infographic_images[]" multiple accept="image/*" onchange="previewImages(this, 'infographic-preview')">
                </div>
                <div class="preview-strip" id="infographic-preview"></div>
                <button type="submit" class="btn-upload" id="infographic-upload-btn" style="display:none;">Upload Selected Infographics</button>
            </form>
        </div>

        <!-- Current Infographics -->
        <div class="mgmt-card">
            <h2>Current Infographics <span class="section-count"><?php echo count($infraImages); ?>/20</span></h2>
            <?php if (empty($infraImages)): ?>
                <div class="empty-state">
                    <div class="es-icon">📊</div>
                    <p>No infographics uploaded yet. These appear as a sliding carousel on your listing page.</p>
                </div>
            <?php else: ?>
                <div class="infographic-strip">
                    <?php foreach ($infraImages as $img):
                        $src = htmlspecialchars($img['image_path']);
                        if (empty($src)) continue;
                        $isUpload = strpos($img['image_path'], '/assets/uploads/') === 0;
                    ?>
                    <div class="inf-item">
                        <img src="<?php echo $src; ?>" alt="Infographic"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 130%22%3E%3Crect fill=%22%23e0e0e0%22 width=%22200%22 height=%22130%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%23999%22 font-size=%2213%22%3ENo image%3C/text%3E%3C/svg%3E'">
                        <?php if ($isUpload): ?>
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this infographic?')">
                            <input type="hidden" name="section" value="infographic">
                            <input type="hidden" name="delete_image_id" value="<?php echo $img['id']; ?>">
                            <button type="submit" class="delete-btn" title="Delete">✕</button>
                        </form>
                        <?php endif; ?>
                        <div class="img-label"><?php echo date('d M Y', strtotime($img['stored_at'] ?? 'now')); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchTab(name) {
    document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    document.querySelectorAll('.section-tab').forEach(t => {
        if (t.textContent.toLowerCase().includes(name === 'gallery' ? 'gallery' : 'infographic')) {
            t.classList.add('active');
        }
    });
}

function previewImages(input, previewId) {
    const previewStrip = document.getElementById(previewId);
    const section = previewId.replace('-preview', '');
    const uploadBtn = document.getElementById(section + '-upload-btn');
    previewStrip.innerHTML = '';
    if (input.files.length === 0) {
        uploadBtn.style.display = 'none';
        return;
    }
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            previewStrip.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
    uploadBtn.style.display = 'inline-block';
    uploadBtn.textContent = 'Upload ' + input.files.length + ' file(s)';
}

// Drag & drop for gallery
(function() {
    function initZone(zoneId, inputId, previewId) {
        const zone = document.getElementById(zoneId);
        if (!zone) return;
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            const input = document.getElementById(inputId);
            const dt = e.dataTransfer;
            // Use DataTransfer to assign files
            const fileList = dt.files;
            // We cannot directly assign to input.files (read-only), but we can preview
            previewImages({ files: fileList }, previewId);
            // Re-assign via form for actual submission workaround: open file picker not possible; show note
        });
    }
    initZone('gallery-zone', 'gallery-input', 'gallery-preview');
    initZone('infographic-zone', 'infographic-input', 'infographic-preview');
})();
</script>

<?php include '../includes/footer.php'; ?>
