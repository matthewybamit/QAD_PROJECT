<?php
// controller/login.php
// Your login logic here

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
