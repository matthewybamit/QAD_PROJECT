<?php
// models/AdminAuth.php
// Safe AdminAuth that expects to live in models/ and to use models/AdminSecurity.php

// include project security config if present
$configPath = __DIR__ . '/../config/security.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

// include AdminSecurity from models/
$securityPath = __DIR__ . '/AdminSecurity.php';
if (file_exists($securityPath)) {
    require_once $securityPath;
}

// If AdminSecurity still missing, we rely on the file above having declared it (guarded).
if (!class_exists('AdminAuth')) {

class AdminAuth {
    private $db;

    public function __construct($database = null) {
        if ($database instanceof PDO) {
            $this->db = $database;
        } elseif (!empty($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->db = $GLOBALS['pdo'];
        } else {
            $this->db = null;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    /**
     * Authenticate admin by email + password.
     * Returns admin row on success, false otherwise.
     */
    public function authenticateAdmin($email, $password) {
        $admin = $this->getAdminByEmail($email);
        if (!$admin) {
            if (class_exists('AdminSecurity')) {
                AdminSecurity::logSecurityEvent(null, 'ADMIN_LOGIN_FAILED', "Failed admin login attempt for unknown email: {$email}", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? '');
            }
            return false;
        }

        if ($admin['status'] === 'locked') {
            if (class_exists('AdminSecurity')) {
                AdminSecurity::logSecurityEvent($admin['id'], 'ADMIN_LOGIN_LOCKED', "Login attempt for locked admin account: {$email}", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? '');
            }
            return false;
        }

        if (class_exists('AdminSecurity') && AdminSecurity::isUserLocked($admin['id'])) {
            AdminSecurity::logSecurityEvent($admin['id'], 'ADMIN_LOGIN_ATTEMPTS_LOCKED', "Login attempt for temporarily locked account: {$email}", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? '');
            return false;
        }

        // verify password
        $storedHash = $admin['password_hash'] ?? ($admin['password'] ?? null);
        $ok = true;
        if (class_exists('AdminSecurity')) {
            $ok = AdminSecurity::verifyPassword($password, $storedHash);
        } else {
            // fallback simple check
            $ok = (!empty($storedHash) && hash_equals((string)$storedHash, (string)$password));
        }

        if (!$ok) {
            if (class_exists('AdminSecurity')) AdminSecurity::incrementFailedAttempts($admin['id'], $_SERVER['REMOTE_ADDR'] ?? null);
            return false;
        }

        if (class_exists('AdminSecurity')) AdminSecurity::resetFailedAttempts($admin['id']);

        // create session
        $this->createAdminSession($admin);

        if (class_exists('AdminSecurity')) {
            AdminSecurity::logSecurityEvent($admin['id'], 'ADMIN_LOGIN_SUCCESS', 'Successful admin login', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? '');
        }

        return $admin;
    }

    private function createAdminSession($admin) {
    // CRITICAL: Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['admin_user_id'] = $admin['id'];
    $_SESSION['admin_user_email'] = $admin['email'] ?? '';
    $_SESSION['admin_user_name'] = $admin['name'] ?? '';
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $this->storeAdminSession(session_id(), $admin['id']);
}

    private function storeAdminSession($sessionId, $userId) {
        try {
            if (!$this->db) return;
            $sessionTimeout = (class_exists('AdminSecurity') ? AdminSecurity::getSecurityConfig('session_timeout', 3600) : 3600);

            $stmt = $this->db->prepare("
                INSERT INTO admin_sessions (id, user_id, ip_address, user_agent, expires_at, created_at, last_activity) 
                VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                last_activity = NOW(),
                expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([
                $sessionId,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $sessionTimeout,
                $sessionTimeout
            ]);
        } catch (PDOException $e) {
            error_log("Failed to store admin session: " . $e->getMessage());
        }
    }

  public function validateSession() {
    if (!isset($_SESSION['admin_user_id'])) return false;

    $sessionTimeout = (class_exists('AdminSecurity') ? AdminSecurity::getSecurityConfig('session_timeout', 3600) : 3600);

    // Check session timeout
    if (time() - ($_SESSION['admin_last_activity'] ?? 0) > $sessionTimeout) {
        $this->logout();
        return false;
    }

    // Validate database session
    try {
        if ($this->db) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM admin_sessions WHERE id = ? AND user_id = ? AND expires_at > NOW()");
            $stmt->execute([session_id(), $_SESSION['admin_user_id']]);
            if ($stmt->fetchColumn() == 0) {
                $this->logout();
                return false;
            }
        }
    } catch (PDOException $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }

    // SECURITY: Check IP consistency (optional - can be disabled for mobile users)
    if (($_SESSION['admin_ip'] ?? '') !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
        if (class_exists('AdminSecurity')) {
            AdminSecurity::logSecurityEvent($_SESSION['admin_user_id'] ?? null, 'SESSION_IP_MISMATCH', "Session IP mismatch - potential session hijacking", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? '');
        }
        // Optional: Comment out these lines if you want to allow IP changes
        $this->logout();
        return false;
    }

    // SECURITY: Check User-Agent consistency (basic check)
    if (isset($_SESSION['admin_user_agent']) && $_SESSION['admin_user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        if (class_exists('AdminSecurity')) {
            AdminSecurity::logSecurityEvent($_SESSION['admin_user_id'] ?? null, 'SESSION_UA_MISMATCH', "User-Agent mismatch - potential session hijacking", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? '');
        }
        // This is less strict than IP check - just log it
    }

    $_SESSION['admin_last_activity'] = time();
    $this->updateSessionActivity();

    return true;
}
    private function updateSessionActivity() {
        try {
            if (!$this->db) return;
            $sessionTimeout = (class_exists('AdminSecurity') ? AdminSecurity::getSecurityConfig('session_timeout', 3600) : 3600);
            $stmt = $this->db->prepare("
                UPDATE admin_sessions 
                SET last_activity = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$sessionTimeout, session_id(), $_SESSION['admin_user_id']]);
        } catch (PDOException $e) {
            error_log("Failed to update session activity: " . $e->getMessage());
        }
    }

    public function logout() {
        if (isset($_SESSION['admin_user_id']) && class_exists('AdminSecurity')) {
            AdminSecurity::logSecurityEvent($_SESSION['admin_user_id'], 'ADMIN_LOGOUT', 'Admin logout', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? '');
        }

        if ($this->db) {
            try {
                $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE id = ?");
                $stmt->execute([session_id()]);
            } catch (PDOException $e) {
                error_log("Failed to remove session from database: " . $e->getMessage());
            }
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    private function getAdminByEmail($email) {
        try {
            if (!$this->db) return false;
            $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAdminByEmail: " . $e->getMessage());
            return false;
        }
    }

    public function getCurrentUser() {
        if (!$this->validateSession()) return null;
        return [
            'id' => $_SESSION['admin_user_id'],
            'email' => $_SESSION['admin_user_email'],
            'name' => $_SESSION['admin_user_name'],
            'role' => 'admin',
            'login_time' => $_SESSION['admin_login_time'],
            'last_activity' => $_SESSION['admin_last_activity']
        ];
    }

    public function extendSession() {
        if (!$this->validateSession()) return false;
        $_SESSION['admin_last_activity'] = time();
        $this->updateSessionActivity();
        if (class_exists('AdminSecurity')) AdminSecurity::logSecurityEvent($_SESSION['admin_user_id'], 'SESSION_EXTENDED', 'Admin session extended', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? '');
        return true;
    }

    public function getUserSessions($userId) {
        try {
            if (!$this->db) return [];
            $stmt = $this->db->prepare("SELECT * FROM admin_sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY last_activity DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user sessions: " . $e->getMessage());
            return [];
        }
    }

    public function terminateSession($sessionId) {
        try {
            if (!$this->db) return false;
            $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error terminating session: " . $e->getMessage());
            return false;
        }
    }
} // end AdminAuth

} // end class_exists guard
