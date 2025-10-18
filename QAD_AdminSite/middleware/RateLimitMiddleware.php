<?php 
// middleware/RateLimitMiddleware.php - Fixed version with CSS fallback

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
                $this->renderBlockedPage();
            }
            exit;
        }
        
        return $next($request);
    }
    
    private function renderBlockedPage() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Rate Limit Exceeded</title>
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
                .container {
                    max-width: 28rem;
                    width: 100%;
                    padding: 2rem;
                    background: white;
                    border-radius: 0.5rem;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .text-center { text-align: center; }
                .icon-wrapper {
                    margin: 0 auto 1.5rem;
                    height: 4rem;
                    width: 4rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    background: #fee2e2;
                }
                .icon {
                    height: 2rem;
                    width: 2rem;
                    color: #dc2626;
                }
                h2 {
                    margin-top: 1.5rem;
                    font-size: 1.875rem;
                    font-weight: bold;
                    color: #111827;
                }
                .text-sm {
                    margin-top: 0.5rem;
                    font-size: 0.875rem;
                    color: #4b5563;
                }
                .btn {
                    margin-top: 1.5rem;
                    width: 100%;
                    background: #2563eb;
                    color: white;
                    padding: 0.5rem 1rem;
                    border-radius: 0.375rem;
                    border: none;
                    cursor: pointer;
                    font-size: 1rem;
                    transition: background 0.2s;
                }
                .btn:hover {
                    background: #1d4ed8;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="text-center">
                    <div class="icon-wrapper">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2>Rate Limit Exceeded</h2>
                    <p class="text-sm">
                        Too many requests from your IP address. Please wait <?= $this->timeWindow ?> seconds before trying again.
                    </p>
                    <button onclick="window.history.back()" class="btn">
                        Go Back
                    </button>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}