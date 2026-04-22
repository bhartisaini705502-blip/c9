<?php
/**
 * Admin - Leads Management
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';

$filter_service = $_GET['service'] ?? '';
$filter_source = $_GET['source'] ?? '';
$search_phone = $_GET['phone'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];
$types = '';

if ($filter_service) {
    $where[] = "service = ?";
    $params[] = $filter_service;
    $types .= 's';
}

if ($filter_source) {
    $where[] = "source = ?";
    $params[] = $filter_source;
    $types .= 's';
}

if ($search_phone) {
    $where[] = "phone LIKE ?";
    $params[] = "%$search_phone%";
    $types .= 's';
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM leads $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_leads = $count_result['total'];
$total_pages = ceil($total_leads / $limit);

// Get leads
$query = "SELECT id, name, phone, email, service, source, status, score, created_at FROM leads $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt->bind_param($types, ...$params);
$stmt->execute();
$leads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    $delete_stmt = $conn->prepare("DELETE FROM leads WHERE id = ?");
    $delete_stmt->bind_param('i', $delete_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: leads-management.php");
    exit;
}

// Handle mark as contacted
if (isset($_POST['action']) && $_POST['action'] === 'mark_contacted' && isset($_POST['lead_id'])) {
    $lead_id = (int)$_POST['lead_id'];
    $update_stmt = $conn->prepare("UPDATE leads SET status = 'contacted' WHERE id = ?");
    $update_stmt->bind_param('i', $lead_id);
    $update_stmt->execute();
    $update_stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// Handle unmark (revert to new)
if (isset($_POST['action']) && $_POST['action'] === 'unmark' && isset($_POST['lead_id'])) {
    $lead_id = (int)$_POST['lead_id'];
    $update_stmt = $conn->prepare("UPDATE leads SET status = 'new' WHERE id = ?");
    $update_stmt->bind_param('i', $lead_id);
    $update_stmt->execute();
    $update_stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

$page_title = "Leads Management - Admin";
require_once '../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .filter-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-section input,
    .filter-section select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .filter-section button {
        background: #0B1C3D;
        color: white;
        padding: 8px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .leads-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    .leads-table thead {
        background: #0B1C3D;
        color: white;
    }

    .leads-table th,
    .leads-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .leads-table tbody tr:hover {
        background: #f8f9fa;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-new {
        background: #e3f2fd;
        color: #1976d2;
    }

    .status-contacted {
        background: #e8f5e9;
        color: #388e3c;
    }

    .score-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        min-width: 50px;
    }

    .score-high {
        background: #e8f5e9;
        color: #25D366;
    }

    .score-medium {
        background: #fff3e0;
        color: #FF9A00;
    }

    .score-low {
        background: #ffebee;
        color: #F44336;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .action-buttons button,
    .action-buttons a {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
    }

    .btn-mark {
        background: #4caf50;
        color: white;
    }

    .btn-mark:hover {
        background: #45a049;
    }

    .btn-unmark {
        background: #FF9A00;
        color: white;
    }

    .btn-unmark:hover {
        background: #E58800;
    }

    .btn-delete {
        background: #f44336;
        color: white;
    }

    .btn-delete:hover {
        background: #da190b;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #0B1C3D;
    }

    .pagination a:hover {
        background: #0B1C3D;
        color: white;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #0B1C3D;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
        margin-top: 8px;
    }
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1>📞 Leads Management</h1>
        <a href="index.php" style="color: #0B1C3D; text-decoration: none;">← Back to Dashboard</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_leads; ?></div>
            <div class="stat-label">Total Leads</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php 
                $new_count = $conn->query("SELECT COUNT(*) as count FROM leads WHERE status = 'new'")->fetch_assoc()['count'];
                echo $new_count;
            ?></div>
            <div class="stat-label">New Leads</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php 
                $contacted_count = $conn->query("SELECT COUNT(*) as count FROM leads WHERE status = 'contacted'")->fetch_assoc()['count'];
                echo $contacted_count;
            ?></div>
            <div class="stat-label">Contacted</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <form style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
            <input type="text" name="phone" placeholder="Search by phone" value="<?php echo esc($search_phone); ?>">
            <select name="service">
                <option value="">All Services</option>
                <option value="Website Development" <?php echo $filter_service === 'Website Development' ? 'selected' : ''; ?>>Website Development</option>
                <option value="SEO Services" <?php echo $filter_service === 'SEO Services' ? 'selected' : ''; ?>>SEO Services</option>
                <option value="Social Media Marketing" <?php echo $filter_service === 'Social Media Marketing' ? 'selected' : ''; ?>>Social Media Marketing</option>
                <option value="General Inquiry" <?php echo $filter_service === 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
            </select>
            <select name="source">
                <option value="">All Sources</option>
                <option value="contact-form" <?php echo $filter_source === 'contact-form' ? 'selected' : ''; ?>>Contact Form</option>
                <option value="service-page" <?php echo $filter_source === 'service-page' ? 'selected' : ''; ?>>Service Page</option>
                <option value="homepage" <?php echo $filter_source === 'homepage' ? 'selected' : ''; ?>>Homepage</option>
            </select>
            <button type="submit">Filter</button>
            <a href="leads-management.php" style="padding: 8px 20px; background: #999; color: white; border-radius: 4px; text-decoration: none;">Reset</a>
        </form>
    </div>

    <!-- Leads Table -->
    <table class="leads-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Service</th>
                <th>Source</th>
                <th>Status</th>
                <th>Score</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leads as $lead): ?>
            <tr>
                <td><strong><?php echo esc($lead['name']); ?></strong></td>
                <td><a href="tel:<?php echo esc($lead['phone']); ?>"><?php echo esc($lead['phone']); ?></a></td>
                <td><?php echo esc($lead['email'] ?? '-'); ?></td>
                <td><?php echo esc($lead['service']); ?></td>
                <td><?php echo esc($lead['source']); ?></td>
                <td>
                    <span class="status-badge status-<?php echo $lead['status']; ?>">
                        <?php echo ucfirst($lead['status']); ?>
                    </span>
                </td>
                <td>
                    <?php 
                        $score = intval($lead['score'] ?? 0);
                        $score_class = $score >= 15 ? 'score-high' : ($score >= 8 ? 'score-medium' : 'score-low');
                    ?>
                    <span class="score-badge <?php echo $score_class; ?>">⭐ <?php echo $score; ?></span>
                </td>
                <td><?php echo date('M d, Y', strtotime($lead['created_at'])); ?></td>
                <td>
                    <div class="action-buttons">
                        <?php if ($lead['status'] === 'new'): ?>
                        <button class="btn-mark" onclick="markContacted(<?php echo $lead['id']; ?>)">✓ Mark</button>
                        <?php elseif ($lead['status'] === 'contacted'): ?>
                        <button class="btn-unmark" onclick="unmarkLead(<?php echo $lead['id']; ?>)">↩ Unmark</button>
                        <?php endif; ?>
                        <a href="?action=delete&id=<?php echo $lead['id']; ?>" class="btn-delete" onclick="return confirm('Delete this lead?')">🗑 Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=1<?php echo $filter_service ? '&service=' . urlencode($filter_service) : ''; ?><?php echo $filter_source ? '&source=' . urlencode($filter_source) : ''; ?>">« First</a>
        <a href="?page=<?php echo $page - 1; ?><?php echo $filter_service ? '&service=' . urlencode($filter_service) : ''; ?><?php echo $filter_source ? '&source=' . urlencode($filter_source) : ''; ?>">← Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i === $page): ?>
            <span style="background: #0B1C3D; color: white;"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?page=<?php echo $i; ?><?php echo $filter_service ? '&service=' . urlencode($filter_service) : ''; ?><?php echo $filter_source ? '&source=' . urlencode($filter_source) : ''; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo $filter_service ? '&service=' . urlencode($filter_service) : ''; ?><?php echo $filter_source ? '&source=' . urlencode($filter_source) : ''; ?>">Next →</a>
        <a href="?page=<?php echo $total_pages; ?><?php echo $filter_service ? '&service=' . urlencode($filter_service) : ''; ?><?php echo $filter_source ? '&source=' . urlencode($filter_source) : ''; ?>">Last »</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($leads)): ?>
    <div style="text-align: center; padding: 40px; color: #999;">
        <p>No leads found</p>
    </div>
    <?php endif; ?>
</div>

<script>
function markContacted(leadId) {
    fetch('leads-management.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mark_contacted&lead_id=' + leadId
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            location.reload();
        }
    });
}

function unmarkLead(leadId) {
    if (confirm('Are you sure you want to unmark this lead as "new"?')) {
        fetch('leads-management.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=unmark&lead_id=' + leadId
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                location.reload();
            }
        });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
