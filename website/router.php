<?php 


$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

// dd($uri);


$routings = [
    '/' => 'controller/landing.php',
    '/listing' => 'controller/listing.php',
    '/404' => '404.php',
];


if (array_key_exists($uri, $routings)) {
    require $routings[$uri];
} else {
    require '404.php';
}

