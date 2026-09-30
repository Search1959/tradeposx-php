<?php
class ServiceCustomer extends BaseModel {
    /** $limit stays null (no LIMIT) for every caller needing the full list to
     * populate a picker (appointment/job-card/invoice/quotation customer pickers) —
     * only ServiceCustomerController::listAction's own paginated screen passes a
     * real limit. */
    public function allForOrg(int $orgId, ?string $search = null, ?int $limit = null, int $offset = 0): array {
        $limitSql = $limit !== null ? ' LIMIT ? OFFSET ?' : '';
        $limitParams = $limit !== null ? [$limit, $offset] : [];
        if ($search) {
            $like = '%' . $search . '%';
            return $this->query('SELECT * FROM service_customers WHERE org_id = ? AND (name LIKE ? OR mobile LIKE ?) ORDER BY name' . $limitSql, array_merge([$orgId, $like, $like], $limitParams));
        }
        return $this->query('SELECT * FROM service_customers WHERE org_id = ? ORDER BY name' . $limitSql, array_merge([$orgId], $limitParams));
    }

    public function countForOrg(int $orgId, ?string $search = null): int {
        if ($search) {
            $like = '%' . $search . '%';
            return $this->count('service_customers', 'org_id = ? AND (name LIKE ? OR mobile LIKE ?)', [$orgId, $like, $like]);
        }
        return $this->count('service_customers', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM service_customers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO service_customers (org_id, name, mobile, email, address, sector, notes) VALUES (?,?,?,?,?,?,?)',
            [$orgId, $d['name'], $d['mobile'], $d['email'] ?? null, $d['address'] ?? null, $d['sector'] ?? null, $d['notes'] ?? null]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE service_customers SET name=?, mobile=?, email=?, address=?, sector=?, notes=? WHERE org_id=? AND id=?',
            [$d['name'], $d['mobile'], $d['email'] ?? null, $d['address'] ?? null, $d['sector'] ?? null, $d['notes'] ?? null, $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM service_customers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function addSpent(int $orgId, int $id, float $amount): bool {
        return $this->execute('UPDATE service_customers SET total_spent = total_spent + ? WHERE org_id = ? AND id = ?', [$amount, $orgId, $id]);
    }
}
