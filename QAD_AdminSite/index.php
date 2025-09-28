<?php
// index.php - Fixed version for direct PDO connection

// Enhanced secure session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration before starting
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.sid_length', 48);
    ini_set('session.sid_bits_per_character', 6);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    
    // Custom session name (don't use default PHPSESSID)
    session_name('ADMIN_SESSION_' . substr(hash('sha256', $_SERVER['HTTP_HOST'] ?? 'default'), 0, 8));
    
    session_start();
    
    // Additional session security checks
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['created'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['remote_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    // Session timeout check
    if (isset($_SESSION['created']) && (time() - $_SESSION['created'] > 1800)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['initiated'] = true;
        $_SESSION['created'] = time();
    }
    
    // Basic session hijacking protection
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        session_unset();
        session_destroy();
        error_log("Potential session hijacking attempt detected");
    }
    
    // Regenerate session ID periodically (every 15 minutes)
    if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 900) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Content Security Policy headers (more restrictive for production)
if (!headers_sent()) {
    $isProduction = (function_exists('env') && env('APP_ENV') === 'production');
    
    if ($isProduction) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self';");
    } else {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'; font-src 'self' https://cdnjs.cloudflare.com; object-src 'none'; base-uri 'self'; form-action 'self';");
    }
}

// Error reporting based on environment
if (file_exists('config/env.php')) {
    require_once 'config/env.php';
    if (env('APP_DEBUG', false)) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . '/logs/php_errors.log');
    }
} else {
    die('Environment configuration not found. Please ensure .env file is properly configured.');
}

// Additional security headers
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    // Remove server information
    header_remove('X-Powered-By');
    header_remove('Server');
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

require_once 'router.php';