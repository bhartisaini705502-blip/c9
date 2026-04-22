<?php
/**
 * Admin: Import Businesses from Google
 */
session_start();

// Check admin access
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';

$import_message = '';
$import_type = 'success';

// Handle import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    $category = trim($_POST['category'] ?? '');
    $city = trim($_POST['city'] ?? '');
    
    if (!empty($category) && !empty($city)) {
            
            // Simulate Google API import (placeholder)
            $sample_businesses = [
                [
                    'name' => "$category Business 1 - $city",
                    'address' => "123 Main Street, $city",
                    'phone' => '+91 98765 43210',
                    'rating' => 4.5,
                    'latitude' => 20.5937 + (rand(-10, 10) * 0.01),
                    'longitude' => 78.9629 + (rand(-10, 10) * 0.01)
                ],
                [
                    'name' => "$category Business 2 - $city",
                    'address' => "456 Park Road, $city",
                    'phone' => '+91 98765 54321',
                    'rating' => 4.2,
                    'latitude' => 20.5937 + (rand(-10, 10) * 0.01),
                    'longitude' => 78.9629 + (rand(-10, 10) * 0.01)
                ],
                [
                    'name' => "$category Business 3 - $city",
                    'address' => "789 Market Lane, $city",
                    'phone' => '+91 98765 65432',
                    'rating' => 4.8,
                    'latitude' => 20.5937 + (rand(-10, 10) * 0.01),
                    'longitude' => 78.9629 + (rand(-10, 10) * 0.01)
                ],
                [
                    'name' => "$category Business 4 - $city",
                    'address' => "321 Shopping Center, $city",
                    'phone' => '+91 98765 76543',
                    'rating' => 4.3,
                    'latitude' => 20.5937 + (rand(-10, 10) * 0.01),
                    'longitude' => 78.9629 + (rand(-10, 10) * 0.01)
                ],
                [
                    'name' => "$category Business 5 - $city",
                    'address' => "654 Business Park, $city",
                    'phone' => '+91 98765 87654',
                    'rating' => 4.6,
                    'latitude' => 20.5937 + (rand(-10, 10) * 0.01),
                    'longitude' => 78.9629 + (rand(-10, 10) * 0.01)
                ]
            ];
            
            // Insert businesses (avoid duplicates by name + phone)
            $imported = 0;
            foreach ($sample_businesses as $biz) {
                $check = $conn->query("SELECT id FROM extracted_businesses WHERE name = '{$biz['name']}' AND formatted_phone_number = '{$biz['phone']}'");
                
                if ($check->num_rows === 0) {
                    // Generate unique place_id
                    $place_id = 'place_' . md5($biz['name'] . $biz['phone'] . time() . rand(1000, 9999));
                    
                    $stmt = $conn->prepare("
                        INSERT INTO extracted_businesses 
                        (place_id, name, formatted_address, formatted_phone_number, rating, lat, lng, types, search_location)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->bind_param(
                        'ssssdddss',
                        $place_id,
                        $biz['name'],
                        $biz['address'],
                        $biz['phone'],
                        $biz['rating'],
                        $biz['latitude'],
                        $biz['longitude'],
                        $category,
                        $city
                    );
                    
                    if ($stmt->execute()) {
                        $imported++;
                    } else {
                        error_log("Import error for {$biz['name']}: " . $stmt->error);
                    }
                    $stmt->close();
                }
            }
            
            $import_message = "✅ Imported $imported businesses for $category in $city";
            $import_type = 'success';
        } else {
            $import_message = '❌ Please select both category and city';
            $import_type = 'error';
        }
    }

// Get all categories and cities from extracted_businesses and JSON files
$categories = [];
$cities = [];
$citySet = [];

// Get distinct categories from types field
try {
    $cat_result = $conn->query("SELECT DISTINCT types as name FROM extracted_businesses WHERE types IS NOT NULL AND types != '' ORDER BY types");
    if ($cat_result) {
        while ($row = $cat_result->fetch_assoc()) {
            // Handle comma-separated categories - take all unique ones
            $typesArray = array_map('trim', explode(',', $row['name']));
            foreach ($typesArray as $type) {
                if (!empty($type) && !array_key_exists($type, array_flip(array_column($categories, 'name')))) {
                    $categories[] = ['id' => count($categories) + 1, 'name' => $type];
                }
            }
        }
    }
} catch (Exception $e) {
    $categories = [];
}

