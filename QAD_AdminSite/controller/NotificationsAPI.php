<?php
// controllers/NotificationsAPI.php
class NotificationsAPI {
    private $db;
    private $adminAuth;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
    }
    
    public function handle() {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!$this->adminAuth->validateSession()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_SERVER['REQUEST_URI'];
        
        try {
            if ($method === 'POST' && str_contains($path, '/mark-read')) {
                $this->markAllAsRead();
            } elseif ($method === 'GET' && str_contains($path, '/notifications')) {
                $this->getNotifications();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
        } catch (Exception $e) {
            error_log("Notifications API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
    
    private function markAllAsRead() {
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!AdminSecurity::validateCSRFToken($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF validation failed']);
            return;
        }
        
        $currentUser = $this->adminAuth->getCurrentUser();
        
        try {
            // Mark notifications as read in session (temporary solution)
            $_SESSION['notifications_read_at'] = time();
            
            // Log the action
            AdminSecurity::logSecurityEvent(
                $currentUser['id'],
                'NOTIFICATIONS_MARKED_READ',
                'Admin marked all notifications as read',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
            
        } catch (Exception $e) {
            error_log("Mark notifications read error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to mark notifications as read']);
        }
    }
    
    private function getNotifications() {
        try {
            $notifications = [];
            $currentUser = $this->adminAuth->getCurrentUser();
            
            // Get current stats
            $stats = $this->getDashboardStats();
            
            // Security incidents
            if ($stats['security_incidents'] > 0) {
                $notifications[] = [
                    'id' => 'security_incidents',
                    'type' => 'security',
                    'title' => 'Security Incidents',
                    'message' => $stats['security_incidents'] . ' security incident' . ($stats['security_incidents'] > 1 ? 's' : '') . ' detected today',
                    'icon' => 'fas fa-exclamation-triangle',
                    'color' => 'red',
                    'url' => '/admin/security',
                    'timestamp' => time()
                ];
            }
            
            // Pending permissions
            if ($stats['pending_permissions'] > 0) {
                $notifications[] = [
                    'id' => 'pending_permissions',
                    'type' => 'permission',
                    'title' => 'Pending Permissions',
                    'message' => $stats['pending_permissions'] . ' permission request' . ($stats['pending_permissions'] > 1 ? 's' : '') . ' awaiting approval',
                    'icon' => 'fas fa-key',
                    'color' => 'blue',
                    'url' => '/admin/permissions',
                    'timestamp' => time()
                ];
            }
            
            // System notifications
            if ($stats['active_sessions'] > 5) {
                $notifications[] = [
                    'id' => 'high_sessions',
                    'type' => 'system',
                    'title' => 'High Session Count',
                    'message' => 'There are ' . $stats['active_sessions'] . ' active admin sessions',
                    'icon' => 'fas fa-users',
                    'color' => 'yellow',
                    'url' => '/admin/security#sessions',
                    'timestamp' => time()
                ];
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'total_count' => count($notifications),
                'unread_count' => $this->getUnreadCount($notifications)
            ]);
            
        } catch (Exception $e) {
            error_log("Get notifications error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch notifications']);
        }
    }
    
    private function getDashboardStats() {
        try {
            // Security incidents today
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as security_incidents 
                FROM admin_security_logs 
                WHERE DATE(created_at) = CURDATE() 
                  AND action IN ('FAILED_LOGIN', 'IP_BLOCKED', 'CSRF_VIOLATION', 'RATE_LIMIT_EXCEEDED')
            ");
            $stmt->execute();
            $securityIncidents = (int)$stmt->fetch(PDO::FETCH_ASSOC)['security_incidents'];

            // Pending permissions
            $stmt = $this->db->prepare("SELECT COUNT(*) as pending_permissions FROM school_edit_permissions WHERE status = 'pending'");
            $stmt->execute();
            $pendingPermissions = (int)$stmt->fetch(PDO::FETCH_ASSOC)['pending_permissions'];

            // Active sessions
            $stmt = $this->db->prepare("SELECT COUNT(*) as active_sessions FROM admin_sessions WHERE expires_at > NOW()");
            $stmt->execute();
            $activeSessions = (int)$stmt->fetch(PDO::FETCH_ASSOC)['active_sessions'];

            return [
                'security_incidents' => $securityIncidents,
                'pending_permissions' => $pendingPermissions,
                'active_sessions' => $activeSessions
            ];
        } catch (PDOException $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return [
                'security_incidents' => 0,
                'pending_permissions' => 0,
                'active_sessions' => 0
            ];
        }
    }
    
    private function getUnreadCount($notifications) {
        $readAt = $_SESSION['notifications_read_at'] ?? 0;
        $unreadCount = 0;
        
        foreach ($notifications as $notification) {
            if ($notification['timestamp'] > $readAt) {
                $unreadCount++;
            }
        }
        
        return $unreadCount;
    }
}

// Handle notifications API request
$notificationsAPI = new NotificationsAPI($db, $adminAuth);
$notificationsAPI->handle();