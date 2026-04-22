<?php
/**
 * Delete Asset API
 */

session_start();
require_once dirname(__FILE__) . '/../../config/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$asset_id = $_POST['id'] ?? null;

if (!$user_id || !$asset_id) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    // Get asset file path
    $stmt = $GLOBALS['conn']->prepare("SELECT file_path FROM assets WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $asset_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Asset not found']);
        exit;
    }
    
    // Delete file
    $file_path = dirname(__FILE__) . '/../../' . ltrim($result['file_path'], '/');
    if (file_exists($file_path)) {
        @unlink($file_path);
    }
    
    // Delete database record
    $stmt = $GLOBALS['conn']->prepare("DELETE FROM assets WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $asset_id, $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
