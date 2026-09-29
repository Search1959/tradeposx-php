<?php
class BaseModel {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    protected function query(string $sql, array $params = []): array {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('BaseModel::query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            return [];
        }
    }

    protected function queryOne(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('BaseModel::queryOne error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            return null;
        }
    }

    protected function execute(string $sql, array $params = []): bool {
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('BaseModel::execute error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            return false;
        }
    }

    protected function insert(string $sql, array $params = []): int {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('BaseModel::insert error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            return 0;
        }
    }

    protected function count(string $table, string $where = '1', array $params = []): int {
        $row = $this->queryOne("SELECT COUNT(*) as cnt FROM `{$table}` WHERE {$where}", $params);
        return (int) ($row['cnt'] ?? 0);
    }
}
