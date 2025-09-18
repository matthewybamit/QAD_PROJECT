<?php
// admin/controllers/AdminDashboard.php
require_once '../../website/models/SchoolQuery.php';

class AdminDashboard {
    private $db;
    private $adminAuth;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
    }
    
    public function index() {
        $currentUser = $this->adminAuth->getCurrentUser();
        
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        $recentActivity = $this->getRecentActivity();
        $pendingPermissions = $this->getPendingPermissions();
        
        require_once 'views/dashboard.php';
    }
    
    private function getDashboardStats() {
        try {
            // Total users
            $stmt = $this->db->prepare("SELECT COUNT(*) as total_users FROM users");
            $stmt->execute();
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
            
            // Total schools
            $stmt = $this->db->prepare("SELECT COUNT(*) as total_schools FROM schools");
            $stmt->execute();
            $totalSchools = $stmt->fetch(PDO::FETCH_ASSOC)['total_schools'];
            
            // Pending permissions
            $stmt = $this->db->prepare("SELECT COUNT(*) as pending_permissions FROM school_edit_permissions WHERE status = 'pending'");
            $stmt->execute();
            $pendingPermissions = $stmt->fetch(PDO::FETCH_ASSOC)['pending_permissions'];
            
            // Active sessions
            $stmt = $this->db->prepare("SELECT COUNT(*) as active_sessions FROM admin_sessions WHERE expires_at > NOW()");
            $stmt->execute();
            $activeSessions = $stmt->fetch(PDO::FETCH_ASSOC)['active_sessions'];
            
            // Security incidents today
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as security_incidents 
                FROM security_logs 
                WHERE DATE(created_at) = CURDATE() 
                AND action IN ('FAILED_LOGIN', 'IP_BLOCKED', 'CSRF_VIOLATION')
            ");
            $stmt->execute();
            $securityIncidents = $stmt->fetch(PDO::FETCH_ASSOC)['security_incidents'];
            
            return [
                'total_users' => $totalUsers,
                'total_schools' => $totalSchools,
                'pending_permissions' => $pendingPermissions,
                'active_sessions' => $activeSessions,
                'security_incidents' => $securityIncidents
            ];
        } catch (PDOException $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getRecentActivity() {
        try {
            $stmt = $this->db->prepare("
                SELECT sl.*, u.name as user_name 
                FROM security_logs sl
                LEFT JOIN users u ON sl.user_id = u.id
                ORDER BY sl.created_at DESC 
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Recent activity error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getPendingPermissions() {
        try {
            $stmt = $this->db->prepare("
                SELECT sep.*, u.name as user_name, u.email, s.school_name
                FROM school_edit_permissions sep
                JOIN users u ON sep.user_id = u.id
                JOIN schools s ON sep.school_id = s.id
                WHERE sep.status = 'pending'
                ORDER BY sep.requested_at DESC
                LIMIT 5
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Pending permissions error: " . $e->getMessage());
            return [];
        }
    }
}

// Create and run dashboard
$dashboard = new AdminDashboard($db, $adminAuth);
$dashboard->index();

// admin/controllers/PermissionManager.php
class PermissionManager {
    private $db;
    private $adminAuth;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
    }
    
    public function index() {
        $currentUser = $this->adminAuth->getCurrentUser();
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePermissionAction();
        }
        
        // Get permission requests with filters
        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';
        $permissions = $this->getPermissionRequests($status, $search);
        
        require_once 'views/permissions.php';
    }
    
    private function handlePermissionAction() {
        $action = $_POST['action'] ?? '';
        $permissionId = (int)($_POST['permission_id'] ?? 0);
        $currentUser = $this->adminAuth->getCurrentUser();
        
        if (!AdminSecurity::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid security token';
            return;
        }
        
        switch ($action) {
            case 'approve':
                $this->approvePermission($permissionId, $currentUser['id']);
                break;
            case 'deny':
                $this->denyPermission($permissionId, $currentUser['id']);
                break;
            case 'extend':
                $hours = (int)($_POST['extend_hours'] ?? 24);
                $this->extendPermission($permissionId, $hours, $currentUser['id']);
                break;
            case 'revoke':
                $this->revokePermission($permissionId, $currentUser['id']);
                break;
        }
    }
    
    private function approvePermission($permissionId, $adminId) {
        try {
            $this->db->beginTransaction();
            
            // Get permission details
            $stmt = $this->db->prepare("
                SELECT sep.*, u.name as user_name, s.school_name 
                FROM school_edit_permissions sep
                JOIN users u ON sep.user_id = u.id
                JOIN schools s ON sep.school_id = s.id
                WHERE sep.id = ?
            ");
            $stmt->execute([$permissionId]);
            $permission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$permission || $permission['status'] !== 'pending') {
                throw new Exception('Invalid permission request');
            }
            
            // Approve permission (24 hours from now)
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET status = 'approved', 
                    approved_at = NOW(), 
                    expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR),
                    approved_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$adminId, $permissionId]);
            
            // Log the approval
            AdminSecurity::logSecurityEvent(
                $adminId,
                'PERMISSION_APPROVED',
                "Approved edit permission for {$permission['user_name']} on {$permission['school_name']}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $this->db->commit();
            $_SESSION['success'] = "Permission approved for {$permission['user_name']}";
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Permission approval error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to approve permission';
        }
    }
    
    private function denyPermission($permissionId, $adminId) {
        try {
            $this->db->beginTransaction();
            
            // Get permission details
            $stmt = $this->db->prepare("
                SELECT sep.*, u.name as user_name, s.school_name 
                FROM school_edit_permissions sep
                JOIN users u ON sep.user_id = u.id
                JOIN schools s ON sep.school_id = s.id
                WHERE sep.id = ?
            ");
            $stmt->execute([$permissionId]);
            $permission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$permission || $permission['status'] !== 'pending') {
                throw new Exception('Invalid permission request');
            }
            
            // Deny permission
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET status = 'denied', 
                    approved_at = NOW(), 
                    approved_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$adminId, $permissionId]);
            
            // Log the denial
            AdminSecurity::logSecurityEvent(
                $adminId,
                'PERMISSION_DENIED',
                "Denied edit permission for {$permission['user_name']} on {$permission['school_name']}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $this->db->commit();
            $_SESSION['success'] = "Permission denied for {$permission['user_name']}";
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Permission denial error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to deny permission';
        }
    }
    
    private function extendPermission($permissionId, $hours, $adminId) {
        try {
            $this->db->beginTransaction();
            
            // Get permission details
            $stmt = $this->db->prepare("
                SELECT sep.*, u.name as user_name, s.school_name 
                FROM school_edit_permissions sep
                JOIN users u ON sep.user_id = u.id
                JOIN schools s ON sep.school_id = s.id
                WHERE sep.id = ? AND sep.status = 'approved'
            ");
            $stmt->execute([$permissionId]);
            $permission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$permission) {
                throw new Exception('Invalid permission request');
            }
            
            // Extend permission
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET expires_at = DATE_ADD(GREATEST(expires_at, NOW()), INTERVAL ? HOUR)
                WHERE id = ?
            ");
            $stmt->execute([$hours, $permissionId]);
            
            // Log the extension
            AdminSecurity::logSecurityEvent(
                $adminId,
                'PERMISSION_EXTENDED',
                "Extended edit permission for {$permission['user_name']} on {$permission['school_name']} by {$hours} hours",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $this->db->commit();
            $_SESSION['success'] = "Permission extended by {$hours} hours for {$permission['user_name']}";
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Permission extension error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to extend permission';
        }
    }
    
    private function revokePermission($permissionId, $adminId) {
        try {
            $this->db->beginTransaction();
            
            // Get permission details
            $stmt = $this->db->prepare("
                SELECT sep.*, u.name as user_name, s.school_name 
                FROM school_edit_permissions sep
                JOIN users u ON sep.user_id = u.id
                JOIN schools s ON sep.school_id = s.id
                WHERE sep.id = ? AND sep.status = 'approved'
            ");
            $stmt->execute([$permissionId]);
            $permission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$permission) {
                throw new Exception('Invalid permission request');
            }
            
            // Revoke permission by setting expiry to now
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET expires_at = NOW(),
                    status = 'expired'
                WHERE id = ?
            ");
            $stmt->execute([$permissionId]);
            
            // Log the revocation
            AdminSecurity::logSecurityEvent(
                $adminId,
                'PERMISSION_REVOKED',
                "Revoked edit permission for {$permission['user_name']} on {$permission['school_name']}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $this->db->commit();
            $_SESSION['success'] = "Permission revoked for {$permission['user_name']}";
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Permission revocation error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to revoke permission';
        }
    }
    
    private function getPermissionRequests($status = 'all', $search = '') {
        try {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($status !== 'all') {
                $whereClause .= " AND sep.status = ?";
                $params[] = $status;
            }
            
            if (!empty($search)) {
                $whereClause .= " AND (u.name LIKE ? OR u.email LIKE ? OR s.school_name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->db->prepare("
                SELECT sep.*, u.name as user_name, u.email, u.avatar, s.school_name,
                       admin.name as approved_by_name
                FROM school_edit_permissions sep
                JOIN users u ON sep.user_id = u.id
                JOIN schools s ON sep.school_id = s.id
                LEFT JOIN users admin ON sep.approved_by = admin.id
                {$whereClause}
                ORDER BY 
                    CASE sep.status 
                        WHEN 'pending' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'denied' THEN 3
                        WHEN 'expired' THEN 4
                    END,
                    sep.requested_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Permission requests error: " . $e->getMessage());
            return [];
        }
    }
}

// Create and run permission manager
$permissionManager = new PermissionManager($db, $adminAuth);
$permissionManager->index();

// admin/controllers/SecurityManager.php
class SecurityManager {
    private $db;
    private $adminAuth;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
    }
    
    public function index() {
        $currentUser = $this->adminAuth->getCurrentUser();
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSecurityAction();
        }
        
        // Get security data
        $securityLogs = $this->getSecurityLogs();
        $suspiciousActivity = $this->getSuspiciousActivity();
        $ipWhitelist = $this->getIPWhitelist();
        $activeSessions = $this->getActiveSessions();
        
        require_once 'views/security.php';
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
        try {
            // Validate IP address
            if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                $_SESSION['error'] = 'Invalid IP address format';
                return;
            }
            
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
            if ($e->getCode() == 23000) { // Duplicate entry
                $_SESSION['error'] = 'IP address already in whitelist';
            } else {
                error_log("IP whitelist error: " . $e->getMessage());
                $_SESSION['error'] = 'Failed to add IP to whitelist';
            }
        }
    }
    
    private function removeIPFromWhitelist($ipId, $adminId) {
        try {
            // Get IP details before deletion
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
            // Get session details
            $stmt = $this->db->prepare("
                SELECT asess.*, u.name as user_name 
                FROM admin_sessions asess
                JOIN users u ON asess.user_id = u.id
                WHERE asess.id = ?
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                $_SESSION['error'] = 'Session not found';
                return;
            }
            
            // Delete session
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
                SELECT sl.*, u.name as user_name 
                FROM security_logs sl
                LEFT JOIN users u ON sl.user_id = u.id
                ORDER BY sl.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
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
                SELECT iw.*, u.name as created_by_name 
                FROM ip_whitelist iw
                JOIN users u ON iw.created_by = u.id
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
                SELECT asess.*, u.name as user_name 
                FROM admin_sessions asess
                JOIN users u ON asess.user_id = u.id
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

// admin/controllers/AdminLogin.php
class AdminLogin {
    private $db;
    private $adminAuth;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
    }
    
    public function index() {
        // Redirect if already logged in
        if ($this->adminAuth->validateSession()) {
            header('Location: /admin/dashboard');
            exit;
        }
        
        $error = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = $this->handleLogin();
        }
        
        $csrfToken = AdminSecurity::generateCSRFToken();
        require_once 'views/login.php';
    }
    
    private function handleLogin() {
        $email = AdminSecurity::sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Basic validation
        if (empty($email) || empty($password)) {
            return 'Please fill in all fields';
        }
        
        if (!AdminSecurity::validateEmail($email)) {
            return 'Invalid email format';
        }
        
        // Attempt authentication
        $user = $this->adminAuth->authenticateAdmin($email, $password);
        
        if ($user) {
            header('Location: /admin/dashboard');
            exit;
        } else {
            AdminSecurity::logSecurityEvent(
                null,
                'ADMIN_LOGIN_FAILED',
                "Failed admin login attempt for email: {$email}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            return 'Invalid credentials or account locked';
        }
    }
}

// Create and run admin login
$adminLogin = new AdminLogin($db, $adminAuth);
$adminLogin->index();


require_once 'views/dashboard.view.php';
?>