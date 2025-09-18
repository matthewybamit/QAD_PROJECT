<?php
// config/env.php
// Simple environment variable loader

function loadEnv($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) {
        throw new Exception('.env file not found at: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) == '"' && substr($value, -1) == '"') ||
                (substr($value, 0, 1) == "'" && substr($value, -1) == "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Set environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Helper function to get environment variable with default
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $default;
}

// Load the .env file
loadEnv();
?>