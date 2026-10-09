<?php
class Sale extends BaseModel {
    public function allForOrg(int $orgId, int $limit = 100, int $offset = 0): array {
        return $this->query(
            'SELECT * FROM sales WHERE org_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$orgId, $limit, $offset]
        );
    }

    public function countForOrg(int $orgId): int {
        return $this->count('sales', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        $sale = $this->queryOne('SELECT * FROM sales WHERE org_id = ? AND id = ?', [$orgId, $id]);
        if ($sale) {
            $sale['items'] = $this->query('SELECT * FROM sale_items WHERE sale_id = ?', [$id]);
        }
        return $sale;
    }

    public function nextSaleNumber(int $orgId): string {
        $count = $this->count('sales', 'org_id = ?', [$orgId]);
        return 'SL-' . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function todayTotal(int $orgId): float {
        $row = $this->queryOne(
            "SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE org_id = ? AND status IS NULL AND DATE(created_at) = CURDATE()",
            [$orgId]
        );
        return (float) ($row['total'] ?? 0);
    }

    /**
     * Creates a sale + its line items in one transaction, deducts stock, and — the
     * fix this rebuild carries over from the KiranaMate incident this session —
     * derives the Udhaar shortfall from the actual numbers (grandTotal - received),
     * never from the payment_method label. A CASH sale that's only part-paid still
     * creates Udhaar for the rest; skipping that was a real bug that let unpaid
     * balances vanish silently.
     *
     * @param array $items each: product_id, product_name, quantity, unit_price, mrp, gst_rate
     * @return array{id:int, saleNumber:string, amountOwed:float}|array{error:string}
     */
    public function createSale(int $orgId, array $data, array $items): array {
        $productModel = new Product();
        $invModel = new InventoryTransaction();
        $customerModel = new Customer();
        $txnModel = new CustomerTransaction();

        // Validate stock before touching anything.
        foreach ($items as $item) {
            $product = $productModel->find($orgId, (int) $item['product_id']);
            if (!$product) return ['error' => 'Product not found: ' . ($item['product_name'] ?? '')];
            if ((float) $product['current_stock'] < (float) $item['quantity']) {
                return ['error' => 'Insufficient stock for ' . $product['name'] . ' (have ' . $product['current_stock'] . ', need ' . $item['quantity'] . ')'];
            }
        }

        $grandTotal = (float) $data['grand_total'];
        $receivedAmount = (float) ($data['received_amount'] ?? $grandTotal);
        $amountOwed = round(max(0, $grandTotal - $receivedAmount), 2);

        if ($amountOwed > 0 && empty($data['customer_id'])) {
            return ['error' => 'A customer must be selected for a part-paid or credit sale (Udhaar needs someone to owe it to).'];
        }

        $paymentStatus = $amountOwed <= 0 ? 'PAID' : ($receivedAmount > 0 ? 'PARTIAL' : 'PENDING');
        $saleNumber = $this->nextSaleNumber($orgId);

        $this->db->beginTransaction();
        try {
            $saleId = $this->insert(
                'INSERT INTO sales (org_id, sale_number, customer_id, customer_name, customer_mobile, customer_gstin,
                 broker_id, broker_commission_percent, broker_commission_amount,
                 subtotal, discount, discount_percent, tax_type, gst_rate, taxable_amount, cgst_amount, sgst_amount, total_tax_amount, grand_total,
                 payment_method, payment_status, received_amount, change_amount, created_by_name, notes, client_ref)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $orgId, $saleNumber, $data['customer_id'] ?: null, $data['customer_name'] ?? null,
                    $data['customer_mobile'] ?? null, $data['customer_gstin'] ?? null,
                    $data['broker_id'] ?: null, $data['broker_commission_percent'] ?? null, $data['broker_commission_amount'] ?? 0,
                    $data['subtotal'] ?? $grandTotal, $data['discount'] ?? 0, $data['discount_percent'] ?? null, $data['tax_type'] ?? null, $data['gst_rate'] ?? null,
                    $data['taxable_amount'] ?? null, $data['cgst_amount'] ?? null, $data['sgst_amount'] ?? null,
                    $data['total_tax_amount'] ?? null, $grandTotal, $data['payment_method'] ?? 'CASH', $paymentStatus,
                    $receivedAmount, max(0, $receivedAmount - $grandTotal), Auth::user()['name'] ?? null,
                    $data['notes'] ?? null, $data['client_ref'] ?? null,
                ]
            );

            if (!empty($data['broker_id']) && (float) ($data['broker_commission_amount'] ?? 0) > 0) {
                (new Broker())->accrueCommission($orgId, (int) $data['broker_id'], (float) $data['broker_commission_amount']);
            }

            foreach ($items as $item) {
                $this->insert(
                    'INSERT INTO sale_items (org_id, sale_id, product_id, product_name, quantity, unit_price, mrp,
                     total_price, gst_rate, gst_amount, hsn, batch_number, expiry_date)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [
                        $orgId, $saleId, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit_price'],
                        $item['mrp'] ?? null, $item['total_price'], $item['gst_rate'] ?? null, $item['gst_amount'] ?? null,
                        $item['hsn'] ?? null, $item['batch_number'] ?? null, $item['expiry_date'] ?: null,
                    ]
                );
                $productModel->adjustStock($orgId, (int) $item['product_id'], -1 * (float) $item['quantity']);
                $invModel->record($orgId, (int) $item['product_id'], 'STOCK_OUT_SALE', (float) $item['quantity'], $saleNumber);
            }

            if ($amountOwed > 0) {
                $balanceAfter = $customerModel->adjustBalance($orgId, (int) $data['customer_id'], $amountOwed);
                $txnModel->record($orgId, (int) $data['customer_id'], 'CREDIT_SALE', $amountOwed, $balanceAfter,
                    $data['payment_method'] ?? 'CASH', $saleNumber, 'Sale ' . $saleNumber);
            }

            $this->db->commit();
            return ['id' => $saleId, 'saleNumber' => $saleNumber, 'amountOwed' => $amountOwed];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Sale::createSale failed: ' . $e->getMessage());
            return ['error' => 'Could not complete sale. Please try again.'];
        }
    }

    /** Reverses stock and any Udhaar this sale created — never hard-deletes a sale
     * (GST/tax records must stay intact), just marks it CANCELLED. */
    public function voidSale(int $orgId, int $id, string $reason): bool {
        $sale = $this->find($orgId, $id);
        if (!$sale || $sale['status'] === 'CANCELLED') return false;

        $productModel = new Product();
        $invModel = new InventoryTransaction();

        $this->db->beginTransaction();
        try {
            foreach ($sale['items'] as $item) {
                if ($item['product_id']) {
                    $productModel->adjustStock($orgId, (int) $item['product_id'], (float) $item['quantity']);
                    $invModel->record($orgId, (int) $item['product_id'], 'SALE_CANCELLED', (float) $item['quantity'], $sale['sale_number']);
                }
            }

            $amountOwed = max(0, (float) $sale['grand_total'] - (float) $sale['received_amount']);
            if ($amountOwed > 0 && $sale['customer_id']) {
                $customerModel = new Customer();
                $txnModel = new CustomerTransaction();
                $balanceAfter = $customerModel->adjustBalance($orgId, (int) $sale['customer_id'], -$amountOwed);
                $txnModel->record($orgId, (int) $sale['customer_id'], 'RETURN_CREDIT', $amountOwed, $balanceAfter,
                    null, $sale['sale_number'], 'Voided: ' . $reason);
            }

            if (!empty($sale['broker_id']) && (float) $sale['broker_commission_amount'] > 0) {
                (new Broker())->payCommission($orgId, (int) $sale['broker_id'], (float) $sale['broker_commission_amount']);
            }

            $this->execute(
                "UPDATE sales SET status = 'CANCELLED', cancel_reason = ?, cancelled_at = NOW() WHERE org_id = ? AND id = ?",
                [$reason, $orgId, $id]
            );
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Sale::voidSale failed: ' . $e->getMessage());
            return false;
        }
    }

    /** Corrects the descriptive bits of a finished sale — never amounts or line items (void and
     * re-bill for those so stock, Udhaar and tax stay consistent). Customer name/mobile are only
     * editable on walk-in sales; a linked customer's details live on the Customers screen. */
    public function updateDetails(int $orgId, int $id, array $d): bool {
        $set = 'payment_method = ?, notes = ?';
        $params = [$d['payment_method'], $d['notes']];
        if (array_key_exists('customer_name', $d)) {
            $set .= ', customer_name = ?, customer_mobile = ?';
            array_push($params, $d['customer_name'], $d['customer_mobile']);
        }
        array_push($params, $orgId, $id);
        return $this->execute("UPDATE sales SET {$set} WHERE org_id = ? AND id = ?", $params);
    }
    /** Every sale in the (optional) date range, oldest first, for the accountant export. */
    public function forExport(int $orgId, ?string $from, ?string $to): array {
        $sql = 'SELECT * FROM sales WHERE org_id = ?'; $p = [$orgId];
        if ($from) { $sql .= ' AND DATE(created_at) >= ?'; $p[] = $from; }
        if ($to)   { $sql .= ' AND DATE(created_at) <= ?'; $p[] = $to; }
        return $this->query($sql . ' ORDER BY created_at, id', $p);
    }}
