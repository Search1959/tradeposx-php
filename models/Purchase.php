<?php
class Purchase extends BaseModel {
    public function allForOrg(int $orgId, int $limit = 100, int $offset = 0): array {
        return $this->query('SELECT * FROM purchases WHERE org_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?', [$orgId, $limit, $offset]);
    }

    public function find(int $orgId, int $id): ?array {
        $purchase = $this->queryOne('SELECT * FROM purchases WHERE org_id = ? AND id = ?', [$orgId, $id]);
        if ($purchase) {
            $purchase['items'] = $this->query('SELECT * FROM purchase_items WHERE purchase_id = ?', [$id]);
        }
        return $purchase;
    }

    public function nextPurchaseNumber(int $orgId): string {
        $count = $this->count('purchases', 'org_id = ?', [$orgId]);
        return 'PU-' . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }

    /** Creates the purchase + line items in one transaction, increases stock for
     * every line (and snapshots the batch/expiry/purchase price onto the product,
     * matching how the KiranaMate app treated a new purchase as the product's
     * current batch), and adds any unpaid balance to the supplier's outstanding.
     *
     * @param array $items each: product_id, product_name, unit, quantity, purchase_price,
     *   gst_percent, gst_amount, total_price, hsn, batch_number, expiry_date, selling_price, mrp
     */
    public function createPurchase(int $orgId, array $data, array $items): array {
        if (empty($items)) return ['error' => 'At least one line item is required.'];

        $productModel = new Product();
        $invModel = new InventoryTransaction();
        $supplierModel = new Supplier();

        $subtotal = 0.0;
        $taxAmount = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['total_price'];
            $taxAmount += (float) ($item['gst_amount'] ?? 0);
        }
        $discountAmount = (float) ($data['discount_amount'] ?? 0);
        $freightCharges = (float) ($data['freight_charges'] ?? 0);
        $grandTotal = round($subtotal + $taxAmount + $freightCharges - $discountAmount, 2);
        $paidAmount = (float) ($data['paid_amount'] ?? $grandTotal);
        $balance = round($grandTotal - $paidAmount, 2);
        $paymentStatus = $balance <= 0 ? 'PAID' : ($paidAmount > 0 ? 'PARTIAL' : 'PENDING');
        $purchaseNumber = $this->nextPurchaseNumber($orgId);

        $this->db->beginTransaction();
        try {
            $purchaseId = $this->insert(
                'INSERT INTO purchases (org_id, purchase_number, supplier_id, supplier_name, invoice_number,
                 purchase_date, invoice_date, subtotal, tax_amount, discount_amount, freight_charges, grand_total,
                 paid_amount, payment_status, payment_method, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $orgId, $purchaseNumber, $data['supplier_id'] ?: null, $data['supplier_name'] ?? null,
                    $data['invoice_number'] ?? null, $data['purchase_date'] ?: date('Y-m-d'), $data['invoice_date'] ?: null,
                    $subtotal, $taxAmount, $discountAmount, $freightCharges, $grandTotal, $paidAmount, $paymentStatus,
                    $data['payment_method'] ?? 'CASH', $data['notes'] ?? null,
                ]
            );

            foreach ($items as $item) {
                $this->insert(
                    'INSERT INTO purchase_items (org_id, purchase_id, product_id, product_name, unit, quantity,
                     purchase_price, gst_percent, gst_amount, total_price, hsn, batch_number, expiry_date)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [
                        $orgId, $purchaseId, $item['product_id'], $item['product_name'], $item['unit'] ?? null,
                        $item['quantity'], $item['purchase_price'], $item['gst_percent'] ?? null, $item['gst_amount'] ?? null,
                        $item['total_price'], $item['hsn'] ?? null, $item['batch_number'] ?? null, $item['expiry_date'] ?: null,
                    ]
                );

                if (!empty($item['product_id'])) {
                    $productModel->adjustStock($orgId, (int) $item['product_id'], (float) $item['quantity']);
                    $invModel->record($orgId, (int) $item['product_id'], 'STOCK_IN_PURCHASE', (float) $item['quantity'], $purchaseNumber);
                    $productModel->updatePurchaseSnapshot(
                        $orgId, (int) $item['product_id'], (float) $item['purchase_price'],
                        $item['batch_number'] ?? null, $item['expiry_date'] ?? null
                    );
                }
            }

            if ($balance > 0 && $data['supplier_id']) {
                $supplierModel->adjustOutstanding($orgId, (int) $data['supplier_id'], $balance);
            }

            $this->db->commit();
            return ['id' => $purchaseId, 'purchaseNumber' => $purchaseNumber];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Purchase::createPurchase failed: ' . $e->getMessage());
            return ['error' => 'Could not save purchase. Please try again.'];
        }
    }

    /** Reverses a purchase's stock-in and supplier balance, mirroring Sale::voidSale() —
     * a purchase is never hard-deleted since it's a financial record, only cancelled. */
    public function cancelPurchase(int $orgId, int $id, string $reason): bool {
        $purchase = $this->find($orgId, $id);
        if (!$purchase || $purchase['status'] === 'CANCELLED') return false;

        $productModel = new Product();
        $invModel = new InventoryTransaction();
        $supplierModel = new Supplier();

        $this->db->beginTransaction();
        try {
            foreach ($purchase['items'] as $item) {
                if ($item['product_id']) {
                    $productModel->adjustStock($orgId, (int) $item['product_id'], -(float) $item['quantity']);
                    $invModel->record($orgId, (int) $item['product_id'], 'MANUAL_REDUCE', -(float) $item['quantity'], $purchase['purchase_number']);
                }
            }

            $balance = round((float) $purchase['grand_total'] - (float) $purchase['paid_amount'], 2);
            if ($balance > 0 && $purchase['supplier_id']) {
                $supplierModel->adjustOutstanding($orgId, (int) $purchase['supplier_id'], -$balance);
            }

            $this->execute(
                "UPDATE purchases SET status = 'CANCELLED', cancel_reason = ?, cancelled_at = NOW() WHERE org_id = ? AND id = ?",
                [$reason, $orgId, $id]
            );
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Purchase::cancelPurchase failed: ' . $e->getMessage());
            return false;
        }
    }
}
