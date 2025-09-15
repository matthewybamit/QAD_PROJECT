<?php
session_start();

require_once 'functions.php';

// Debug: Show current URI and session
$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

// Include database connection
require_once 'config/db.php';

// Include the router
require 'router.php';