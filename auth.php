<?php
// Set session cookie parameters before session_start()
ini_set('session.cookie_lifetime', '0');
ini_set('session.use_cookies', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

// Include active users tracking
require_once 'active_users.php';

session_start();

// Check if this session has been terminated by an admin
$current_session_id = session_id();
$termination_file = "terminated_sessions/{$current_session_id}";

if (file_exists($termination_file)) {
    // This session has been forcibly terminated
    
    // Remove from active users
    removeActiveUser($current_session_id);
    
    // Destroy the session
    session_unset();
    session_destroy();
    
    // Delete the termination file
    unlink($termination_file);
    
    // Redirect to login page with message
    header('Location: index.php?terminated=1');
    exit();
}

// Check if user is not authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php');
    exit();
}

// Check if a password reset has occurred since this session was created
if (file_exists('session_reset.txt')) {
    $reset_time = file_get_contents('session_reset.txt');
    
    // If the password was reset after this session was created, or if this session doesn't have reset time
    if (!isset($_SESSION['password_reset_time']) || $reset_time > $_SESSION['password_reset_time']) {
        // Remove from active users
        removeActiveUser(session_id());
        
        // Destroy the session and redirect to login
        session_unset();
        session_destroy();
        header('Location: index.php?reset=1');
        exit();
    }
}

// Check for session timeout (2 hours)
$timeout = 7200; // 2 hours in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Remove from active users
    removeActiveUser(session_id());
    
    // Session has expired
    session_unset();     // unset $_SESSION variable for this page
    session_destroy();   // destroy session data
    header('Location: index.php?timeout=1');
    exit();
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

// Update user in active users list with new timestamp
addActiveUser(session_id(), time());
?> 