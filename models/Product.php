<?php
class Product extends BaseModel {
    public function allForOrg(int $orgId, ?string $search = null): array {
        if ($search) {
            $like = '%' . $search . '%';
            return $this->query(
                'SELECT * FROM products WHERE org_id = ? AND (name LIKE ? OR sku LIKE ? OR barcode LIKE ?) ORDER BY name',
                [$orgId, $like, $like, $like]
            );
        }
        return $this->query('SELECT * FROM products WHERE org_id = ? ORDER BY name', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM products WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function findByBarcode(int $orgId, string $barcode): ?array {
        return $this->queryOne('SELECT * FROM products WHERE org_id = ? AND barcode = ?', [$orgId, $barcode]);
    }

    public function lowStock(int $orgId): array {
        return $this->query(
            "SELECT * FROM products WHERE org_id = ? AND status = 'ACTIVE' AND current_stock <= min_stock ORDER BY name",
            [$orgId]
        );
    }

    private function attributesJson(array $d): ?string {
        $sectorFields = ['scheduleCategory','grade','thickness','width','length','heatNumber','millName','imei',
            'serialNumber','warrantyMonths','purity','makingCharge','stoneWeightGrams','gsm','color','fabricType',
            'size','cropSeason','vehicleModel','oemNumber','shade','amcCost','nextServiceDueDate','hazardClass','density'];
        $attrs = [];
        foreach ($sectorFields as $f) {
            if (!empty($d[$f])) $attrs[$f] = $d[$f];
        }
        return $attrs ? json_encode($attrs) : null;
    }

    /** current_stock always starts at 0 here — the controller applies any opening
     * stock via adjustStock() afterwards, which is also what writes the matching
     * INITIAL_STOCK audit row. Setting it here too would silently double-count it. */
    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO products (org_id, name, sku, barcode, category, brand, unit, purchase_price, selling_price, mrp,
             current_stock, min_stock, supplier_id, gst_percent, hsn, batch_number, expiry_date, manufacturing_date,
             description, attributes, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?,?,?)',
            [
                $orgId, $d['name'], $d['sku'] ?? null, $d['barcode'] ?? null, $d['category'] ?? 'Other', $d['brand'] ?? null,
                $d['unit'] ?? 'pcs', $d['purchase_price'] ?? 0, $d['selling_price'] ?? 0, $d['mrp'] ?? 0,
                $d['min_stock'] ?? 0, $d['supplier_id'] ?: null, $d['gst_percent'] ?? 0,
                $d['hsn'] ?? null, $d['batch_number'] ?? null, $d['expiry_date'] ?: null, $d['manufacturing_date'] ?: null,
                $d['description'] ?? null, $this->attributesJson($d), $d['status'] ?? 'ACTIVE',
            ]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE products SET name=?, sku=?, barcode=?, category=?, brand=?, unit=?, purchase_price=?, selling_price=?,
             mrp=?, min_stock=?, supplier_id=?, gst_percent=?, hsn=?, batch_number=?, expiry_date=?, manufacturing_date=?,
             description=?, attributes=?, status=? WHERE org_id=? AND id=?',
            [
                $d['name'], $d['sku'] ?? null, $d['barcode'] ?? null, $d['category'] ?? 'Other', $d['brand'] ?? null,
                $d['unit'] ?? 'pcs', $d['purchase_price'] ?? 0, $d['selling_price'] ?? 0, $d['mrp'] ?? 0,
                $d['min_stock'] ?? 0, $d['supplier_id'] ?: null, $d['gst_percent'] ?? 0, $d['hsn'] ?? null,
                $d['batch_number'] ?? null, $d['expiry_date'] ?: null, $d['manufacturing_date'] ?: null,
                $d['description'] ?? null, $this->attributesJson($d), $d['status'] ?? 'ACTIVE', $orgId, $id,
            ]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM products WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    /** Positive $qty increases stock, negative decreases — always call alongside an
     * InventoryTransaction row so stock changes stay auditable (see InventoryTransaction model). */
    public function adjustStock(int $orgId, int $id, float $qty): bool {
        return $this->execute('UPDATE products SET current_stock = current_stock + ? WHERE org_id = ? AND id = ?', [$qty, $orgId, $id]);
    }
}
