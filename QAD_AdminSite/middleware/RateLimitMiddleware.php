<?php 
// middleware/RateLimitMiddleware.php - Fixed version

class RateLimitMiddleware {
    private $maxRequests;
    private $timeWindow;
    
    public function __construct($maxRequests = null, $timeWindow = null) {
        $this->maxRequests = $maxRequests ?? AdminSecurity::getSecurityConfig('rate_limit_requests', 60);
        $this->timeWindow = $timeWindow ?? AdminSecurity::getSecurityConfig('rate_limit_window', 60);
    }
    
    public function handle($request, $next) {
        if (!AdminSecurity::getSecurityConfig('rate_limiting_enabled')) {
            return $next($request);
        }
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $identifier = "admin_" . $ip;
        
        // Check rate limit
        if (!AdminSecurity::checkRateLimit($identifier, $this->maxRequests, $this->timeWindow)) {
            AdminSecurity::logSecurityEvent(
                $_SESSION['admin_user_id'] ?? null,
                'RATE_LIMIT_EXCEEDED',
                'Rate limit exceeded for IP: ' . $ip,
                $ip,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            // Return appropriate response based on request type
            if ($this->isAjaxRequest()) {
                http_response_code(429);
                header('Content-Type: application/json');
                header('Retry-After: ' . $this->timeWindow);
                echo json_encode([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $this->timeWindow
                ]);
            } else {
                http_response_code(429);
                header('Retry-After: ' . $this->timeWindow);
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Rate Limit Exceeded</title>
                    <script src="https://cdn.tailwindcss.com"></script>
                </head>
                <body class="bg-gray-100 min-h-screen flex items-center justify-center">
                    <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-md">
                        <div class="text-center">
                            <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-red-100">
                                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">Rate Limit Exceeded</h2>
                            <p class="mt-2 text-center text-sm text-gray-600">
                                Too many requests from your IP address. Please wait <?= $this->timeWindow ?> seconds before trying again.
                            </p>
                            <div class="mt-6">
                                <button onclick="window.history.back()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Go Back
                                </button>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                <?php
            }
            exit;
        }
        
        return $next($request);
    }
    
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}