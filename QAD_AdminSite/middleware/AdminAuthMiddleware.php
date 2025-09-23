<?php
// middleware/AdminAuthMiddleware.php - Fixed version

// class AdminAuthMiddleware {
//     private $adminAuth;
    
//     public function __construct($adminAuth) {
//         $this->adminAuth = $adminAuth;
//     }
    
//     public function handle($request, $next) {
//         // Check maintenance mode first
//         if (AdminSecurity::isMaintenanceMode()) {
//             $secret = $_GET['secret'] ?? '';
//             if (!AdminSecurity::validateMaintenanceSecret($secret)) {
//                 $this->showMaintenancePage();
//                 exit;
//             }
//         }
        
//         // Check if user is authenticated
//         if (!$this->adminAuth->validateSession()) {
//             if ($this->isAjaxRequest()) {
//                 http_response_code(401);
//                 header('Content-Type: application/json');
//                 echo json_encode(['error' => 'Unauthorized', 'redirect' => '/admin/login']);
//                 exit;
//             } else {
//                 $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
//                 header('Location: /admin/login');
//                 exit;
//             }
//         }
        
//         // Check IP whitelist for admin access
//         if (AdminSecurity::getSecurityConfig('ip_whitelist_enabled') && !AdminSecurity::validateIP($_SERVER['REMOTE_ADDR'])) {
//             AdminSecurity::logSecurityEvent(
//                 $_SESSION['admin_user_id'] ?? null,
//                 'IP_BLOCKED',
//                 'Access denied from unauthorized IP',
//                 $_SERVER['REMOTE_ADDR'],
//                 $_SERVER['HTTP_USER_AGENT'] ?? ''
//             );
            
//             http_response_code(403);
//             if ($this->isAjaxRequest()) {
//                 header('Content-Type: application/json');
//                 echo json_encode(['error' => 'Access denied from your IP address']);
//             } else {
//                 echo '<h1>Access Denied</h1><p>Your IP address is not authorized to access this admin panel.</p>';
//             }
//             exit;
//         }
        
//         return $next($request);
//     }
    
//     private function isAjaxRequest() {
//         return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
//                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
//     }
    
//     private function showMaintenancePage() {
//         http_response_code(503);
//         header('Retry-After: 3600');
//         ?>
        <!-- <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Panel - Maintenance Mode</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-900 min-h-screen flex items-center justify-center">
            <div class="max-w-md w-full space-y-8 p-8">
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-yellow-100">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-white">Maintenance Mode</h2>
                    <p class="mt-2 text-center text-sm text-gray-400">
                        The admin panel is currently undergoing maintenance. Please try again later.
                    </p>
                    <div class="mt-6">
                        <form method="GET" class="space-y-4">
                            <input type="password" name="secret" placeholder="Maintenance Secret" 
                                   class="w-full px-3 py-2 border border-gray-600 bg-gray-800 text-white rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Access Admin Panel
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </body>
        </html> -->
        <?php
//     }
// }




// middleware/AdminAuthMiddleware.php
// Cleaned up: maintenance mode removed. Keeps auth + optional IP whitelist.

class AdminAuthMiddleware {
    private $adminAuth;

    public function __construct($adminAuth) {
        $this->adminAuth = $adminAuth;
    }

    public function handle($request, $next) {
        // --------------------------
        // Authentication
        // --------------------------
        if (!$this->adminAuth->validateSession()) {
            if ($this->isAjaxRequest()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized', 'redirect' => '/admin/login']);
                exit;
            } else {
                // Save intended URL and redirect to login page
                if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
                $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/';
                header('Location: /admin/login');
                exit;
            }
        }

        // --------------------------
        // Optional: IP whitelist enforcement
        // --------------------------
        // Only enforce if AdminSecurity exists and ip_whitelist_enabled is true
        if (class_exists('AdminSecurity')) {
            $whitelistEnabled = (int) AdminSecurity::getSecurityConfig('ip_whitelist_enabled', 0);
            if ($whitelistEnabled) {
                $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
                // Ensure validateIP exists
                if (method_exists('AdminSecurity', 'validateIP')) {
                    $allowed = AdminSecurity::validateIP($remoteIp);
                } else {
                    // If validateIP not implemented, default to deny (safer)
                    $allowed = false;
                }

                if (!$allowed) {
                    AdminSecurity::logSecurityEvent(
                        $_SESSION['admin_user_id'] ?? null,
                        'IP_BLOCKED',
                        'Access denied from unauthorized IP',
                        $remoteIp,
                        $_SERVER['HTTP_USER_AGENT'] ?? ''
                    );

                    http_response_code(403);
                    if ($this->isAjaxRequest()) {
                        header('Content-Type: application/json');
                        echo json_encode(['error' => 'Access denied from your IP address']);
                    } else {
                        echo '<h1>Access Denied</h1><p>Your IP address is not authorized to access this admin panel.</p>';
                    }
                    exit;
                }
            }
        }

        // Continue to next middleware/controller
        return $next($request);
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
