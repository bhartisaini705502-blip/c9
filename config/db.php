<?php
/**
 * Database Configuration and Connection
 * Using MySQLi for prepared statements and security
 */

// Database credentials (from environment or hardcoded for testing)
define('DB_HOST', getenv('DB_HOST') ?: 'srv1553.hstgr.io');
define('DB_USER', getenv('DB_USER') ?: 'u640422689_mapdirectory');
define('DB_PASS', getenv('DB_PASS') ?: 'Ashish@1234#!');
define('DB_NAME', getenv('DB_NAME') ?: 'u640422689_mapdirectory');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Create connection with error handling
$conn = null;
$db_unavailable = true;

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
        $db_unavailable = false;
    }
} catch (Exception $e) {
    // Database is unavailable, use sample data
    $conn = null;
}

// Define constant based on connection result
define('DB_UNAVAILABLE', $db_unavailable);

// Transform queries from 'businesses' table to 'extracted_businesses' 
function transformQuery($query) {
    // Replace table name
    $query = str_ireplace('FROM businesses', 'FROM extracted_businesses', $query);
    
    // Handle status field mappings with proper value conversion
    $query = str_ireplace("status = 'active'", "business_status = 'OPERATIONAL'", $query);
    $query = str_ireplace("status = 'inactive'", "business_status != 'OPERATIONAL'", $query);
    $query = str_ireplace("status = ?", "business_status = ?", $query);
    
    // Map other column names
    $mappings = [
        'category' => 'types',
        'address' => 'formatted_address',
        'city' => 'search_location',
        'phone' => 'formatted_phone_number',
        'reviews_count' => 'user_ratings_total',
        'latitude' => 'lat',
        'longitude' => 'lng',
        'state' => 'vicinity',
    ];
    
    foreach ($mappings as $old => $new) {
        // Be careful with word boundaries
        $query = str_ireplace(' ' . $old . ' ', ' ' . $new . ' ', $query);
        $query = str_ireplace('SELECT ' . $old, 'SELECT ' . $new . ' as ' . $old, $query);
        $query = str_ireplace('DISTINCT ' . $old, 'DISTINCT ' . $new, $query);
        $query = str_ireplace('COUNT(DISTINCT ' . $old . ')', 'COUNT(DISTINCT ' . $new . ')', $query);
        $query = str_ireplace('WHERE ' . $old, 'WHERE ' . $new, $query);
        $query = str_ireplace('ORDER BY ' . $old, 'ORDER BY ' . $new, $query);
        $query = str_ireplace(', ' . $old . ' ', ', ' . $new . ' ', $query);
    }
    
    return $query;
}

// Function to execute prepared statement
function executeQuery($query, $params = [], $types = '') {
    global $conn;
    
    if (!$conn || DB_UNAVAILABLE) {
        return null;
    }
    
    try {
        // Transform query to use actual table structure
        $query = transformQuery($query);
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return null;
        }
        
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            return null;
        }
        
        return $stmt;
    } catch (Exception $e) {
        return null;
    }
}

// Function to get single row
function getRow($query, $params = [], $types = '') {
    $stmt = executeQuery($query, $params, $types);
    if (!$stmt) return getSampleRow($query);
    
    try {
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        return getSampleRow($query);
    }
}

// Function to get all rows
function getRows($query, $params = [], $types = '') {
    $stmt = executeQuery($query, $params, $types);
    if (!$stmt) return getSampleData($query);
    
    try {
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    } catch (Exception $e) {
        return getSampleData($query);
    }
}

// Sample data for demo when database is unavailable
function getSampleData($query) {
    // Return sample businesses for demo
    if (stripos($query, 'SELECT * FROM') !== false || stripos($query, 'SELECT DISTINCT') !== false) {
        return [
            ['id' => 1, 'name' => 'ABC Electricals', 'category' => 'Electrician', 'address' => '123 Main St', 'city' => 'Dehradun', 'state' => 'Uttarakhand', 'phone' => '+91-9876543210', 'website' => 'https://example.com', 'rating' => 4.8, 'reviews_count' => 156, 'latitude' => 30.1975, 'longitude' => 78.1629],
            ['id' => 2, 'name' => 'Best Salon', 'category' => 'Salon', 'address' => '456 Park Ave', 'city' => 'Dehradun', 'state' => 'Uttarakhand', 'phone' => '+91-9876543211', 'website' => 'https://example.com', 'rating' => 4.6, 'reviews_count' => 89, 'latitude' => 30.1975, 'longitude' => 78.1629],
            ['id' => 3, 'name' => 'Fast Plumbing', 'category' => 'Plumbing', 'address' => '789 Water St', 'city' => 'Dehradun', 'state' => 'Uttarakhand', 'phone' => '+91-9876543212', 'website' => 'https://example.com', 'rating' => 4.5, 'reviews_count' => 73, 'latitude' => 30.1975, 'longitude' => 78.1629],
            ['id' => 4, 'name' => 'Tech Support Plus', 'category' => 'IT Services', 'address' => '321 Tech Park', 'city' => 'Dehradun', 'state' => 'Uttarakhand', 'phone' => '+91-9876543213', 'website' => 'https://example.com', 'rating' => 4.7, 'reviews_count' => 120, 'latitude' => 30.1975, 'longitude' => 78.1629],
            ['id' => 5, 'name' => 'Green Gardening', 'category' => 'Gardening', 'address' => '654 Garden Lane', 'city' => 'Dehradun', 'state' => 'Uttarakhand', 'phone' => '+91-9876543214', 'website' => 'https://example.com', 'rating' => 4.4, 'reviews_count' => 62, 'latitude' => 30.1975, 'longitude' => 78.1629],
        ];
    }
    if (stripos($query, 'COUNT(*)') !== false || stripos($query, 'COUNT(DISTINCT') !== false) {
        return [];
    }
    if (stripos($query, 'SELECT DISTINCT') !== false) {
        return [
            ['city' => 'Dehradun'],
            ['city' => 'Delhi'],
            ['city' => 'Bangalore'],
        ];
    }
    return [];
}

function getSampleRow($query) {
    if (stripos($query, 'COUNT(*)') !== false) {
        return ['count' => 5];
    }
    if (stripos($query, 'COUNT(DISTINCT') !== false) {
        return ['count' => 5];
    }
    if (stripos($query, 'AVG(rating)') !== false) {
        return ['avg' => 4.6];
    }
    return null;
}

// Function to execute insert/update/delete
function execute($query, $params = [], $types = '') {
    $stmt = executeQuery($query, $params, $types);
    global $conn;
    return ($stmt && $conn) ? $conn->affected_rows : false;
}

?>
