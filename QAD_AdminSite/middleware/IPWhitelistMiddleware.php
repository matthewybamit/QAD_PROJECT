<?php

//middleware/IPWhitelistMiddleware.php - New middleware for IP restrictions

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
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Access Denied</title>
                    <script src="https://cdn.tailwindcss.com"></script>
                </head>
                <body class="bg-gray-100 min-h-screen flex items-center justify-center">
                    <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-md">
                        <div class="text-center">
                            <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-red-100">
                                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"></path>
                                </svg>
                            </div>
                            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">Access Denied</h2>
                            <p class="mt-2 text-center text-sm text-gray-600">
                                Your IP address (<?= htmlspecialchars($clientIP) ?>) is not authorized to access this admin panel.
                            </p>
                            <p class="mt-4 text-xs text-gray-500">
                                If you believe this is an error, please contact your administrator.
                            </p>
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