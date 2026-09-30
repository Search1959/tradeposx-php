<?php
/** A separate roster from ServiceStaff (which already tracks its own per-line-item
 * commission on invoices) — this is a whole-invoice-level commission earner, e.g.
 * a specialist/consultant credited for bringing in or overseeing the whole job,
 * not for performing a specific line item. */
class SpecializedStaff extends BaseModel {
    /** $limit stays null (no LIMIT) for the invoice screen's picker — only
     * SpecializedStaffController::listAction's own paginated screen passes a real limit. */
    public function allForOrg(int $orgId, ?int $limit = null, int $offset = 0): array {
        $limitSql = $limit !== null ? ' LIMIT ? OFFSET ?' : '';
        $limitParams = $limit !== null ? [$limit, $offset] : [];
        return $this->query('SELECT * FROM specialized_staff WHERE org_id = ? ORDER BY name' . $limitSql, array_merge([$orgId], $limitParams));
    }

    public function countForOrg(int $orgId): int {
        return $this->count('specialized_staff', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM specialized_staff WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO specialized_staff (org_id, name, mobile, default_commission_percent, notes) VALUES (?,?,?,?,?)',
            [$orgId, $d['name'], $d['mobile'] ?? null, $d['default_commission_percent'] ?: 10, $d['notes'] ?? null]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE specialized_staff SET name=?, mobile=?, default_commission_percent=?, notes=? WHERE org_id=? AND id=?',
            [$d['name'], $d['mobile'] ?? null, $d['default_commission_percent'] ?: 10, $d['notes'] ?? null, $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM specialized_staff WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function accrueCommission(int $orgId, int $id, float $amount): bool {
        return $this->execute('UPDATE specialized_staff SET commission_payable = commission_payable + ? WHERE org_id = ? AND id = ?', [$amount, $orgId, $id]);
    }

    public function payCommission(int $orgId, int $id, float $amount): bool {
        return $this->execute('UPDATE specialized_staff SET commission_payable = GREATEST(0, commission_payable - ?) WHERE org_id = ? AND id = ?', [$amount, $orgId, $id]);
    }
}
