<?php
// Simple Authentication Toggle
// Add this at the top of your files that need authentication checking

// ========================================
// TOGGLE THIS LINE TO SWITCH USER TYPES
// ========================================
$isAdmin = true;  // Change to false for user mode
// ========================================

// Optional: You can also check for a URL parameter for quick testing
if (isset($_GET['admin'])) {
    $isAdmin = $_GET['admin'] === '1' || $_GET['admin'] === 'true';
}

// Function to check if current user is admin
function isAdmin() {
    global $isAdmin;
    return $isAdmin;
}

// Function to get user type string
function getUserType() {
    return isAdmin() ? 'Admin' : 'User';
}

// Function to redirect non-admins from admin pages
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /');
        exit();
    }
}

// Function to show different content based on user type
function showIfAdmin($adminContent, $userContent = '') {
    return isAdmin() ? $adminContent : $userContent;
}
?>