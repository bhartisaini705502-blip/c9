<?php
/**
 * Manage Locations - With Persistent Storage
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

// Locations data file
$locationsFile = __DIR__ . '/../data/locations.json';
$dataDir = __DIR__ . '/../data';

// Create data directory if it doesn't exist
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Default Indian cities list
$defaultCities = [
    'Ahmedabad', 'Allahabad', 'Amritsar', 'Aurangabad', 'Bangalore', 'Bhopal', 'Bhubaneswar',
    'Chandigarh', 'Chennai', 'Coimbatore', 'Cuttack', 'Dammam', 'Dehradun', 'Delhi', 'Dhanbad',
    'Durgapur', 'Erode', 'Faridabad', 'Faisalabad', 'Ghaziabad', 'Ghazipur', 'Goa', 'Gorakhpur',
    'Gurgaon', 'Guwahati', 'Hyderabad', 'Indore', 'Jaipur', 'Jalandhar', 'Jammu', 'Jamshedpur',
    'Jodhpur', 'Kanpur', 'Kochi', 'Kohima', 'Kolkata', 'Kota', 'Lucknow', 'Ludhiana', 'Madurai',
    'Meerut', 'Moradabad', 'Mumbai', 'Mysore', 'Nagpur', 'Navi Mumbai', 'Noida', 'Panaji',
    'Patna', 'Puducherry', 'Pune', 'Raipur', 'Rajkot', 'Ranchi', 'Sagar', 'Salem', 'Salone',
    'Sangli', 'Satara', 'Shimla', 'Shillong', 'Sholapur', 'Siliguri', 'Srinagar', 'Surat',
    'Thrissur', 'Tirupati', 'Tirupur', 'Trivandrum', 'Udaipur', 'Ujjain', 'Ulipur', 'Vadodara',
    'Varanasi', 'Vasai', 'Vijayawada', 'Visakhapatnam', 'Warangal', 'Yamunanagar'
];

// Load locations from file or use defaults
$indianCities = $defaultCities;
if (file_exists($locationsFile)) {
    $jsonData = file_get_contents($locationsFile);
    $loaded = json_decode($jsonData, true);
    if (is_array($loaded) && !empty($loaded)) {
        $indianCities = $loaded;
    }
}

// Save locations to file
function saveLocations($cities) {
    global $locationsFile;
    $cities = array_unique($cities);
    sort($cities);
    file_put_contents($locationsFile, json_encode($cities, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Handle add location
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_location'])) {
        $new_location = trim($_POST['new_location'] ?? '');
        
        if (!empty($new_location) && strlen($new_location) > 0) {
            if (in_array($new_location, $indianCities)) {
                $error = "Location '{$new_location}' already exists!";
            } else {
                $indianCities[] = $new_location;
                saveLocations($indianCities);
                $message = "✓ Location '{$new_location}' added successfully!";
            }
        } else {
            $error = "Location name cannot be empty";
        }
    } elseif (isset($_POST['delete_location'])) {
        $location_to_delete = trim($_POST['location_to_delete'] ?? '');
        
        if (!empty($location_to_delete)) {
            $key = array_search($location_to_delete, $indianCities);
            if ($key !== false) {
                unset($indianCities[$key]);
                $indianCities = array_values($indianCities);
                saveLocations($indianCities);
                $message = "✓ Location '{$location_to_delete}' deleted successfully!";
            }
        }
    }
}

// Search functionality
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$filteredLocations = $indianCities;

if (!empty($searchQuery)) {
    $filteredLocations = array_filter($indianCities, function($location) use ($searchQuery) {
        return stripos($location, $searchQuery) !== false;
    });
    $filteredLocations = array_values($filteredLocations);
}

// Pagination setup
$itemsPerPage = 12;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Sort locations
sort($filteredLocations);
$totalLocations = count($filteredLocations);
$totalPages = ceil($totalLocations / $itemsPerPage) ?: 1;

// Validate page number
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $itemsPerPage;
}

// Get paginated locations
$paginatedLocations = array_slice($filteredLocations, $offset, $itemsPerPage);

// Build pagination URL with search query
$paginationUrl = '';
if (!empty($searchQuery)) {
    $paginationUrl = '&search=' . urlencode($searchQuery);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Locations - Admin</title>
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

        .admin-container {
            max-width: 1200px;
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

        .message {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            border-left: 4px solid;
        }

        .success {
            background: #f0f9ff;
            color: #0c5a2e;
            border-left-color: #22c55e;
        }

        .error {
            background: #fef2f2;
            color: #7f1d1d;
            border-left-color: #ef4444;
        }

        .cards-section {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0B1C3D;
            margin-bottom: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: flex-end;
        }

        .form-row input,
        .form-row textarea {
            padding: 11px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-row input:focus,
        .form-row textarea:focus {
            outline: none;
            border-color: #0B1C3D;
            box-shadow: 0 0 0 3px rgba(11, 28, 61, 0.1);
        }

        .form-row button {
            padding: 11px 22px;
            background: #0B1C3D;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
            transition: all 0.3s ease;
        }

        .form-row button:hover {
            background: #1E3A8A;
            transform: translateY(-1px);
        }

        .location-count {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 20px;
            text-align: center;
        }

        .location-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .location-card {
            background: white;
            padding: 18px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .location-card:hover {
            border-color: #FF6A00;
            box-shadow: 0 4px 12px rgba(255, 106, 0, 0.15);
        }

        .location-card h4 {
            font-size: 18px;
            font-weight: 600;
            color: #0B1C3D;
            margin-bottom: 6px;
        }

        .location-card p {
            color: #9ca3af;
            font-size: 13px;
            margin-bottom: 14px;
        }

        .location-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .location-actions a,
        .location-actions button {
            flex: 1;
            padding: 9px 12px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: #f0f9ff;
            color: #0B1C3D;
            border: 1px solid #bfdbfe;
        }

        .btn-view:hover {
            background: #0B1C3D;
            color: white;
        }

        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-delete:hover {
            background: #dc2626;
            color: white;
        }

        .no-locations {
            text-align: center;
            padding: 50px 30px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            color: #9ca3af;
            grid-column: 1/-1;
            font-size: 15px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 35px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            border: 1px solid #d1d5db;
            color: #374151;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: white;
            cursor: pointer;
        }

        .pagination a:hover {
            background: #0B1C3D;
            color: white;
            border-color: #0B1C3D;
        }

        .pagination span.current {
            background: #0B1C3D;
            color: white;
            border-color: #0B1C3D;
        }

        .pagination span.disabled {
            color: #d1d5db;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .admin-header h1 {
                font-size: 22px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-row button {
                width: 100%;
            }

            .location-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .location-actions {
                flex-direction: column;
            }

            .location-actions a,
            .location-actions button {
                width: 100%;
            }

            .card {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>📍 Manage Locations</h1>
    </div>

    <div class="admin-container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo esc($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo esc($error); ?></div>
        <?php endif; ?>

        <div class="cards-section">
            <!-- Search Location -->
            <div class="card">
                <h3>🔍 Search Locations</h3>
                <form method="GET">
                    <div class="form-row">
                        <input type="text" name="search" placeholder="Search by location name..." value="<?php echo esc($searchQuery); ?>">
                        <button type="submit">Search</button>
                    </div>
                </form>
                <?php if (!empty($searchQuery)): ?>
                    <div style="margin-top: 12px;">
                        <a href="locations.php" style="color: #FF6A00; text-decoration: none; font-size: 13px; font-weight: 600;">✕ Clear Search</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add Location -->
            <div class="card">
                <h3>➕ Add New Location</h3>
                <form method="POST">
                    <div class="form-row">
                        <input type="text" name="new_location" placeholder="Enter location name (e.g., Nainital, Mussoorie)" required>
                        <button type="submit" name="add_location">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="location-count">
            📍 Showing <?php echo count($paginatedLocations); ?> of <?php echo $totalLocations; ?> locations (Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>)
        </div>

        <div class="location-grid">
            <?php if (empty($paginatedLocations)): ?>
                <div class="no-locations">
                    🔍 No locations found. Try a different search or add a new location.
                </div>
            <?php else: ?>
                <?php foreach ($paginatedLocations as $location): ?>
                    <div class="location-card">
                        <div>
                            <h4><?php echo esc($location); ?></h4>
                            <p>India</p>
                        </div>
                        <div class="location-actions">
                            <a href="/pages/search.php?location=<?php echo urlencode($location); ?>" target="_blank" class="btn-view">View</a>
                            <form method="POST" style="display: contents;">
                                <input type="hidden" name="location_to_delete" value="<?php echo esc($location); ?>">
                                <button type="submit" name="delete_location" class="btn-delete" onclick="return confirm('Delete <?php echo esc($location); ?>?');">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=1<?php echo $paginationUrl; ?>">« First</a>
                    <a href="?page=<?php echo $currentPage - 1; ?><?php echo $paginationUrl; ?>">‹ Prev</a>
                <?php else: ?>
                    <span class="disabled">« First</span>
                    <span class="disabled">‹ Prev</span>
                <?php endif; ?>

                <?php 
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                if ($startPage > 1): ?>
                    <a href="?page=1<?php echo $paginationUrl; ?>">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="disabled">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo $paginationUrl; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="disabled">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $totalPages; ?><?php echo $paginationUrl; ?>"><?php echo $totalPages; ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?><?php echo $paginationUrl; ?>">Next ›</a>
                    <a href="?page=<?php echo $totalPages; ?><?php echo $paginationUrl; ?>">Last »</a>
                <?php else: ?>
                    <span class="disabled">Next ›</span>
                    <span class="disabled">Last »</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
