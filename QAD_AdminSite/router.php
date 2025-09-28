<?php
//router.php
// Load required files
$requiredFiles = [
    'config/admin_db.php',
    'config/security.php',
    'models/AdminAuth.php',
    'models/SecurityLog.php',
    '../website/models/SchoolEditPermissions.php',
    'middleware/SecurityHeadersMiddleware.php',
    'middleware/AdminAuthMiddleware.php',
    'middleware/CSRFMiddleware.php',
    'middleware/RateLimitMiddleware.php',
    'middleware/IPWhitelistMiddleware.php',
    'utils/SessionHelper.php',
    'utils/ValidationHelper.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        die("Required file not found: $file");
    }
    require_once $file;
}

// Apply security headers first
$securityHeadersMiddleware = new SecurityHeadersMiddleware();
$securityHeadersMiddleware->handle($_REQUEST, function($request) {
    return $request;
});

// Initialize database connection using your admin_db.php system
try {
    // The admin_db.php file sets up $db as a PDO connection directly
    if (!isset($db) || !($db instanceof PDO)) {
        throw new Exception('Database connection not available or invalid type');
    }
    
    // Test database connection
    $db->query("SELECT 1");
    
    // Initialize auth and logging with the PDO connection
    $adminAuth = new AdminAuth($db);  // $db is already the PDO connection
    $securityLog = new SecurityLog($db);
    
} catch (Exception $e) {
    error_log("Admin panel database error: " . $e->getMessage());
    if (env('APP_DEBUG', false)) {
        die("Database initialization failed: " . $e->getMessage());
    } else {
        die("Service temporarily unavailable. Please try again later.");
    }
}

// Parse request path
$path = $_SERVER['REQUEST_URI'];
$path = parse_url($path, PHP_URL_PATH);
$path = str_replace('/admin', '', $path);
$path = rtrim($path, '/') ?: '/';

// Define public routes (no authentication required)
$publicRoutes = ['/login', '/health', '/maintenance'];

// Apply middleware for protected routes
if (!in_array($path, $publicRoutes)) {
    try {
        // Rate limiting
        $rateLimitMiddleware = new RateLimitMiddleware();
        $rateLimitMiddleware->handle($_REQUEST, function($request) use ($adminAuth, $securityLog) {
            
            // IP whitelist check
            $ipWhitelistMiddleware = new IPWhitelistMiddleware();
            return $ipWhitelistMiddleware->handle($request, function($request) use ($adminAuth) {
                
                // Admin authentication
                $adminAuthMiddleware = new AdminAuthMiddleware($adminAuth);
                return $adminAuthMiddleware->handle($request, function($request) {
                    
                    // CSRF protection for POST requests
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $csrfMiddleware = new CSRFMiddleware();
                        return $csrfMiddleware->handle($request, function($request) {
                            return $request;
                        });
                    }
                    
                    return $request;
                });
            });
        });
    } catch (Exception $e) {
        error_log("Middleware error: " . $e->getMessage());
        // Middleware already handled the response, so we exit here
        exit;
    }
}

// Route handling
try {
    switch ($path) {
        case '/':
        case '/dashboard':
            handleDashboard($db, $adminAuth, $securityLog);
            break;
            
        case '/login':
            handleLogin($db, $adminAuth);
            break;
            
        case '/logout':
            handleLogout($db, $adminAuth);
            break;
            
        case '/permissions':
            handlePermissions($db, $adminAuth);
            break;
            
        case '/security':
            handleSecurity($db, $adminAuth, $securityLog);
            break;
            
        case '/users':
            handleUsers($db, $adminAuth);
            break;
            
        case '/schools':
            handleSchools($db, $adminAuth);
            break;
            
        case '/health':
            handleHealthCheck($db);
            break;
            
        case '/api/extend-session':
            handleExtendSession($adminAuth);
            break;
            
        case '/api/security-status':
            handleSecurityStatus($db, $adminAuth);
            break;
            
        default:
            handle404();
            break;
    }
} catch (Exception $e) {
    error_log("Route handling error: " . $e->getMessage());
    if (env('APP_DEBUG', false)) {
        die("Error: " . $e->getMessage());
    } else {
        http_response_code(500);
        echo "Internal server error";
    }
}

// Route handler functions

function handleDashboard($db, $adminAuth, $securityLog) {
    $currentUser = $adminAuth->getCurrentUser();
    $stats = getDashboardStats($db);
    $recentActivity = $securityLog->getRecentLogs(10);
    $pendingPermissions = getPendingPermissions($db);
    
    $pageTitle = 'Dashboard';
    $currentPage = 'dashboard';
    
    require_once 'controller/dashboard.php';
}

function handleLogin($db, $adminAuth) {
    // Redirect if already logged in
    if ($adminAuth->validateSession()) {
        $redirectUrl = $_SESSION['intended_url'] ?? '/dashboard';
        unset($_SESSION['intended_url']);
        header("Location: $redirectUrl");
        exit;
    }
    
    $error = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $email = ValidationHelper::validateEmail($_POST['email'] ?? '', 'Email');
            $password = ValidationHelper::validateRequired($_POST['password'] ?? '', 'Password');
            
            // Attempt authentication
            $user = $adminAuth->authenticateAdmin($email, $password);
            
            if ($user) {
                $redirectUrl = $_SESSION['intended_url'] ?? '/dashboard';
                unset($_SESSION['intended_url']);
                header("Location: $redirectUrl");
                exit;
            } else {
                $error = 'Invalid credentials or account locked';
            }
            
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
    
    $csrfToken = AdminSecurity::generateCSRFToken();
    $pageTitle = 'Admin Login';
    
    require_once 'views/login.view.php';
}

