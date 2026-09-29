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
            'INSERT INTO service_items (org_id, name, category, price, duration_minutes, warranty_days, gst_percent, sector, description, is_popular)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $d['name'], $d['category'] ?? null, $d['price'] ?? 0, $d['duration_minutes'] ?: null,
             $d['warranty_days'] ?: 0, $d['gst_percent'] ?? 0, $d['sector'] ?? 'General', $d['description'] ?? null,
             !empty($d['is_popular']) ? 1 : 0]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE service_items SET name=?, category=?, price=?, duration_minutes=?, warranty_days=?, gst_percent=?, sector=?, description=?, is_popular=?
             WHERE org_id=? AND id=?',
            [$d['name'], $d['category'] ?? null, $d['price'] ?? 0, $d['duration_minutes'] ?: null,
             $d['warranty_days'] ?: 0, $d['gst_percent'] ?? 0, $d['sector'] ?? 'General', $d['description'] ?? null,
             !empty($d['is_popular']) ? 1 : 0, $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM service_items WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    /** Service items have no SKU/barcode to match on — name (case-insensitive) is the
     * only natural key, same as how a shop owner would recognize "the same" service. */
    public function findForImportMatch(int $orgId, string $name): ?array {
        return $this->queryOne('SELECT * FROM service_items WHERE org_id = ? AND LOWER(name) = LOWER(?)', [$orgId, $name]);
    }

    /** Merges only the columns the import row actually filled in, leaving the rest
     * of the existing record (e.g. is_popular) untouched — same pattern as
     * Product::updateFromImportMerge(). */
    public function updateFromImportMerge(int $orgId, int $id, array $existing, array $data): bool {
        $merged = [
            'name' => $data['name'] ?: $existing['name'],
            'category' => $data['category'] !== '' ? $data['category'] : $existing['category'],
            'price' => $data['price'] !== null ? $data['price'] : $existing['price'],
            'duration_minutes' => $data['duration_minutes'] !== null ? $data['duration_minutes'] : $existing['duration_minutes'],
            'warranty_days' => $data['warranty_days'] !== null ? $data['warranty_days'] : $existing['warranty_days'],
            'gst_percent' => $data['gst_percent'] !== null ? $data['gst_percent'] : $existing['gst_percent'],
            'sector' => $data['sector'] !== '' ? $data['sector'] : $existing['sector'],
            'description' => $data['description'] !== '' ? $data['description'] : $existing['description'],
        ];
        return $this->execute(
            'UPDATE service_items SET name=?, category=?, price=?, duration_minutes=?, warranty_days=?, gst_percent=?, sector=?, description=? WHERE org_id=? AND id=?',
            [$merged['name'], $merged['category'], $merged['price'], $merged['duration_minutes'], $merged['warranty_days'],
             $merged['gst_percent'], $merged['sector'], $merged['description'], $orgId, $id]
        );
    }
}
