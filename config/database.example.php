<?php
// Copy this to config/database.php and fill in real credentials — that file is
// gitignored and never committed (this repo is public). For local dev, use
// host=localhost/dbname=tradeposx_dev/username=root/password=''.
class Database {
    private static $instance = null;
    private $pdo;

    private $host = 'localhost';
    private $dbname = 'CHANGE_ME';
    private $username = 'CHANGE_ME';
    private $password = 'CHANGE_ME';
    private $charset = 'utf8mb4';

    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            die('Database connection error. Please try again later.');
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }
}
