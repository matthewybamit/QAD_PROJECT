<?php
// router.php - Updated: landing page instead of redirecting to login
$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

// Define public routes
$publicRoutes = ['/login', '/auth/callback', '/logout'];

// Check authentication for protected routes (non-admin)
if (!in_array($uri, $publicRoutes) && strpos($uri, '/admin/') !== 0) {
    require_once 'models/GoogleAuth.php';
    
    if (!GoogleAuth::isLoggedIn()) {
        // Instead of redirecting, show landing page
        require 'controller/landing.php';
        exit;
    }
}

// Define routes
$routings = [
    '/login' => 'controller/auth/login.php',
    '/auth/callback' => 'controller/auth/callback.php', 
    '/logout' => 'controller/auth/logout.php',
    '/' => 'controller/landing.php',
    '/landing' => 'controller/landing.php',
    '/listing' => 'controller/listing.php',
    '/profile' => 'controller/profile.php',
    
    // Admin routes
    '/admin/permissions' => 'controller/admin/permissions.php',
    '/admin/security' => 'controller/admin/security.php',
    '/admin/logs' => 'controller/admin/logs.php',
    
    '/404' => '404.php'
];

// Handle school routes with permission checking
if (preg_match('/^\/school\/(\d+)$/', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    $schoolId = $matches[1];
    
    require_once 'models/GoogleAuth.php';
    require_once 'models/SchoolEditPermissions.php';
    
    $currentUser = GoogleAuth::isLoggedIn() ? GoogleAuth::getCurrentUser() : null;
    $editPermissions = new SchoolEditPermissions($db->connection);
    
    $canEdit = false;
    if ($currentUser) {
        if ($currentUser['role'] === 'admin') {
            require_once 'models/AdminSecurity.php';
            $adminSecurity = new AdminSecurity($db->connection);
            if ($adminSecurity->verifyAdminAccess($currentUser['id'])) {
                $canEdit = true;
                $adminSecurity->logAdminActivity($currentUser['id'], 'school_edit_access', "Accessed school ID: $schoolId");
            }
        } else {
            $canEdit = $editPermissions->canUserEditSchool($currentUser['id'], $schoolId);
        }
    }

    if ($canEdit) {
        require 'controller/profilingEdit.php';
    } else {
        require 'controller/profilingFormUser.php';
    }
    exit;
}

// Handle permission request (POST)
if ($uri === '/request-permission' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'models/GoogleAuth.php';
    require_once 'models/SchoolEditPermissions.php';
    
    if (!GoogleAuth::isLoggedIn()) {
        // Stay on landing instead of redirect
        require 'controller/landing.php';
        exit;
    }
    
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid request.';
        require 'controller/landing.php';
        exit;
    }
    
    $currentUser = GoogleAuth::getCurrentUser();
    $editPermissions = new SchoolEditPermissions($db->connection);
    $schoolId = filter_var($_POST['school_id'], FILTER_VALIDATE_INT);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($schoolId && strlen($reason) >= 10) {
        $result = $editPermissions->requestEditPermission($currentUser['id'], $schoolId, $reason);
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
    } else {
        $_SESSION['flash_message'] = 'Please provide a detailed reason (minimum 10 characters).';
        $_SESSION['flash_type'] = 'error';
    }
    
    require 'controller/landing.php';
    exit;
}

// Handle admin routes (with security checks)
if (strpos($uri, '/admin/') === 0) {
    require_once 'models/GoogleAuth.php';
    require_once 'models/AdminSecurity.php';
    
    if (!GoogleAuth::isAdmin()) {
        $_SESSION['error'] = 'Access denied.';
        require 'controller/landing.php';
        exit;
    }
    
    $currentUser = GoogleAuth::getCurrentUser();
    $adminSecurity = new AdminSecurity($db->connection);
    
    if (!$adminSecurity->verifyAdminAccess($currentUser['id'])) {
        session_destroy();
        require 'controller/landing.php';
        exit;
    }
    
    // Log admin access
    $adminSecurity->logAdminActivity($currentUser['id'], 'page_access', "Accessed: $uri");
}

// Route to requested file
if (array_key_exists($uri, $routings)) {
    require $routings[$uri];
} else {
    http_response_code(404);
    echo "Page not found";
}
?>
