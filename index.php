<?php
// Set session cookie parameters before session_start()
ini_set('session.cookie_lifetime', '0');
ini_set('session.use_cookies', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

session_start();

// Include the active users tracking functionality
require_once 'active_users.php';

// Initialize mode variables
$reset_mode = false;
$show_password_mode = false;

// Check if password is already set in session
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: home.php');
    exit();
}

// Show current password functionality
if (isset($_GET['action']) && $_GET['action'] === 'show_password') {
    $show_password_mode = true;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_code_verify'])) {
        $correct_reset_code = "RAHULJADHAV310";
        
        if ($_POST['reset_code_verify'] === $correct_reset_code) {
            // Get current password
            $current_password = 'Rahul310'; // Default password
            
            if (file_exists('password_config.php')) {
                include 'password_config.php';
                if (defined('SYSTEM_PASSWORD')) {
                    $current_password = SYSTEM_PASSWORD;
                }
            }
            
            $password_revealed = true;
        } else {
            $error = "Invalid reset code. Please try again.";
        }
    }
} else {
    $show_password_mode = false;
}

// Reset password functionality
if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    $reset_mode = true;
    $show_password_mode = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_code']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        // In a real-world scenario, you would validate against a stored reset code
        // For this demo system, we're using a hardcoded reset code
        $correct_reset_code = "RAHULJADHAV310"; // This should be generated dynamically in a real system
        
        // Check if passwords match
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = "Passwords do not match. Please try again.";
        } elseif ($_POST['reset_code'] === $correct_reset_code) {
            // In a real system, you would save this to a database
            // For this demo, we'll just write to a config file
            $new_password = htmlspecialchars($_POST['new_password']);
            $config_content = "<?php\n";
            $config_content .= "// Password configuration\n";
            $config_content .= "define('SYSTEM_PASSWORD', '{$new_password}');\n";
            $config_content .= "?>";
            
            // Save the new password to a file
            if (file_put_contents('password_config.php', $config_content)) {
                // Create or update the session reset file with current timestamp
                // This will force all active sessions to log out
                file_put_contents('session_reset.txt', time());
                
                // Set success message and redirect to login page
                $_SESSION['success_message'] = "Password has been reset successfully. You can now login with your new password. All users have been logged out.";
                header('Location: index.php');
                exit();
            } else {
                $error = "Failed to reset password. Please try again or contact administrator.";
            }
        } else {
            $error = "Invalid reset code. Please try again.";
        }
    }
} else if (!isset($_GET['action']) || $_GET['action'] !== 'show_password') {
    $reset_mode = false;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$reset_mode && !$show_password_mode && isset($_POST['password'])) {
    // Include password config if it exists
    $correct_password = 'Rahul310'; // Default password
    
    if (file_exists('password_config.php')) {
        include 'password_config.php';
        if (defined('SYSTEM_PASSWORD')) {
            $correct_password = SYSTEM_PASSWORD;
        }
    }
    
    $password = $_POST['password'];
    
    if ($password === $correct_password) {
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time(); // Set initial last activity time
        
        // Store the current session_reset timestamp in the user's session
        if (file_exists('session_reset.txt')) {
            $_SESSION['password_reset_time'] = file_get_contents('session_reset.txt');
        } else {
            $_SESSION['password_reset_time'] = time();
        }
        
        // Add the user to active users list
        addActiveUser(session_id(), time());
        
        header('Location: home.php');
        exit();
    } else {
        $error = "Incorrect password. Please try again.";
    }
}

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error = "Your session has expired. Please login again.";
}

// Check for reset message
if (isset($_GET['reset'])) {
    $error = "Password has been changed. Please login with your new password.";
}

// Check for terminated message
if (isset($_GET['terminated'])) {
    $error = "Your session has been terminated by an administrator. Please login again.";
}

// Check for success message from reset
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message so it doesn't show again
}

