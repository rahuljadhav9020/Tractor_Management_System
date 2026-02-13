<?php
// This is a helper file to manually reset the password if needed.
// IMPORTANT: Delete this file after use for security reasons.

// Only allow access from localhost for security
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die("Access denied. This tool can only be used from the local machine.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = htmlspecialchars($_POST['new_password']);
    $config_content = "<?php\n";
    $config_content .= "// Password configuration\n";
    $config_content .= "define('SYSTEM_PASSWORD', '{$new_password}');\n";
    $config_content .= "?>";
    
    if (file_put_contents('password_config.php', $config_content)) {
        // Create or update the session reset file with current timestamp
        // This will force all active sessions to log out
        file_put_contents('session_reset.txt', time());
        
        $message = "Password has been reset successfully to: " . $new_password . ". All users have been logged out.";
    } else {
        $message = "Failed to reset password. Check file permissions.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Helper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">Password Reset Helper</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Warning:</strong> This tool allows direct password reset without verification. 
                            Delete this file after use for security.
                        </div>
                        
                        <div class="alert alert-info mb-3">
                            <p><strong>Admin Information:</strong></p>
                            <ul class="mb-0">
                                <li>User Reset Code: <strong>RAHULJADHAV310</strong> (hint: RJ310)</li>
                                <li>Default Password: <strong>Rahul310</strong></li>
                            </ul>
                        </div>
                        
                        <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="text" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <button type="submit" class="btn btn-danger">Reset Password</button>
                        </form>
                        
                        <hr>
                        <a href="index.php" class="btn btn-secondary">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 