<?php
// controller/auth/callback.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../models/GoogleAuth.php';
require_once __DIR__ . '/../../config/db.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log('Starting callback processing');

$googleAuth = new GoogleAuth($db->connection);

// Validate required parameters
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    error_log('Missing code or state parameters');
    $_SESSION['error'] = 'Invalid callback request';
    header('Location: /login');
    exit;
}

error_log('Code: ' . $_GET['code']);
error_log('State received: ' . $_GET['state']);
error_log('State in session: ' . ($_SESSION['oauth_state'] ?? 'not set'));

$user = $googleAuth->handleCallback($_GET['code'], $_GET['state']);

if ($user) {
    $googleAuth->loginUser($user);
    header('Location: /');
    exit;
} else {
    $_SESSION['error'] = 'Authentication failed. Please try again.';
    header('Location: /login');
    exit;
}