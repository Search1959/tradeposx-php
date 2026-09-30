<?php
class Product extends BaseModel {
    /** $limit stays null (no LIMIT at all) for every caller that needs the full
     * catalog to populate a picker (POS, purchase/sale cart, mobile POS) — only
     * ProductController::listAction's own paginated screen passes a real limit. */
    public function allForOrg(int $orgId, ?string $search = null, ?int $limit = null, int $offset = 0): array {
        $limitSql = $limit !== null ? ' LIMIT ? OFFSET ?' : '';
        $limitParams = $limit !== null ? [$limit, $offset] : [];
        if ($search) {
            $like = '%' . $search . '%';
            return $this->query(
                'SELECT * FROM products WHERE org_id = ? AND (name LIKE ? OR sku LIKE ? OR barcode LIKE ?) ORDER BY name' . $limitSql,
                array_merge([$orgId, $like, $like, $like], $limitParams)
            );
        }
        return $this->query('SELECT * FROM products WHERE org_id = ? ORDER BY name' . $limitSql, array_merge([$orgId], $limitParams));
    }

    public function countForOrg(int $orgId, ?string $search = null): int {
        if ($search) {
            $like = '%' . $search . '%';
            return $this->count('products', 'org_id = ? AND (name LIKE ? OR sku LIKE ? OR barcode LIKE ?)', [$orgId, $like, $like, $like]);
        }
        return $this->count('products', 'org_id = ?', [$orgId]);
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

    /** Called after a purchase — a new purchase becomes the product's current
     * cost/batch/expiry. A targeted UPDATE (not the general update()) so it can't
     * accidentally clobber the sparse `attributes` JSON or any other field. */
    public function updatePurchaseSnapshot(int $orgId, int $id, float $purchasePrice, ?string $batchNumber, ?string $expiryDate): bool {
        return $this->execute(
            'UPDATE products SET purchase_price = ?, batch_number = ?, expiry_date = ? WHERE org_id = ? AND id = ?',
            [$purchasePrice, $batchNumber, $expiryDate ?: null, $orgId, $id]
        );
    }

    /** Positive $qty increases stock, negative decreases — always call alongside an
     * InventoryTransaction row so stock changes stay auditable (see InventoryTransaction model). */
    public function adjustStock(int $orgId, int $id, float $qty): bool {
        return $this->execute('UPDATE products SET current_stock = current_stock + ? WHERE org_id = ? AND id = ?', [$qty, $orgId, $id]);
    }

    /** Used by AI bill scanning: an item on a scanned bill may or may not
     * already exist as a product — this matches by exact (case-insensitive)
     * name, or creates a new one. Category always defaults to 'Other', never
     * a guessed/hardcoded category — the prior app this scanning feature is
     * ported from had a real bug where every scanned import landed under one
     * wrong hardcoded category regardless of what was actually on the bill. */
    public function findOrCreateByName(int $orgId, string $name, array $defaults): int {
        $existing = $this->queryOne('SELECT * FROM products WHERE org_id = ? AND LOWER(name) = LOWER(?)', [$orgId, $name]);
        if ($existing) return (int) $existing['id'];
        return $this->create($orgId, array_merge(['name' => $name, 'category' => 'Other'], $defaults));
    }

    /** Bulk import match: barcode first (if given), then SKU — either can be
     * blank in a row. Returns null if neither matches an existing product. */
    public function findForImportMatch(int $orgId, ?string $barcode, ?string $sku): ?array {
        if ($barcode) {
            $row = $this->queryOne("SELECT * FROM products WHERE org_id = ? AND barcode = ? AND barcode != ''", [$orgId, $barcode]);
            if ($row) return $row;
        }
        if ($sku) {
            $row = $this->queryOne("SELECT * FROM products WHERE org_id = ? AND sku = ? AND sku != ''", [$orgId, $sku]);
            if ($row) return $row;
        }
        return null;
    }

    /** Merge mode for updating an existing product from an import row — only
     * overwrites a field when the row actually supplied a value, so a blank
     * cell doesn't wipe out data the row just didn't happen to include. */
    public function updateFromImportMerge(int $orgId, int $id, array $existing, array $row): bool {
        $merged = $existing;
        foreach (['name','sku','barcode','category','brand','unit','purchase_price','selling_price','mrp',
                  'min_stock','gst_percent','hsn'] as $field) {
            if (isset($row[$field]) && $row[$field] !== '' && $row[$field] !== null) {
                $merged[$field] = $row[$field];
            }
        }
        return $this->update($orgId, $id, $merged);
    }
}
