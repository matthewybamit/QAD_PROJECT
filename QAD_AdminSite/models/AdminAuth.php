<?php
// models/AdminAuth.php
// Fixed: uses admin_users table (separate from users), preserves all features.

require_once __DIR__ . '/../config/security.php';

class AdminAuth {
    private $db;

    public function __construct(PDO $database) {
        $this->db = $database; // PDO connection expected (admin_db.php should provide $db as PDO)
    }

    /**
     * Authenticate admin by email + password.
     * Returns user array on success, false on failure.
     */
    public function authenticateAdmin($email, $password) {
        $user = $this->getUserByEmail($email);

        if (!$user) {
            // Unknown admin email
            AdminSecurity::logSecurityEvent(
                null,
                'ADMIN_LOGIN_FAILED',
                "Failed admin login attempt for unknown email: {$email}",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            return false;
        }

        // Check lockout table (shared logic)
        if (AdminSecurity::isUserLocked($user['id'])) {
            AdminSecurity::logSecurityEvent(
                $user['id'],
                'ADMIN_LOGIN_LOCKED',
                "Login attempt for locked admin account: {$email}",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            return false;
        }

        // Check admin account status (admin_users.status)
        if (isset($user['status']) && $user['status'] !== 'active') {
            AdminSecurity::incrementFailedAttempts($user['id'], $_SERVER['REMOTE_ADDR'] ?? null);
            AdminSecurity::logSecurityEvent(
                $user['id'],
                'ADMIN_LOGIN_INACTIVE',
                "Attempt to login to inactive/locked admin: {$email}",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            return false;
        }

        // Verify password
        if (empty($user['password_hash'])) {
            AdminSecurity::logSecurityEvent(
                $user['id'],
                'ADMIN_LOGIN_NO_PASSWORD',
                "Admin user has no password hash: {$email}",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            return false;
        }

        $passwordValid = AdminSecurity::verifyPassword($password, $user['password_hash']);
        if (!$passwordValid) {
            AdminSecurity::incrementFailedAttempts($user['id'], $_SERVER['REMOTE_ADDR'] ?? null);
            AdminSecurity::logSecurityEvent(
                $user['id'],
                'ADMIN_LOGIN_FAILED_WRONG_PASS',
                "Invalid password attempt for admin: {$email}",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            return false;
        }

        // Successful login: reset attempts, create session and log
        AdminSecurity::resetFailedAttempts($user['id']);
        $this->createAdminSession($user);

        AdminSecurity::logSecurityEvent(
            $user['id'],
            'ADMIN_LOGIN_SUCCESS',
            'Successful admin login',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return $user;
    }

    /**
     * Create secure session values and persist session row.
     */
    private function createAdminSession(array $user) {
        session_regenerate_id(true);

        $_SESSION['admin_user_id']      = $user['id'];
        $_SESSION['admin_user_email']   = $user['email'];
        $_SESSION['admin_user_name']    = $user['name'] ?? null;
        $_SESSION['admin_login_time']   = time();
        $_SESSION['admin_last_activity']= time();
        $_SESSION['admin_ip']          = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SESSION['admin_user_agent']  = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Persist session to DB (storeAdminSession handles exceptions)
        $this->storeAdminSession(session_id(), $user['id']);
    }

    /**
     * Persist or update admin session row.
     */
    private function storeAdminSession($sessionId, $userId) {
        try {
            $sessionTimeout = AdminSecurity::getSecurityConfig('session_timeout') 
                              ?? AdminSecurity::SESSION_TIMEOUT;

            $sql = "
                INSERT INTO admin_sessions 
                    (id, user_id, ip_address, user_agent, expires_at, created_at, last_activity)
                VALUES
                    (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    last_activity = NOW(),
                    expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $sessionId,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $sessionTimeout,
                $sessionTimeout
            ]);
        } catch (PDOException $e) {
            // don't expose DB errors to user — log for debugging
            error_log("Failed to store admin session: " . $e->getMessage());
        }
    }

    /**
     * Validate session: session existence, timeout, DB row, IP check, update activity.
     */
    public function validateSession() {
        if (empty($_SESSION['admin_user_id'])) {
            return false;
        }

        $sessionTimeout = AdminSecurity::getSecurityConfig('session_timeout') 
                          ?? AdminSecurity::SESSION_TIMEOUT;

        // session inactivity check
        if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > $sessionTimeout)) {
            $this->logout();
            return false;
        }

        // verify session row exists and not expired
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM admin_sessions
                WHERE id = ? AND user_id = ? AND expires_at > NOW()
            ");
            $stmt->execute([session_id(), $_SESSION['admin_user_id']]);
            if ($stmt->fetchColumn() == 0) {
                $this->logout();
                return false;
            }
        } catch (PDOException $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }

        // optional IP match check (detect hijack)
        if (isset($_SESSION['admin_ip']) && ($_SESSION['admin_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? null))) {
            AdminSecurity::logSecurityEvent(
                $_SESSION['admin_user_id'],
                'SESSION_IP_MISMATCH',
                "Session IP mismatch: {$_SESSION['admin_ip']} vs {$_SERVER['REMOTE_ADDR']}",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            $this->logout();
            return false;
        }

        // update activity timestamps
        $_SESSION['admin_last_activity'] = time();
        $this->updateSessionActivity();

        return true;
    }

    /**
     * Update last activity & expiry in DB.
     */
    private function updateSessionActivity() {
        try {
            $sessionTimeout = AdminSecurity::getSecurityConfig('session_timeout') 
                              ?? AdminSecurity::SESSION_TIMEOUT;

            $stmt = $this->db->prepare("
                UPDATE admin_sessions
                SET last_activity = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $sessionTimeout,
                session_id(),
                $_SESSION['admin_user_id']
            ]);
        } catch (PDOException $e) {
            error_log("Failed to update session activity: " . $e->getMessage());
        }
    }

    /**
     * Logout: remove DB session row, clear PHP session and cookie.
     */
    public function logout() {
        if (!empty($_SESSION['admin_user_id'])) {
            AdminSecurity::logSecurityEvent(
                $_SESSION['admin_user_id'],
                'ADMIN_LOGOUT',
                'Admin logout',
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            try {
                $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE id = ?");
                $stmt->execute([session_id()]);
            } catch (PDOException $e) {
                error_log("Failed to remove session from database: " . $e->getMessage());
            }
        }

        // clear session array
        $_SESSION = [];

        // clear cookie if used
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"] ?? '',
                $params["secure"] ?? false,
                $params["httponly"] ?? false
            );
        }

        // destroy session
        session_destroy();
    }

    /**
     * Fetch admin user by email from admin_users table.
     */
    private function getUserByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getUserByEmail: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Return current admin user info (from session).
     */
    public function getCurrentUser() {
        if (!$this->validateSession()) {
            return null;
        }

        return [
            'id' => $_SESSION['admin_user_id'],
            'email' => $_SESSION['admin_user_email'],
            'name' => $_SESSION['admin_user_name'],
            'role' => 'admin',
            'login_time' => $_SESSION['admin_login_time'] ?? null,
            'last_activity' => $_SESSION['admin_last_activity'] ?? null
        ];
    }

    /**
     * Extend session activity (keep-alive).
     */
    public function extendSession() {
        if (!$this->validateSession()) {
            return false;
        }

        $_SESSION['admin_last_activity'] = time();
        $this->updateSessionActivity();

        AdminSecurity::logSecurityEvent(
            $_SESSION['admin_user_id'],
            'SESSION_EXTENDED',
            'Admin session extended',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return true;
    }

    /**
     * Return active sessions for a user.
     */
    public function getUserSessions($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM admin_sessions
                WHERE user_id = ? AND expires_at > NOW()
                ORDER BY last_activity DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user sessions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Terminate a specific session by id.
     */
    public function terminateSession($sessionId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error terminating session: " . $e->getMessage());
            return false;
        }
    }
}
?>
