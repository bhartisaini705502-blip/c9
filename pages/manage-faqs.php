<?php
require_once '../config/db.php';
require_once '../config/auth.php';
requireLogin();

$user_id     = $_SESSION['user_id'];
$business_id = (int)($_GET['id'] ?? 0);

if (!$business_id) {
    header('Location: /pages/dashboard.php');
    exit;
}

// Verify user has an approved claim on this business (same pattern as manage-images.php)
$claimStmt = $GLOBALS['conn']->prepare("
    SELECT id FROM listing_claims
    WHERE business_id = ? AND user_id = ? AND claim_status = 'approved'
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

if (!$business) {
    header('Location: /pages/dashboard.php');
    exit;
}

// Create table if it doesn't exist
$GLOBALS['conn']->query("CREATE TABLE IF NOT EXISTS business_faqs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    question    TEXT NOT NULL,
    answer      TEXT NOT NULL,
    sort_order  INT DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_business (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$error   = '';
$success = '';

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $question = trim($_POST['question'] ?? '');
    $answer   = trim($_POST['answer']   ?? '');

    if ($question === '' || $answer === '') {
        $error = 'Both question and answer are required.';
    } else {
        $count = (int)$GLOBALS['conn']->query(
            "SELECT COUNT(*) as c FROM business_faqs WHERE business_id = $business_id"
        )->fetch_assoc()['c'];

        if ($count >= 20) {
            $error = 'Maximum 20 FAQs allowed.';
        } else {
            $ins = $GLOBALS['conn']->prepare(
                "INSERT INTO business_faqs (business_id, question, answer, sort_order) VALUES (?, ?, ?, ?)"
            );
            $ins->bind_param('issi', $business_id, $question, $answer, $count);
            $ins->execute();
            $ins->close();
            $success = 'FAQ added successfully!';
        }
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $faq_id   = (int)($_POST['faq_id']   ?? 0);
    $question = trim($_POST['question'] ?? '');
    $answer   = trim($_POST['answer']   ?? '');

    if (!$faq_id || $question === '' || $answer === '') {
        $error = 'Both question and answer are required.';
    } else {
        $upd = $GLOBALS['conn']->prepare(
            "UPDATE business_faqs SET question = ?, answer = ? WHERE id = ? AND business_id = ?"
        );
        $upd->bind_param('ssii', $question, $answer, $faq_id, $business_id);
        $upd->execute();
        $upd->close();
        $success = 'FAQ updated successfully!';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $faq_id = (int)($_POST['faq_id'] ?? 0);
    if ($faq_id) {
        $del = $GLOBALS['conn']->prepare("DELETE FROM business_faqs WHERE id = ? AND business_id = ?");
        $del->bind_param('ii', $faq_id, $business_id);
        $del->execute();
        $del->close();
        $success = 'FAQ deleted.';
    }
}

// Handle reorder (drag-and-drop order save)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reorder') {
    $order = json_decode($_POST['order'] ?? '[]', true);
    if (is_array($order)) {
        $upd = $GLOBALS['conn']->prepare(
            "UPDATE business_faqs SET sort_order = ? WHERE id = ? AND business_id = ?"
        );
        foreach ($order as $pos => $faqId) {
            $pos = (int)$pos;
            $faqId = (int)$faqId;
            $upd->bind_param('iii', $pos, $faqId, $business_id);
            $upd->execute();
        }
        $upd->close();
        echo json_encode(['ok' => true]);
        exit;
    }
}

// Load FAQs
$faqs = $GLOBALS['conn']->query(
    "SELECT id, question, answer, sort_order FROM business_faqs
     WHERE business_id = $business_id ORDER BY sort_order ASC, id ASC"
)->fetch_all(MYSQLI_ASSOC) ?? [];

$page_title = 'Manage FAQs – ' . $business['name'];
include '../includes/header.php';
?>

<style>
.faq-mgmt { max-width: 860px; margin: 40px auto; padding: 0 20px 80px; }
.faq-header { background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 28px; }
.faq-header h1 { margin: 0 0 6px; font-size: 24px; }
.faq-header p  { margin: 0; opacity: .85; font-size: 14px; }
.faq-card { background: #fff; border-radius: 12px; padding: 28px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.faq-card h2  { margin: 0 0 20px; font-size: 18px; color: #1e293b; }
label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
input[type=text], textarea {
    width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: 14px; font-family: inherit; box-sizing: border-box; transition: border .2s;
}
input[type=text]:focus, textarea:focus { border-color: #0ea5e9; outline: none; box-shadow: 0 0 0 3px rgba(14,165,233,.12); }
textarea { min-height: 90px; resize: vertical; }
.form-row { margin-bottom: 16px; }
.btn-primary { padding: 10px 24px; background: linear-gradient(135deg, #0ea5e9, #0369a1); color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all .25s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(3,105,161,.35); }
.alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.back-link { display: inline-flex; align-items: center; gap: 8px; color: #0ea5e9; text-decoration: none; font-weight: 600; font-size: 14px; margin-bottom: 20px; }
.back-link:hover { text-decoration: underline; }
/* FAQ list */
.faq-list { list-style: none; padding: 0; margin: 0; }
.faq-row { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 18px; margin-bottom: 12px; cursor: grab; transition: background .2s; }
.faq-row:active { cursor: grabbing; background: #f0f9ff; }
.faq-row.dragging { opacity: .5; }
.faq-row-header { display: flex; align-items: center; gap: 12px; }
.drag-handle { color: #94a3b8; font-size: 18px; cursor: grab; user-select: none; flex-shrink: 0; }
.faq-q { font-weight: 600; font-size: 14px; color: #1e293b; flex: 1; }
.faq-row-btns { display: flex; gap: 8px; flex-shrink: 0; }
.btn-sm { padding: 5px 12px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .2s; }
.btn-edit-sm  { background: #eff6ff; color: #1d4ed8; }
.btn-edit-sm:hover  { background: #dbeafe; }
.btn-del-sm   { background: #fff1f2; color: #be123c; }
.btn-del-sm:hover   { background: #ffe4e6; }
.faq-a { font-size: 13px; color: #475569; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0; }
/* Edit inline form */
.edit-form { display: none; margin-top: 14px; padding-top: 14px; border-top: 1px dashed #cbd5e1; }
.edit-form.open { display: block; }
.edit-row-btns { display: flex; gap: 8px; margin-top: 10px; }
.btn-save-sm   { padding: 7px 16px; background: #0ea5e9; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-cancel-sm { padding: 7px 16px; background: #f1f5f9; color: #475569; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
.empty-state { text-align: center; color: #94a3b8; padding: 40px 0; font-size: 15px; }
.hint { font-size: 12px; color: #94a3b8; margin-top: 4px; }
</style>

<div class="faq-mgmt">
    <a href="/pages/dashboard.php" class="back-link">&larr; Back to Dashboard</a>

    <div class="faq-header">
        <h1>Manage FAQs</h1>
        <p><?php echo htmlspecialchars($business['name']); ?> &mdash; Add up to 20 frequently asked questions for your listing.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Add New FAQ -->
    <div class="faq-card">
        <h2>Add New FAQ</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <label for="question">Question</label>
                <input type="text" id="question" name="question" placeholder="e.g. What are your working hours?" maxlength="300" required>
            </div>
            <div class="form-row">
                <label for="answer">Answer</label>
                <textarea id="answer" name="answer" placeholder="Provide a clear and helpful answer..." maxlength="1000" required></textarea>
                <p class="hint">Max 1000 characters</p>
            </div>
            <button type="submit" class="btn-primary">Add FAQ</button>
        </form>
    </div>

    <!-- Existing FAQs -->
    <div class="faq-card">
        <h2>Your FAQs (<?php echo count($faqs); ?>/20)
            <?php if (count($faqs) > 1): ?>
                <span style="font-size:12px;font-weight:400;color:#94a3b8;margin-left:10px;">Drag to reorder</span>
            <?php endif; ?>
        </h2>

        <?php if (empty($faqs)): ?>
            <div class="empty-state">
                No FAQs yet. Add your first one above to replace the AI-generated FAQs on your profile.
            </div>
        <?php else: ?>
            <ul class="faq-list" id="faq-sortable">
                <?php foreach ($faqs as $idx => $faq): ?>
                <li class="faq-row" data-id="<?php echo $faq['id']; ?>">
                    <div class="faq-row-header">
                        <span class="drag-handle" title="Drag to reorder">⠿</span>
                        <span class="faq-q"><?php echo htmlspecialchars($faq['question']); ?></span>
                        <div class="faq-row-btns">
                            <button class="btn-sm btn-edit-sm" onclick="toggleEdit(<?php echo $faq['id']; ?>)">Edit</button>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this FAQ?')">
                                <input type="hidden" name="action"  value="delete">
                                <input type="hidden" name="faq_id" value="<?php echo $faq['id']; ?>">
                                <button type="submit" class="btn-sm btn-del-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                    <div class="faq-a"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></div>

                    <!-- Inline edit form -->
                    <div class="edit-form" id="edit-<?php echo $faq['id']; ?>">
                        <form method="POST">
                            <input type="hidden" name="action"  value="edit">
                            <input type="hidden" name="faq_id" value="<?php echo $faq['id']; ?>">
                            <div class="form-row">
                                <label>Question</label>
                                <input type="text" name="question" value="<?php echo htmlspecialchars($faq['question']); ?>" maxlength="300" required>
                            </div>
                            <div class="form-row">
                                <label>Answer</label>
                                <textarea name="answer" maxlength="1000" required><?php echo htmlspecialchars($faq['answer']); ?></textarea>
                            </div>
                            <div class="edit-row-btns">
                                <button type="submit" class="btn-save-sm">Save Changes</button>
                                <button type="button" class="btn-cancel-sm" onclick="toggleEdit(<?php echo $faq['id']; ?>)">Cancel</button>
                            </div>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleEdit(id) {
    const el = document.getElementById('edit-' + id);
    el.classList.toggle('open');
}

// Drag-and-drop reorder
(function () {
    const list = document.getElementById('faq-sortable');
    if (!list) return;

    let dragged = null;

    list.addEventListener('dragstart', e => {
        dragged = e.target.closest('.faq-row');
        if (dragged) dragged.classList.add('dragging');
    });
    list.addEventListener('dragend', e => {
        if (dragged) {
            dragged.classList.remove('dragging');
            saveOrder();
        }
        dragged = null;
    });
    list.addEventListener('dragover', e => {
        e.preventDefault();
        const target = e.target.closest('.faq-row');
        if (target && target !== dragged) {
            const rect = target.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            if (e.clientY < midY) {
                list.insertBefore(dragged, target);
            } else {
                list.insertBefore(dragged, target.nextSibling);
            }
        }
    });

    // Make rows draggable
    Array.from(list.querySelectorAll('.faq-row')).forEach(row => {
        row.setAttribute('draggable', 'true');
    });

    function saveOrder() {
        const order = Array.from(list.querySelectorAll('.faq-row')).map(r => r.dataset.id);
        const fd = new FormData();
        fd.append('action', 'reorder');
        fd.append('order', JSON.stringify(order));
        fetch(window.location.href, { method: 'POST', body: fd }).catch(() => {});
    }
})();
</script>

<?php include '../includes/footer.php'; ?>
