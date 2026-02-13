<?php
// Include active users tracking
require_once 'active_users.php';

session_start();

// Get the session ID before destroying the session
$session_id = session_id();

// Remove the user from active users list
removeActiveUser($session_id);

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit();
?> 