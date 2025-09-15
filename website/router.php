<?php
// router.php
// Fixed to prevent redirect loops

$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

// Define public routes first
$publicRoutes = [
    '/login',
    '/auth/callback',
    '/logout'
];

// Check if current route requires authentication
if (!in_array($uri, $publicRoutes)) {
    require_once 'models/GoogleAuth.php';
    
    if (!GoogleAuth::isLoggedIn()) {
        if ($uri !== '/login') {
            header('Location: /login');
            exit;
        }
    } elseif ($uri === '/login') {
        // If user is logged in and tries to access login page
        header('Location: /');
        exit;
    }
}

// Define routes FIRST
$routings = [
    // Auth routes (must be accessible without login)
    '/login' => 'controller/auth/login.php',
    '/auth/callback' => 'controller/auth/callback.php',
    '/logout' => 'controller/auth/logout.php',
    
    // Protected routes
    '/' => 'controller/landing.php',
    '/landing' => 'controller/landing.php',
    '/listing' => 'controller/listing.php',
    '/404' => '404.php',
    
    // Additional routes
    '/profile' => 'views/profile.view.php',
];

// Handle dynamic school routes first (before authentication check)
if (preg_match('/^\/school\/(\d+)$/', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    
    // Include Google Auth only when needed
    require_once 'models/GoogleAuth.php';
    
    // Check authentication for school routes
    if (!GoogleAuth::isLoggedIn()) {
        header('Location: /login');
        exit;
    }
    
    // Route based on user role
    if (GoogleAuth::isAdmin()) {
        require 'controller/profilingForm.php';
    } else {
        require 'controller/profilingFormUser.php';
    }
    exit; // Important: stop execution here
}

// Handle regular routes
if (array_key_exists($uri, $routings)) {
    require $routings[$uri];
} else {
    // 404 for unknown routes
    http_response_code(404);
    echo "Page not found";
}
?>