<?php
/**
 * User Login Page
 */

require_once '../config/db.php';
require_once '../config/auth.php';

// Redirect if already logged in - to appropriate dashboard
if (isLoggedIn()) {
    // Check user role from database
    $userData = getUserData();
    if ($userData && $userData['role'] === 'telecaller') {
        redirect('/pages/telecaller-dashboard.php');
    } else {
        redirect('/pages/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = loginUser($_POST['username'], $_POST['password']);
    if ($result['success']) {
        // Check user role from database
        $userData = getUserData();
        if ($userData && $userData['role'] === 'telecaller') {
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/pages/telecaller-dashboard.php';
        } else {
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/pages/dashboard.php';
        }
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = $result['error'];
    }
}

$page_title = 'Login';
$meta_description = 'Login to your account to claim and manage business listings.';

include '../includes/header.php';
?>

<style>
.auth-container {
    max-width: 400px;
    margin: 60px auto;
    padding: 40px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-header h1 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 28px;
}

.auth-header p {
    color: #666;
    margin: 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #DDD;
    border-radius: 5px;
    font-size: 14px;
    font-family: inherit;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-login {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.auth-links {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
    font-size: 14px;
}

.auth-links a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.auth-links a:hover {
    text-decoration: underline;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
    background: #F8D7DA;
    color: #721C24;
    border: 1px solid #F5C6CB;
}

@media (max-width: 600px) {
    .auth-container {
        margin: 20px;
        padding: 20px;
    }
}
</style>

<div class="container">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Login</h1>
            <p>Access your claimed listings</p>
        </div>

        <?php if ($error): ?>
            <div class="alert"><?php echo esc($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email *</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>

            <div class="auth-links">
                <a href="/auth/register.php">Create Account</a>
                <a href="/auth/forgot-password.php">Forgot Password?</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
