<?php
/**
 * User Registration Page
 */

require_once '../config/db.php';
require_once '../config/auth.php';

redirectIfLoggedIn('/pages/dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = registerUser($_POST);
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['error'];
    }
}

$page_title = 'Register Account';
$meta_description = 'Create a new account to claim and manage business listings.';

include '../includes/header.php';
?>

<style>
.auth-container {
    max-width: 500px;
    margin: 40px auto;
    padding: 30px;
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

.form-group input, .form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #DDD;
    border-radius: 5px;
    font-size: 14px;
    font-family: inherit;
    box-sizing: border-box;
}

.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.btn-register {
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

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.auth-link {
    text-align: center;
    margin-top: 20px;
    color: #666;
}

.auth-link a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.auth-link a:hover {
    text-decoration: underline;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-error {
    background: #F8D7DA;
    color: #721C24;
    border: 1px solid #F5C6CB;
}

.alert-success {
    background: #D4EDDA;
    color: #155724;
    border: 1px solid #C3E6CB;
}

@media (max-width: 600px) {
    .auth-container {
        margin: 20px;
        padding: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Register to claim and manage business listings</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo esc($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo esc($success); ?>
                <p style="margin-top: 10px;">
                    <a href="/auth/login.php" style="color: #155724; text-decoration: underline;">Click here to login</a>
                </p>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? esc($_POST['username']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? esc($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required value="<?php echo isset($_POST['full_name']) ? esc($_POST['full_name']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? esc($_POST['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo isset($_POST['company_name']) ? esc($_POST['company_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"><?php echo isset($_POST['address']) ? esc($_POST['address']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>

                <button type="submit" class="btn-register">Create Account</button>
            </form>

            <div class="auth-link">
                Already have an account? <a href="/auth/login.php">Login here</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
