<?php
/**
 * Authentication Configuration & Session Management
 */

// Start output buffering to prevent header issues
if (!ob_get_level()) {
    ob_start();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start(); // Suppress warning if headers already sent
}

// Get current user
function getCurrentUser() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function getUserData() {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    if (!$conn) {
        return null;
    }
    
    $query = "SELECT id, username, email, full_name, phone, company_name, role, status FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user has specific role
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    $user = getUserData();
    return $user && $user['role'] === $role;
}

// Check if user is admin
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    $user = getUserData();
    return $user && in_array($user['role'], ['admin', 'manager']);
}

// Login user
function loginUser($username, $password) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'error' => 'Database unavailable'];
    }
    
    $query = "SELECT id, username, password, role, status FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['success' => false, 'error' => 'Database error'];
    }
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        return ['success' => false, 'error' => 'User not found'];
    }
    
    if ($user['status'] === 'suspended') {
        return ['success' => false, 'error' => 'Your account is suspended'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid password'];
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    
    return ['success' => true];
}

// Logout user
function logoutUser() {
    session_destroy();
    header('Location: /');
    exit;
}

// Register user
function registerUser($data) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'error' => 'Database unavailable'];
    }
    
    // Validate input
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
        return ['success' => false, 'error' => 'All fields are required'];
    }
    
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters'];
    }
    
    // Check if user exists
    $query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['success' => false, 'error' => 'Database error'];
    }
    $stmt->bind_param('ss', $data['username'], $data['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'error' => 'Username or email already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $query = "INSERT INTO users (username, email, password, full_name, phone, company_name, address) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['success' => false, 'error' => 'Database error'];
    }
    
    $phone = $data['phone'] ?? '';
    $company = $data['company_name'] ?? '';
    $address = $data['address'] ?? '';
    
    $stmt->bind_param('sssssss', $data['username'], $data['email'], $hashedPassword, $data['full_name'], $phone, $company, $address);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Registration successful! You can now login.'];
    } else {
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Require admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /');
        exit;
    }
}

// Redirect if logged in
function redirectIfLoggedIn($path = '/') {
    if (isLoggedIn()) {
        header('Location: ' . $path);
        exit;
    }
}

?>
