<?php
/**
 * Client Login Page
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in']) {
    header('Location: /client/dashboard.php');
    exit;
}

require_once '../config/db.php';

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, name, password FROM clients WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $client = $result->fetch_assoc();
            if (password_verify($password, $client['password'])) {
                $_SESSION['client_logged_in'] = true;
                $_SESSION['client_id'] = $client['id'];
                $_SESSION['client_name'] = $client['name'];
                header('Location: /client/dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
        $stmt->close();
    } else {
        $error = 'Please enter email and password';
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT id FROM clients WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Create account
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO clients (name, email, password, phone, company) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $name, $email, $hashed_password, $phone, $company);
            
            if ($stmt->execute()) {
                $success = 'Account created! Please login.';
                $_SESSION['registration_success'] = true;
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Check for registration success message
$show_register = isset($_SESSION['registration_success']) && $_SESSION['registration_success'];
unset($_SESSION['registration_success']);

$page_title = "Client Login - ConnectWith";
require_once '../includes/header.php';
?>

<style>
    .auth-container {
        max-width: 900px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .auth-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .auth-box {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .auth-box h2 {
        color: #0B1C3D;
        margin-top: 0;
        font-size: 24px;
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
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        box-sizing: border-box;
    }

    .form-group input:focus {
        outline: none;
        border-color: #FF6A00;
        box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
    }

    .submit-btn {
        width: 100%;
        padding: 12px;
        background: #FF6A00;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
    }

    .submit-btn:hover {
        background: #E55A00;
    }

    .alert {
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .alert-error {
        background: #ffebee;
        color: #c62828;
        border-left: 4px solid #c62828;
    }

    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        border-left: 4px solid #2e7d32;
    }

    .divider {
        text-align: center;
        color: #999;
        margin: 25px 0;
        position: relative;
    }

    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #ddd;
    }

    .divider span {
        background: white;
        padding: 0 10px;
        position: relative;
    }

    .toggle-text {
        text-align: center;
        color: #666;
        font-size: 14px;
        margin-top: 20px;
    }

    .toggle-text button {
        background: none;
        border: none;
        color: #FF6A00;
        cursor: pointer;
        font-weight: 600;
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .auth-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="auth-container">
    <div class="auth-grid">
        <!-- Login Form -->
        <div class="auth-box" id="loginBox">
            <h2>Client Login</h2>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" name="login" class="submit-btn">Login to Account</button>
            </form>

            <div class="toggle-text">
                Don't have an account? <button type="button" onclick="toggleForms()">Register here</button>
            </div>
        </div>

        <!-- Register Form -->
        <div class="auth-box" id="registerBox" style="display: none;">
            <h2>Create Account</h2>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone">
                </div>

                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="company">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirm" required>
                </div>

                <button type="submit" name="register" class="submit-btn">Create Account</button>
            </form>

            <div class="toggle-text">
                Already have an account? <button type="button" onclick="toggleForms()">Login here</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleForms() {
    const loginBox = document.getElementById('loginBox');
    const registerBox = document.getElementById('registerBox');
    
    if (loginBox.style.display === 'none') {
        loginBox.style.display = 'block';
        registerBox.style.display = 'none';
    } else {
        loginBox.style.display = 'none';
        registerBox.style.display = 'block';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
