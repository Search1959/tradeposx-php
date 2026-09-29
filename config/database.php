<?php
class Database {
    private static $instance = null;
    private $pdo;

    // Hostinger production DB — create this database + user in hPanel before first deploy,
    // then fill in the real values here. For local dev, swap to host=localhost/root/''.
    private $host = 'localhost';
    private $dbname = 'tradeposx_dev';
    private $username = 'root';
    private $password = '';
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
