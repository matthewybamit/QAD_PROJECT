<?php
// functions.php
// Updated to use Google Auth instead of auth_toggle.php

require_once 'models/GoogleAuth.php';

/**
 * Check if user is logged in
 * Replaces your auth_toggle.php function
 */
function isLoggedIn() {
    return GoogleAuth::isLoggedIn();
}

/**
 * Check if current user is admin
 * Replaces your auth_toggle.php function
 */
function isAdmin() {
    return GoogleAuth::isAdmin();
}

/**
 * Get current user data
 */
function getCurrentUser() {
    return GoogleAuth::getCurrentUser();
}

/**
 * Require authentication
 */
function requireAuth($redirectTo = '/login') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

/**
 * Display flash messages
 */
function displayFlashMessage($type) {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        
        $bgColor = $type === 'error' ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200';
        $textColor = $type === 'error' ? 'text-red-800' : 'text-green-800';
        
        echo '<div class="' . $bgColor . ' border rounded-md p-4 mb-4">';
        echo '<p class="text-sm ' . $textColor . '">' . htmlspecialchars($message) . '</p>';
        echo '</div>';
    }
}

/**
 * Get user avatar HTML
 */
function getUserAvatar($user = null, $size = 8) {
    if (!$user) $user = getCurrentUser();
    
    if ($user && $user['avatar']) {
        return '<img class="h-' . $size . ' w-' . $size . ' rounded-full" src="' . htmlspecialchars($user['avatar']) . '" alt="' . htmlspecialchars($user['name']) . '">';
    } else {
        $name = $user ? $user['name'] : 'User';
        return '<img class="h-' . $size . ' w-' . $size . ' rounded-full" src="https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=3b82f6&color=fff" alt="' . htmlspecialchars($name) . '">';
    }
}
?>