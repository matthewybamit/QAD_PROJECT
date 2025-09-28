<?php
// controllers/Logout.php
require_once __DIR__ . '/../config/admin_db.php'; // adjust path
class AdminLogout {
    private $db;
    private $adminAuth;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
    }
    
    public function handle() {
        // Only allow POST requests for logout (security best practice)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToLogin('Invalid logout request');
            return;
        }
        
        // Validate CSRF token
        if (!AdminSecurity::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            AdminSecurity::logSecurityEvent(
                $_SESSION['admin_user_id'] ?? null,
                'LOGOUT_CSRF_VIOLATION',
                'CSRF token validation failed during logout',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            $this->redirectToLogin('Security validation failed');
            return;
        }
        
        // Get current user info for logging
        $currentUser = $this->adminAuth->getCurrentUser();
        $userId = $currentUser['id'] ?? null;
        $userName = $currentUser['name'] ?? 'Unknown';
        
        try {
            // Log successful logout before destroying session
            if ($userId) {
                AdminSecurity::logSecurityEvent(
                    $userId,
                    'ADMIN_LOGOUT_SUCCESS',
                    "Admin user {$userName} logged out successfully",
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
                
                // Update last logout time
                $this->updateLastLogout($userId);
            }
            
            // Perform logout (this will destroy session and clear database)
            $this->adminAuth->logout();
            
            // Clear any additional cookies
            $this->clearAdminCookies();
            
            // Redirect with success message
            $this->redirectToLogin('You have been successfully logged out', 'success');
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            
            // Still attempt to logout even if logging fails
            $this->adminAuth->logout();
            $this->redirectToLogin('Logout completed');
        }
    }
    
    private function updateLastLogout($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE admin_users 
                SET last_logout_at = NOW(), 
                    last_logout_ip = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $userId
            ]);
        } catch (PDOException $e) {
            error_log("Failed to update last logout: " . $e->getMessage());
        }
    }
    
    private function clearAdminCookies() {
        // Clear any admin-related cookies
        $adminCookies = [
            'admin_remember_token',
            'admin_preferences',
            'admin_session_backup'
        ];
        
        foreach ($adminCookies as $cookieName) {
            if (isset($_COOKIE[$cookieName])) {
                setcookie($cookieName, '', time() - 3600, '/', '', 
                    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 
                    true
                );
            }
        }
    }
    
    private function redirectToLogin($message = '', $type = 'info') {
        // Start new session for flash message
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($message) {
            $_SESSION['logout_message'] = $message;
            $_SESSION['logout_message_type'] = $type;
        }
        
        // Redirect to login page
        header('Location: /admin/login');
        exit;
    }
}

// Handle logout request
if (!isset($adminLogout)) {
    $adminLogout = new AdminLogout($db, $adminAuth);
}
$adminLogout->handle();