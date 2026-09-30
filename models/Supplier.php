<?php
class Supplier extends BaseModel {
    /** $limit stays null (no LIMIT) for every caller needing the full list to
     * populate a picker (New Purchase's supplier dropdown) — only
     * SupplierController::listAction's own paginated screen passes a real limit. */
    public function allForOrg(int $orgId, ?int $limit = null, int $offset = 0): array {
        $limitSql = $limit !== null ? ' LIMIT ? OFFSET ?' : '';
        $limitParams = $limit !== null ? [$limit, $offset] : [];
        return $this->query('SELECT * FROM suppliers WHERE org_id = ? ORDER BY name' . $limitSql, array_merge([$orgId], $limitParams));
    }

    public function countForOrg(int $orgId): int {
        return $this->count('suppliers', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM suppliers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO suppliers (org_id, name, contact_person, mobile, company_name, category, gstin, address, city, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $d['name'], $d['contact_person'] ?? null, $d['mobile'] ?? null, $d['company_name'] ?? null,
             $d['category'] ?? null, $d['gstin'] ?? null, $d['address'] ?? null, $d['city'] ?? null, $d['notes'] ?? null]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE suppliers SET name=?, contact_person=?, mobile=?, company_name=?, category=?, gstin=?, address=?, city=?, notes=?
             WHERE org_id=? AND id=?',
            [$d['name'], $d['contact_person'] ?? null, $d['mobile'] ?? null, $d['company_name'] ?? null,
             $d['category'] ?? null, $d['gstin'] ?? null, $d['address'] ?? null, $d['city'] ?? null, $d['notes'] ?? null,
             $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM suppliers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function adjustOutstanding(int $orgId, int $id, float $delta): bool {
        return $this->execute('UPDATE suppliers SET outstanding_balance = outstanding_balance + ? WHERE org_id = ? AND id = ?', [$delta, $orgId, $id]);
    }
}
