<?php
// middleware/SecurityMiddleware.php
// Enhanced Security Middleware with IP blocking, rate limiting, and advanced protection

class SecurityMiddleware {
    private static $db;
    
    public static function init($database) {
        self::$db = $database;
    }
    
    /**
     * Check if IP is blacklisted
     */
    public static function checkIPBlacklist($ip = null) {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (!self::$db) return true;
        
        try {
            $stmt = self::$db->prepare("SELECT id, reason FROM ip_blacklist WHERE ip_address = ?");
            $stmt->execute([$ip]);
            $blocked = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($blocked) {
                AdminSecurity::logSecurityEvent(
                    null,
                    'IP_BLOCKED_ACCESS',
                    "Blocked IP {$ip} attempted access: {$blocked['reason']}",
                    $ip,
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
                
                http_response_code(403);
                die(self::renderBlockedPage($blocked['reason']));
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("IP blacklist check error: " . $e->getMessage());
            return true; // Fail open to avoid breaking the site
        }
    }
    
    /**
     * Check if IP is whitelisted (if whitelist is enabled)
     */
    public static function checkIPWhitelist($ip = null) {
        if (!AdminSecurity::getSecurityConfig('ip_whitelist_enabled', false)) {
            return true;
        }
        
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Always allow localhost
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }
        
        if (!self::$db) return true;
        
        try {
            $stmt = self::$db->prepare("SELECT id FROM ip_whitelist WHERE ip_address = ?");
            $stmt->execute([$ip]);
            
            if (!$stmt->fetch()) {
                AdminSecurity::logSecurityEvent(
                    null,
                    'IP_NOT_WHITELISTED',
                    "Non-whitelisted IP {$ip} attempted access",
                    $ip,
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
                
                http_response_code(403);
                die(self::renderBlockedPage("Your IP address is not authorized to access this system."));
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("IP whitelist check error: " . $e->getMessage());
            return true; // Fail open
        }
    }
    
    /**
     * Advanced rate limiting with automatic IP blacklisting
     */
    public static function checkRateLimit($identifier = null, $maxRequests = 60, $timeWindow = 60, $autoBlock = true) {
        $identifier = $identifier ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        if (!AdminSecurity::checkRateLimit($identifier, $maxRequests, $timeWindow)) {
            AdminSecurity::logSecurityEvent(
                null,
                'RATE_LIMIT_EXCEEDED',
                "Rate limit exceeded for {$identifier} ({$maxRequests} requests in {$timeWindow}s)",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            // Auto-block aggressive IPs
            if ($autoBlock && self::$db) {
                self::autoBlockIP($identifier, 'Exceeded rate limit');
            }
            
            http_response_code(429);
            header('Retry-After: ' . $timeWindow);
            die(json_encode(['error' => 'Too many requests. Please slow down.']));
        }
        
        return true;
    }
    
    /**
     * Check for suspicious patterns in request
     */
    public static function checkSuspiciousPatterns() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Check for SQL injection attempts
        $sqlPatterns = [
            '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',
            '/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',
            '/\w*((\%27)|(\'))((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i',
            '/((\%27)|(\'))union/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $requestUri)) {
                AdminSecurity::logSecurityEvent(
                    null,
                    'SQL_INJECTION_ATTEMPT',
                    "SQL injection attempt detected in URI: {$requestUri}",
                    $ip,
                    $userAgent
                );
                
                if (self::$db) {
                    self::autoBlockIP($ip, 'SQL injection attempt');
                }
                
                http_response_code(403);
                die('Access denied');
            }
        }
        
        // Check for XSS attempts
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $requestUri)) {
                AdminSecurity::logSecurityEvent(
                    null,
                    'XSS_ATTEMPT',
                    "XSS attempt detected in URI: {$requestUri}",
                    $ip,
                    $userAgent
                );
                
                http_response_code(403);
                die('Access denied');
            }
        }
        
        // Check for path traversal
        if (preg_match('/\.\.[\/\\\\]/', $requestUri)) {
            AdminSecurity::logSecurityEvent(
                null,
                'PATH_TRAVERSAL_ATTEMPT',
                "Path traversal attempt detected: {$requestUri}",
                $ip,
                $userAgent
            );
            
            http_response_code(403);
            die('Access denied');
        }
        
