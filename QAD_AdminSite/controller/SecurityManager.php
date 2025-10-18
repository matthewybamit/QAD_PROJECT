<?php

// controllers/SecurityManager.php
class SecurityManager {
    private $db;
    private $adminAuth;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
    }
    
    public function index() {
        $currentUser = $this->adminAuth->getCurrentUser();

        // Ensure session started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate CSRF token ONCE
        if (!isset($_SESSION['csrf_token']) || time() - ($_SESSION['csrf_token_time'] ?? 0) > AdminSecurity::CSRF_TOKEN_EXPIRY) {
            $csrfToken = AdminSecurity::generateCSRFToken();
        } else {
            $csrfToken = $_SESSION['csrf_token'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSecurityAction();
        }

        // Get all security data
        $securityLogs = $this->getSecurityLogs();
        $suspiciousActivity = $this->getSuspiciousActivity();
        $ipWhitelist = $this->getIPWhitelist();
        $ipBlacklist = $this->getIPBlacklist();
        $activeSessions = $this->getActiveSessions();
        $failedLoginAttempts = $this->getFailedLoginAttempts();
        $rateLimitStats = $this->getRateLimitStats();
        $securityStats = $this->getSecurityStats();
        $recentBlocks = $this->getRecentBlocks();
        $lockedAccounts = $this->getLockedAccounts();

        require_once 'views/security.view.php';
    }

    
    private function handleSecurityAction() {
        $action = $_POST['action'] ?? '';
        $currentUser = $this->adminAuth->getCurrentUser();
        
        if (!AdminSecurity::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid security token';
            return;
        }
        
        switch ($action) {
            case 'add_ip_whitelist':
                $this->addIPToWhitelist($_POST['ip_address'] ?? '', $_POST['description'] ?? '', $currentUser['id']);
                break;
            case 'remove_ip_whitelist':
                $this->removeIPFromWhitelist((int)($_POST['ip_id'] ?? 0), $currentUser['id']);
                break;
            case 'add_ip_blacklist':
                $this->addIPToBlacklist($_POST['ip_address'] ?? '', $_POST['reason'] ?? '', $currentUser['id']);
                break;
            case 'remove_ip_blacklist':
                $this->removeIPFromBlacklist((int)($_POST['ip_id'] ?? 0), $currentUser['id']);
                break;
            case 'terminate_session':
                $this->terminateSession($_POST['session_id'] ?? '', $currentUser['id']);
                break;
            case 'unlock_account':
                $this->unlockAccount((int)($_POST['user_id'] ?? 0), $currentUser['id']);
                break;
            case 'clear_failed_attempts':
                $this->clearFailedAttempts((int)($_POST['user_id'] ?? 0), $currentUser['id']);
                break;
            case 'export_logs':
                $this->exportSecurityLogs($_POST['format'] ?? 'csv', $_POST['days'] ?? 7);
                break;
        }
    }
    
    // ===== IP WHITELIST =====
    private function addIPToWhitelist($ipAddress, $description, $adminId) {
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $_SESSION['error'] = 'Invalid IP address format';
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ip_whitelist (ip_address, description, created_by) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$ipAddress, $description, $adminId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'IP_WHITELISTED',
                "Added IP {$ipAddress} to whitelist: {$description}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "IP address {$ipAddress} added to whitelist";
        } catch (PDOException $e) {
            $_SESSION['error'] = $e->getCode() == 23000 ? 
                'IP address already in whitelist' : 'Failed to add IP to whitelist';
            error_log("IP whitelist error: " . $e->getMessage());
        }
    }
    
    private function removeIPFromWhitelist($ipId, $adminId) {
        try {
            $stmt = $this->db->prepare("SELECT ip_address FROM ip_whitelist WHERE id = ?");
            $stmt->execute([$ipId]);
            $ip = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ip) {
                $_SESSION['error'] = 'IP address not found';
                return;
            }
            
            $stmt = $this->db->prepare("DELETE FROM ip_whitelist WHERE id = ?");
            $stmt->execute([$ipId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'IP_REMOVED',
                "Removed IP {$ip['ip_address']} from whitelist",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "IP address {$ip['ip_address']} removed from whitelist";
        } catch (PDOException $e) {
            error_log("IP removal error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to remove IP from whitelist';
        }
    }

    // ===== IP BLACKLIST =====
    private function addIPToBlacklist($ipAddress, $reason, $adminId) {
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $_SESSION['error'] = 'Invalid IP address format';
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ip_blacklist (ip_address, reason, blocked_by, blocked_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$ipAddress, $reason, $adminId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'IP_BLACKLISTED',
                "Added IP {$ipAddress} to blacklist: {$reason}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "IP address {$ipAddress} added to blacklist";
        } catch (PDOException $e) {
            $_SESSION['error'] = $e->getCode() == 23000 ? 
                'IP address already in blacklist' : 'Failed to add IP to blacklist';
            error_log("IP blacklist error: " . $e->getMessage());
        }
    }
    
    private function removeIPFromBlacklist($ipId, $adminId) {
        try {
            $stmt = $this->db->prepare("SELECT ip_address FROM ip_blacklist WHERE id = ?");
            $stmt->execute([$ipId]);
            $ip = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ip) {
                $_SESSION['error'] = 'IP address not found';
                return;
            }
            
            $stmt = $this->db->prepare("DELETE FROM ip_blacklist WHERE id = ?");
            $stmt->execute([$ipId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'IP_UNBLOCKED',
                "Removed IP {$ip['ip_address']} from blacklist",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "IP address {$ip['ip_address']} removed from blacklist";
        } catch (PDOException $e) {
            error_log("IP unblock error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to remove IP from blacklist';
        }
    }
    
    // ===== SESSION MANAGEMENT =====
    private function terminateSession($sessionId, $adminId) {
        try {
            $stmt = $this->db->prepare("
                SELECT asess.*, au.name as user_name 
                FROM admin_sessions asess
                JOIN admin_users au ON asess.user_id = au.id
                WHERE asess.id = ?
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                $_SESSION['error'] = 'Session not found';
                return;
            }
            
            $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'SESSION_TERMINATED',
                "Terminated session for {$session['user_name']} from {$session['ip_address']}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "Session terminated for {$session['user_name']}";
        } catch (PDOException $e) {
            error_log("Session termination error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to terminate session';
        }
    }

    // ===== ACCOUNT MANAGEMENT =====
    private function unlockAccount($userId, $adminId) {
        try {
            $stmt = $this->db->prepare("SELECT name, email FROM admin_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $_SESSION['error'] = 'User not found';
                return;
            }
            
            // Remove from lockouts table
            $stmt = $this->db->prepare("DELETE FROM admin_lockouts WHERE admin_user_id = ?");
            $stmt->execute([$userId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'ACCOUNT_UNLOCKED',
                "Manually unlocked account for {$user['name']} ({$user['email']})",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "Account unlocked for {$user['name']}";
        } catch (PDOException $e) {
            error_log("Account unlock error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to unlock account';
        }
    }

    private function clearFailedAttempts($userId, $adminId) {
        try {
            $stmt = $this->db->prepare("SELECT name, email FROM admin_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $_SESSION['error'] = 'User not found';
                return;
            }
            
            $stmt = $this->db->prepare("
                UPDATE admin_lockouts 
                SET failed_attempts = 0, locked_until = NULL 
                WHERE admin_user_id = ?
            ");
            $stmt->execute([$userId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'FAILED_ATTEMPTS_CLEARED',
                "Cleared failed login attempts for {$user['name']} ({$user['email']})",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "Failed attempts cleared for {$user['name']}";
        } catch (PDOException $e) {
            error_log("Clear attempts error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to clear failed attempts';
        }
    }

    // ===== DATA RETRIEVAL =====
    private function getSecurityLogs($limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT sl.*, au.name as user_name 
                FROM admin_security_logs sl
                LEFT JOIN admin_users au ON sl.admin_user_id = au.id
                ORDER BY sl.created_at DESC 
                LIMIT ?
            ");
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Security logs error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getSuspiciousActivity() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as incident_count,
                    MAX(created_at) as last_incident,
                    GROUP_CONCAT(DISTINCT action SEPARATOR ', ') as actions
                FROM admin_security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND (
                    action LIKE '%FAILED%' 
                    OR action LIKE '%BLOCKED%' 
                    OR action LIKE '%VIOLATION%' 
                    OR action LIKE '%EXCEEDED%'
                    OR action LIKE '%LOCKED%'
                )
                GROUP BY ip_address
                HAVING incident_count >= 2
                ORDER BY incident_count DESC, last_incident DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Suspicious activity error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getIPWhitelist() {
        try {
            $stmt = $this->db->prepare("
                SELECT iw.*, au.name as created_by_name 
                FROM ip_whitelist iw
                LEFT JOIN admin_users au ON iw.created_by = au.id
                ORDER BY iw.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("IP whitelist error: " . $e->getMessage());
            return [];
        }
    }

    private function getIPBlacklist() {
        try {
            $stmt = $this->db->prepare("
                SELECT ib.*, au.name as blocked_by_name 
                FROM ip_blacklist ib
                LEFT JOIN admin_users au ON ib.blocked_by = au.id
                ORDER BY ib.blocked_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("IP blacklist error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getActiveSessions() {
        try {
            $stmt = $this->db->prepare("
                SELECT asess.*, au.name as user_name, au.email 
                FROM admin_sessions asess
                JOIN admin_users au ON asess.user_id = au.id
                WHERE asess.expires_at > NOW()
                ORDER BY asess.last_activity DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Active sessions error: " . $e->getMessage());
            return [];
        }
    }

    private function getFailedLoginAttempts() {
        try {
            $stmt = $this->db->prepare("
                SELECT al.*, au.name, au.email, au.status
                FROM admin_lockouts al
                JOIN admin_users au ON al.admin_user_id = au.id
                WHERE al.failed_attempts > 0
                ORDER BY al.last_attempt DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed login attempts error: " . $e->getMessage());
            return [];
        }
    }

    private function getRateLimitStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as hits,
                    MAX(created_at) as last_hit
                FROM admin_security_logs 
                WHERE action LIKE '%RATE_LIMIT%'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY ip_address
                ORDER BY hits DESC
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Rate limit stats error: " . $e->getMessage());
            return [];
        }
    }

    private function getSecurityStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN action LIKE '%LOGIN_SUCCESS%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as logins_24h,
                    COUNT(CASE WHEN action LIKE '%LOGIN_FAILED%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as failed_logins_24h,
                    COUNT(CASE WHEN action LIKE '%CSRF%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as csrf_violations_24h,
                    COUNT(CASE WHEN action LIKE '%RATE_LIMIT%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as rate_limits_24h,
                    COUNT(CASE WHEN action LIKE '%SESSION_TERMINATED%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as terminated_sessions_24h
                FROM admin_security_logs
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Security stats error: " . $e->getMessage());
            return [
                'logins_24h' => 0,
                'failed_logins_24h' => 0,
                'csrf_violations_24h' => 0,
                'rate_limits_24h' => 0,
                'terminated_sessions_24h' => 0
            ];
        }
    }

    private function getRecentBlocks() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action,
                    ip_address,
                    details,
                    created_at
                FROM admin_security_logs 
                WHERE (
                    action LIKE '%BLOCKED%' 
                    OR action LIKE '%BLACKLISTED%' 
                    OR action LIKE '%LOCKED%'
                )
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Recent blocks error: " . $e->getMessage());
            return [];
        }
    }

    private function getLockedAccounts() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    au.id,
                    au.name,
                    au.email,
                    al.failed_attempts,
                    al.locked_until,
                    al.last_attempt
                FROM admin_users au
                JOIN admin_lockouts al ON au.id = al.admin_user_id
                WHERE al.locked_until > NOW()
                ORDER BY al.locked_until DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Locked accounts error: " . $e->getMessage());
            return [];
        }
    }

    // ===== EXPORT FUNCTIONALITY =====
    private function exportSecurityLogs($format = 'csv', $days = 7) {
        try {
            $stmt = $this->db->prepare("
                SELECT sl.*, au.name as user_name 
                FROM admin_security_logs sl
                LEFT JOIN admin_users au ON sl.admin_user_id = au.id
                WHERE sl.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY sl.created_at DESC
            ");
            $stmt->execute([$days]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="security_logs_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Time', 'User', 'Action', 'Details', 'IP Address', 'User Agent']);
                
                foreach ($logs as $log) {
                    fputcsv($output, [
                        $log['created_at'],
                        $log['user_name'] ?? 'System',
                        $log['action'],
                        $log['details'],
                        $log['ip_address'],
                        $log['user_agent']
                    ]);
                }
                
                fclose($output);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Export logs error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to export logs';
        }
    }
}

// Create and run security manager
$securityManager = new SecurityManager($db, $adminAuth);
$securityManager->index();