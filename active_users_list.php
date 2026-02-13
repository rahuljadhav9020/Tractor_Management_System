<?php
// Include authentication check
require_once 'auth.php';
require_once 'active_users.php';

// Function to terminate a user session by creating a special termination file
function terminateUserSession($sessionId) {
    // Create a directory for terminated sessions if it doesn't exist
    if (!file_exists('terminated_sessions')) {
        mkdir('terminated_sessions', 0755, true);
    }
    
    // Write the session ID to a file - auth.php will check for this file
    file_put_contents("terminated_sessions/{$sessionId}", time());
    return true;
}

// Handle user removal with reset code
$remove_error = null;
$remove_success = null;

if (isset($_POST['action']) && $_POST['action'] === 'remove_user' && isset($_POST['user_id']) && isset($_POST['reset_code'])) {
    $correct_reset_code = "RAHULJADHAV310";
    
    if ($_POST['reset_code'] === $correct_reset_code) {
        // Load all active users
        $activeUsers = getActiveUsers();
        $removed = false;
        $terminatedSessionId = '';
        
        // Find the session that starts with the short ID
        $shortId = $_POST['user_id'];
        foreach ($activeUsers as $sessionId => $timestamp) {
            if (strpos($sessionId, substr($shortId, 0, 6)) === 0) {
                // Save the full session ID for termination
                $terminatedSessionId = $sessionId;
                
                // Remove from active users
                unset($activeUsers[$sessionId]);
                $removed = true;
                break;
            }
        }
        
        if ($removed) {
            // Save the updated active users list
            saveActiveUsers($activeUsers);
            
            // Also terminate their actual session
            if (!empty($terminatedSessionId)) {
                terminateUserSession($terminatedSessionId);
                $remove_success = "User session terminated successfully. User has been logged out.";
            } else {
                $remove_success = "User session removed successfully.";
            }
        } else {
            $remove_error = "User session not found.";
        }
    } else {
        $remove_error = "Invalid reset code. Please try again.";
    }
}

// Get active user details
$userList = getActiveUserDetails();
$userCount = count($userList);

// Include the active_users_count variable for the navbar
$active_users_count = $userCount;

// Modal for reset code entry
$show_modal = isset($_GET['remove']) && !empty($_GET['id']);
$user_id_to_remove = $show_modal ? $_GET['id'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Users - Tractor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .user-item {
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }
        .user-item:hover {
            background-color: #f8f9fa;
            border-left-color: #2575fc;
        }
        .time-badge {
            font-size: 0.8rem;
            background-color: #e9ecef;
            color: #6c757d;
            border-radius: 12px;
            padding: 2px 8px;
        }
        .user-id {
            font-family: monospace;
            background-color: #f1f3f5;
            padding: 2px 6px;
            border-radius: 4px;
            color: #495057;
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
        }
    </style>
</head>
<body>
    <!-- Include the navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <?php if ($remove_error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $remove_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($remove_success): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $remove_success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
    
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i> Active Users (<?php echo $userCount; ?>)
                        </h5>
                        <span id="clock" class="text-light"></span>
                    </div>
                    <div class="card-body">
                        <?php if ($userCount > 0): ?>
                            <ul class="list-group">
                                <?php foreach ($userList as $index => $user): ?>
                                    <li class="list-group-item user-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-user me-2 text-primary"></i>
                                            <span class="user-id"><?php echo $user['id']; ?></span>
                                            <span class="ms-2 text-secondary">Session <?php echo $index + 1; ?></span>
                                        </div>
                                        <div>
                                            <span class="time-badge me-2">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo $user['formatted_time']; ?>
                                            </span>
                                            <a href="?remove=1&id=<?php echo urlencode($user['id']); ?>" class="btn btn-sm btn-danger">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No active users found.
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <div class="alert alert-light border">
                                <small>
                                    <i class="fas fa-info-circle me-1 text-primary"></i>
                                    This list shows all current active sessions. Users who have been inactive for more than 2 hours are automatically removed. 
                                    You can manually remove a session by clicking the remove button (requires reset code).
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reset Code Modal -->
    <div class="modal fade" id="resetCodeModal" tabindex="-1" aria-labelledby="resetCodeModalLabel" aria-hidden="true" <?php if ($show_modal) echo 'data-bs-backdrop="static"'; ?>>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="resetCodeModalLabel">
                        <i class="fas fa-shield-alt me-2"></i> Admin Verification Required
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p>Please enter the reset code to remove this user session:</p>
                        <input type="hidden" name="action" value="remove_user">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id_to_remove); ?>">
                        
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
                            <div id="resetCodeHint" class="hint-text mt-2">Hint: RJ310</div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Warning: Removing a user session will immediately log them out. This action cannot be undone.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-user-times me-2"></i> Remove User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update clock
        function updateClock() {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        
        // Update every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial update
        
        // Auto-refresh page every 60 seconds
        setTimeout(function() {
            window.location.reload();
        }, 60000);
        
        // Toggle hint visibility
        function toggleHint() {
            const hintElement = document.getElementById('resetCodeHint');
            if (hintElement.style.display === 'block') {
                hintElement.style.display = 'none';
            } else {
                hintElement.style.display = 'block';
            }
        }
        
        // Show modal automatically if remove parameter is present
        <?php if ($show_modal): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var resetModal = new bootstrap.Modal(document.getElementById('resetCodeModal'));
            resetModal.show();
        });
        <?php endif; ?>
    </script>
</body>
</html> 