        // Check for suspicious User-Agent
        $suspiciousAgents = ['sqlmap', 'nikto', 'acunetix', 'nmap', 'masscan', 'metasploit'];
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                AdminSecurity::logSecurityEvent(
                    null,
                    'SUSPICIOUS_USER_AGENT',
                    "Suspicious user agent detected: {$userAgent}",
                    $ip,
                    $userAgent
                );
                
                if (self::$db) {
                    self::autoBlockIP($ip, 'Suspicious user agent detected');
                }
                
                http_response_code(403);
                die('Access denied');
            }
        }
        
        return true;
    }
    
    /**
     * Validate CSRF token for POST requests
     */
    public static function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!AdminSecurity::validateCSRFToken($token)) {
                AdminSecurity::logSecurityEvent(
                    $_SESSION['admin_user_id'] ?? null,
                    'CSRF_VIOLATION',
                    "CSRF token validation failed",
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
                
                http_response_code(403);
                die('Invalid security token. Please refresh the page and try again.');
            }
        }
        
        return true;
    }
    
    /**
     * Check session security
     */
    public static function validateSessionSecurity() {
        if (!isset($_SESSION['admin_user_id'])) {
            return true; // Not logged in, skip
        }
        
        // Check session fingerprint
        $currentFingerprint = self::generateFingerprint();
        
        if (!isset($_SESSION['security_fingerprint'])) {
            $_SESSION['security_fingerprint'] = $currentFingerprint;
        } elseif ($_SESSION['security_fingerprint'] !== $currentFingerprint) {
            AdminSecurity::logSecurityEvent(
                $_SESSION['admin_user_id'] ?? null,
                'SESSION_HIJACK_ATTEMPT',
                "Session fingerprint mismatch - possible hijacking",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            // Destroy session
            session_destroy();
            http_response_code(403);
            die('Security violation detected. Please login again.');
        }
        
        return true;
    }
    
    /**
     * Generate session fingerprint
     */
    private static function generateFingerprint() {
        return hash('sha256', 
            ($_SERVER['HTTP_USER_AGENT'] ?? '') . 
            ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') .
            ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '')
        );
    }
    
    /**
     * Automatically block an IP address
     */
    private static function autoBlockIP($ip, $reason) {
        if (!self::$db) return;
        
        try {
            // Check if already blocked
            $stmt = self::$db->prepare("SELECT id FROM ip_blacklist WHERE ip_address = ?");
            $stmt->execute([$ip]);
            
            if (!$stmt->fetch()) {
                $stmt = self::$db->prepare("
                    INSERT INTO ip_blacklist (ip_address, reason, blocked_by, blocked_at) 
                    VALUES (?, ?, NULL, NOW())
                ");
                $stmt->execute([$ip, "Auto-blocked: {$reason}"]);
                
                AdminSecurity::logSecurityEvent(
                    null,
                    'IP_AUTO_BLOCKED',
                    "IP {$ip} was automatically blocked: {$reason}",
                    $ip,
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
            }
        } catch (PDOException $e) {
            error_log("Auto-block IP error: " . $e->getMessage());
        }
    }
    
    /**
     * Render blocked page
     */
    private static function renderBlockedPage($reason = 'Access denied') {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container { 
            text-align: center; 
            background: white; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 500px;
        }
        .icon { 
            font-size: 64px; 
            color: #e74c3c; 
            margin-bottom: 20px;
        }
        h1 { 
            color: #e74c3c; 
            margin: 0 0 10px 0;
        }
        p { 
            color: #555; 
            line-height: 1.6;
        }
        .reason {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚫</div>
        <h1>Access Denied</h1>
        <p>Your access to this resource has been blocked.</p>
        <div class="reason">
            <strong>Reason:</strong> {$reason}
        </div>
        <p style="margin-top: 20px; font-size: 12px; color: #999;">
            If you believe this is an error, please contact the system administrator.
        </p>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Apply all security checks
     */
    public static function applyAll() {
        self::checkIPBlacklist();
        self::checkIPWhitelist();
        self::checkRateLimit();
        self::checkSuspiciousPatterns();
        self::validateSessionSecurity();
        
        return true;
    }
}

// Auto-initialize if database is available
if (isset($db) && $db instanceof PDO) {
    SecurityMiddleware::init($db);
}