
<?php
// config/admin_config.php - Admin-specific configuration
require_once '../../config/env.php';

class AdminConfig {
    // Security settings
    const MAX_LOGIN_ATTEMPTS = 3;
    const LOCKOUT_DURATION = 900; // 15 minutes
    const SESSION_TIMEOUT = 1800; // 30 minutes
    const CSRF_TOKEN_EXPIRY = 3600; // 1 hour
    const PASSWORD_MIN_LENGTH = 8;
    const SESSION_COOKIE_LIFETIME = 0; // Browser session only
    
    // Rate limiting
    const RATE_LIMIT_REQUESTS = 60; // per minute
    const RATE_LIMIT_WINDOW = 60; // seconds
    
    // File upload limits for admin
    const MAX_UPLOAD_SIZE = 5242880; // 5MB
    const ALLOWED_UPLOAD_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    
    public static function get($key, $default = null) {
        $adminKeys = [
            'debug' => env('APP_DEBUG', false),
            'environment' => env('APP_ENV', 'development'),
            'session_timeout' => env('ADMIN_SESSION_TIMEOUT', self::SESSION_TIMEOUT),
            'max_login_attempts' => env('ADMIN_MAX_LOGIN_ATTEMPTS', self::MAX_LOGIN_ATTEMPTS),
            'lockout_duration' => env('ADMIN_LOCKOUT_DURATION', self::LOCKOUT_DURATION),
            'csrf_expiry' => env('ADMIN_CSRF_EXPIRY', self::CSRF_TOKEN_EXPIRY),
            'encryption_key' => env('ADMIN_ENCRYPTION_KEY', ''),
            'csrf_secret' => env('ADMIN_CSRF_SECRET', ''),
            'ip_whitelist_enabled' => env('ADMIN_IP_WHITELIST', false),
            'rate_limiting_enabled' => env('ADMIN_RATE_LIMITING', true),
            'two_factor_enabled' => env('ADMIN_2FA_ENABLED', false),
            'email_alerts_enabled' => env('ADMIN_EMAIL_ALERTS', true),
            'admin_email' => env('ADMIN_EMAIL', ''),
            'smtp_host' => env('SMTP_HOST', ''),
            'smtp_port' => env('SMTP_PORT', 587),
            'smtp_user' => env('SMTP_USER', ''),
            'smtp_pass' => env('SMTP_PASS', ''),
        ];
        
        return $adminKeys[$key] ?? $default;
    }
    
    public static function isProduction() {
        return self::get('environment') === 'production';
    }
    
    public static function isDebug() {
        return self::get('debug', false);
    }
}