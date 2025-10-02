<?php 
// controllers/Permission.php
class PermissionManager {
    private $db;
    private $adminAuth;
    private $csrfToken;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;

        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) || 
            (time() - $_SESSION['csrf_token_time']) > AdminSecurity::CSRF_TOKEN_EXPIRY) {
            AdminSecurity::generateCSRFToken();
        }

        $this->csrfToken = $_SESSION['csrf_token'];
    }
    
    public function index() {
        $currentUser = $this->adminAuth->getCurrentUser();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePermissionAction();
        }
        
        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';
        $permissions = $this->getPermissionRequests($status, $search);

        $csrfToken = $this->csrfToken;
        
        require_once 'views/permissions.view.php';
    }
    
    private function handlePermissionAction() {
        $action = $_POST['action'] ?? '';
        $permissionId = (int)($_POST['permission_id'] ?? 0);
        $currentUser = $this->adminAuth->getCurrentUser();

        if (!AdminSecurity::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid security token';
            $this->redirectAfterPost();
            return;
        }

        switch ($action) {
            case 'approve':
                $this->approvePermission($permissionId, $currentUser['id']);
                break;
            case 'return':
                $remarks = trim($_POST['admin_remarks'] ?? '');
                $this->returnPermission($permissionId, $currentUser['id'], $remarks);
                break;
            case 'deny':
                $remarks = trim($_POST['admin_remarks'] ?? '');
                $this->denyPermission($permissionId, $currentUser['id'], $remarks);
                break;
            case 'extend':
                $hours = (int)($_POST['extend_hours'] ?? 24);
                $this->extendPermission($permissionId, $hours, $currentUser['id']);
                break;
            case 'revoke':
                $this->revokePermission($permissionId, $currentUser['id']);
                break;
        }

        $this->redirectAfterPost();
    }

    private function redirectAfterPost() {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    private function approvePermission($permissionId, $adminId) {
        try {
            $this->db->beginTransaction();
            
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
            
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET status = 'approved', 
                    approved_at = NOW(), 
                    expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR),
                    approved_by = ?,
                    admin_remarks = NULL
                WHERE id = ?
            ");
            $stmt->execute([$adminId, $permissionId]);
            
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
    
    private function returnPermission($permissionId, $adminId, $remarks) {
        try {
            if (empty($remarks)) {
                throw new Exception('Please provide remarks explaining why the request is being returned');
            }

            $this->db->beginTransaction();
            
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
            
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET status = 'returned', 
                    approved_at = NOW(), 
                    approved_by = ?,
                    admin_remarks = ?
                WHERE id = ?
            ");
            $stmt->execute([$adminId, $remarks, $permissionId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'PERMISSION_RETURNED',
                "Returned edit permission for {$permission['user_name']} on {$permission['school_name']}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $this->db->commit();
            $_SESSION['success'] = "Request returned to {$permission['user_name']} with feedback";
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Permission return error: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }
    }
    
    private function denyPermission($permissionId, $adminId, $remarks) {
        try {
            $this->db->beginTransaction();
            
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
            
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET status = 'denied', 
                    approved_at = NOW(), 
                    approved_by = ?,
                    admin_remarks = ?
                WHERE id = ?
            ");
            $stmt->execute([$adminId, $remarks, $permissionId]);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'PERMISSION_DENIED',
                "Permanently denied edit permission for {$permission['user_name']} on {$permission['school_name']}",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $this->db->commit();
            $_SESSION['success'] = "Request permanently denied for {$permission['user_name']}";
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Permission denial error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to deny permission';
        }
    }
    
    private function extendPermission($permissionId, $hours, $adminId) {
        try {
            $this->db->beginTransaction();
            
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
            
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET expires_at = DATE_ADD(GREATEST(expires_at, NOW()), INTERVAL ? HOUR)
                WHERE id = ?
            ");
            $stmt->execute([$hours, $permissionId]);
            
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
            
            $stmt = $this->db->prepare("
                UPDATE school_edit_permissions 
                SET expires_at = NOW(),
                    status = 'expired'
                WHERE id = ?
            ");
            $stmt->execute([$permissionId]);
            
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
                        WHEN 'returned' THEN 2
                        WHEN 'approved' THEN 3
                        WHEN 'denied' THEN 4
                        WHEN 'expired' THEN 5
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

$permissionManager = new PermissionManager($db, $adminAuth);
$permissionManager->index();