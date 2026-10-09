<?php
class ServiceItem extends BaseModel {
    private static bool $stockColumnsChecked = false;

    /** Goods sold on a service bill (chargers, spares, accessories) are tracked as item_type STOCK with a
     * stock_qty; plain services keep item_type SERVICE and a NULL stock_qty. Columns are added on first use
     * so a live database needs no manual migration. */
    public function __construct() {
        parent::__construct();
        if (!self::$stockColumnsChecked) {
            self::$stockColumnsChecked = true;
            $cols = array_column($this->query('SHOW COLUMNS FROM service_items'), 'Field');
            if (!in_array('item_type', $cols, true)) {
                $this->db->exec("ALTER TABLE service_items ADD COLUMN item_type VARCHAR(10) NOT NULL DEFAULT 'SERVICE'");
            }
            if (!in_array('stock_qty', $cols, true)) {
                $this->db->exec('ALTER TABLE service_items ADD COLUMN stock_qty DECIMAL(12,3) NULL');
            }
        }
    }
    /** $limit stays null (no LIMIT) for every caller needing the full catalog to
     * populate a picker (appointment/invoice/quotation service pickers) — only
     * ServiceItemController::listAction's own paginated screen passes a real limit. */
    public function allForOrg(int $orgId, ?int $limit = null, int $offset = 0): array {
        $limitSql = $limit !== null ? ' LIMIT ? OFFSET ?' : '';
        $limitParams = $limit !== null ? [$limit, $offset] : [];
        return $this->query('SELECT * FROM service_items WHERE org_id = ? ORDER BY name' . $limitSql, array_merge([$orgId], $limitParams));
    }

    public function countForOrg(int $orgId): int {
        return $this->count('service_items', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM service_items WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO service_items (org_id, name, category, price, duration_minutes, warranty_days, gst_percent, sector, description, is_popular, item_type, stock_qty)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $d['name'], $d['category'] ?? null, $d['price'] ?? 0, $d['duration_minutes'] ?: null,
             $d['warranty_days'] ?: 0, $d['gst_percent'] ?? 0, $d['sector'] ?? 'General', $d['description'] ?? null,
             !empty($d['is_popular']) ? 1 : 0, ($d['item_type'] ?? 'SERVICE') === 'STOCK' ? 'STOCK' : 'SERVICE',
             ($d['item_type'] ?? 'SERVICE') === 'STOCK' ? (float) ($d['stock_qty'] ?? 0) : null]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE service_items SET name=?, category=?, price=?, duration_minutes=?, warranty_days=?, gst_percent=?, sector=?, description=?, is_popular=?, item_type=?, stock_qty=?
             WHERE org_id=? AND id=?',
            [$d['name'], $d['category'] ?? null, $d['price'] ?? 0, $d['duration_minutes'] ?: null,
             $d['warranty_days'] ?: 0, $d['gst_percent'] ?? 0, $d['sector'] ?? 'General', $d['description'] ?? null,
             !empty($d['is_popular']) ? 1 : 0, ($d['item_type'] ?? 'SERVICE') === 'STOCK' ? 'STOCK' : 'SERVICE',
             ($d['item_type'] ?? 'SERVICE') === 'STOCK' ? (float) ($d['stock_qty'] ?? 0) : null, $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM service_items WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    /** Touches only gst_percent — used by the list screen's bulk "Set GST% for
     * Selected" to fix a batch of items that imported or were saved at 0% without
     * rewriting every other column (unlike update(), which expects a full form post). */
    public function updateGstPercent(int $orgId, int $id, float $gstPercent): bool {
        return $this->execute('UPDATE service_items SET gst_percent = ? WHERE org_id = ? AND id = ?', [$gstPercent, $orgId, $id]);
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

    /** Moves stock for a STOCK item (negative delta = sold, positive = returned). Plain services are left alone,
     * so callers can pass every invoice line without checking the type first. Stock may go below zero —
     * a counter sale is never blocked; the screen just warns. */
    public function adjustStock(int $orgId, int $id, float $delta): void {
        $this->execute("UPDATE service_items SET stock_qty = COALESCE(stock_qty, 0) + ? WHERE org_id = ? AND id = ? AND item_type = 'STOCK'", [$delta, $orgId, $id]);
    }

    /** Turns an item into (or refreshes) a stock item with this quantity on hand. */
    public function setStock(int $orgId, int $id, float $qty): bool {
        return $this->execute("UPDATE service_items SET item_type = 'STOCK', stock_qty = ? WHERE org_id = ? AND id = ?", [$qty, $orgId, $id]);
    }
}