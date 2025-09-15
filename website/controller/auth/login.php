<?php
// controller/auth/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any existing OAuth state
if (isset($_SESSION['oauth_state'])) {
    unset($_SESSION['oauth_state']);
}

// Include database connection and GoogleAuth
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/GoogleAuth.php';

// Initialize GoogleAuth with database connection
$googleAuth = new GoogleAuth($db->connection);
$googleAuthUrl = $googleAuth->getAuthUrl();

// Include login view
require_once __DIR__ . '/../../views/auth/login.view.php';
