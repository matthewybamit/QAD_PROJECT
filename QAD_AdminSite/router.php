<?php
// router.php - Clean version with no duplicates

// Load required files
$requiredFiles = [
    'config/admin_db.php',
    'config/security.php',
    'models/AdminAuth.php',
    'models/SecurityLog.php',
    '../website/models/SchoolEditPermissions.php',
    'middleware/SecurityHeadersMiddleware.php',
    'middleware/IPWhitelistMiddleware.php',
    'middleware/RateLimitMiddleware.php',
    'middleware/CSRFMiddleware.php',
    'middleware/AdminAuthMiddleware.php',
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

// Initialize database connection
try {
    if (!isset($db) || !($db instanceof PDO)) {
        throw new Exception('Database connection not available or invalid type');
    }
    
    $db->query("SELECT 1");
    
    $adminAuth = new AdminAuth($db);
    $securityLog = new SecurityLog($db);
    
} catch (Exception $e) {
    error_log("Admin panel database error: " . $e->getMessage());
    die(env('APP_DEBUG', false) ? "Database initialization failed: " . $e->getMessage() : "Service temporarily unavailable");
}

// Parse request path
$path = $_SERVER['REQUEST_URI'];
$path = parse_url($path, PHP_URL_PATH);
$path = str_replace('/admin', '', $path);
$path = rtrim($path, '/') ?: '/';

// Define public routes (no authentication required)
$publicRoutes = ['/login', '/health'];

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
        exit;
    }
}

// Route handling
try {
    switch ($path) {
        case '/':
        case '/dashboard':
            $currentUser = $adminAuth->getCurrentUser();
            $stats = getDashboardStats($db);
            $recentActivity = $securityLog->getRecentLogs(10);
            $pendingPermissions = getPendingPermissions($db);
            $pageTitle = 'Dashboard';
            $currentPage = 'dashboard';
            require_once 'controller/dashboard.php';
            break;
            
        case '/login':
            // Redirect if already logged in
            if ($adminAuth->validateSession()) {
                $redirectUrl = $_SESSION['intended_url'] ?? '/admin/dashboard';
                unset($_SESSION['intended_url']);
                header("Location: $redirectUrl");
                exit;
            }
            
            $error = null;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $email = ValidationHelper::validateEmail($_POST['email'] ?? '', 'Email');
                    $password = ValidationHelper::validateRequired($_POST['password'] ?? '', 'Password');
                    
                    $user = $adminAuth->authenticateAdmin($email, $password);
                    
                    if ($user) {
                        $redirectUrl = $_SESSION['intended_url'] ?? '/admin/dashboard';
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
            break;
            
        case '/logout':
            require_once 'controller/Logout.php';
            $adminLogout = new AdminLogout($db, $adminAuth);
            $adminLogout->handle();
            exit;
            
        case '/permissions':
            require_once 'controller/Permission.php';
            $permissionManager = new PermissionManager($db, $adminAuth);
            $permissionManager->index();
            break;
            
        case '/security':
            require_once 'controller/SecurityManager.php';
            $securityManager = new SecurityManager($db, $adminAuth);
            $securityManager->index();
            break;
            
        case '/users':
            require_once 'controller/Users.php';
            $adminUsers = new AdminUsers($db, $adminAuth);
            $adminUsers->index();
            break;
            
        case '/schools':
            require_once 'controller/Schools.php';
            $adminSchools = new AdminSchools($db, $adminAuth);
            $adminSchools->index();
            break;
            
        case '/health':
            header('Content-Type: application/json');
            try {
                $db->query("SELECT 1");
                $dbStatus = 'connected';
            } catch (Exception $e) {
                $dbStatus = 'error';
            }
            echo json_encode([
                'status' => $dbStatus === 'connected' ? 'ok' : 'error',
                'timestamp' => date('c'),
                'database' => $dbStatus,
                'version' => '1.0.0',
                'environment' => env('APP_ENV', 'unknown')
            ], JSON_PRETTY_PRINT);
            exit;
            
        default:
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
                    <title>404 - Page Not Found</title>
                    <script src="https://cdn.tailwindcss.com"></script>
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body {
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                            background: #f3f4f6;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 100vh;
                            padding: 1rem;
                        }
                        .container-404 {
                            max-width: 28rem;
                            width: 100%;
                            padding: 2rem;
                            background: white;
                            border-radius: 0.5rem;
                            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                            text-align: center;
                        }
                        .title-404 {
                            font-size: 3rem;
                            font-weight: bold;
                            color: #111827;
                            margin-bottom: 1rem;
                        }
                        .text-404 {
                            color: #6b7280;
                            margin-bottom: 1.5rem;
                        }
                        .btn-404 {
                            display: inline-block;
                            background: #2563eb;
                            color: white;
                            padding: 0.5rem 1.5rem;
                            border-radius: 0.375rem;
                            text-decoration: none;
                            transition: background 0.2s;
                        }
                        .btn-404:hover {
                            background: #1d4ed8;
                        }
                    </style>
                </head>
                <body>
                    <div class="container-404 max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-md text-center">
                        <h1 class="title-404 text-4xl font-bold text-gray-900 mb-4">404</h1>
                        <p class="text-404 text-gray-600 mb-6">Page not found</p>
                        <a href="/admin/dashboard" class="btn-404 inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                            Go to Dashboard
                        </a>
                    </div>
                </body>
                </html>
                <?php
            }
            break;
    }
} catch (Exception $e) {
    error_log("Route handling error: " . $e->getMessage());
    http_response_code(500);
    echo env('APP_DEBUG', false) ? "Error: " . $e->getMessage() : "Internal server error";
}

// ==================== Helper Functions ====================

function getDashboardStats($db) {
    try {
        $stats = [];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM schools");
        $stats['total_schools'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM school_edit_permissions WHERE status = 'pending'");
        $stats['pending_permissions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM admin_sessions WHERE expires_at > NOW()");
        $stats['active_sessions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->query("
            SELECT COUNT(*) as count FROM admin_security_logs 
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