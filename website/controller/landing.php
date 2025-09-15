<?php
// controller/landing.php
// Fixed to prevent redirect loops

// The router already checked authentication, so we're safe here
// Just get the user data and show the landing page

$currentUser = null;
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $currentUser = [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
        'avatar' => $_SESSION['user_avatar'] ?? null
    ];
}

// Include the landing view
require_once 'views/landing.view.php';
