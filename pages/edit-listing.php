<?php
/**
 * Edit Claimed Listing
 */

require_once '../config/db.php';
require_once '../config/auth.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: /pages/dashboard.php');
    exit;
}

$business_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Check if user owns this claim and it's approved
$claimQuery = "SELECT lc.id, lc.claim_status FROM listing_claims lc WHERE lc.business_id = ? AND lc.user_id = ? AND lc.claim_status = 'approved'";
$claimStmt = $GLOBALS['conn']->prepare($claimQuery);
$claimStmt->bind_param('ii', $business_id, $user_id);
$claimStmt->execute();
$claimResult = $claimStmt->get_result();

if ($claimResult->num_rows === 0) {
    header('Location: /pages/dashboard.php');
    exit;
}

// Get business details
$businessQuery = "SELECT * FROM extracted_businesses WHERE id = ?";
$businessStmt = $GLOBALS['conn']->prepare($businessQuery);
$businessStmt->bind_param('i', $business_id);
$businessStmt->execute();
$businessResult = $businessStmt->get_result();
$business = $businessResult->fetch_assoc();

if (!$business) {
    header('Location: /pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process edits
    $editableFields = ['formatted_phone_number', 'website', 'opening_hours_weekday'];
    
    foreach ($editableFields as $field) {
        if (isset($_POST[$field])) {
            $oldValue = $business[$field] ?? '';
            $newValue = $_POST[$field];
            
            if ($oldValue !== $newValue) {
                // Log the edit for admin approval
                $editQuery = "INSERT INTO business_edits (business_id, editor_id, field_name, old_value, new_value, edit_status) 
                             VALUES (?, ?, ?, ?, ?, 'pending')";
                $editStmt = $GLOBALS['conn']->prepare($editQuery);
                $editStmt->bind_param('iisss', $business_id, $user_id, $field, $oldValue, $newValue);
                $editStmt->execute();
            }
        }
    }
    
    $success = 'Changes submitted for admin approval!';
}

$page_title = 'Edit Listing - ' . $business['name'];
include '../includes/header.php';
?>

<style>
.edit-container {
    max-width: 700px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.edit-header {
    margin-bottom: 30px;
    border-bottom: 2px solid #667eea;
    padding-bottom: 20px;
}

.edit-header h1 {
    margin: 0 0 10px 0;
    color: #333;
}

.edit-header p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    color: #333;
    font-weight: 600;
    font-size: 14px;
}

.form-group input, .form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #DDD;
    border-radius: 5px;
    font-size: 14px;
    font-family: inherit;
    box-sizing: border-box;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-help {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.btn-submit {
    padding: 12px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.info-box {
    padding: 15px;
    background: #E8E8FF;
    border-left: 4px solid #667eea;
    border-radius: 5px;
    margin-bottom: 25px;
    color: #667eea;
    font-size: 14px;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background: #D4EDDA;
    color: #155724;
    border: 1px solid #C3E6CB;
}

.alert-error {
    background: #F8D7DA;
    color: #721C24;
    border: 1px solid #F5C6CB;
}
</style>

<div class="container">
    <div class="edit-container">
        <div class="edit-header">
            <h1>✏️ Edit Listing</h1>
            <p><?php echo esc($business['name']); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo esc($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo esc($success); ?></div>
        <?php endif; ?>

        <div class="info-box">
            💡 All changes will be reviewed by our admin team before being published. This ensures data quality and accuracy.
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="formatted_phone_number">Phone Number</label>
                <input type="tel" id="formatted_phone_number" name="formatted_phone_number" 
                       value="<?php echo esc($business['formatted_phone_number'] ?? ''); ?>">
                <div class="form-help">Leave blank if unchanged</div>
            </div>

            <div class="form-group">
                <label for="website">Website URL</label>
                <input type="url" id="website" name="website" 
                       value="<?php echo esc($business['website'] ?? ''); ?>" placeholder="https://example.com">
                <div class="form-help">Leave blank if unchanged</div>
            </div>

            <div class="form-group">
                <label for="opening_hours_weekday">Business Hours</label>
                <textarea id="opening_hours_weekday" name="opening_hours_weekday" placeholder="Monday: 9:00 AM - 6:00 PM | Tuesday: 9:00 AM - 6:00 PM | ...">
<?php echo esc($business['opening_hours_weekday'] ?? ''); ?></textarea>
                <div class="form-help">Format: Day: Hours | Day: Hours (separated by |)</div>
            </div>

            <button type="submit" class="btn-submit">Submit for Approval</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
