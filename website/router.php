<?php 

// Include the authentication toggle
require_once 'auth_toggle.php'; // Your authentication snippet

$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

$routings = [
    '/' => 'controller/listing.php',
    '/listing' => 'controller/listing.php',
    '/404' => '404.php',
];

// Handle dynamic routes with parameters
if (preg_match('/^\/school\/(\d+)$/', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    
    // Route based on user type
    if (isAdmin()) {
        // Admin sees the editable form
        require 'controller/profilingForm.php';
    } else {
        // Regular users see the read-only profile
        require 'controller/profilingFormUser.php';
    }
    
} elseif (array_key_exists($uri, $routings)) {
    require $routings[$uri];
} else {
    require '404.php';
}