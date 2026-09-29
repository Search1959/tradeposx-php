<?php
class ServiceItem extends BaseModel {
    public function allForOrg(int $orgId): array {
        return $this->query('SELECT * FROM service_items WHERE org_id = ? ORDER BY name', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM service_items WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO service_items (org_id, name, category, price, duration_minutes, gst_percent, sector, description, is_popular)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [$orgId, $d['name'], $d['category'] ?? null, $d['price'] ?? 0, $d['duration_minutes'] ?: null,
             $d['gst_percent'] ?? 0, $d['sector'] ?? 'General', $d['description'] ?? null, !empty($d['is_popular']) ? 1 : 0]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE service_items SET name=?, category=?, price=?, duration_minutes=?, gst_percent=?, sector=?, description=?, is_popular=?
             WHERE org_id=? AND id=?',
            [$d['name'], $d['category'] ?? null, $d['price'] ?? 0, $d['duration_minutes'] ?: null,
             $d['gst_percent'] ?? 0, $d['sector'] ?? 'General', $d['description'] ?? null, !empty($d['is_popular']) ? 1 : 0,
             $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM service_items WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }
}
