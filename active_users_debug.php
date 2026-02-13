<?php
// This is a debug file to help diagnose active users issues
session_start();

// Include active users tracking
require_once 'active_users.php';

// Get user details
$isLoggedIn = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$sessionId = session_id();
$userList = getActiveUserDetails();
$userCount = count($userList);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Users Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3>Active Users Debug Information</h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h5>Session Information</h5>
                    <ul class="list-group">
                        <li class="list-group-item">Session ID: <?php echo $sessionId; ?></li>
                        <li class="list-group-item">Logged In: <?php echo $isLoggedIn ? 'Yes' : 'No'; ?></li>
                        <li class="list-group-item">Last Activity: <?php echo isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : 'Not set'; ?></li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h5>Active Users Count: <?php echo $userCount; ?></h5>
                    <form method="POST">
                        <button type="submit" name="add_test_user" class="btn btn-success mb-3">Add Test User</button>
                        <button type="submit" name="clean_users" class="btn btn-warning mb-3">Clean Expired Users</button>
                        <button type="submit" name="reset_users" class="btn btn-danger mb-3">Reset All Users</button>
                    </form>
                    
                    <?php 
                    // Handle form actions
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        if (isset($_POST['add_test_user'])) {
                            addActiveUser('test_user_'.time(), time());
                            echo '<div class="alert alert-success">Added test user</div>';
                            $userList = getActiveUserDetails(); // Refresh
                            $userCount = count($userList);
                        } elseif (isset($_POST['clean_users'])) {
                            countActiveUsers(); // This cleans expired users
                            echo '<div class="alert alert-warning">Cleaned expired users</div>';
                            $userList = getActiveUserDetails(); // Refresh
                            $userCount = count($userList);
                        } elseif (isset($_POST['reset_users'])) {
                            file_put_contents('active_users.dat', serialize(array()));
                            echo '<div class="alert alert-danger">Reset all users</div>';
                            $userList = getActiveUserDetails(); // Refresh
                            $userCount = count($userList);
                        }
                    }
                    ?>
                </div>

                <div>
                    <h5>Active Users (<?php echo $userCount; ?>)</h5>
                    <?php if ($userCount > 0): ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Session ID</th>
                                    <th>Last Activity</th>
                                    <th>Time Ago</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userList as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', $user['timestamp']); ?></td>
                                    <td><?php echo $user['formatted_time']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">No active users found</div>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <h5>Session Variables</h5>
                    <pre class="bg-light p-3 rounded"><?php print_r($_SESSION); ?></pre>
                </div>

                <div class="mt-3">
                    <a href="index.php" class="btn btn-primary">Back to Login</a>
                    <a href="home.php" class="btn btn-secondary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 