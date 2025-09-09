<?php 


$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

// dd($uri);


$routings = [
    '/' => 'controller/listing.php',
    '/listing' => 'controller/profilingForm.php',
    '/profiling/{id}' => 'controller/profilingForm.php', // Add this line
    '/404' => '404.php',
];

// Update routing logic to handle parameters
if (preg_match('/^\/profiling\/(\d+)$/', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    require 'controller/profilingForm.php';
} elseif (array_key_exists($uri, $routings)) {
    require $routings[$uri];
} else {
    require '404.php';
}

