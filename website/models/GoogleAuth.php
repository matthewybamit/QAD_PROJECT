<?php
// models/GoogleAuth.php
// Simple Google OAuth authentication

class GoogleAuth {
    private $config;
    private $pdo;

    public function __construct($pdo = null) {
        $this->config = require __DIR__ . '/../config/google_auth.php';
        $this->pdo = $pdo;
    }

    /**
     * Generate Google OAuth login URL
     */
    public function getAuthUrl() {
        // Generate a random state parameter
        $state = bin2hex(random_bytes(16));
        
        // Store state in session
        $_SESSION['oauth_state'] = $state;
        
        // Build auth URL with state parameter
        $params = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'email profile',
            'state' => $state
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Handle Google OAuth callback
     */
public function handleCallback($code, $state) {
    try {
        // Verify state
        if (!isset($_SESSION['oauth_state']) || $_SESSION['oauth_state'] !== $state) {
            error_log('State mismatch. Expected: ' . ($_SESSION['oauth_state'] ?? 'none') . ', Got: ' . $state);
            return null;
        }

        // Exchange code for token
        $token = $this->getAccessToken($code);
        if (!$token) {
            error_log('Failed to get access token');
            return null;
        }

        // Get user info from Google
        $userInfo = $this->getUserInfo($token);
        if (!$userInfo) {
            error_log('Failed to get user info');
            return null;
        }

        // ✅ Save/update user in DB
        $user = $this->createOrUpdateUser($userInfo);
        if (!$user) {
            error_log('Failed to save user in DB');
            return null;
        }

        // Clear OAuth state
        unset($_SESSION['oauth_state']);

        return $user;

    } catch (Exception $e) {
        error_log('Auth Error: ' . $e->getMessage());
        return null;
    }
}

    /**
     * Exchange code for token
     */
    private function getAccessToken($code) {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code' => $code,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ]));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('CURL Error: ' . $error);
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            error_log('Token Response Error: ' . $response);
            return null;
        }

        return $data['access_token'];
    }

    /**
     * Get user info from Google
     */
    private function getUserInfo($token) {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Create or update user in database
     */
    private function createOrUpdateUser($userInfo) {
        if (!$this->pdo) {
            throw new Exception("Database connection (\$pdo) not set in GoogleAuth.");
        }

        try {
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$userInfo['email']]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingUser) {
                // Update existing user
                $stmt = $this->pdo->prepare("
                    UPDATE users SET 
                        google_id = ?, 
                        name = ?, 
                        avatar = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $userInfo['id'],
                    $userInfo['name'],
                    $userInfo['picture'] ?? null,
                    $existingUser['id']
                ]);
                return $this->getUserById($existingUser['id']);
            } else {
                // Create new user
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (google_id, name, email, avatar, role, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, 'user', NOW(), NOW())
                ");
                $stmt->execute([
                    $userInfo['id'],
                    $userInfo['name'],
                    $userInfo['email'],
                    $userInfo['picture'] ?? null
                ]);
                return $this->getUserById($this->pdo->lastInsertId());
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get user by ID
     */
    private function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Login user
     */
    public function loginUser($user) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_avatar']= $user['avatar'];
        $_SESSION['logged_in']  = true;
    }

    /**
     * Static helper methods
     */
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function getCurrentUser() {
        if (!self::isLoggedIn()) return null;

        return [
            'id'     => $_SESSION['user_id'],
            'name'   => $_SESSION['user_name'],
            'email'  => $_SESSION['user_email'],
            'role'   => $_SESSION['user_role'],
            'avatar' => $_SESSION['user_avatar']
        ];
    }

    public static function logout() {
        session_unset();
        session_destroy();
    }

    public static function isAdmin() {
        if (!self::isLoggedIn()) return false;
        $user = self::getCurrentUser();
        return in_array($user['role'], ['admin', 'school_admin']);
    }
}
