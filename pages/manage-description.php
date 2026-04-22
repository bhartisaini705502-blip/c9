<?php
/**
 * Manage Business Description
 */

require_once '../config/db.php';
require_once '../config/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Get all claimed businesses
$allClaimedQuery = "SELECT lc.business_id, eb.name, eb.ai_description FROM listing_claims lc 
                    JOIN extracted_businesses eb ON lc.business_id = eb.id 
                    WHERE lc.user_id = ? AND lc.claim_status = 'approved'
                    ORDER BY lc.claimed_at DESC";
$allClaimedStmt = $GLOBALS['conn']->prepare($allClaimedQuery);
$allClaimedStmt->bind_param('i', $user_id);
$allClaimedStmt->execute();
$result = $allClaimedStmt->get_result();
$allClaimed = [];
while ($row = $result->fetch_assoc()) {
    $allClaimed[] = $row;
}

if (empty($allClaimed)) {
    header('Location: /pages/dashboard.php');
    exit;
}

// Get selected business_id from parameter or use the first one
$selectedBusinessId = isset($_GET['business_id']) ? (int)$_GET['business_id'] : null;

// Validate that the selected business belongs to this user
$claimedBusiness = null;
foreach ($allClaimed as $claimed) {
    if ($selectedBusinessId === $claimed['business_id'] || $selectedBusinessId === null) {
        $claimedBusiness = $claimed;
        $selectedBusinessId = $claimed['business_id'];
        break;
    }
}

if (!$claimedBusiness) {
    header('Location: /pages/dashboard.php');
    exit;
}

$business_id = $claimedBusiness['business_id'];
$message = '';
$error = '';

// Handle description update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');
    
    if (!$description) {
        $error = 'Description cannot be empty';
    } elseif (preg_match('/<\s*script\b/i', $description) || preg_match('/on\w+\s*=/i', $description) || preg_match('/javascript\s*:/i', $description)) {
        $error = 'Script content is not allowed in descriptions';
    } else {
        // Get old description
        $oldQuery = "SELECT ai_description FROM extracted_businesses WHERE id = ?";
        $oldStmt = $GLOBALS['conn']->prepare($oldQuery);
        $oldStmt->bind_param('i', $business_id);
        $oldStmt->execute();
        $oldRow = $oldStmt->get_result()->fetch_assoc();
        
        // Update extracted_businesses immediately (source of truth)
        $updateBusiness = $GLOBALS['conn']->prepare("UPDATE extracted_businesses SET ai_description = ? WHERE id = ?");
        $updateBusiness->bind_param('si', $description, $business_id);
        $updateOk = $updateBusiness->execute();
        $updateBusiness->close();

        // Keep review history in business_descriptions
        $insertQuery = "INSERT INTO business_descriptions (business_id, editor_id, description, old_description, status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $GLOBALS['conn']->prepare($insertQuery);
        $stmt->bind_param('iiss', $business_id, $user_id, $description, $oldRow['ai_description']);
        $stmt->execute();
        $stmt->close();

        if ($updateOk) {
            $message = 'Description submitted for review successfully';
            // Refresh the in-page preview
            foreach ($allClaimed as &$c) {
                if ((int)$c['business_id'] === (int)$business_id) { $c['ai_description'] = $description; }
            }
            unset($c);
            $claimedBusiness['ai_description'] = $description;
        } else {
            $error = 'Failed to update description';
        }
    }
}

