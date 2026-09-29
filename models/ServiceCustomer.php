<?php
class ServiceCustomer extends BaseModel {
    public function allForOrg(int $orgId, ?string $search = null): array {
        if ($search) {
            $like = '%' . $search . '%';
            return $this->query('SELECT * FROM service_customers WHERE org_id = ? AND (name LIKE ? OR mobile LIKE ?) ORDER BY name', [$orgId, $like, $like]);
        }
        return $this->query('SELECT * FROM service_customers WHERE org_id = ? ORDER BY name', [$orgId]);
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
