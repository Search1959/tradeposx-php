<?php
class ServiceStaff extends BaseModel {
    public function allForOrg(int $orgId): array {
        return $this->query('SELECT * FROM service_staff WHERE org_id = ? ORDER BY name', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM service_staff WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO service_staff (org_id, name, role, mobile, specialty, commission_percent, daily_target, status)
             VALUES (?,?,?,?,?,?,?,?)',
            [$orgId, $d['name'], $d['role'] ?? null, $d['mobile'] ?? null, $d['specialty'] ?? null,
             $d['commission_percent'] ?? 0, $d['daily_target'] ?: null, $d['status'] ?? 'Active']
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE service_staff SET name=?, role=?, mobile=?, specialty=?, commission_percent=?, daily_target=?, status=?
             WHERE org_id=? AND id=?',
            [$d['name'], $d['role'] ?? null, $d['mobile'] ?? null, $d['specialty'] ?? null,
             $d['commission_percent'] ?? 0, $d['daily_target'] ?: null, $d['status'] ?? 'Active', $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM service_staff WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function accrueCommission(int $orgId, int $id, float $amount): bool {
        return $this->execute('UPDATE service_staff SET commission_payable = commission_payable + ? WHERE org_id = ? AND id = ?', [$amount, $orgId, $id]);
    }

    public function incrementJobsCompleted(int $orgId, int $id): bool {
        return $this->execute('UPDATE service_staff SET total_jobs_completed = total_jobs_completed + 1 WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    /** Pays out (reduces) commission owed — a manual "settled up" action, not tied
     * to any specific invoice, matching how commission payouts work in practice. */
    public function payCommission(int $orgId, int $id, float $amount): bool {
        return $this->execute('UPDATE service_staff SET commission_payable = GREATEST(0, commission_payable - ?) WHERE org_id = ? AND id = ?', [$amount, $orgId, $id]);
    }
}
