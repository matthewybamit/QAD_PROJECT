<?php
// admin/scripts/generate-admin-password.php
// Usage: php generate-admin-password.php [password] [email]

// Create scripts directory if it doesn't exist
if (!is_dir(__DIR__)) {
    mkdir(__DIR__, 0755, true);
}

echo "Admin Password Generator\n";
echo "========================\n\n";

// Get password from command line or prompt
$password = $argv[1] ?? null;
$email = $argv[2] ?? 'admin@yoursite.com';

if (!$password) {
    echo "Enter admin password: ";
    $password = trim(fgets(STDIN));
}

if (strlen($password) < 8) {
    die("Error: Password must be at least 8 characters long\n");
}

// Check password strength
$hasUpper = preg_match('/[A-Z]/', $password);
$hasLower = preg_match('/[a-z]/', $password);
$hasNumber = preg_match('/\d/', $password);
$hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

$strength = 0;
$strength += $hasUpper ? 1 : 0;
$strength += $hasLower ? 1 : 0;
$strength += $hasNumber ? 1 : 0;
$strength += $hasSpecial ? 1 : 0;

echo "Password strength: ";
switch ($strength) {
    case 1:
        echo "Weak ❌\n";
        break;
    case 2:
        echo "Fair ⚠️\n";
        break;
    case 3:
        echo "Good ✅\n";
        break;
    case 4:
        echo "Strong 💪\n";
        break;
    default:
        echo "Very Weak ❌❌\n";
}

if ($strength < 3) {
    echo "Warning: Consider using a stronger password with uppercase, lowercase, numbers, and symbols.\n";
}

echo "\n";

// Generate password hash
$hash = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,  // 64 MB
    'time_cost' => 4,        // 4 iterations
    'threads' => 3,          // 3 threads
]);

echo "Generated password hash:\n";
echo "$hash\n\n";

echo "SQL Commands:\n";
echo "=============\n\n";

// Check if user already exists
echo "-- 1. Check if admin user exists:\n";
echo "SELECT id, name, email, role FROM users WHERE email = '$email';\n\n";

echo "-- 2a. If user exists, UPDATE their password:\n";
echo "UPDATE users SET password_hash = '$hash' WHERE email = '$email' AND role = 'admin';\n\n";

echo "-- 2b. If user doesn't exist, CREATE new admin user:\n";
echo "INSERT INTO users (google_id, name, email, role, password_hash, created_at, updated_at) \n";
echo "VALUES ('admin_" . uniqid() . "', 'Admin User', '$email', 'admin', '$hash', NOW(), NOW());\n\n";

echo "-- 3. Verify the admin user:\n";
echo "SELECT id, name, email, role, \n";
echo "       CASE WHEN password_hash IS NOT NULL THEN 'SET' ELSE 'NOT SET' END as password_status\n";
echo "FROM users WHERE email = '$email';\n\n";

// Test the password
echo "Password verification test: ";
if (password_verify($password, $hash)) {
    echo "✅ PASSED\n";
} else {
    echo "❌ FAILED\n";
}

// Additional security info
echo "\nSecurity Information:\n";
echo "====================\n";
echo "Algorithm: Argon2ID\n";
echo "Memory Cost: 64 MB\n";
echo "Time Cost: 4 iterations\n";
echo "Threads: 3\n";
echo "Hash Length: " . strlen($hash) . " characters\n";
echo "Created: " . date('Y-m-d H:i:s') . "\n\n";

echo "Next Steps:\n";
echo "===========\n";
echo "1. Run one of the SQL commands above in your database\n";
echo "2. Verify the admin user was created/updated successfully\n";
echo "3. Try logging in at /admin/login with:\n";
echo "   Email: $email\n";
echo "   Password: [the password you entered]\n";
echo "4. Keep this password secure and don't share it\n\n";

// Save to log file for reference (without password)
$logFile = __DIR__ . '/../logs/admin-setup.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

$logEntry = date('Y-m-d H:i:s') . " - Admin password generated for: $email\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

echo "Setup logged to: $logFile\n";
?>

<?php
// admin/scripts/create-admin-user.php
// Interactive script to create admin user

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

echo "Admin User Creation Script\n";
echo "==========================\n\n";

// Load environment
require_once __DIR__ . '/../../config/env.php';

echo "Enter admin user details:\n";

// Get user input
echo "Name: ";
$name = trim(fgets(STDIN));

echo "Email: ";
$email = trim(fgets(STDIN));