// Get all description updates
$descQuery = "SELECT * FROM business_descriptions WHERE business_id = ? ORDER BY created_at DESC LIMIT 10";
$descStmt = $GLOBALS['conn']->prepare($descQuery);
$descStmt->bind_param('i', $business_id);
$descStmt->execute();
$descriptions = $descStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pending = array_filter($descriptions, fn($d) => $d['status'] === 'pending');
$approved = array_filter($descriptions, fn($d) => $d['status'] === 'approved');
$rejected = array_filter($descriptions, fn($d) => $d['status'] === 'rejected');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Description - <?php echo htmlspecialchars($claimedBusiness['name']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-primary { background: #1E3A8A; color: white; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .desc-card { border: 1px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 8px; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .ql-container { min-height: 220px; font-size: 15px; }
        .ql-editor { min-height: 200px; }
        .ql-toolbar { border-radius: 6px 6px 0 0; background: #f9f9f9; }
        .ql-container { border-radius: 0 0 6px 6px; }
        /* Rendered description */
        .desc-rendered p { margin: 0 0 10px; }
        .desc-rendered ul, .desc-rendered ol { padding-left: 22px; margin-bottom: 10px; }
        .desc-rendered h2, .desc-rendered h3 { color: #0B1C3D; margin: 12px 0 6px; }
        .desc-rendered strong { color: #333; }
        .desc-rendered blockquote { border-left: 3px solid #FF6A00; padding-left: 12px; color: #666; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>📝 Business Description</h1>
        <?php if (count($allClaimed) > 1): ?>
        <div style="margin: 20px 0; padding: 15px; background: #f0f7ff; border-radius: 6px;">
            <label style="font-weight: bold; margin-right: 10px;">Select Business:</label>
            <select onchange="window.location.href='/pages/manage-description.php?business_id=' + this.value" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ccc;">
                <?php foreach ($allClaimed as $claimed): ?>
                <option value="<?php echo $claimed['business_id']; ?>" <?php echo $selectedBusinessId === $claimed['business_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($claimed['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <p>Business: <strong><?php echo htmlspecialchars($claimedBusiness['name']); ?></strong></p>
        
        <?php if ($message): ?>
            <div style="background: #d4edda; padding: 15px; border-radius: 6px; margin: 15px 0; color: #155724;">✓ <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background: #f8d7da; padding: 15px; border-radius: 6px; margin: 15px 0; color: #721c24;">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Current Description -->
        <?php
        function renderManagedDesc(string $text): string {
            if (empty(trim($text))) return '<em style="color:#aaa">No description set yet.</em>';
            if ($text !== strip_tags($text)) {
                return '<div class="desc-rendered">' . strip_tags($text, '<p><br><strong><em><u><s><ul><ol><li><h2><h3><h4><a><blockquote><pre><code><span>') . '</div>';
            }
            return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        }
        ?>
        <div class="desc-card" style="background: #f9f9f9; border: 2px solid #0B1C3D;">
            <h3>Current Description</h3>
            <?php echo renderManagedDesc($claimedBusiness['ai_description'] ?? ''); ?>
        </div>
        
        <!-- Edit Form with Quill editor -->
        <div class="desc-card" style="background: #f9f9f9; border: 2px solid #0B1C3D;">
            <h3>Update Description</h3>
            <p style="font-size:13px;color:#666;margin-bottom:14px;">Use the toolbar below to format your description — bold, italic, headings, bullet lists, links and more.</p>
            <form method="POST" id="desc-form">
                <input type="hidden" name="description" id="desc-hidden">
                <div id="desc-editor" style="background:#fff;"></div>
                <button type="submit" class="btn btn-primary" style="margin-top:14px;" onclick="syncDesc()">📤 Save Description</button>
            </form>
        </div>
        
        <!-- History Tabs -->
        <div style="border-bottom: 2px solid #ddd; margin: 20px 0;">
            <button class="tab-btn" onclick="showTab('pending')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Pending (<?php echo count($pending); ?>)</button>
            <button class="tab-btn" onclick="showTab('approved')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Approved (<?php echo count($approved); ?>)</button>
            <button class="tab-btn" onclick="showTab('rejected')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Rejected (<?php echo count($rejected); ?>)</button>
        </div>
        
        <!-- Pending -->
        <div id="pending" class="tab-content">
            <?php if (empty($pending)): ?>
                <p style="color: #666;">No pending description updates</p>
            <?php else: ?>
                <?php foreach ($pending as $desc): ?>
                    <div class="desc-card">
                        <span class="status-badge status-pending">⏳ Pending Review</span>
                        <p style="color: #666; font-size: 13px; margin: 10px 0;">Submitted: <?php echo date('M d, Y g:i A', strtotime($desc['created_at'])); ?></p>
                        <?php echo renderManagedDesc($desc['description']); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Approved -->
        <div id="approved" class="tab-content" style="display: none;">
            <?php if (empty($approved)): ?>
                <p style="color: #666;">No approved description updates</p>
            <?php else: ?>
                <?php foreach ($approved as $desc): ?>
                    <div class="desc-card">
                        <span class="status-badge status-approved">✅ Approved</span>
                        <p style="color: #666; font-size: 13px; margin: 10px 0;">Approved: <?php echo date('M d, Y', strtotime($desc['reviewed_at'] ?? $desc['created_at'])); ?></p>
                        <?php echo renderManagedDesc($desc['description']); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Rejected -->
        <div id="rejected" class="tab-content" style="display: none;">
            <?php if (empty($rejected)): ?>
                <p style="color: #666;">No rejected description updates</p>
            <?php else: ?>
                <?php foreach ($rejected as $desc): ?>
                    <div class="desc-card">
                        <span class="status-badge status-rejected">❌ Rejected</span>
                        <p>Reason: <?php echo htmlspecialchars($desc['rejection_reason'] ?? 'No reason provided'); ?></p>
                        <?php echo renderManagedDesc($desc['description']); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
    var descQuill = new Quill('#desc-editor', {
        theme: 'snow',
        placeholder: 'Write a compelling description of your business...',
        modules: {
            toolbar: [
                [{ header: [2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'code-block'],
                ['link'],
                ['clean']
            ]
        }
    });

    // Pre-fill with current description
    <?php
    $current = $claimedBusiness['ai_description'] ?? '';
    if (!empty($current)):
        if ($current !== strip_tags($current)):
    ?>
    descQuill.root.innerHTML = <?php echo json_encode($current); ?>;
    <?php else: ?>
    descQuill.setText(<?php echo json_encode($current); ?>);
    <?php endif; endif; ?>

    function syncDesc() {
        var html = descQuill.root.innerHTML;
        if (html === '<p><br></p>' || descQuill.getText().trim() === '') {
            alert('Description cannot be empty.');
            return false;
        }
        document.getElementById('desc-hidden').value = html;
    }

    document.getElementById('desc-form').addEventListener('submit', function(e) {
        var html = descQuill.root.innerHTML;
        if (html === '<p><br></p>' || descQuill.getText().trim() === '') {
            e.preventDefault();
            alert('Description cannot be empty.');
            return;
        }
        document.getElementById('desc-hidden').value = html;
    });
    </script>

    <script>
        function showTab(name) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.getElementById(name).style.display = 'block';
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
