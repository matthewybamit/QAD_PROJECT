<?php 
// config/db.php
// Updated to use environment variables

// Load environment variables
require_once __DIR__ . '/env.php';

class Database {
    public $connection;

    public function __construct() {
        $host = env('DB_HOST', );
        $port = env('DB_PORT', );
        $dbname = env('DB_NAME', );
        $charset = env('DB_CHARSET', );
        $username = env('DB_USER', );
        $password = env('DB_PASS', );

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function query($query, $params = []) {
        try {
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
}

// Create global database instance
$db = new Database();
$pdo = $db->connection; // For backward compatibility
?>