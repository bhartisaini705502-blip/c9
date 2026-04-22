<?php
/**
 * API: Download and store Google business photos locally
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/google-api.php';

header('Content-Type: application/json');

$business_id = $_POST['business_id'] ?? null;

if (!$business_id) {
    echo json_encode(['success' => false, 'error' => 'Missing business_id']);
    exit;
}

$business_id = (int)$business_id;

if ($GLOBALS['conn']) {
    // Create business_images table if it doesn't exist
    $create_table = "
        CREATE TABLE IF NOT EXISTS business_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            business_id INT NOT NULL,
            image_path VARCHAR(255),
            image_url TEXT,
            stored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_business_id (business_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $GLOBALS['conn']->query($create_table);
    
    // Check if images already stored
    $existing = $GLOBALS['conn']->query("SELECT COUNT(*) as count FROM business_images WHERE business_id = $business_id");
    $has_images = $existing->fetch_assoc()['count'] > 0;
    
    if ($has_images) {
        echo json_encode(['success' => true, 'message' => 'Images already stored']);
        exit;
    }
    
    // Get business data
    $business = $GLOBALS['conn']->query("SELECT photo_references FROM extracted_businesses WHERE id = $business_id")->fetch_assoc();
    
    if (!$business || empty($business['photo_references'])) {
        echo json_encode(['success' => false, 'error' => 'No photos available']);
        exit;
    }
    
    // Parse photo references
    $photoRefs = array_filter(explode('|', $business['photo_references']));
    $upload_dir = dirname(__DIR__) . '/uploads/business-photos';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }
    
    $stored_count = 0;
    $api_key = getenv('GOOGLE_API_KEY2') ?: '';
    
    // If no API key, store the URLs as-is
    if (empty($api_key)) {
        foreach (array_slice($photoRefs, 0, 6) as $ref) {
            // Just store the reference for later use
            $stmt = $GLOBALS['conn']->prepare("
                INSERT IGNORE INTO business_images (business_id, image_path, image_url)
                VALUES (?, ?, ?)
            ");
            
            if ($stmt) {
                $rel_path = 'photo_ref:' . $ref;
                $stmt->bind_param('iss', $business_id, $rel_path, $ref);
                $stmt->execute();
                $stmt->close();
                $stored_count++;
            }
        }
        echo json_encode(['success' => true, 'stored' => $stored_count, 'note' => 'API key not configured, using photo references']);
        exit;
    }
    
    foreach (array_slice($photoRefs, 0, 6) as $ref) {
        $photoUrl = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=800&photo_reference=" . urlencode($ref) . "&key=" . $api_key;
        
        $filename = $business_id . '_' . md5($ref) . '.jpg';
        $filepath = $upload_dir . '/' . $filename;
        
        // Download image if not already stored
        if (!file_exists($filepath)) {
            $context = stream_context_create([
                'http' => ['timeout' => 5, 'follow_location' => true],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ]);
            $image_data = @file_get_contents($photoUrl, false, $context);
            
            if ($image_data && strlen($image_data) > 1000) { // Ensure it's a valid image
                @file_put_contents($filepath, $image_data);
                
                // Save to database
                $rel_path = '/uploads/business-photos/' . $filename;
                $stmt = $GLOBALS['conn']->prepare("
                    INSERT IGNORE INTO business_images (business_id, image_path, image_url)
                    VALUES (?, ?, ?)
                ");
                
                if ($stmt) {
                    $stmt->bind_param('iss', $business_id, $rel_path, $photoUrl);
                    $stmt->execute();
                    $stmt->close();
                    $stored_count++;
                }
            }
        } else {
            $stored_count++;
        }
    }
    
    echo json_encode(['success' => true, 'stored' => $stored_count]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
}
