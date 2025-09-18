<?php
// admin/config/security.php - Fixed version for your directory structure

require_once __DIR__ . '/env.php';

class AdminSecurity {
    const MAX_LOGIN_ATTEMPTS = 3;
    const LOCKOUT_DURATION = 900; // 15 minutes
    const SESSION_TIMEOUT = 1800; // 30 minutes
    const CSRF_TOKEN_EXPIRY = 3600; // 1 hour
    
    private static function getDatabase() {
        // Use the global $db variable set by admin_db.php
        global $db;
        if (isset($db) && $db instanceof PDO) {
            return $db;
        }
        
        // If $db is not available, try to load admin_db.php
        if (!isset($db)) {
            require_once __DIR__ . '/admin_db.php';
            global $db;
        }
        
        if (!isset($db) || !($db instanceof PDO)) {
            throw new Exception('Database connection not available in AdminSecurity');
        }
        
        return $db;
    }
    
    public static function validateIP($ip) {
        // Check if IP whitelist is enabled
        if (!self::getSecurityConfig('ip_whitelist_enabled')) {
            return true; // IP whitelist disabled, allow all IPs
        }
        
        try {
            $connection = self::getDatabase();
            $stmt = $connection->prepare("SELECT COUNT(*) FROM ip_whitelist WHERE ip_address = ?");
            $stmt->execute([$ip]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("IP validation error: " . $e->getMessage());
            return true; // Fail open - allow access on error when whitelist is optional
        }
    }
    
    public static function logSecurityEvent($userId, $action, $details, $ipAddress, $userAgent) {
        try {
            $connection = self::getDatabase();
            $stmt = $connection->prepare("
                INSERT INTO security_logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $action, $details, $ipAddress, $userAgent]);
        } catch (Exception $e) {
            error_log("Security logging failed: " . $e->getMessage());
        }
    }
    
    public static function isUserLocked($userId) {
        try {
            $connection = self::getDatabase();
            $stmt = $connection->prepare("
                SELECT locked_until FROM user_lockouts 
                WHERE user_id = ? AND locked_until > NOW()
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn() !== false;
        } catch (Exception $e) {
            error_log("User lock check error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function incrementFailedAttempts($userId, $ipAddress) {
        try {
            $connection = self::getDatabase();
            
            // Update or insert failed attempt
            $stmt = $connection->prepare("
                INSERT INTO user_lockouts (user_id, failed_attempts, last_attempt, locked_until) 
                VALUES (?, 1, NOW(), IF(1 >= ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NULL))
                ON DUPLICATE KEY UPDATE 
                failed_attempts = failed_attempts + 1,
                last_attempt = NOW(),
                locked_until = IF(failed_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? SECOND), locked_until)
            ");
            
            $stmt->execute([
                $userId, 
                self::MAX_LOGIN_ATTEMPTS, 
                self::LOCKOUT_DURATION,
                self::MAX_LOGIN_ATTEMPTS,
                self::LOCKOUT_DURATION
            ]);
            
            self::logSecurityEvent($userId, 'FAILED_LOGIN', 'Failed login attempt', $ipAddress, $_SERVER['HTTP_USER_AGENT'] ?? '');
            
        } catch (Exception $e) {
            error_log("Failed to log login attempt: " . $e->getMessage());
        }
    }
    
    public static function resetFailedAttempts($userId) {
        try {
            $connection = self::getDatabase();
            $stmt = $connection->prepare("DELETE FROM user_lockouts WHERE user_id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Failed to reset login attempts: " . $e->getMessage());
        }
    }
    
    public static function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }
    
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Check if token has expired
        if (time() - $_SESSION['csrf_token_time'] > self::CSRF_TOKEN_EXPIRY) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function getSecurityConfig($key, $default = null) {
        $config = [
            'max_login_attempts' => function_exists('env') ? env('ADMIN_MAX_LOGIN_ATTEMPTS', self::MAX_LOGIN_ATTEMPTS) : self::MAX_LOGIN_ATTEMPTS,
            'lockout_duration' => function_exists('env') ? env('ADMIN_LOCKOUT_DURATION', self::LOCKOUT_DURATION) : self::LOCKOUT_DURATION,
            'session_timeout' => function_exists('env') ? env('ADMIN_SESSION_TIMEOUT', self::SESSION_TIMEOUT) : self::SESSION_TIMEOUT,
            'csrf_expiry' => function_exists('env') ? env('ADMIN_CSRF_EXPIRY', self::CSRF_TOKEN_EXPIRY) : self::CSRF_TOKEN_EXPIRY,
            'ip_whitelist_enabled' => function_exists('env') ? env('ADMIN_IP_WHITELIST', false) : false,
            'rate_limiting_enabled' => function_exists('env') ? env('ADMIN_RATE_LIMITING', true) : true,
        ];
        
        return $config[$key] ?? $default;
    }
    
    public static function checkRateLimit($identifier, $maxRequests = 60, $timeWindow = 60) {
        if (!self::getSecurityConfig('rate_limiting_enabled')) {
            return true; // Rate limiting disabled
        }
        
        $cacheFile = sys_get_temp_dir() . "/admin_rate_limit_" . md5($identifier);
        $requests = [];
        
        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            $requests = json_decode($data, true) ?: [];
        }
        
        // Remove old requests
        $currentTime = time();
        $requests = array_filter($requests, function($timestamp) use ($currentTime, $timeWindow) {
            return $timestamp > ($currentTime - $timeWindow);
        });
        
        // Check if limit exceeded
        if (count($requests) >= $maxRequests) {
            return false;
        }
        
        // Add current request
        $requests[] = $currentTime;
        file_put_contents($cacheFile, json_encode($requests));
        
        return true;
    }
    
    public static function cleanupExpiredData() {
        try {
            $connection = self::getDatabase();
            
            // Clean expired sessions
            $stmt = $connection->prepare("DELETE FROM admin_sessions WHERE expires_at < NOW()");
            $stmt->execute();
            
            // Clean expired lockouts
            $stmt = $connection->prepare("DELETE FROM user_lockouts WHERE locked_until < NOW() AND locked_until IS NOT NULL");
            $stmt->execute();
            
            // Clean old security logs (keep 90 days)
            $stmt = $connection->prepare("DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            error_log("Cleanup error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function isMaintenanceMode() {
        return function_exists('env') ? env('MAINTENANCE_MODE', false) : false;
    }
    
    public static function validateMaintenanceSecret($secret) {
        if (!function_exists('env')) return false;
        $maintenanceSecret = env('MAINTENANCE_SECRET', '');
        return $maintenanceSecret && hash_equals($maintenanceSecret, $secret);
    }
}

// Auto-cleanup on class load (runs once per request)
if (rand(1, 100) === 1) { // 1% chance to run cleanup
    try {
        AdminSecurity::cleanupExpiredData();
    } catch (Exception $e) {
        error_log("Auto-cleanup failed: " . $e->getMessage());
    }
}
?>