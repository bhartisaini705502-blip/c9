<?php
/**
 * Database Migration: India Directory System
 * Creates states, cities, categories tables and updates businesses
 * Run once in browser: http://localhost:5000/config/migrate-directory-system.php
 */

require_once 'db.php';

if (!$conn) {
    die('Database connection failed');
}

$errors = [];
$success = [];

try {
    // ====== 1. CREATE STATES TABLE ======
    $sql = "CREATE TABLE IF NOT EXISTS states (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        $success[] = '✓ States table created';
    } else {
        $errors[] = 'States table: ' . $conn->error;
    }

    // ====== 2. CREATE CITIES TABLE ======
    $sql = "CREATE TABLE IF NOT EXISTS cities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        state_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (state_id) REFERENCES states(id),
        UNIQUE KEY unique_city_state (state_id, name),
        INDEX idx_state_id (state_id)
    )";
    
    if ($conn->query($sql)) {
        $success[] = '✓ Cities table created';
    } else {
        $errors[] = 'Cities table: ' . $conn->error;
    }

    // ====== 3. CREATE CATEGORIES TABLE ======
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(100) NOT NULL UNIQUE,
        icon VARCHAR(50),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        $success[] = '✓ Categories table created';
    } else {
        $errors[] = 'Categories table: ' . $conn->error;
    }

    // ====== 4. UPDATE BUSINESSES TABLE ======
    $alterQueries = [
        "ALTER TABLE extracted_businesses ADD COLUMN IF NOT EXISTS category_id INT",
        "ALTER TABLE extracted_businesses ADD COLUMN IF NOT EXISTS city_id INT",
        "ALTER TABLE extracted_businesses ADD COLUMN IF NOT EXISTS is_verified TINYINT DEFAULT 0",
        "ALTER TABLE extracted_businesses ADD COLUMN IF NOT EXISTS data_source VARCHAR(50) DEFAULT 'local'",
        "ALTER TABLE extracted_businesses ADD INDEX IF NOT EXISTS idx_category_id (category_id)",
        "ALTER TABLE extracted_businesses ADD INDEX IF NOT EXISTS idx_city_id (city_id)",
        "ALTER TABLE extracted_businesses ADD INDEX IF NOT EXISTS idx_data_source (data_source)"
    ];
    
    foreach ($alterQueries as $query) {
        if ($conn->query($query)) {
            // Silent success
        } else {
            if (strpos($conn->error, 'Duplicate column') === false) {
                $errors[] = 'Alter table: ' . $conn->error;
            }
        }
    }
    $success[] = '✓ Businesses table updated';

    // ====== 5. INSERT INDIAN STATES ======
    $states = [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
        'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
        'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
        'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
        'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
        'Lakshadweep', 'Delhi', 'Puducherry', 'Ladakh'
    ];
    
    $inserted_states = 0;
    foreach ($states as $state) {
        $stmt = $conn->prepare("INSERT IGNORE INTO states (name) VALUES (?)");
        $stmt->bind_param('s', $state);
        if ($stmt->execute()) {
            $inserted_states++;
        }
        $stmt->close();
    }
    $success[] = "✓ States inserted: $inserted_states";

    // ====== 6. INSERT CITIES FOR MAJOR STATES ======
    $cities_data = [
        'Maharashtra' => ['Mumbai', 'Pune', 'Nagpur', 'Ahmedabad', 'Nashik', 'Aurangabad', 'Thane'],
        'Delhi' => ['Delhi', 'New Delhi', 'Dwarka', 'Noida'],
        'Karnataka' => ['Bangalore', 'Mysore', 'Mangalore', 'Hubballi', 'Udupi'],
        'Tamil Nadu' => ['Chennai', 'Coimbatore', 'Madurai', 'Salem', 'Trichy'],
        'Uttar Pradesh' => ['Lucknow', 'Kanpur', 'Varanasi', 'Agra', 'Allahabad', 'Meerut'],
        'West Bengal' => ['Kolkata', 'Darjeeling', 'Asansol', 'Durgapur', 'Siliguri'],
        'Gujarat' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Jamnagar'],
        'Rajasthan' => ['Jaipur', 'Jodhpur', 'Udaipur', 'Ajmer', 'Bikaner'],
        'Punjab' => ['Chandigarh', 'Amritsar', 'Ludhiana', 'Jalandhar', 'Patiala'],
        'Haryana' => ['Gurgaon', 'Faridabad', 'Hisar', 'Rohtak', 'Panipat'],
        'Madhya Pradesh' => ['Indore', 'Bhopal', 'Jabalpur', 'Gwalior', 'Ujjain'],
        'Telangana' => ['Hyderabad', 'Warangal', 'Nizamabad', 'Karimnagar'],
        'Kerala' => ['Kochi', 'Thiruvananthapuram', 'Kozhikode', 'Thrissur', 'Alappuzha']
    ];
    
    $inserted_cities = 0;
    foreach ($cities_data as $state_name => $cities) {
        $state_result = $conn->query("SELECT id FROM states WHERE name = '$state_name'");
        if ($state_result && $state_result->num_rows > 0) {
            $state_row = $state_result->fetch_assoc();
            $state_id = $state_row['id'];
            
            foreach ($cities as $city) {
                $stmt = $conn->prepare("INSERT IGNORE INTO cities (state_id, name) VALUES (?, ?)");
                $stmt->bind_param('is', $state_id, $city);
                if ($stmt->execute()) {
                    $inserted_cities++;
                }
                $stmt->close();
            }
        }
    }
    $success[] = "✓ Cities inserted: $inserted_cities";

    // ====== 7. INSERT CATEGORIES ======
    $categories = [
        ['Restaurant', 'restaurant', '🍽️'],
        ['Hospital', 'hospital', '🏥'],
        ['Salon', 'salon', '💇'],
        ['Gym', 'gym', '💪'],
        ['Real Estate', 'real-estate', '🏠'],
        ['School', 'school', '🎓'],
        ['Coaching', 'coaching', '📚'],
        ['Digital Marketing', 'digital-marketing', '📱'],
        ['Hotel', 'hotel', '🛏️'],
        ['Cafe', 'cafe', '☕'],
        ['Beauty Parlor', 'beauty-parlor', '💄'],
        ['Clothing Store', 'clothing-store', '👔'],
        ['Grocery Store', 'grocery-store', '🛒'],
        ['Pharmacy', 'pharmacy', '💊'],
        ['Car Repair', 'car-repair', '🔧'],
        ['Bank', 'bank', '🏦'],
        ['Insurance', 'insurance', '📋'],
        ['Legal Services', 'legal-services', '⚖️'],
        ['IT Services', 'it-services', '💻'],
        ['Plumber', 'plumber', '🔌']
    ];
    
    $inserted_cats = 0;
    foreach ($categories as $cat) {
        $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, slug, icon) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $cat[0], $cat[1], $cat[2]);
        if ($stmt->execute()) {
            $inserted_cats++;
        }
        $stmt->close();
    }
    $success[] = "✓ Categories inserted: $inserted_cats";

} catch (Exception $e) {
    $errors[] = 'Exception: ' . $e->getMessage();
}

@$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Directory System Migration</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #155724; background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 4px; }
        h1 { color: #0B1C3D; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗂️ Directory System Migration</h1>
        
        <?php if (!empty($success)): ?>
            <h2>✅ Success</h2>
            <?php foreach ($success as $msg): ?>
                <div class="success"><?php echo $msg; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <h2>❌ Errors</h2>
            <?php foreach ($errors as $msg): ?>
                <div class="error"><?php echo $msg; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <hr>
        <p><strong>Tables created:</strong> states, cities, categories</p>
        <p><strong>Businesses table updated with:</strong> category_id, city_id, is_verified, data_source</p>
        <p><a href="/">Back to Home</a></p>
    </div>
</body>
</html>
