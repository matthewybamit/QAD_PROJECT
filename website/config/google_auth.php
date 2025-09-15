<?php
// config/google_auth.php
// Updated to use environment variables

// Load environment variables
require_once __DIR__ . '/env.php';

return [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/auth/callback'),
    'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url' => 'https://oauth2.googleapis.com/token',
    'userinfo_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
    'scope' => 'email profile'
];
?>