function handleLogout($db, $adminAuth) {
    require_once 'controller/Logout.php';
    $adminLogout = new AdminLogout($db, $adminAuth);
    $adminLogout->handle();
    exit;
}
function handlePermissions($db, $adminAuth) {
    require_once 'controller/Permission.php';
    $permissionManager = new PermissionManager($db, $adminAuth);
    $permissionManager->index();
}

function handleSecurity($db, $adminAuth, $securityLog) {
    require_once 'controller/SecurityManager.php';
    $securityManager = new SecurityManager($db, $adminAuth);
    $securityManager->index();
}
function handleNotificationsAPI($db, $adminAuth) {
    require_once 'controllers/NotificationsAPI.php';
    // The controller will handle the API request
    exit;
}

function handleUsers($db, $adminAuth) {
    // Users management - to be implemented
    $currentUser = $adminAuth->getCurrentUser();
    $pageTitle = 'User Management';
    $currentPage = 'users';
    
    echo "User management feature - Coming soon";
}

function handleSchools($db, $adminAuth) {
    // Schools management - to be implemented
    $currentUser = $adminAuth->getCurrentUser();
    $pageTitle = 'School Management';
    $currentPage = 'schools';
    
    echo "School management feature - Coming soon";
}

function handleHealthCheck($db) {
    header('Content-Type: application/json');
    
    try {
        // Test database connection - $db is already the PDO connection
        $db->query("SELECT 1");
        $dbStatus = 'connected';
    } catch (Exception $e) {
        $dbStatus = 'error';
    }
    
    $health = [
        'status' => $dbStatus === 'connected' ? 'ok' : 'error',
        'timestamp' => date('c'),
        'database' => $dbStatus,
        'version' => '1.0.0',
        'environment' => env('APP_ENV', 'unknown')
    ];
    
    echo json_encode($health, JSON_PRETTY_PRINT);
    exit;
}

function handleExtendSession($adminAuth) {
    header('Content-Type: application/json');
    
    if (!$adminAuth->validateSession()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    if ($adminAuth->extendSession()) {
        echo json_encode(['success' => true, 'message' => 'Session extended']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to extend session']);
    }
    exit;
}

function handleSecurityStatus($db, $adminAuth) {
    header('Content-Type: application/json');
    
    if (!$adminAuth->validateSession()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    try {
        // Get recent security alerts - $db is the PDO connection
        $stmt = $db->prepare("
            SELECT 
                action,
                COUNT(*) as count,
                ip_address,
                MAX(created_at) as last_occurrence
            FROM security_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND action IN ('FAILED_LOGIN', 'IP_BLOCKED', 'CSRF_VIOLATION', 'RATE_LIMIT_EXCEEDED')
            GROUP BY action, ip_address
            HAVING count > 3
            ORDER BY count DESC, last_occurrence DESC
            LIMIT 10
        ");
        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'ok',
            'alerts' => $alerts,
            'timestamp' => date('c')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
}

function handle404() {
    http_response_code(404);
    
    if (env('APP_DEBUG', false)) {
        echo "404 - Route not found: " . $_SERVER['REQUEST_URI'];
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Page Not Found</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-100 min-h-screen flex items-center justify-center">
            <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-md text-center">
                <h1 class="text-4xl font-bold text-gray-900">404</h1>
                <p class="text-gray-600">Page not found</p>
                <a href="/dashboard" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Go to Dashboard
                </a>
            </div>
        </body>
        </html>
        <?php
    }
}

// Helper functions

function getDashboardStats($db) {
    try {
        $stats = [];
        
        // Total users - $db is the PDO connection directly
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total schools
        $stmt = $db->query("SELECT COUNT(*) as count FROM schools");
        $stats['total_schools'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Pending permissions
        $stmt = $db->query("SELECT COUNT(*) as count FROM school_edit_permissions WHERE status = 'pending'");
        $stats['pending_permissions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Active admin sessions
        $stmt = $db->query("SELECT COUNT(*) as count FROM admin_sessions WHERE expires_at > NOW()");
        $stats['active_sessions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Security incidents today
        $stmt = $db->query("
            SELECT COUNT(*) as count FROM security_logs 
            WHERE DATE(created_at) = CURDATE() 
            AND action IN ('FAILED_LOGIN', 'IP_BLOCKED', 'CSRF_VIOLATION', 'RATE_LIMIT_EXCEEDED')
        ");
        $stats['security_incidents'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("getDashboardStats error: " . $e->getMessage());
        return [
            'total_users' => 0,
            'total_schools' => 0,
            'pending_permissions' => 0,
            'active_sessions' => 0,
            'security_incidents' => 0
        ];
    }
}

function getPendingPermissions($db) {
    try {
        // $db is the PDO connection directly
        $stmt = $db->prepare("
            SELECT sep.*, u.name as user_name, u.email, s.school_name
            FROM school_edit_permissions sep
            JOIN users u ON sep.user_id = u.id
            JOIN schools s ON sep.school_id = s.id
            WHERE sep.status = 'pending'
            ORDER BY sep.requested_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("getPendingPermissions error: " . $e->getMessage());
        return [];
    }
}

?>