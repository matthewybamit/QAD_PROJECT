<?php
// controllers/Dashboard.php - Simple debug to find the issue
require_once '../website/models/SchoolQuery.php';

class AdminDashboard {
    private $db;
    private $adminAuth;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
    }
    
    public function index() {
        $currentUser = $this->adminAuth->getCurrentUser();
        
        $stats = $this->getDashboardStats();
        $recentActivity = $this->getRecentActivity();
        $pendingPermissions = $this->getPendingPermissions();
        
        // Debug: Log what we're passing to the view
        error_log("=== DASHBOARD DEBUG OUTPUT ===");
        error_log("Stats: " . json_encode($stats));
        error_log("Recent Activity Count: " . count($recentActivity));
        error_log("Recent Activity Data: " . json_encode($recentActivity));
        error_log("Pending Permissions Count: " . count($pendingPermissions));
        error_log("===============================");
        
        require_once 'views/dashboard.view.php';
    }
    
    private function getDashboardStats() {
        try {
            // Total application users
            $stmt = $this->db->prepare("SELECT COUNT(*) as total_users FROM users");
            $stmt->execute();
            $totalUsers = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

            // Total schools
            $stmt = $this->db->prepare("SELECT COUNT(*) as total_schools FROM schools");
            $stmt->execute();
            $totalSchools = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_schools'];

            // Pending edit requests
            $stmt = $this->db->prepare("SELECT COUNT(*) as pending_permissions FROM school_edit_permissions WHERE status = 'pending'");
            $stmt->execute();
            $pendingPermissions = (int)$stmt->fetch(PDO::FETCH_ASSOC)['pending_permissions'];

            // Active admin sessions
            $stmt = $this->db->prepare("SELECT COUNT(*) as active_sessions FROM admin_sessions WHERE expires_at > NOW()");
            $stmt->execute();
            $activeSessions = (int)$stmt->fetch(PDO::FETCH_ASSOC)['active_sessions'];

            // Security incidents today
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as security_incidents 
                FROM admin_security_logs 
                WHERE DATE(created_at) = CURDATE() 
                  AND action IN ('FAILED_LOGIN', 'IP_BLOCKED', 'CSRF_VIOLATION')
            ");
            $stmt->execute();
            $securityIncidents = (int)$stmt->fetch(PDO::FETCH_ASSOC)['security_incidents'];

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
    
    private function getRecentActivity($limit = 10) {
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
            error_log("Dashboard recent activity error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getPendingPermissions($limit = 5) {
        try {
            $sql = "
                SELECT sep.*, au.name as user_name, au.email as user_email, s.school_name
                FROM school_edit_permissions sep
                JOIN admin_users au ON sep.user_id = au.id
                JOIN schools s ON sep.school_id = s.id
                WHERE sep.status = 'pending'
                ORDER BY sep.requested_at DESC
                LIMIT ?
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Dashboard pending permissions error: " . $e->getMessage());
            return [];
        }
    }
}

// Test environment
error_log("Dashboard: Starting dashboard execution");
error_log("Dashboard: Database available: " . (isset($db) ? 'YES' : 'NO'));
error_log("Dashboard: AdminAuth available: " . (isset($adminAuth) ? 'YES' : 'NO'));

// Create and run dashboard
$dashboard = new AdminDashboard($db, $adminAuth);
$dashboard->index();

error_log("Dashboard: Execution completed");
?>