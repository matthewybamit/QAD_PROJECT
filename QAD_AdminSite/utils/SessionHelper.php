<?php 


// /utils/SessionHelper.php - Session management utilities

class SessionHelper {
    public static function regenerateId() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    public static function destroySession() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function setFlashMessage($message, $type = 'info') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    
    public static function getFlashMessage() {
        $message = $_SESSION['flash_message'] ?? null;
        $type = $_SESSION['flash_type'] ?? 'info';
        
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        return $message ? ['message' => $message, 'type' => $type] : null;
    }
    
    public static function isValidSession() {
        return session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['admin_user_id']);
    }
    
    public static function getSessionInfo() {
        if (!self::isValidSession()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['admin_user_id'] ?? null,
            'user_name' => $_SESSION['admin_user_name'] ?? null,
            'user_email' => $_SESSION['admin_user_email'] ?? null,
            'login_time' => $_SESSION['admin_login_time'] ?? null,
            'last_activity' => $_SESSION['admin_last_activity'] ?? null,
            'ip' => $_SESSION['admin_ip'] ?? null,
            'user_agent' => $_SESSION['admin_user_agent'] ?? null
        ];
    }
}