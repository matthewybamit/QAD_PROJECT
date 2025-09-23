<?php
// index.php - Fixed version for direct PDO connection

// Start secure session
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'sid_length' => 48,
    'sid_bits_per_character' => 6
]);


// Error reporting based on environment
if (file_exists('config/env.php')) {
    require_once 'config/env.php';
    if (env('APP_DEBUG', false)) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', 0);
    }
} else {
    die('Environment configuration not found. Please ensure .env file is properly configured.');
}
require_once 'router.php';