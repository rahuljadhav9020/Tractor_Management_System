<?php
// File to track active users
// This is a simple implementation using a text file

/**
 * Add a user to the active users list
 * @param string $sessionId The session ID to track
 * @param string $timestamp The login timestamp
 * @return int The number of active users
 */
function addActiveUser($sessionId, $timestamp) {
    $activeUsers = getActiveUsers();
    
    // Add or update the user
    $activeUsers[$sessionId] = $timestamp;
    
    // Save the updated list
    saveActiveUsers($activeUsers);
    
    // Return count of active users
    return count($activeUsers);
}

/**
 * Remove a user from the active users list
 * @param string $sessionId The session ID to remove
 * @return int The number of active users after removal
 */
function removeActiveUser($sessionId) {
    $activeUsers = getActiveUsers();
    
    // Remove the user if they exist
    if (isset($activeUsers[$sessionId])) {
        unset($activeUsers[$sessionId]);
    }
    
    // Save the updated list
    saveActiveUsers($activeUsers);
    
    // Return count of active users
    return count($activeUsers);
}

/**
 * Get the number of currently active users
 * @return int The number of active users
 */
function countActiveUsers() {
    $activeUsers = getActiveUsers();
    
    // Clean up expired sessions (older than 2 hours)
    $timeout = 7200; // 2 hours in seconds
    $now = time();
    
    foreach ($activeUsers as $sessionId => $timestamp) {
        if ($now - $timestamp > $timeout) {
            unset($activeUsers[$sessionId]);
        }
    }
    
    // Save the cleaned list
    saveActiveUsers($activeUsers);
    
    // Return count of active users
    return count($activeUsers);
}

/**
 * Get active user details with formatted timestamps
 * @return array Array of active users with formatted timestamps
 */
function getActiveUserDetails() {
    $activeUsers = getActiveUsers();
    $timeout = 7200; // 2 hours in seconds
    $now = time();
    $userDetails = [];
    
    foreach ($activeUsers as $sessionId => $timestamp) {
        if ($now - $timestamp > $timeout) {
            unset($activeUsers[$sessionId]);
            continue;
        }
        
        // Format time as "X minutes ago" or "just now"
        $timeAgo = $now - $timestamp;
        
        if ($timeAgo < 60) {
            $formattedTime = "just now";
        } elseif ($timeAgo < 3600) {
            $minutes = floor($timeAgo / 60);
            $formattedTime = $minutes . " minute" . ($minutes != 1 ? "s" : "") . " ago";
        } else {
            $hours = floor($timeAgo / 3600);
            $minutes = floor(($timeAgo % 3600) / 60);
            $formattedTime = $hours . " hour" . ($hours != 1 ? "s" : "");
            if ($minutes > 0) {
                $formattedTime .= " " . $minutes . " minute" . ($minutes != 1 ? "s" : "");
            }
            $formattedTime .= " ago";
        }
        
        // Create a short ID for display (first 6 chars of session ID)
        $shortId = substr($sessionId, 0, 6) . '...';
        
        $userDetails[] = [
            'id' => $shortId,
            'timestamp' => $timestamp,
            'formatted_time' => $formattedTime
        ];
    }
    
    // Sort by most recent first
    usort($userDetails, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    // Save the cleaned list
    saveActiveUsers($activeUsers);
    
    return $userDetails;
}

/**
 * Load the active users array from file
 * @return array The active users array
 */
function getActiveUsers() {
    $activeUsersFile = 'active_users.dat';
    
    if (file_exists($activeUsersFile)) {
        $data = file_get_contents($activeUsersFile);
        if (!empty($data)) {
            return unserialize($data);
        }
    }
    
    return array();
}

/**
 * Save the active users array to file
 * @param array $activeUsers The array of active users
 */
function saveActiveUsers($activeUsers) {
    $activeUsersFile = 'active_users.dat';
    file_put_contents($activeUsersFile, serialize($activeUsers));
}
?> 