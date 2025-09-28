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


/** ───── PASSWORD POLICY ───── */
public static function validatePasswordStrength($password) {
    $errors = [];
    $minLength = self::getSecurityConfig('password_min_length', 12);
    
    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least {$minLength} characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    // Check against common passwords
    $commonPasswords = [
        'password', 'password123', '123456', 'admin', 'administrator',
        'qwerty', 'letmein', 'welcome', 'monkey', 'dragon'
    ];
    
    if (in_array(strtolower($password), $commonPasswords)) {
        $errors[] = 'Password is too common and easily guessable';
    }
    
    return empty($errors) ? true : $errors;
}

public static function checkPasswordHistory($userId, $newPasswordHash, $historyCount = 5) {
    try {
        $connection = self::getDatabase();
        $stmt = $connection->prepare("
            SELECT password_hash 
            FROM admin_password_history 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $historyCount]);
        $oldPasswords = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($oldPasswords as $oldHash) {
            if (password_verify($newPasswordHash, $oldHash)) {
                return false; // Password was used recently
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Password history check error: " . $e->getMessage());
        return true; // Allow if check fails
    }
}

public static function addToPasswordHistory($userId, $passwordHash) {
    try {
        $connection = self::getDatabase();
        
        // Add new password to history
        $stmt = $connection->prepare("
            INSERT INTO admin_password_history (user_id, password_hash, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$userId, $passwordHash]);
        
        // Keep only last 5 passwords
        $stmt = $connection->prepare("
            DELETE FROM admin_password_history 
            WHERE user_id = ? 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM admin_password_history 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ) tmp
            )
        ");
        $stmt->execute([$userId, $userId]);
        
    } catch (Exception $e) {
        error_log("Password history storage error: " . $e->getMessage());
    }
}

/** ───── ENHANCED INPUT VALIDATION ───── */
public static function validateInput($input, $type = 'string', $maxLength = 255) {
    if (is_array($input)) {
        return array_map(function($item) use ($type, $maxLength) {
            return self::validateInput($item, $type, $maxLength);
        }, $input);
    }
    
    // Basic sanitization
    $input = trim($input);
    
    // Length check
    if (strlen($input) > $maxLength) {
        throw new InvalidArgumentException("Input exceeds maximum length of {$maxLength}");
    }
    
    // Type-specific validation
    switch ($type) {
        case 'email':
            if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email format");
            }
            break;
            
        case 'ip':
            if (!filter_var($input, FILTER_VALIDATE_IP)) {
                throw new InvalidArgumentException("Invalid IP address format");
            }
            break;
            
        case 'alphanumeric':
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
                throw new InvalidArgumentException("Input must contain only alphanumeric characters, underscores, and hyphens");
            }
            break;
            
        case 'numeric':
            if (!is_numeric($input)) {
                throw new InvalidArgumentException("Input must be numeric");
            }
            break;
    }
    
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/** ───── BRUTE FORCE PROTECTION ───── */
public static function checkBruteForceProtection($identifier, $action = 'login') {
    $key = "bruteforce_{$action}_{$identifier}";
    $attempts = self::getRateLimitAttempts($key);
    
    // Progressive delays
    $delays = [0, 1, 2, 5, 10, 30, 60, 300, 600]; // seconds
    $delayIndex = min(count($delays) - 1, $attempts);
    $delay = $delays[$delayIndex];
    
    if ($delay > 0) {
        sleep($delay);
        self::logSecurityEvent(
            null,
            'BRUTE_FORCE_DELAY',
            "Applied {$delay}s delay after {$attempts} failed {$action} attempts",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
    }
    
    return true;
}

private static function getRateLimitAttempts($key) {
    $cacheFile = sys_get_temp_dir() . "/admin_bf_" . md5($key);
    
    if (!file_exists($cacheFile)) {
        return 0;
    }
    
    $data = json_decode(file_get_contents($cacheFile), true);
    if (!$data || !isset($data['attempts'], $data['last_attempt'])) {
        return 0;
    }
    
    // Reset if last attempt was more than 1 hour ago
    if (time() - $data['last_attempt'] > 3600) {
        unlink($cacheFile);
        return 0;
    }
    
    return $data['attempts'];
}

public static function recordFailedAttempt($identifier, $action = 'login') {
    $key = "bruteforce_{$action}_{$identifier}";
    $cacheFile = sys_get_temp_dir() . "/admin_bf_" . md5($key);
    
    $attempts = self::getRateLimitAttempts($key) + 1;
    
    $data = [
        'attempts' => $attempts,
        'last_attempt' => time()
    ];
    
    file_put_contents($cacheFile, json_encode($data));
}

public static function clearFailedAttempts($identifier, $action = 'login') {
    $key = "bruteforce_{$action}_{$identifier}";
    $cacheFile = sys_get_temp_dir() . "/admin_bf_" . md5($key);
    
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
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