<?php
class InventoryTransaction extends BaseModel {
    public function record(int $orgId, int $productId, string $type, float $quantity, ?string $referenceId = null, ?string $notes = null): int {
        return $this->insert(
            'INSERT INTO inventory_transactions (org_id, product_id, type, quantity, reference_id, notes) VALUES (?,?,?,?,?,?)',
            [$orgId, $productId, $type, $quantity, $referenceId, $notes]
        );
    }

    public function forProduct(int $orgId, int $productId): array {
        return $this->query(
            'SELECT * FROM inventory_transactions WHERE org_id = ? AND product_id = ? ORDER BY created_at DESC',
            [$orgId, $productId]
        );
    }
}
