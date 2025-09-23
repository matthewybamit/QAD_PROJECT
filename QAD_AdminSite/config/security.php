<?php
// config/security.php - Fixed version with correct table names

require_once __DIR__ . '/env.php';

class AdminSecurity {
    const MAX_LOGIN_ATTEMPTS = 3;
    const LOCKOUT_DURATION = 900; // 15 minutes
    const SESSION_TIMEOUT = 1800; // 30 minutes
    const CSRF_TOKEN_EXPIRY = 3600; // 1 hour
    
    private static function getDatabase() {
        global $db;
        if (isset($db) && $db instanceof PDO) {
            return $db;
        }
        
        if (!isset($db)) {
            require_once __DIR__ . '/admin_db.php';
            global $db;
        }
        
        if (!isset($db) || !($db instanceof PDO)) {
            throw new Exception('Database connection not available in AdminSecurity');
        }
        
        return $db;
    }

    /** ───── IP WHITELIST ───── */
    public static function validateIP($ip) {
        if (!self::getSecurityConfig('ip_whitelist_enabled')) {
            return true;
        }
        
        try {
            $connection = self::getDatabase();
            $stmt = $connection->prepare("SELECT COUNT(*) FROM ip_whitelist WHERE ip_address = ?");
            $stmt->execute([$ip]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("IP validation error: " . $e->getMessage());
            return true;
        }
    }

    /** ───── LOGGING ───── */
    public static function logSecurityEvent($adminUserId, $action, $details, $ipAddress, $userAgent) {
        try {
            $connection = self::getDatabase();
            $stmt = $connection->prepare("
                INSERT INTO admin_security_logs (admin_user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $result = $stmt->execute([$adminUserId, $action, $details, $ipAddress, $userAgent]);
            
            if ($result) {
                error_log("Security event logged: $action for user $adminUserId");
            } else {
                error_log("Failed to log security event: $action");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Security logging failed: " . $e->getMessage());
            return false;
        }
    }

    /** ───── LOCKOUT MANAGEMENT ───── */
    public static function isUserLocked($adminUserId) {
        try {
            $connection = self::getDatabase();
            // Fixed table name from admin_user_lockouts to admin_lockouts
            $stmt = $connection->prepare("
                SELECT locked_until FROM admin_lockouts 
                WHERE admin_user_id = ? AND locked_until > NOW()
            ");
            $stmt->execute([$adminUserId]);
            return $stmt->fetchColumn() !== false;
        } catch (Exception $e) {
            error_log("Admin lock check error: " . $e->getMessage());
            return false;
        }
    }

    public static function incrementFailedAttempts($adminUserId, $ipAddress) {
        try {
            $connection = self::getDatabase();
            // Fixed table name from admin_user_lockouts to admin_lockouts
            $stmt = $connection->prepare("
                INSERT INTO admin_lockouts (admin_user_id, failed_attempts, last_attempt, locked_until) 
                VALUES (?, 1, NOW(), IF(1 >= ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NULL))
                ON DUPLICATE KEY UPDATE 
                    failed_attempts = failed_attempts + 1,
                    last_attempt = NOW(),
                    locked_until = IF(failed_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? SECOND), locked_until)
            ");
            $stmt->execute([
                $adminUserId,
                self::MAX_LOGIN_ATTEMPTS,
                self::LOCKOUT_DURATION,
                self::MAX_LOGIN_ATTEMPTS,
                self::LOCKOUT_DURATION
            ]);

            self::logSecurityEvent(
                $adminUserId,
                'FAILED_LOGIN',
                'Failed login attempt',
                $ipAddress,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
        } catch (Exception $e) {
            error_log("Failed to log admin login attempt: " . $e->getMessage());
        }
    }

    public static function resetFailedAttempts($adminUserId) {
        try {
            $connection = self::getDatabase();
            // Fixed table name from admin_user_lockouts to admin_lockouts
            $stmt = $connection->prepare("DELETE FROM admin_lockouts WHERE admin_user_id = ?");
            $stmt->execute([$adminUserId]);
        } catch (Exception $e) {
            error_log("Failed to reset admin login attempts: " . $e->getMessage());
        }
    }

    /** ───── CSRF PROTECTION ───── */
    public static function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }

    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'], $_SESSION['csrf_token_time'])) {
            return false;
        }
        
        if (time() - $_SESSION['csrf_token_time'] > self::CSRF_TOKEN_EXPIRY) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /** ───── SANITIZATION ───── */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** ───── PASSWORDS ───── */
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

    /** ───── CONFIG ───── */
    public static function getSecurityConfig($key, $default = null) {
        $config = [
            'max_login_attempts'   => function_exists('env') ? env('ADMIN_MAX_LOGIN_ATTEMPTS', self::MAX_LOGIN_ATTEMPTS) : self::MAX_LOGIN_ATTEMPTS,
            'lockout_duration'     => function_exists('env') ? env('ADMIN_LOCKOUT_DURATION', self::LOCKOUT_DURATION) : self::LOCKOUT_DURATION,
            'session_timeout'      => function_exists('env') ? env('ADMIN_SESSION_TIMEOUT', self::SESSION_TIMEOUT) : self::SESSION_TIMEOUT,
            'csrf_expiry'          => function_exists('env') ? env('ADMIN_CSRF_EXPIRY', self::CSRF_TOKEN_EXPIRY) : self::CSRF_TOKEN_EXPIRY,
            'ip_whitelist_enabled' => function_exists('env') ? env('ADMIN_IP_WHITELIST', false) : false,
            'rate_limiting_enabled'=> function_exists('env') ? env('ADMIN_RATE_LIMITING', true) : true,
        ];
        return $config[$key] ?? $default;
    }

    /** ───── RATE LIMITING ───── */
    public static function checkRateLimit($identifier, $maxRequests = 60, $timeWindow = 60) {
        if (!self::getSecurityConfig('rate_limiting_enabled')) {
            return true;
        }
        
        $cacheFile = sys_get_temp_dir() . "/admin_rate_limit_" . md5($identifier);
        $requests = [];
        
        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            $requests = json_decode($data, true) ?: [];
        }
        
        $currentTime = time();
        $requests = array_filter($requests, fn($ts) => $ts > ($currentTime - $timeWindow));
        
        if (count($requests) >= $maxRequests) {
            return false;
        }
        
        $requests[] = $currentTime;
        file_put_contents($cacheFile, json_encode($requests));
        
        return true;
    }

    /** ───── CLEANUP ───── */
    public static function cleanupExpiredData() {
        try {
            $connection = self::getDatabase();

            // Expired admin sessions
            $stmt = $connection->prepare("DELETE FROM admin_sessions WHERE expires_at < NOW()");
            $stmt->execute();

            // Expired admin lockouts - Fixed table name
            $stmt = $connection->prepare("DELETE FROM admin_lockouts WHERE locked_until < NOW() AND locked_until IS NOT NULL");
            $stmt->execute();

            // Old security logs (keep 90 days)
            $stmt = $connection->prepare("DELETE FROM admin_security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            error_log("Cleanup error: " . $e->getMessage());
            return false;
        }
    }

    /** ───── MAINTENANCE ───── */
    public static function isMaintenanceMode() {
        return function_exists('env') ? env('MAINTENANCE_MODE', false) : false;
    }

    public static function validateMaintenanceSecret($secret) {
        if (!function_exists('env')) return false;
        $maintenanceSecret = env('MAINTENANCE_SECRET', '');
        return $maintenanceSecret && hash_equals($maintenanceSecret, $secret);
    }

    /** ───── HELPER METHODS FOR DEBUGGING ───── */
    public static function debugLogActivity($adminUserId, $action, $details) {
        return self::logSecurityEvent(
            $adminUserId, 
            $action, 
            $details, 
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );
    }
}

// Auto-cleanup on class load (1% chance per request)
if (rand(1, 100) === 1) {
    try {
        AdminSecurity::cleanupExpiredData();
    } catch (Exception $e) {
        error_log("Auto-cleanup failed: " . $e->getMessage());
    }
}