<?php
/**
 * Utility Functions
 */

// Load Google API config if not already loaded
if (!defined('GOOGLE_PLACES_API_KEY')) {
    require_once dirname(__DIR__) . '/config/google-api.php';
}

// Escape output for security (XSS protection)
function esc($text) {
    if ($text === null || $text === '') {
        return '';
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Generate slug from string
function generateSlug($str) {
    $str = trim($str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    $str = trim($str, '-');
    return $str;
}

// Format phone number
function formatPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) == 10) {
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}

// Get page title
function getPageTitle($type) {
    $titles = [
        'home' => 'Find Perfect Services Near You',
        'search' => 'Search Results',
        'business_detail' => 'Business Profile',
        'dashboard' => 'My Dashboard',
        'register' => 'Create Account',
        'login' => 'Login'
    ];
    return $titles[$type] ?? 'Business Directory';
}

// Search businesses function
function searchBusinesses($query, $category = '', $location = '', $limit = 10) {
    global $conn;
    
    if (empty($conn)) {
        return [];
    }
    
    $searchQuery = '%' . $query . '%';
    $searchLocation = '%' . $location . '%';
    
    if (!empty($location)) {
        $sql = "SELECT id, name, formatted_address, search_location, rating, user_ratings_total, types, formatted_phone_number
                FROM extracted_businesses
                WHERE (name LIKE ? OR types LIKE ?)
                AND search_location LIKE ?
                AND business_status = 'OPERATIONAL'
                ORDER BY rating DESC, user_ratings_total DESC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssi', $searchQuery, $searchQuery, $searchLocation, $limit);
    } else {
        $sql = "SELECT id, name, formatted_address, search_location, rating, user_ratings_total, types, formatted_phone_number
                FROM extracted_businesses
                WHERE (name LIKE ? OR types LIKE ?)
                AND business_status = 'OPERATIONAL'
                ORDER BY rating DESC, user_ratings_total DESC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $searchQuery, $searchQuery, $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC) ?? [];
}

// Format rating display
function formatRating($rating) {
    if ($rating === null || $rating === '') {
        return 'No rating';
    }
    
    $rating = floatval($rating);
    return number_format($rating, 1) . '/5';
}

// Format review count
function formatReviewCount($count) {
    if ($count === null || $count === '') {
        return '0 reviews';
    }
    
    $count = intval($count);
    
    if ($count === 0) return 'No reviews';
    if ($count === 1) return '1 review';
    
    return number_format($count) . ' reviews';
}

// Truncate text
function truncate($text, $limit = 100, $suffix = '...') {
    if (strlen($text) <= $limit) {
        return $text;
    }
    
    return substr($text, 0, $limit) . $suffix;
}

// Get first category
function getFirstCategory($types) {
    if (empty($types)) {
        return 'Business';
    }
    
    if (is_array($types)) {
        return $types[0] ?? 'Business';
    }
    
    // Parse comma-separated types
    $typesArray = array_map('trim', explode(',', $types));
    return $typesArray[0] ?? 'Business';
}

// Pagination helper
function paginate($total, $perPage, $currentPage = 1) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'offset' => $offset,
        'hasNext' => $currentPage < $totalPages,
        'hasPrev' => $currentPage > 1
    ];
}

// Slugify function for URLs
function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

?>
