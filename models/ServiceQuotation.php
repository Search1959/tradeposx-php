<?php
class ServiceQuotation extends BaseModel {
    public function allForOrg(int $orgId, int $limit = 20, int $offset = 0): array {
        return $this->query('SELECT * FROM service_quotations WHERE org_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?', [$orgId, $limit, $offset]);
    }

    public function countForOrg(int $orgId): int {
        return $this->count('service_quotations', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        $q = $this->queryOne('SELECT * FROM service_quotations WHERE org_id = ? AND id = ?', [$orgId, $id]);
        if ($q) {
            $q['items'] = $this->query('SELECT * FROM service_invoice_items WHERE quotation_id = ?', [$id]);
        }
        return $q;
    }

    public function nextQuoteNo(int $orgId): string {
        $count = $this->count('service_quotations', 'org_id = ?', [$orgId]);
        return 'QUO-' . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }

    /** @param array $items each: service_id?, name, item_type, price, quantity, total */
    public function create(int $orgId, array $data, array $items): array {
        if (empty($items)) return ['error' => 'At least one item is required.'];

        $itemsTotal = array_reduce($items, fn($sum, $i) => $sum + (float) $i['total'], 0.0);
        $labourCharges = (float) ($data['labour_charges'] ?? 0);
        $materialCharges = (float) ($data['material_charges'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $gstAmount = (float) ($data['gst_amount'] ?? 0);
        $grandTotal = round($itemsTotal + $labourCharges + $materialCharges + $gstAmount - $discount, 2);
        $quoteNo = $this->nextQuoteNo($orgId);

        $this->db->beginTransaction();
        try {
            $quoteId = $this->insert(
                'INSERT INTO service_quotations (org_id, quote_no, customer_id, customer_name, mobile, quote_date,
                 valid_until, labour_charges, material_charges, discount, gst_amount, grand_total, status, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $orgId, $quoteNo, $data['customer_id'] ?: null, $data['customer_name'] ?? null, $data['mobile'] ?? null,
                    $data['quote_date'] ?: date('Y-m-d'), $data['valid_until'] ?: null, $labourCharges, $materialCharges,
                    $discount, $gstAmount, $grandTotal, 'Draft', $data['notes'] ?? null,
                ]
            );
            foreach ($items as $item) {
                $this->insert(
                    'INSERT INTO service_invoice_items (org_id, quotation_id, service_id, name, item_type, price, quantity, total)
                     VALUES (?,?,?,?,?,?,?,?)',
                    [$orgId, $quoteId, $item['service_id'] ?? null, $item['name'], $item['item_type'] ?? 'SERVICE',
                     $item['price'], $item['quantity'], $item['total']]
                );
            }
            $this->db->commit();
            return ['id' => $quoteId, 'quoteNo' => $quoteNo];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('ServiceQuotation::create failed: ' . $e->getMessage());
            return ['error' => 'Could not save quotation. Please try again.'];
        }
    }

    public function updateStatus(int $orgId, int $id, string $status): bool {
        return $this->execute('UPDATE service_quotations SET status = ? WHERE org_id = ? AND id = ?', [$status, $orgId, $id]);
    }

    /** @param array $items each: service_id?, name, item_type, price, quantity, total */
    public function update(int $orgId, int $id, array $data, array $items): array {
        if (empty($items)) return ['error' => 'At least one item is required.'];

        $itemsTotal = array_reduce($items, fn($sum, $i) => $sum + (float) $i['total'], 0.0);
        $labourCharges = (float) ($data['labour_charges'] ?? 0);
        $materialCharges = (float) ($data['material_charges'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $gstAmount = (float) ($data['gst_amount'] ?? 0);
        $grandTotal = round($itemsTotal + $labourCharges + $materialCharges + $gstAmount - $discount, 2);

        $this->db->beginTransaction();
        try {
            $this->execute(
                'UPDATE service_quotations SET customer_id = ?, customer_name = ?, mobile = ?, quote_date = ?,
                 valid_until = ?, labour_charges = ?, material_charges = ?, discount = ?, gst_amount = ?, grand_total = ?, notes = ?
                 WHERE org_id = ? AND id = ?',
                [
                    $data['customer_id'] ?: null, $data['customer_name'] ?? null, $data['mobile'] ?? null,
                    $data['quote_date'] ?: date('Y-m-d'), $data['valid_until'] ?: null, $labourCharges, $materialCharges,
                    $discount, $gstAmount, $grandTotal, $data['notes'] ?? null, $orgId, $id,
                ]
            );

            $this->execute('DELETE FROM service_invoice_items WHERE org_id = ? AND quotation_id = ?', [$orgId, $id]);
            foreach ($items as $item) {
                $this->insert(
                    'INSERT INTO service_invoice_items (org_id, quotation_id, service_id, name, item_type, price, quantity, total)
                     VALUES (?,?,?,?,?,?,?,?)',
                    [$orgId, $id, $item['service_id'] ?? null, $item['name'], $item['item_type'] ?? 'SERVICE',
                     $item['price'], $item['quantity'], $item['total']]
                );
            }
            $this->db->commit();
            return ['id' => $id];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('ServiceQuotation::update failed: ' . $e->getMessage());
            return ['error' => 'Could not save quotation. Please try again.'];
        }
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM service_quotations WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    /** Converts an Approved quotation into a real invoice, carrying its items
     * over unpaid (status UNPAID — collecting payment happens on the invoice
     * itself afterwards, same as any other invoice), and marks the quotation
     * as converted so it isn't converted twice. */
    public function convertToInvoice(int $orgId, int $id): array {
        $quote = $this->find($orgId, $id);
        if (!$quote) return ['error' => 'Quotation not found.'];
        if ($quote['status'] === 'ConvertedToInvoice' || $quote['status'] === 'ConvertedToJob') {
            return ['error' => 'This quotation was already converted.'];
        }

        $invoiceModel = new ServiceInvoice();
        $items = array_map(fn($i) => [
            'service_id' => $i['service_id'], 'name' => $i['name'], 'item_type' => $i['item_type'],
            'price' => $i['price'], 'quantity' => $i['quantity'], 'total' => $i['total'], 'staff_id' => $i['staff_id'] ?? null,
        ], $quote['items']);

        $result = $invoiceModel->createInvoice($orgId, [
            'customer_id' => $quote['customer_id'], 'customer_name' => $quote['customer_name'], 'mobile' => $quote['mobile'],
            'labour_charges' => $quote['labour_charges'], 'material_charges' => $quote['material_charges'],
            'discount' => $quote['discount'], 'gst_amount' => $quote['gst_amount'], 'paid_amount' => 0,
            'notes' => 'Converted from quotation ' . $quote['quote_no'],
        ], $items);

        if (isset($result['error'])) return $result;

        $this->updateStatus($orgId, $id, 'ConvertedToInvoice');
        return ['invoiceId' => $result['id'], 'invoiceNo' => $result['invoiceNo']];
    }
}