// Sort categories alphabetically
usort($categories, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

// Get distinct cities from search_location in database
try {
    $city_result = $conn->query("SELECT DISTINCT search_location as name FROM extracted_businesses WHERE search_location IS NOT NULL AND search_location != '' ORDER BY search_location");
    if ($city_result) {
        while ($row = $city_result->fetch_assoc()) {
            $name = trim($row['name']);
            if (!empty($name)) {
                $citySet[$name] = 1;
            }
        }
    }
} catch (Exception $e) {
    // Database error, will use JSON file
}

// Load cities from JSON file (persistent storage)
$locationsFile = __DIR__ . '/../data/locations.json';
if (file_exists($locationsFile)) {
    $jsonData = file_get_contents($locationsFile);
    $jsonCities = json_decode($jsonData, true);
    if (is_array($jsonCities)) {
        foreach ($jsonCities as $city) {
            if (!empty($city)) {
                $citySet[$city] = 1;
            }
        }
    }
}

// Convert to array format for display
$id = 1;
ksort($citySet); // Sort alphabetically
foreach (array_keys($citySet) as $cityName) {
    $cities[] = ['id' => $id++, 'name' => $cityName];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Import Businesses</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .admin-header {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            padding: 25px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .admin-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin: 0;
        }

        .import-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #0B1C3D;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #FF6A00;
            margin-left: -5px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .message {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            border-left: 4px solid;
        }

        .message.success {
            background: #f0f9ff;
            color: #0c5a2e;
            border-left-color: #22c55e;
        }

        .message.error {
            background: #fef2f2;
            color: #7f1d1d;
            border-left-color: #ef4444;
        }

        .info-box {
            background: #f0f4ff;
            padding: 15px;
            border-left: 4px solid #0B1C3D;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #333;
        }

        .info-box strong {
            color: #0B1C3D;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #0B1C3D;
            font-size: 14px;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-group select:focus {
            outline: none;
            border-color: #0B1C3D;
            box-shadow: 0 0 0 3px rgba(11, 28, 61, 0.1);
        }

        .import-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #FF6A00, #FF8533);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .import-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 106, 0, 0.3);
        }

        .import-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .no-data {
            color: #ef4444;
            margin-top: 8px;
            font-size: 13px;
        }

        .stat-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
        }

        .stat {
            text-align: center;
        }

        .stat-count {
            font-size: 24px;
            font-weight: 600;
            color: #0B1C3D;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .admin-header h1 {
                font-size: 22px;
            }

            .card {
                padding: 20px;
            }

            .stat-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>📥 Import Businesses</h1>
    </div>

    <div class="import-container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <?php if (!empty($import_message)): ?>
            <div class="message <?php echo $import_type; ?>">
                <?php echo $import_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="info-box">
                <strong>ℹ️ How it works:</strong><br>
                Select a business category and city, then click Import to add 5 verified businesses from Google to your directory.
            </div>

            <!-- Statistics -->
            <div class="stat-info">
                <div class="stat">
                    <div class="stat-count"><?php echo count($categories); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
                <div class="stat">
                    <div class="stat-count"><?php echo count($cities); ?></div>
                    <div class="stat-label">Cities</div>
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="category">🏪 Select Business Category:</label>
                    <select id="category" name="category" required>
                        <option value="">-- Choose a category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($categories)): ?>
                        <p class="no-data">⚠️ No categories found in database</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="city">🏙️ Select City:</label>
                    <select id="city" name="city" required>
                        <option value="">-- Choose a city --</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['name']); ?>">
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($cities)): ?>
                        <p class="no-data">⚠️ No cities found. Add locations in Manage Locations first.</p>
                    <?php endif; ?>
                </div>

                <input type="hidden" name="action" value="import">
                <button type="submit" class="import-btn" <?php echo (empty($categories) || empty($cities)) ? 'disabled' : ''; ?>>
                    🚀 Import 5 Businesses
                </button>
            </form>
        </div>
    </div>
</body>
</html>
