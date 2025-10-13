<?php
// admin/controllers/Users.php
class AdminUsers {
    private $db;
    private $adminAuth;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    public function index() {
        $currentUser = $this->adminAuth->getCurrentUser();
        
        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUserAction();
        }
        
        // Get users with filters
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? 'all';
        
        $result = $this->getUsers($page, $limit, $search, $status);
        $users = $result['users'];
        $totalPages = $result['totalPages'];
        $totalRecords = $result['totalRecords'];
        
        // Get stats
        $stats = $this->getUserStats();
        
        $csrfToken = $_SESSION['csrf_token'];
        require_once 'views/users.view.php';
    }
    
    private function handleUserAction() {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/users');
            exit;
        }
        
        $action = $_POST['action'] ?? '';
        $currentUser = $this->adminAuth->getCurrentUser();
        
        switch ($action) {
            case 'suspend':
                $this->suspendUser((int)$_POST['user_id'], $currentUser['id']);
                break;
            case 'activate':
                $this->activateUser((int)$_POST['user_id'], $currentUser['id']);
                break;
            case 'delete':
                $this->deleteUser((int)$_POST['user_id'], $currentUser['id']);
                break;
            case 'revoke_permissions':
                $this->revokeAllPermissions((int)$_POST['user_id'], $currentUser['id']);
                break;
        }
        
        header('Location: /admin/users');
        exit;
    }
    
    private function getUsers($page, $limit, $search, $status) {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $whereClause .= " AND (name LIKE ? OR email LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($status !== 'all') {
                $whereClause .= " AND status = ?";
                $params[] = $status;
            }
            
            // Get users with permission counts
            $sql = "
                SELECT u.*, 
                       COUNT(DISTINCT sep.id) as total_requests,
                       SUM(CASE WHEN sep.status = 'approved' AND sep.expires_at > NOW() THEN 1 ELSE 0 END) as active_permissions
                FROM users u
                LEFT JOIN school_edit_permissions sep ON u.id = sep.user_id
                {$whereClause}
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge($params, [$limit, $offset]));
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM users {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalRecords / $limit);
            
            return [
                'users' => $users,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords
            ];
        } catch (PDOException $e) {
            error_log("Get users error: " . $e->getMessage());
            return ['users' => [], 'totalPages' => 1, 'totalRecords' => 0];
        }
    }
    
    private function suspendUser($userId, $adminId) {
        try {
            $this->db->beginTransaction();
            
            // Get user details
            $stmt = $this->db->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Suspend user
            $stmt = $this->db->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Revoke all active permissions
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET status = 'expired', expires_at = NOW() 
                WHERE user_id = ? AND status = 'approved'
            ");
            $stmt->execute([$userId]);
            
            // Log action
            AdminSecurity::logSecurityEvent(
                $adminId,
                'USER_SUSPENDED',
                "Suspended user: {$user['name']} ({$user['email']})",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $this->db->commit();
            $_SESSION['success'] = "User suspended successfully";
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Suspend user error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to suspend user";
        }
    }
    
    private function activateUser($userId, $adminId) {
        try {
            $stmt = $this->db->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $stmt = $this->db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$userId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'USER_ACTIVATED',
                "Activated user: {$user['name']} ({$user['email']})",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "User activated successfully";
        } catch (Exception $e) {
            error_log("Activate user error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to activate user";
        }
    }
    
    private function deleteUser($userId, $adminId) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Delete user (CASCADE will handle related records)
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'USER_DELETED',
                "Deleted user: {$user['name']} ({$user['email']})",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $this->db->commit();
            $_SESSION['success'] = "User deleted successfully";
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Delete user error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete user";
        }
    }
    
    private function revokeAllPermissions($userId, $adminId) {
        try {
            $stmt = $this->db->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET status = 'expired', expires_at = NOW() 
                WHERE user_id = ? AND status IN ('pending', 'approved', 'returned')
            ");
            $stmt->execute([$userId]);
            $affectedRows = $stmt->rowCount();
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'PERMISSIONS_REVOKED',
                "Revoked all permissions for user: {$user['name']} ({$affectedRows} permissions)",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "All permissions revoked successfully";
        } catch (Exception $e) {
            error_log("Revoke permissions error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to revoke permissions";
        }
    }
    
    private function getUserStats() {
        try {
            $stats = [];
            
            // Total users
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $stats['total'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active users
            $stmt = $this->db->prepare("SELECT COUNT(*) as active FROM users WHERE status = 'active'");
            $stmt->execute();
            $stats['active'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['active'];
            
            // Suspended users
            $stmt = $this->db->prepare("SELECT COUNT(*) as suspended FROM users WHERE status = 'suspended'");
            $stmt->execute();
            $stats['suspended'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['suspended'];
            
            // Users with active permissions
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT user_id) as with_permissions 
                FROM school_edit_permissions 
                WHERE status = 'approved' AND expires_at > NOW()
            ");
            $stmt->execute();
            $stats['with_permissions'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['with_permissions'];
            
            // New users this month
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as new_this_month 
                FROM users 
                WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                  AND YEAR(created_at) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute();
            $stats['new_this_month'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['new_this_month'];
            
            return $stats;
        } catch (PDOException $e) {
            error_log("User stats error: " . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'suspended' => 0, 'with_permissions' => 0, 'new_this_month' => 0];
        }
    }
}

$adminUsers = new AdminUsers($db, $adminAuth);
$adminUsers->index();