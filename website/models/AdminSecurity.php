<?php
// models/AdminSecurity.php
class AdminSecurity {
    private $pdo;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900; // 15 minutes
    private const SESSION_TIMEOUT = 3600; // 1 hour
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Enhanced admin verification with multiple security layers
     */
    public function verifyAdminAccess($userId) {
        try {
            // 1. Check if user exists and has admin role
            $stmt = $this->pdo->prepare("
                SELECT u.*, ul.locked_until, ul.failed_attempts 
                FROM users u
                LEFT JOIN user_lockouts ul ON u.id = ul.user_id
                WHERE u.id = ? AND u.role IN ('admin', 'school_admin')
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->logSecurity($userId, 'unauthorized_admin_access', 'Non-admin user attempted admin access');
                return false;
            }
            
            // 2. Check account lockout
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $this->logSecurity($userId, 'locked_account_access', 'Locked account attempted access');
                return false;
            }
            
            // 3. Check session validity and timeout
            if (!$this->verifySession($userId)) {
                return false;
            }
            
            // 4. IP whitelist check (optional - can be configured)
            if (!$this->checkIPWhitelist()) {
                $this->logSecurity($userId, 'unauthorized_ip', 'Access from non-whitelisted IP');
                return false;
            }
            
            // 5. Rate limiting
            if (!$this->checkRateLimit($userId)) {
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Admin verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enhanced session management
     */
    public function verifySession($userId) {
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT) {
                $this->logSecurity($userId, 'session_timeout', 'Session expired');
                session_destroy();
                return false;
            }
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['regenerated']) || time() - $_SESSION['regenerated'] > 300) {
            session_regenerate_id(true);
            $_SESSION['regenerated'] = time();
        }
        
        return true;
    }
    
    /**
     * IP Whitelist check (configurable)
     */
    private function checkIPWhitelist() {
        // Get whitelisted IPs from config or database
        $whitelistedIPs = $this->getWhitelistedIPs();
        
        // Skip if no whitelist configured
        if (empty($whitelistedIPs)) {
            return true;
        }
        
        $clientIP = $this->getClientIP();
        return in_array($clientIP, $whitelistedIPs);
    }
    
    /**
     * Rate limiting to prevent brute force
     */
    private function checkRateLimit($userId) {
        $ip = $this->getClientIP();
        
        try {
            // Check requests in last 5 minutes
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as request_count 
                FROM activity_logs 
                WHERE (user_id = ? OR ip_address = ?) 
                AND action LIKE 'admin_%' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $stmt->execute([$userId, $ip]);
            $result = $stmt->fetch();
            
            // Max 50 admin requests per 5 minutes
            if ($result['request_count'] > 50) {
                $this->logSecurity($userId, 'rate_limit_exceeded', 'Too many admin requests');
                return false;
            }
            
            return true;
        } catch (PDOException $e) {
            return true; // Fail open for availability
        }
    }
    
    /**
     * Two-factor authentication for admin actions
     */
    public function requireTwoFactor($userId, $action) {
        // For critical actions, require additional verification
        $criticalActions = ['user_promotion', 'bulk_approve', 'system_settings'];
        
        if (in_array($action, $criticalActions)) {
            return $this->verifyTwoFactorCode($userId);
        }
        
        return true;
    }
    
    /**
     * Account lockout after failed attempts
     */
    public function handleFailedAttempt($userId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_lockouts (user_id, failed_attempts, last_attempt, locked_until)
                VALUES (?, 1, NOW(), NULL)
                ON DUPLICATE KEY UPDATE 
                failed_attempts = failed_attempts + 1,
                last_attempt = NOW(),
                locked_until = CASE 
                    WHEN failed_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                    ELSE locked_until 
                END
            ");
            $stmt->execute([$userId, self::MAX_LOGIN_ATTEMPTS, self::LOCKOUT_DURATION]);
            
            $this->logSecurity($userId, 'admin_failed_attempt', 'Failed admin authentication');
            
        } catch (PDOException $e) {
            error_log('Failed to handle lockout: ' . $e->getMessage());
        }
    }
    
    /**
     * Get client IP address (handles proxies)
     */
    private function getClientIP() {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Get whitelisted IPs from configuration
     */
    private function getWhitelistedIPs() {
        // This could be stored in database or config file
        // For now, return empty array to disable IP whitelist
        return [];
        
        // Example whitelist:
        // return ['192.168.1.100', '203.0.113.10'];
    }
    
    /**
     * Enhanced security logging
     */
    private function logSecurity($userId, $action, $details) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $action,
                $details,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (PDOException $e) {
            error_log('Security logging failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Two-factor authentication verification (placeholder)
     */
    private function verifyTwoFactorCode($userId) {
        // This would integrate with TOTP apps like Google Authenticator
        // For now, return true - implement based on your needs
        return true;
    }
    
    /**
     * Admin activity monitoring
     */
    public function logAdminActivity($userId, $action, $details) {
        $this->logSecurity($userId, 'admin_' . $action, $details);
    }
}
?>