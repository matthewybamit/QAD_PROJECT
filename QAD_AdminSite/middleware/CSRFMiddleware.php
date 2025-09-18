<?php
// middleware/CSRFMiddleware.php


class CSRFMiddleware {
    public function handle($request, $next) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!AdminSecurity::validateCSRFToken($token)) {
                AdminSecurity::logSecurityEvent(
                    $_SESSION['admin_user_id'] ?? null,
                    'CSRF_VIOLATION',
                    'Invalid CSRF token - potential CSRF attack',
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
                
                // Return JSON response for AJAX requests
                if ($this->isAjaxRequest()) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'CSRF token validation failed']);
                    exit;
                }
                
                // Redirect with error message for regular requests
                $_SESSION['error'] = 'Security token validation failed. Please try again.';
                $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/dashboard';
                header("Location: $referer");
                exit;
            }
        }
        
        return $next($request);
    }
    
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}
