<?php
// config/admin_db.php
// Reworked: keeps AdminDatabase class but also instantiates it and exposes $db (PDO) and $pdo

// --- Load env helper (try a few common locations) ---
$envLoaded = false;
$tryPaths = [
    __DIR__ . '/env.php',            // admin/config/env.php (preferred if you put env here)
    __DIR__ . '/../config/env.php',  // admin/config/../config/env.php
    __DIR__ . '/../../config/env.php', // project-root/config/env.php
    __DIR__ . '/../../env.php',      // project-root/env.php
];

foreach ($tryPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $envLoaded = true;
        break;
    }
}
// If env is not loaded, that's okay — the env() function must exist; otherwise fallback to defaults.

// --- AdminDatabase class (unchanged behavior, small tidy-ups) ---
class AdminDatabase {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $port;
    private $charset;
    private $connection;

    public function __construct() {
        // Use admin-specific credentials, fallback to main app env
        $this->host = (function_exists('env') ? env('ADMIN_DB_HOST', env('DB_HOST', '127.0.0.1')) : '127.0.0.1');
        $this->port = (function_exists('env') ? env('ADMIN_DB_PORT', env('DB_PORT', '3306')) : '3306');
        $this->dbname = (function_exists('env') ? env('ADMIN_DB_NAME', env('DB_NAME', 'myapp')) : 'myapp');
        $this->username = (function_exists('env') ? env('ADMIN_DB_USER', env('DB_USER', 'root')) : 'root');
        $this->password = (function_exists('env') ? env('ADMIN_DB_PASS', env('DB_PASS', '')) : '');
        $this->charset = (function_exists('env') ? env('ADMIN_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')) : 'utf8mb4');
    }

    public function connect() {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_TIMEOUT => 10
            ];

            // OPTIONAL SSL constant - add only if available in your environment
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);

            // Some safe session-level settings
            try {
                $this->connection->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
                $this->connection->exec("SET SESSION autocommit = 1");
            } catch (PDOException $e) {
                // non-fatal: log but continue
                if (function_exists('error_log')) error_log('Warning setting session vars: '.$e->getMessage());
            }

            if (function_exists('env') && env('APP_DEBUG', false)) {
                error_log("Admin DB connected: {$this->username}@{$this->host}/{$this->dbname}");
            }

            return $this->connection;
        } catch (PDOException $e) {
            // Logging and friendly error handling
            error_log("Admin database connection failed: " . $e->getMessage());
            if (function_exists('env') && env('APP_ENV') === 'production') {
                throw new Exception("Database connection failed");
            }
            throw new Exception("Admin database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connect();
    }

    public function disconnect() {
        $this->connection = null;
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

// --- Instantiate and expose $adminDb and $db (PDO) for compatibility ---
// Create an AdminDatabase instance and a PDO connection in $db and $pdo so existing code works.
try {
    $adminDb = new AdminDatabase();
    $db = $adminDb->getConnection(); // <-- $db expected by AdminAuth and AdminSecurity
    $pdo = $db; // backward-compat: some code may expect $pdo variable
} catch (Exception $e) {
    // If you are in development, show the error; otherwise, show a friendly message.
    if (function_exists('env') && env('APP_DEBUG', false)) {
        // helpful for dev
        die("Admin DB initialization error: " . $e->getMessage());
    } else {
        die("Server error: failed to initialize admin database.");
    }
}

// end of file
