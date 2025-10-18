<?php
// middleware/AdminAuthMiddleware.php
// FIXED VERSION - Removed conflicting session regeneration

class AdminAuthMiddleware {
    private $adminAuth;
    
    public function __construct($adminAuth) {
        $this->adminAuth = $adminAuth;
    }

    public function handle($request, $next) {
        // Ensure session is active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // REMOVED: Conflicting session regeneration logic
        // Session regeneration is now only handled in index.php
        
        // Authentication check
        if (!$this->adminAuth->validateSession()) {
            return $this->handleUnauthenticated();
        }

        // ADDED: Update last activity on authenticated requests
        $_SESSION['last_activity'] = time();

        return $next($request);
    }

    private function handleUnauthenticated() {
        $this->setSecureIntendedUrl();
        
        if ($this->isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Unauthorized',
                'redirect' => '/admin/login'
            ]);
        } else {
            header('Location: /admin/login', true, 302);
        }
        exit;
    }

    private function setSecureIntendedUrl() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard';
        $parsed = parse_url($uri);
        $path = $parsed['path'] ?? '/admin/dashboard';

        // Validate path
        if (strpos($path, '/admin/') === 0 && 
            !preg_match('/\.\.|\/{2,}|[<>"\']/', $path) &&
            strlen($path) < 200) {
            $_SESSION['intended_url'] = $path;
        } else {
            $_SESSION['intended_url'] = '/admin/dashboard';
        }
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}