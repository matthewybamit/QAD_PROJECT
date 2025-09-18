<?php
// admin/debug-database.php - Debug database connection issues

echo "Database Connection Debug\n";
echo "=========================\n\n";

// Step 1: Check if env.php exists and loads
echo "1. Checking environment loading:\n";

$envPaths = [
    __DIR__ . '/config/env.php',
    __DIR__ . '/../config/env.php', 
    __DIR__ . '/../../config/env.php',
    __DIR__ . '/../QAD_AdminSite/config/env.php'
];

$envLoaded = false;
foreach ($envPaths as $path) {
    if (file_exists($path)) {
        echo "Found env.php at: $path\n";
        try {
            require_once $path;
            $envLoaded = true;
            echo "✓ Successfully loaded env.php\n";
            break;
        } catch (Exception $e) {
            echo "✗ Error loading env.php: " . $e->getMessage() . "\n";
        }
    }
}

if (!$envLoaded) {
    echo "✗ No env.php file found in:\n";
    foreach ($envPaths as $path) {
        echo "  - $path\n";
    }
}

// Step 2: Check if env() function works
echo "\n2. Testing env() function:\n";
if (function_exists('env')) {
    echo "✓ env() function available\n";
    
    $dbVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'];
    foreach ($dbVars as $var) {
        $value = env($var, 'NOT_SET');
        // Don't show password in full
        if ($var === 'DB_PASS') {
            $display = empty($value) ? 'EMPTY' : str_repeat('*', min(8, strlen($value)));
        } else {
            $display = $value;
        }
        echo "  $var: $display\n";
    }
} else {
    echo "✗ env() function not available\n";
}

// Step 3: Test direct database connection
echo "\n3. Testing direct database connection:\n";

// Get database settings
$host = function_exists('env') ? env('DB_HOST', 'localhost') : 'localhost';
$port = function_exists('env') ? env('DB_PORT', '3306') : '3306';
$dbname = function_exists('env') ? env('DB_NAME', 'myapp') : 'myapp';
$username = function_exists('env') ? env('DB_USER', 'root') : 'root';
$password = function_exists('env') ? env('DB_PASS', '') : '';

echo "Attempting connection with:\n";
echo "  Host: $host\n";
echo "  Port: $port\n";
echo "  Database: $dbname\n";
echo "  Username: $username\n";
echo "  Password: " . (empty($password) ? 'EMPTY' : str_repeat('*', min(8, strlen($password)))) . "\n\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✓ Direct PDO connection successful!\n";
    
    // Test query
    $stmt = $pdo->query("SELECT NOW() as current_time, DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "✓ Test query successful\n";
    echo "  Current time: " . $result['current_time'] . "\n";
    echo "  Current database: " . $result['current_db'] . "\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n\n";
    
    // Common error solutions
    echo "Common solutions:\n";
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "- Check username and password in .env file\n";
        echo "- Make sure MySQL user '$username' exists and has permissions\n";
    }
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "- Make sure XAMPP/MySQL is running\n";
        echo "- Check if MySQL is running on port $port\n";
    }
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "- Create database '$dbname' in phpMyAdmin\n";
        echo "- Or update DB_NAME in .env file\n";
    }
    
    exit(1);
}

// Step 4: Test AdminDatabase class
echo "\n4. Testing AdminDatabase class:\n";

if (file_exists('config/admin_db.php')) {
    echo "Loading admin_db.php...\n";
    
    try {
        // Temporarily enable debug mode
        $_ENV['APP_DEBUG'] = 'true';
        
        require_once 'config/admin_db.php';
        
        if (isset($db) && $db instanceof PDO) {
            echo "✓ AdminDatabase loaded successfully\n";
            echo "✓ \$db variable is PDO instance\n";
            
            // Test the connection
            $stmt = $db->query("SELECT 1");
            echo "✓ Database query through \$db successful\n";
            
        } else {
            echo "✗ \$db variable not set or wrong type\n";
            echo "Type: " . (isset($db) ? gettype($db) : 'undefined') . "\n";
        }
        
    } catch (Exception $e) {
        echo "✗ AdminDatabase failed to load\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
} else {
    echo "✗ config/admin_db.php not found\n";
}

// Step 5: Check .env file content
echo "\n5. Checking .env file:\n";
$envFile = '.env';
if (file_exists($envFile)) {
    echo "Content of $envFile:\n";
    echo str_repeat("-", 40) . "\n";
    $content = file_get_contents($envFile);
    // Hide password values in output
    $content = preg_replace('/^(.*_PASS.*?=)(.+)$/m', '$1***HIDDEN***', $content);
    echo $content;
    echo str_repeat("-", 40) . "\n";
} else {
    echo "✗ .env file not found\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "SUMMARY AND NEXT STEPS\n";
echo str_repeat("=", 50) . "\n";

if (isset($pdo) && $pdo instanceof PDO) {
    echo "✓ Basic database connection works\n";
    echo "The issue is likely in the AdminDatabase class or environment loading.\n\n";
    
    echo "Try these fixes:\n";
    echo "1. Make sure your .env file has correct database credentials\n";
    echo "2. Ensure config/env.php exists and loads properly\n";
    echo "3. Check file paths in admin_db.php\n";
    
} else {
    echo "✗ Database connection failed\n";
    echo "Fix your database connection first:\n\n";
    
    echo "1. Start XAMPP and ensure MySQL is running\n";
    echo "2. Check your .env file has correct database settings:\n";
    echo "   DB_HOST=localhost\n";
    echo "   DB_NAME=your_database_name\n";
    echo "   DB_USER=root\n";
    echo "   DB_PASS=your_password\n";
    echo "3. Create the database if it doesn't exist\n";
    echo "4. Test connection again\n";
}

?>