echo "Password: ";
$password = trim(fgets(STDIN));

// Validate input
if (empty($name) || empty($email) || empty($password)) {
    die("Error: All fields are required\n");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email format\n");
}

if (strlen($password) < 8) {
    die("Error: Password must be at least 8 characters long\n");
}

// Connect to database
try {
    require_once __DIR__ . '/../../config/db.php';
    global $db;
    
    if (!$db || !$db->connection) {
        throw new Exception("Database connection not available");
    }
    
    // Check if user already exists
    $stmt = $db->connection->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo "User with email $email already exists. Update password? (y/n): ";
        $update = trim(fgets(STDIN));
        
        if (strtolower($update) !== 'y') {
            die("Operation cancelled\n");
        }
        
        // Update existing user
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $db->connection->prepare("
            UPDATE users 
            SET password_hash = ?, role = 'admin', updated_at = NOW() 
            WHERE email = ?
        ");
        $stmt->execute([$hash, $email]);
        
        echo "✅ Admin user updated successfully!\n";
        
    } else {
        // Create new user
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $googleId = 'admin_' . uniqid();
        
        $stmt = $db->connection->prepare("
            INSERT INTO users (google_id, name, email, role, password_hash, created_at, updated_at) 
            VALUES (?, ?, ?, 'admin', ?, NOW(), NOW())
        ");
        $stmt->execute([$googleId, $name, $email, $hash]);
        
        echo "✅ Admin user created successfully!\n";
    }
    
    // Verify creation
    $stmt = $db->connection->prepare("
        SELECT id, name, email, role, 
               CASE WHEN password_hash IS NOT NULL THEN 'SET' ELSE 'NOT SET' END as password_status
        FROM users WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUser Details:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Name: " . $user['name'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    echo "Password: " . $user['password_status'] . "\n";
    
    echo "\nYou can now login at /admin/login with:\n";
    echo "Email: $email\n";
    echo "Password: [the password you entered]\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>

<?php
// admin/scripts/check-admin-setup.php
// Quick setup verification script

require_once __DIR__ . '/../../config/env.php';

echo "Admin Setup Verification\n";
echo "========================\n\n";

try {
    require_once __DIR__ . '/../../config/db.php';
    global $db;
    
    echo "✅ Database connection: OK\n";
    
    // Check admin users
    $stmt = $db->connection->prepare("
        SELECT id, name, email, role,
               CASE WHEN password_hash IS NOT NULL THEN 'SET' ELSE 'NOT SET' END as password_status
        FROM users WHERE role = 'admin'
    ");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "❌ No admin users found\n";
        echo "Run: php create-admin-user.php\n";
    } else {
        echo "✅ Admin users found: " . count($admins) . "\n";
        foreach ($admins as $admin) {
            echo "   - {$admin['name']} ({$admin['email']}) - Password: {$admin['password_status']}\n";
        }
    }
    
    // Check required tables
    $tables = ['security_logs', 'admin_sessions', 'ip_whitelist', 'user_lockouts'];
    foreach ($tables as $table) {
        $stmt = $db->connection->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            echo "✅ Table $table: OK\n";
        } else {
            echo "❌ Table $table: MISSING\n";
        }
    }
    
    // Check environment variables
    $envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_ENV'];
    foreach ($envVars as $var) {
        if (env($var)) {
            echo "✅ ENV $var: SET\n";
        } else {
            echo "❌ ENV $var: NOT SET\n";
        }
    }
    
    echo "\n";
    
    if (!empty($admins)) {
        $adminWithPassword = array_filter($admins, function($admin) {
            return $admin['password_status'] === 'SET';
        });
        
        if (!empty($adminWithPassword)) {
            echo "🎉 Admin panel is ready!\n";
            echo "Visit: /admin/login\n";
        } else {
            echo "⚠️ Admin users exist but need passwords set\n";
            echo "Run: php generate-admin-password.php\n";
        }
    } else {
        echo "❌ Setup incomplete - no admin users\n";
        echo "Run: php create-admin-user.php\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

<?php
// admin/scripts/reset-admin-password.php
// Reset password for existing admin user

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

echo "Admin Password Reset\n";
echo "====================\n\n";

// Load environment and database
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';

try {
    global $db;
    
    // Get list of admin users
    $stmt = $db->connection->prepare("SELECT id, name, email FROM users WHERE role = 'admin' ORDER BY name");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        die("No admin users found. Run create-admin-user.php first.\n");
    }
    
    echo "Select admin user to reset password:\n";
    foreach ($admins as $index => $admin) {
        echo ($index + 1) . ". {$admin['name']} ({$admin['email']})\n";
    }
    
    echo "\nEnter number (1-" . count($admins) . "): ";
    $selection = (int)trim(fgets(STDIN));
    
    if ($selection < 1 || $selection > count($admins)) {
        die("Invalid selection\n");
    }
    
    $selectedAdmin = $admins[$selection - 1];
    
    echo "\nSelected: {$selectedAdmin['name']} ({$selectedAdmin['email']})\n";
    echo "Enter new password: ";
    $password = trim(fgets(STDIN));
    
    if (strlen($password) < 8) {
        die("Error: Password must be at least 8 characters long\n");
    }
    
    // Generate hash and update
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    
    $stmt = $db->connection->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hash, $selectedAdmin['id']]);
    
    echo "✅ Password updated successfully for {$selectedAdmin['name']}\n";
    echo "\nYou can now login with:\n";
    echo "Email: {$selectedAdmin['email']}\n";
    echo "Password: [the new password]\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>

<?php
// admin/scripts/cleanup-maintenance.php
// Maintenance cleanup script

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';

echo "Admin Panel Maintenance Cleanup\n";
echo "===============================\n\n";

try {
    global $db;
    $cleaned = 0;
    
    // Clean expired admin sessions
    echo "Cleaning expired admin sessions...\n";
    $stmt = $db->connection->prepare("DELETE FROM admin_sessions WHERE expires_at < NOW()");
    $stmt->execute();
    $sessionsCleaned = $stmt->rowCount();
    $cleaned += $sessionsCleaned;
    echo "✅ Removed $sessionsCleaned expired sessions\n";
    
    // Clean expired user lockouts
    echo "Cleaning expired user lockouts...\n";
    $stmt = $db->connection->prepare("DELETE FROM user_lockouts WHERE locked_until < NOW() AND locked_until IS NOT NULL");
    $stmt->execute();
    $lockoutsCleaned = $stmt->rowCount();
    $cleaned += $lockoutsCleaned;
    echo "✅ Removed $lockoutsCleaned expired lockouts\n";
    
    // Clean old security logs (keep 90 days)
    echo "Cleaning old security logs (keeping 90 days)...\n";
    $stmt = $db->connection->prepare("DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $stmt->execute();
    $logsCleaned = $stmt->rowCount();
    $cleaned += $logsCleaned;
    echo "✅ Removed $logsCleaned old log entries\n";
    
    // Update expired permissions
    echo "Updating expired permissions...\n";
    $stmt = $db->connection->prepare("UPDATE school_edit_permissions SET status = 'expired' WHERE status = 'approved' AND expires_at < NOW()");
    $stmt->execute();
    $permissionsExpired = $stmt->rowCount();
    echo "✅ Expired $permissionsExpired permissions\n";
    
    // Clean temporary rate limit files
    echo "Cleaning temporary rate limit files...\n";
    $tempDir = sys_get_temp_dir();
    $rateLimitFiles = glob($tempDir . "/admin_rate_limit_*");
    $filesCleaned = 0;
    
    foreach ($rateLimitFiles as $file) {
        if (filemtime($file) < time() - 3600) { // Older than 1 hour
            unlink($file);
            $filesCleaned++;
        }
    }
    echo "✅ Removed $filesCleaned temporary files\n";
    
    echo "\n🎉 Maintenance cleanup completed!\n";
    echo "Total items cleaned: " . ($cleaned + $filesCleaned) . "\n";
    
    // Show current statistics
    echo "\nCurrent Statistics:\n";
    echo "==================\n";
    
    $stmt = $db->connection->query("SELECT COUNT(*) as count FROM admin_sessions WHERE expires_at > NOW()");
    $activeSessions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Active admin sessions: $activeSessions\n";
    
    $stmt = $db->connection->query("SELECT COUNT(*) as count FROM security_logs WHERE DATE(created_at) = CURDATE()");
    $todaysLogs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Today's security events: $todaysLogs\n";
    
    $stmt = $db->connection->query("SELECT COUNT(*) as count FROM school_edit_permissions WHERE status = 'pending'");
    $pendingPerms = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Pending permissions: $pendingPerms\n";
    
} catch (Exception $e) {
    die("Error during maintenance: " . $e->getMessage() . "\n");
}
?>