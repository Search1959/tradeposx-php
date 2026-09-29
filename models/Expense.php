<?php
class Expense extends BaseModel {
    public function allForOrg(int $orgId): array {
        return $this->query('SELECT * FROM expenses WHERE org_id = ? ORDER BY created_at DESC', [$orgId]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO expenses (org_id, category, amount, notes) VALUES (?,?,?,?)',
            [$orgId, $d['category'], $d['amount'], $d['notes'] ?? null]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM expenses WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function totalForOrg(int $orgId): float {
        $row = $this->queryOne('SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE org_id = ?', [$orgId]);
        return (float) ($row['total'] ?? 0);
    }
}