// Get the count of active users
$active_users_count = countActiveUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tractor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header i {
            font-size: 3rem;
            color: #2575fc;
            margin-bottom: 1rem;
        }
        .login-header h1 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #666;
            margin-bottom: 0;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.8rem;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #2575fc;
            box-shadow: 0 0 0 0.2rem rgba(37, 117, 252, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border: none;
            border-radius: 8px;
            padding: 0.8rem;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .forgot-password a {
            color: #2575fc;
            text-decoration: none;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
        .hint-text {
            display: none;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
            font-style: italic;
        }
        .hint-btn {
            color: #2575fc;
            background: none;
            border: none;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
            margin-left: 0.5rem;
        }
        .password-toggle {
            cursor: pointer;
            color: #6c757d;
        }
        .password-toggle:hover {
            color: #2575fc;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 1rem;
        }
        .password-revealed {
            text-align: center;
            margin: 1rem 0;
            padding: 1rem;
            background-color: #e8f4ff;
            border-radius: 8px;
            font-size: 1.1rem;
        }
        .character {
            display: inline-block;
            font-family: monospace;
            font-weight: bold;
            font-size: 1.2rem;
            padding: 2px 4px;
            margin: 0 1px;
        }
        .uppercase {
            color: #d63384;
            background-color: #f8e1ee;
        }
        .lowercase {
            color: #0d6efd;
            background-color: #e6f2ff;
        }
        .number {
            color: #fd7e14;
            background-color: #fff3e6;
        }
        .special {
            color: #20c997;
            background-color: #e6fbf5;
        }
        .active-users-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #198754;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="active-users-badge" style="cursor: pointer;" onclick="showActiveUsers()">
                <i class="fas fa-users"></i> 
                <span><?php echo $active_users_count; ?> Active</span>
            </div>
            
            <div class="login-header">
                <i class="fas fa-tractor"></i>
                <h1>राहुल शेती फार्म & वॉटर सप्लायर्स, कोरडेवाडी</h1>
                
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($show_password_mode): ?>
            <!-- Show Current Password Form -->
            <form method="POST">
                <p class="text-center mb-3">Enter the reset code to reveal the current password:</p>
                
                <?php if (isset($password_revealed) && $password_revealed): ?>
                <div class="password-revealed">
                    <p class="mb-1">Current Password:</p>
                    <?php
                    // Display the password with case sensitivity highlighted
                    $password_chars = str_split($current_password);
                    foreach ($password_chars as $char) {
                        if (ctype_upper($char)) {
                            echo '<span class="character uppercase">' . htmlspecialchars($char) . '</span>';
                        } elseif (ctype_lower($char)) {
                            echo '<span class="character lowercase">' . htmlspecialchars($char) . '</span>';
                        } elseif (ctype_digit($char)) {
                            echo '<span class="character number">' . htmlspecialchars($char) . '</span>';
                        } else {
                            echo '<span class="character special">' . htmlspecialchars($char) . '</span>';
                        }
                    }
                    ?>
                </div>
                <div class="mt-3">
                    <small class="d-block text-center mb-2">Password Legend:</small>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <span class="badge bg-light text-dark border">
                            <span class="character uppercase">A</span> Uppercase
                        </span>
                        <span class="badge bg-light text-dark border">
                            <span class="character lowercase">a</span> Lowercase
                        </span>
                        <span class="badge bg-light text-dark border">
                            <span class="character number">1</span> Number
                        </span>
                        <span class="badge bg-light text-dark border">
                            <span class="character special">!</span> Special
                        </span>
                    </div>
                </div>
                <?php else: ?>
                <div class="mb-3">
                    <label for="reset_code_verify" class="form-label">
                        Reset Code 
                        <button type="button" class="hint-btn" onclick="toggleHint()">Need a hint?</button>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-key"></i>
                        </span>
                        <input type="text" class="form-control" id="reset_code_verify" name="reset_code_verify" required>
                    </div>
                    <div id="resetCodeHint" class="hint-text">Hint: RJ310</div>
                </div>
                <button type="submit" class="btn btn-info w-100">
                    <i class="fas fa-eye me-2"></i>Show Current Password
                </button>
                <?php endif; ?>
                
                <div class="forgot-password">
                    <a href="index.php">Back to Login</a>
                </div>
            </form>
                
            <?php elseif ($reset_mode): ?>
            <!-- Reset Password Form -->
            <form method="POST">
                <p class="text-center mb-3">Enter the reset code and your new password:</p>
                <div class="mb-3">
                    <label for="reset_code" class="form-label">
                        Reset Code 
                        <button type="button" class="hint-btn" onclick="toggleHint()">Need a hint?</button>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-key"></i>
                        </span>
                        <input type="text" class="form-control" id="reset_code" name="reset_code" required>
                    </div>
                    <div id="resetCodeHint" class="hint-text">Hint: RJ310</div>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <span class="input-group-text password-toggle" onclick="togglePasswordVisibility('new_password')">
                            <i class="fas fa-eye" id="new_password_toggle"></i>
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <span class="input-group-text password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password_toggle"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-save me-2"></i>Reset Password
                </button>
                <div class="forgot-password">
                    <a href="index.php">Back to Login</a>
                </div>
            </form>
            <?php else: ?>
            <!-- Login Form -->
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="password" class="form-label">Enter Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <span class="input-group-text password-toggle" onclick="togglePasswordVisibility('password')">
                            <i class="fas fa-eye" id="password_toggle"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
                
                <div class="action-buttons">
                    <a href="index.php?action=reset" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-key me-1"></i>Reset Password
                    </a>
                    <a href="index.php?action=show_password" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-eye me-1"></i>Show Password
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleHint() {
            const hintElement = document.getElementById('resetCodeHint');
            if (hintElement.style.display === 'block') {
                hintElement.style.display = 'none';
            } else {
                hintElement.style.display = 'block';
            }
        }
        
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(inputId + '_toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        function showActiveUsers() {
            <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true): ?>
            window.location.href = 'active_users_list.php';
            <?php else: ?>
            alert('Please login to view active users.');
            <?php endif; ?>
        }
    </script>
</body>
</html> 