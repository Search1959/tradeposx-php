<?php
class Broker extends BaseModel {
    /** $limit stays null (no LIMIT) for the POS screen's picker — only
     * BrokerController::listAction's own paginated screen passes a real limit. */
    public function allForOrg(int $orgId, ?int $limit = null, int $offset = 0): array {
        $limitSql = $limit !== null ? ' LIMIT ? OFFSET ?' : '';
        $limitParams = $limit !== null ? [$limit, $offset] : [];
        return $this->query('SELECT * FROM brokers WHERE org_id = ? ORDER BY name' . $limitSql, array_merge([$orgId], $limitParams));
    }

    public function countForOrg(int $orgId): int {
        return $this->count('brokers', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM brokers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO brokers (org_id, name, mobile, default_commission_percent, notes) VALUES (?,?,?,?,?)',
            [$orgId, $d['name'], $d['mobile'] ?? null, $d['default_commission_percent'] ?: 5, $d['notes'] ?? null]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE brokers SET name=?, mobile=?, default_commission_percent=?, notes=? WHERE org_id=? AND id=?',
            [$d['name'], $d['mobile'] ?? null, $d['default_commission_percent'] ?: 5, $d['notes'] ?? null, $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM brokers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function accrueCommission(int $orgId, int $id, float $amount): bool {
        return $this->execute('UPDATE brokers SET commission_payable = commission_payable + ? WHERE org_id = ? AND id = ?', [$amount, $orgId, $id]);
    }

    /** Pays out (reduces) commission owed — a manual "settled up" action, not tied
     * to any specific sale, matching how commission payouts work in practice
     * (ServiceStaff::payCommission uses the same pattern). */
    public function payCommission(int $orgId, int $id, float $amount): bool {
        return $this->execute('UPDATE brokers SET commission_payable = GREATEST(0, commission_payable - ?) WHERE org_id = ? AND id = ?', [$amount, $orgId, $id]);
    }
}
