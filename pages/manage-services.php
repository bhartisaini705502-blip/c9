<?php
/**
 * Manage Business Services
 */

require_once '../config/db.php';
require_once '../config/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Get all claimed businesses
$allClaimedQuery = "SELECT lc.business_id, eb.name FROM listing_claims lc 
                    JOIN extracted_businesses eb ON lc.business_id = eb.id 
                    WHERE lc.user_id = ? AND lc.claim_status = 'approved'
                    ORDER BY lc.claimed_at DESC";
$allClaimedStmt = $GLOBALS['conn']->prepare($allClaimedQuery);
$allClaimedStmt->bind_param('i', $user_id);
$allClaimedStmt->execute();
$allClaimed = $allClaimedStmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

// Handle service operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : null;
        $service_name = trim($_POST['service_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = isset($_POST['price']) ? floatval($_POST['price']) : null;
        $duration = trim($_POST['duration'] ?? '');
        
        if (!$service_name) {
            $error = 'Service name is required';
        } else {
            if ($service_id) {
                $updateQuery = "UPDATE business_services SET service_name = ?, description = ?, price = ?, duration = ? WHERE id = ? AND business_id = ?";
                $stmt = $GLOBALS['conn']->prepare($updateQuery);
                $stmt->bind_param('ssdsii', $service_name, $description, $price, $duration, $service_id, $business_id);
                if ($stmt->execute()) {
                    $message = 'Service updated successfully';
                } else {
                    $error = 'Failed to update service';
                }
            } else {
                $insertQuery = "INSERT INTO business_services (business_id, editor_id, service_name, description, price, duration, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                $stmt = $GLOBALS['conn']->prepare($insertQuery);
                $stmt->bind_param('iissds', $business_id, $user_id, $service_name, $description, $price, $duration);
                if ($stmt->execute()) {
                    $message = 'Service added and sent for review';
                } else {
                    $error = 'Failed to add service';
                }
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $service_id = (int)$_POST['service_id'];
        $deleteQuery = "DELETE FROM business_services WHERE id = ? AND business_id = ?";
        $stmt = $GLOBALS['conn']->prepare($deleteQuery);
        $stmt->bind_param('ii', $service_id, $business_id);
        if ($stmt->execute()) {
            $message = 'Service deleted';
        } else {
            $error = 'Failed to delete service';
        }
    }
}

// Get all services
$servicesQuery = "SELECT * FROM business_services WHERE business_id = ? ORDER BY created_at DESC";
$servicesStmt = $GLOBALS['conn']->prepare($servicesQuery);
$servicesStmt->bind_param('i', $business_id);
$servicesStmt->execute();
$services = $servicesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pending = array_filter($services, fn($s) => $s['status'] === 'pending');
$approved = array_filter($services, fn($s) => $s['status'] === 'approved');
$rejected = array_filter($services, fn($s) => $s['status'] === 'rejected');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Services - <?php echo htmlspecialchars($claimedBusiness['name']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-primary { background: #1E3A8A; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .service-card { border: 1px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 8px; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .ql-container { min-height: 120px; font-size: 14px; }
        .ql-editor { min-height: 100px; }
        .ql-toolbar { border-radius: 6px 6px 0 0; background: #f9f9f9; }
        .ql-container { border-radius: 0 0 6px 6px; }
        .service-desc p { margin: 0 0 6px; font-size: 13px; }
        .service-desc ul, .service-desc ol { padding-left: 18px; margin-bottom: 6px; font-size: 13px; }
        .service-desc strong { color: #333; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>🔧 Manage Services</h1>
        <?php if (count($allClaimed) > 1): ?>
        <div style="margin: 20px 0; padding: 15px; background: #f0f7ff; border-radius: 6px;">
            <label style="font-weight: bold; margin-right: 10px;">Select Business:</label>
            <select onchange="window.location.href='/pages/manage-services.php?business_id=' + this.value" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ccc;">
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
        
        <!-- Form -->
        <div class="service-card" style="background: #f9f9f9; border: 2px solid #0B1C3D;">
            <h3>Add/Edit Service</h3>
            <form method="POST" id="service-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="service_id" id="service-id" value="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Service Name *</label>
                        <input type="text" name="service_name" id="service-name" placeholder="e.g., Hair Cut, Dental Cleaning..." required>
                    </div>
                    <div class="form-group">
                        <label>Price (₹)</label>
                        <input type="number" name="price" id="service-price" placeholder="e.g., 500" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <input type="text" name="duration" id="service-duration" placeholder="e.g., 30 mins, 1 hour">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="hidden" name="description" id="service-description">
                    <div id="service-desc-editor" style="background:#fff;"></div>
                </div>
                <button type="submit" class="btn btn-primary" id="service-submit">➕ Add Service</button>
                <button type="button" class="btn" id="service-cancel" style="display:none; background:#6c757d; color:white;" onclick="resetServiceForm()">Cancel Edit</button>
            </form>
        </div>
        
        <!-- Tabs -->
        <div style="border-bottom: 2px solid #ddd; margin: 20px 0;">
            <button class="tab-btn" onclick="showTab('pending')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Pending (<?php echo count($pending); ?>)</button>
            <button class="tab-btn" onclick="showTab('approved')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Approved (<?php echo count($approved); ?>)</button>
            <button class="tab-btn" onclick="showTab('rejected')" style="padding: 10px 20px; background: #f0f0f0; border: none; cursor: pointer;">Rejected (<?php echo count($rejected); ?>)</button>
        </div>
        
        <!-- Services by Status -->
        <div id="pending" class="tab-content">
            <?php if (empty($pending)): ?>
                <p style="color: #666;">No pending services</p>
            <?php else: ?>
                <?php foreach ($pending as $service): ?>
                    <div class="service-card">
                        <h4><?php echo htmlspecialchars($service['service_name']); ?></h4>
                        <span class="status-badge status-pending">⏳ Pending</span>
                        <p>Price: ₹<?php echo $service['price'] ?: 'N/A'; ?> | Duration: <?php echo htmlspecialchars($service['duration'] ?: 'N/A'); ?></p>
                        <?php if ($service['description']): ?>
                            <div class="service-desc"><?php $d=$service['description']; echo ($d!==strip_tags($d)) ? strip_tags($d,'<p><br><strong><em><u><ul><ol><li><a>') : nl2br(htmlspecialchars(substr($d,0,200))); ?></div>
                        <?php endif; ?>
                        <button type="button" class="btn btn-primary" onclick="editService(<?php echo htmlspecialchars(json_encode($service), ENT_QUOTES, 'UTF-8'); ?>)">✏️ Edit</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete?')">🗑️ Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div id="approved" class="tab-content" style="display: none;">
            <?php if (empty($approved)): ?>
                <p style="color: #666;">No approved services</p>
            <?php else: ?>
                <?php foreach ($approved as $service): ?>
                    <div class="service-card">
                        <h4><?php echo htmlspecialchars($service['service_name']); ?></h4>
                        <span class="status-badge status-approved">✅ Approved</span>
                        <p>Price: ₹<?php echo $service['price'] ?: 'N/A'; ?> | Duration: <?php echo htmlspecialchars($service['duration'] ?: 'N/A'); ?></p>
                        <?php if ($service['description']): ?><div class="service-desc"><?php $d=$service['description']; echo ($d!==strip_tags($d)) ? strip_tags($d,'<p><br><strong><em><u><ul><ol><li><a>') : nl2br(htmlspecialchars($d)); ?></div><?php endif; ?>
                        <button type="button" class="btn btn-primary" onclick="editService(<?php echo htmlspecialchars(json_encode($service), ENT_QUOTES, 'UTF-8'); ?>)">✏️ Edit</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete?')">🗑️ Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div id="rejected" class="tab-content" style="display: none;">
            <?php if (empty($rejected)): ?>
                <p style="color: #666;">No rejected services</p>
            <?php else: ?>
                <?php foreach ($rejected as $service): ?>
                    <div class="service-card">
                        <h4><?php echo htmlspecialchars($service['service_name']); ?></h4>
                        <span class="status-badge status-rejected">❌ Rejected</span>
                        <p>Reason: <?php echo htmlspecialchars($service['rejection_reason'] ?? 'No reason provided'); ?></p>
                        <button type="button" class="btn btn-primary" onclick="editService(<?php echo htmlspecialchars(json_encode($service), ENT_QUOTES, 'UTF-8'); ?>)">✏️ Edit</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete?')">🗑️ Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        var serviceQuill = new Quill('#service-desc-editor', {
            theme: 'snow',
            placeholder: 'Details about this service (optional)...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        document.getElementById('service-form').addEventListener('submit', function() {
            document.getElementById('service-description').value = serviceQuill.root.innerHTML === '<p><br></p>' ? '' : serviceQuill.root.innerHTML;
        });

        function showTab(name) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.getElementById(name).style.display = 'block';
        }

        function editService(service) {
            document.getElementById('service-id').value = service.id || '';
            document.getElementById('service-name').value = service.service_name || '';
            document.getElementById('service-price').value = service.price || '';
            document.getElementById('service-duration').value = service.duration || '';
            var desc = service.description || '';
            if (desc && desc !== strip_tags_check(desc)) {
                serviceQuill.root.innerHTML = desc;
            } else {
                serviceQuill.setText(desc);
            }
            document.getElementById('service-submit').textContent = 'Save Service';
            document.getElementById('service-cancel').style.display = 'inline-block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function strip_tags_check(str) {
            return str.replace(/<[^>]*>/g, '');
        }

        function resetServiceForm() {
            document.getElementById('service-form').reset();
            document.getElementById('service-id').value = '';
            serviceQuill.setText('');
            document.getElementById('service-submit').textContent = '➕ Add Service';
            document.getElementById('service-cancel').style.display = 'none';
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
