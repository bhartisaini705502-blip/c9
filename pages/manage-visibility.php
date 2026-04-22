<?php
/**
 * Manage Section Visibility
 * Listing managers can show/hide any section or popup on their business listing page.
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

// Ensure visibility table exists
$GLOBALS['conn']->query("CREATE TABLE IF NOT EXISTS business_visibility (
    business_id INT NOT NULL,
    section_key VARCHAR(60) NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (business_id, section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// All toggleable items, grouped
$allSections = [
    'Page Sections' => [
        'infographics'  => ['label' => 'Business Infographics Carousel',   'icon' => '📊', 'desc' => 'Sliding carousel for infographic images'],
        'gallery'       => ['label' => 'Photo Gallery',                     'icon' => '🖼️', 'desc' => 'Grid of uploaded business gallery photos'],
        'about'         => ['label' => 'About This Business',               'icon' => '📄', 'desc' => 'AI-generated business description'],
        'details'       => ['label' => 'Business Details',                  'icon' => '📋', 'desc' => 'Phone, address, website, category'],
        'hours'         => ['label' => 'Business Hours',                    'icon' => '🕐', 'desc' => 'Opening and closing times'],
        'map'           => ['label' => 'Location Map',                      'icon' => '🗺️', 'desc' => 'Embedded Google Maps location'],
        'faqs'          => ['label' => 'Frequently Asked Questions',        'icon' => '❓', 'desc' => 'FAQ section below the map'],
        'services'      => ['label' => 'Services Offered',                  'icon' => '🔧', 'desc' => 'List of services with prices'],
        'offers'        => ['label' => 'Special Offers & Promotions',       'icon' => '🎉', 'desc' => 'Discount and promotion cards'],
        'updates'       => ['label' => 'Latest Updates',                    'icon' => '📝', 'desc' => 'Blog/news updates from the business'],
        'reviews'       => ['label' => 'Reviews',                           'icon' => '⭐', 'desc' => 'Local and Google review tabs'],
        'related'       => ['label' => 'Similar Businesses',                'icon' => '🏢', 'desc' => 'Related business suggestions'],
    ],
    'Sidebar Widgets' => [
        'sidebar_cta'   => ['label' => 'Connect Now Panel',                 'icon' => '📞', 'desc' => 'Call, WhatsApp & Share buttons in sidebar'],
        'sidebar_form'  => ['label' => 'Get Callback Form',                 'icon' => '📩', 'desc' => 'Lead capture form in the right sidebar'],
    ],
    'Popups & Floating Buttons' => [
        'popup_query'         => ['label' => 'Send Query Popup',            'icon' => '💬', 'desc' => 'Modal form triggered by "Send Query" button'],
        'popup_exit_intent'   => ['label' => 'Exit Intent Popup',           'icon' => '🚀', 'desc' => '"Wait! Get Best Deals Instantly" popup on exit'],
        'popup_timed'         => ['label' => 'Timed "Need Help?" Popup',    'icon' => '⏱️', 'desc' => 'Auto-shows after visitor stays a few seconds'],
        'popup_floating_cta'  => ['label' => 'Floating Call/WhatsApp Buttons','icon'=> '📲','desc' => 'Fixed floating buttons in the corner'],
        'popup_mobile_bar'    => ['label' => 'Mobile Sticky Bar',           'icon' => '📱', 'desc' => 'Fixed bottom bar on mobile with Call/Chat/Enquiry'],
    ],
];

// Load current settings
$visSettings = [];
$vRes = $GLOBALS['conn']->query("SELECT section_key, is_visible FROM business_visibility WHERE business_id = $business_id");
if ($vRes) {
    while ($vRow = $vRes->fetch_assoc()) {
        $visSettings[$vRow['section_key']] = (bool)$vRow['is_visible'];
    }
}

$success = '';
$error   = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_visibility') {
    // Collect all keys from all groups
    $allKeys = [];
    foreach ($allSections as $items) {
        $allKeys = array_merge($allKeys, array_keys($items));
    }

    foreach ($allKeys as $key) {
        // Checkbox: present = visible, absent = hidden
        $isVisible = isset($_POST['vis_' . $key]) ? 1 : 0;
        $stmt = $GLOBALS['conn']->prepare("
            INSERT INTO business_visibility (business_id, section_key, is_visible)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE is_visible = VALUES(is_visible)
        ");
        $stmt->bind_param('isi', $business_id, $key, $isVisible);
        $stmt->execute();
        $stmt->close();
        $visSettings[$key] = (bool)$isVisible;
    }
    $success = 'Visibility settings saved successfully!';
}

$page_title = 'Manage Visibility – ' . $business['name'];
include '../includes/header.php';
?>

<style>
.vis-wrap { max-width: 860px; margin: 40px auto; padding: 0 20px 60px; }

.vis-header { background: linear-gradient(135deg, #0B1C3D 0%, #1a3360 100%); color: white; padding: 30px; border-radius: 14px; margin-bottom: 30px; }
.vis-header h1 { margin: 0 0 6px; font-size: 24px; }
.vis-header p { margin: 0; opacity: 0.8; font-size: 14px; }

.vis-group { background: #fff; border-radius: 14px; padding: 24px 28px; margin-bottom: 24px; box-shadow: 0 2px 10px rgba(0,0,0,.07); }
.vis-group h2 { margin: 0 0 20px; font-size: 17px; color: #0B1C3D; padding-bottom: 12px; border-bottom: 2px solid #f0f0f0; }

.vis-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #f5f5f5; gap: 16px; }
.vis-row:last-child { border-bottom: none; padding-bottom: 0; }

.vis-info { display: flex; align-items: flex-start; gap: 14px; flex: 1; min-width: 0; }
.vis-icon { font-size: 22px; flex-shrink: 0; margin-top: 2px; }
.vis-label { font-weight: 600; color: #222; font-size: 15px; margin-bottom: 3px; }
.vis-desc  { font-size: 13px; color: #888; }

/* Toggle switch */
.toggle-switch { position: relative; width: 52px; height: 28px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; inset: 0; background: #ccc; border-radius: 28px; transition: background 0.3s; }
.toggle-slider:before { content: ''; position: absolute; height: 20px; width: 20px; left: 4px; bottom: 4px; background: white; border-radius: 50%; transition: transform 0.3s; box-shadow: 0 1px 4px rgba(0,0,0,.2); }
.toggle-switch input:checked + .toggle-slider { background: #22c55e; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }

.vis-status { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
.status-visible  { color: #22c55e; }
.status-hidden   { color: #ef4444; }

.btn-save { display: inline-block; padding: 12px 32px; background: linear-gradient(135deg, #FF6A00, #FF8533); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.2s; margin-top: 8px; }
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(255,106,0,0.35); }

.alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 22px; font-size: 14px; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

.back-link { display: inline-flex; align-items: center; gap: 8px; color: #667eea; text-decoration: none; font-weight: 600; font-size: 14px; margin-bottom: 20px; }
.back-link:hover { text-decoration: underline; }

.save-bar { position: sticky; bottom: 0; background: #fff; border-top: 1px solid #eee; padding: 16px 0; margin-top: 8px; display: flex; align-items: center; gap: 16px; z-index: 100; }
.save-hint { font-size: 13px; color: #888; }

@media (max-width: 600px) {
    .vis-label { font-size: 14px; }
    .vis-group { padding: 18px 16px; }
}
</style>

<div class="vis-wrap">
    <a href="/pages/dashboard.php" class="back-link">&larr; Back to Dashboard</a>

    <div class="vis-header">
        <h1>👁️ Page Visibility Settings</h1>
        <p><?php echo htmlspecialchars($business['name']); ?> — Control which sections and popups visitors see on your listing page.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" id="vis-form">
        <input type="hidden" name="action" value="save_visibility">

        <?php foreach ($allSections as $groupName => $items): ?>
        <div class="vis-group">
            <h2><?php echo htmlspecialchars($groupName); ?></h2>

            <?php foreach ($items as $key => $meta):
                $isVisible = $visSettings[$key] ?? true;
            ?>
            <div class="vis-row" id="row-<?php echo $key; ?>">
                <div class="vis-info">
                    <span class="vis-icon"><?php echo $meta['icon']; ?></span>
                    <div>
                        <div class="vis-label"><?php echo htmlspecialchars($meta['label']); ?></div>
                        <div class="vis-desc"><?php echo htmlspecialchars($meta['desc']); ?></div>
                        <div class="vis-status <?php echo $isVisible ? 'status-visible' : 'status-hidden'; ?>" id="status-<?php echo $key; ?>">
                            <?php echo $isVisible ? '● Visible' : '● Hidden'; ?>
                        </div>
                    </div>
                </div>
                <label class="toggle-switch" title="<?php echo $isVisible ? 'Click to hide' : 'Click to show'; ?>">
                    <input type="checkbox" name="vis_<?php echo $key; ?>" id="toggle-<?php echo $key; ?>"
                           <?php echo $isVisible ? 'checked' : ''; ?>
                           onchange="updateStatus('<?php echo $key; ?>', this.checked)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <div class="save-bar">
            <button type="submit" class="btn-save">💾 Save All Settings</button>
            <span class="save-hint">Changes take effect immediately on the live listing page.</span>
        </div>
    </form>
</div>

<script>
function updateStatus(key, isVisible) {
    const statusEl = document.getElementById('status-' + key);
    if (!statusEl) return;
    if (isVisible) {
        statusEl.textContent = '● Visible';
        statusEl.className = 'vis-status status-visible';
    } else {
        statusEl.textContent = '● Hidden';
        statusEl.className = 'vis-status status-hidden';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
