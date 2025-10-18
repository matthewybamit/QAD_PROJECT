<?php
// middleware/IPWhitelistMiddleware.php - Fixed version with CSS fallback

class IPWhitelistMiddleware {
    public function handle($request, $next) {
        // Only apply if IP whitelist is enabled
        if (!AdminSecurity::getSecurityConfig('ip_whitelist_enabled')) {
            return $next($request);
        }
        
        $clientIP = $this->getClientIP();
        
        if (!AdminSecurity::validateIP($clientIP)) {
            AdminSecurity::logSecurityEvent(
                $_SESSION['admin_user_id'] ?? null,
                'IP_ACCESS_DENIED',
                "Access denied from non-whitelisted IP: $clientIP",
                $clientIP,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            http_response_code(403);
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'IP address not authorized']);
            } else {
                $this->renderBlockedPage($clientIP);
            }
            exit;
        }
        
        return $next($request);
    }
    
    private function renderBlockedPage($clientIP) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Denied</title>
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
                .text-xs {
                    margin-top: 1rem;
                    font-size: 0.75rem;
                    color: #6b7280;
                }
                .ip-highlight {
                    font-weight: 600;
                    color: #dc2626;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="text-center">
                    <div class="icon-wrapper">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </div>
                    <h2>Access Denied</h2>
                    <p class="text-sm">
                        Your IP address (<span class="ip-highlight"><?= htmlspecialchars($clientIP) ?></span>) is not authorized to access this admin panel.
                    </p>
                    <p class="text-xs">
                        If you believe this is an error, please contact your administrator.
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function getClientIP() {
        // Check for various headers that might contain the real IP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}