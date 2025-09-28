<?php
// controller/login.php - Enhanced security version

class AdminLogin {
    private $db;
    private $adminAuth;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
    }
    
    public function index() {
        // Redirect if already logged in
        if ($this->adminAuth->validateSession()) {
            $redirectUrl = $this->getIntendedUrl();
            header("Location: $redirectUrl");
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
        $clientIP = $this->getClientIP();
        
        // Rate limiting check
        if (!$this->checkRateLimit($clientIP)) {
            AdminSecurity::logSecurityEvent(
                null,
                'LOGIN_RATE_LIMIT_EXCEEDED',
                "Rate limit exceeded for IP: {$clientIP}",
                $clientIP,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            return 'Too many login attempts. Please try again later.';
        }
        
        // CSRF validation
        if (!AdminSecurity::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            AdminSecurity::logSecurityEvent(
                null,
                'LOGIN_CSRF_VIOLATION',
                "CSRF token validation failed during login",
                $clientIP,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            return 'Security validation failed. Please try again.';
        }
        
        try {
            // Input validation
            $email = AdminSecurity::validateInput($_POST['email'] ?? '', 'email', 255);
            $password = $_POST['password'] ?? '';
            
            // Length checks
            if (strlen($password) > 1000) {
                throw new InvalidArgumentException('Password too long');
            }
            
            if (empty($password)) {
                throw new InvalidArgumentException('Password is required');
            }
            
            // Apply brute force protection delay
            AdminSecurity::checkBruteForceProtection($clientIP, 'login');
            
            // Attempt authentication
            $user = $this->adminAuth->authenticateAdmin($email, $password);
            
            if ($user) {
                // Success - clear failed attempts
                AdminSecurity::clearFailedAttempts($clientIP, 'login');
                $this->clearLoginAttempts($clientIP);
                
                // Redirect to intended URL or dashboard
                $redirectUrl = $this->getIntendedUrl();
                header("Location: $redirectUrl");
                exit;
            } else {
                // Failed login
                $this->recordFailedLogin($email, $clientIP);
                return 'Invalid credentials or account locked. Please try again.';
            }
            
        } catch (InvalidArgumentException $e) {
            AdminSecurity::logSecurityEvent(
                null,
                'LOGIN_VALIDATION_ERROR',
                "Login validation error: " . $e->getMessage(),
                $clientIP,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            return 'Please check your input and try again.';
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            AdminSecurity::logSecurityEvent(
                null,
                'LOGIN_SYSTEM_ERROR',
                "System error during login",
                $clientIP,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            return 'System error. Please try again later.';
        }
    }
    
    private function recordFailedLogin($email, $clientIP) {
        AdminSecurity::recordFailedAttempt($clientIP, 'login');
        
        // Increment database attempts counter
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (ip_address, email, attempts, last_attempt) 
                VALUES (?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE 
                attempts = attempts + 1, 
                last_attempt = NOW()
            ");
            $stmt->execute([$clientIP, $email]);
        } catch (PDOException $e) {
            error_log("Failed to record login attempt: " . $e->getMessage());
        }
        
        AdminSecurity::logSecurityEvent(
            null,
            'ADMIN_LOGIN_FAILED',
            "Failed admin login attempt for email: {$email}",
            $clientIP,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
    }
    
    private function checkRateLimit($clientIP) {
        try {
            $stmt = $this->db->prepare("
                SELECT attempts, last_attempt 
                FROM login_attempts 
                WHERE ip_address = ? 
                AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$clientIP, $this->lockoutDuration]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['attempts'] >= $this->maxLoginAttempts) {
                return false;
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true; // Allow login if check fails
        }
    }
    
    private function clearLoginAttempts($clientIP) {
        try {
            $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt->execute([$clientIP]);
        } catch (PDOException $e) {
            error_log("Failed to clear login attempts: " . $e->getMessage());
        }
    }
    
    private function getIntendedUrl() {
        $intended = $_SESSION['intended_url'] ?? '/admin/dashboard';
        unset($_SESSION['intended_url']);
        
        // Security: Only allow internal redirects
        $parsed = parse_url($intended);
        if (isset($parsed['host']) || isset($parsed['scheme'])) {
            return '/admin/dashboard';
        }
        
        // Ensure it starts with /admin
        if (!str_starts_with($intended, '/admin')) {
            return '/admin/dashboard';
        }
        
        return $intended;
    }
    
    private function getClientIP() {
        // Secure IP detection - only trust specific headers if behind known proxies
        $trustedProxies = ['127.0.0.1', '::1']; // Add your actual proxy IPs
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Only check forwarded headers if request comes from trusted proxy
        if (in_array($ip, $trustedProxies) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIPs = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $firstIP = trim($forwardedIPs[0]);
            
            if (filter_var($firstIP, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $firstIP;
            }
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }
}

// Create and run admin login
$adminLogin = new AdminLogin($db, $adminAuth);
$adminLogin->index();