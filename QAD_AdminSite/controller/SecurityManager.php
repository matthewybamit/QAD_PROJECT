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
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSecurityAction();
        }
        
        $securityLogs = $this->getSecurityLogs();
        $suspiciousActivity = $this->getSuspiciousActivity();
        $ipWhitelist = $this->getIPWhitelist();
        $activeSessions = $this->getActiveSessions();
        
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
            case 'add_ip':
                $this->addIPToWhitelist($_POST['ip_address'] ?? '', $_POST['description'] ?? '', $currentUser['id']);
                break;
            case 'remove_ip':
                $this->removeIPFromWhitelist((int)($_POST['ip_id'] ?? 0), $currentUser['id']);
                break;
            case 'terminate_session':
                $this->terminateSession($_POST['session_id'] ?? '', $currentUser['id']);
                break;
        }
    }
    
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
                    GROUP_CONCAT(DISTINCT action) as actions
                FROM security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND action IN ('FAILED_LOGIN', 'IP_BLOCKED', 'CSRF_VIOLATION', 'RATE_LIMIT_EXCEEDED')
                GROUP BY ip_address
                HAVING incident_count >= 3
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
                JOIN admin_users au ON iw.created_by = au.id
                ORDER BY iw.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("IP whitelist error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getActiveSessions() {
        try {
            $stmt = $this->db->prepare("
                SELECT asess.*, au.name as user_name 
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
}

// Create and run security manager
$securityManager = new SecurityManager($db, $adminAuth);
$securityManager->index();
