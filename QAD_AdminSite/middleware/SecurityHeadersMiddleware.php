<?php
// admin/middleware/SecurityHeadersMiddleware.php

class SecurityHeadersMiddleware {
    public function handle($request, $next) {
        // Security headers
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // HSTS (only if HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Content Security Policy
        $isProduction = function_exists('env') && env('APP_ENV') === 'production';
        if ($isProduction) {
            $csp = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self'";
        } else {
            $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'";
        }
        header("Content-Security-Policy: $csp");
        
        // Remove server information
        header_remove('X-Powered-By');
        header_remove('Server');
        
        return $next($request);
    }
}