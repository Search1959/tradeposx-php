<?php
class ServiceInvoice extends BaseModel {
    public function allForOrg(int $orgId, int $limit = 20, int $offset = 0): array {
        return $this->query('SELECT * FROM service_invoices WHERE org_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?', [$orgId, $limit, $offset]);
    }

    public function countForOrg(int $orgId): int {
        return $this->count('service_invoices', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        $inv = $this->queryOne('SELECT * FROM service_invoices WHERE org_id = ? AND id = ?', [$orgId, $id]);
        if ($inv) {
            $inv['items'] = $this->query('SELECT * FROM service_invoice_items WHERE invoice_id = ?', [$id]);
        }
        return $inv;
    }

    public function nextInvoiceNo(int $orgId): string {
        // Highest existing SVI- number (not just the row count) so numbers stay unique after an invoice is deleted.
        $count = $this->count('service_invoices', 'org_id = ?', [$orgId]);
        $row = $this->queryOne("SELECT MAX(CAST(SUBSTRING(invoice_no, 5) AS UNSIGNED)) AS m FROM service_invoices WHERE org_id = ? AND invoice_no LIKE 'SVI-%'", [$orgId]);
        $next = max($count, (int) ($row['m'] ?? 0)) + 1;
        return 'SVI-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Creates the invoice + line items in one transaction. Balance is tracked per
     * invoice (Service ERP has no customer-level running Udhaar ledger like
     * Trading) — the same "derive owed from the actual numbers" rule from the
     * Sale model still applies: amountOwed = grandTotal - paidAmount, regardless
     * of the payment_method label. Each line with a staff_id assigned auto-accrues
     * that staff member's commission_percent on that line's own total.
     *
     * @param array $items each: service_id?, name, item_type, price, quantity, total, staff_id?
     */
    public function createInvoice(int $orgId, array $data, array $items): array {
        if (empty($items)) return ['error' => 'At least one item is required.'];

        $staffModel = new ServiceStaff();
        $customerModel = new ServiceCustomer();
        $itemModel = new ServiceItem();

        $itemsTaxable = array_reduce($items, fn($sum, $i) => $sum + (float) $i['total'], 0.0);
        $labourCharges = (float) ($data['labour_charges'] ?? 0);
        $materialCharges = (float) ($data['material_charges'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $taxType = $data['tax_type'] ?? null;
        $gstAmount = (float) ($data['gst_amount'] ?? 0);
        $taxableAmount = $data['taxable_amount'] ?? null;
        $cgstAmount = $data['cgst_amount'] ?? null;
        $sgstAmount = $data['sgst_amount'] ?? null;
        $grandTotal = round($itemsTaxable + $labourCharges + $materialCharges + $gstAmount - $discount, 2);
        $paidAmount = (float) ($data['paid_amount'] ?? $grandTotal);
        $balance = round(max(0, $grandTotal - $paidAmount), 2);
        $status = $balance <= 0 ? 'PAID' : ($paidAmount > 0 ? 'PARTIAL' : 'UNPAID');
        $invoiceNo = $this->nextInvoiceNo($orgId);

        $this->db->beginTransaction();
        try {
            $invoiceId = $this->insert(
                'INSERT INTO service_invoices (org_id, invoice_no, customer_id, customer_name, mobile,
                 specialized_staff_id, staff_commission_percent, staff_commission_amount, invoice_date,
                 labour_charges, material_charges, discount, discount_percent, tax_type, gst_amount, taxable_amount, cgst_amount, sgst_amount,
                 grand_total, paid_amount, balance_amount, payment_method, status, job_card_id, appointment_id, notes, client_ref)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $orgId, $invoiceNo, $data['customer_id'] ?: null, $data['customer_name'] ?? null,
                    $data['mobile'] ?? null,
                    $data['specialized_staff_id'] ?: null, $data['staff_commission_percent'] ?? null, $data['staff_commission_amount'] ?? 0,
                    $data['invoice_date'] ?: date('Y-m-d'), $labourCharges, $materialCharges,
                    $discount, $data['discount_percent'] ?? null, $taxType, $gstAmount, $taxableAmount, $cgstAmount, $sgstAmount, $grandTotal, $paidAmount,
                    $balance, $data['payment_method'] ?? 'CASH', $status, $data['job_card_id'] ?: null,
                    $data['appointment_id'] ?: null, $data['notes'] ?? null, $data['client_ref'] ?? null,
                ]
            );

            if (!empty($data['specialized_staff_id']) && (float) ($data['staff_commission_amount'] ?? 0) > 0) {
                (new SpecializedStaff())->accrueCommission($orgId, (int) $data['specialized_staff_id'], (float) $data['staff_commission_amount']);
            }

            foreach ($items as $item) {
                $this->insert(
                    'INSERT INTO service_invoice_items (org_id, invoice_id, service_id, name, item_type, price, quantity, total, gst_rate, gst_amount, staff_id)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    [
                        $orgId, $invoiceId, $item['service_id'] ?? null, $item['name'], $item['item_type'] ?? 'SERVICE',
                        $item['price'], $item['quantity'], $item['total'], $item['gst_rate'] ?? null, $item['gst_amount'] ?? null,
                        $item['staff_id'] ?: null,
                    ]
                );
                if (!empty($item['service_id'])) {
                    $itemModel->adjustStock($orgId, (int) $item['service_id'], -(float) $item['quantity']);
                }
                if (!empty($item['staff_id'])) {
                    $staff = $staffModel->find($orgId, (int) $item['staff_id']);
                    if ($staff && (float) $staff['commission_percent'] > 0) {
                        $commission = round((float) $item['total'] * (float) $staff['commission_percent'] / 100, 2);
                        $staffModel->accrueCommission($orgId, (int) $item['staff_id'], $commission);
                    }
                }
            }

            if (!empty($data['customer_id'])) {
                $customerModel->addSpent($orgId, (int) $data['customer_id'], $grandTotal);
            }

            $this->db->commit();
            return ['id' => $invoiceId, 'invoiceNo' => $invoiceNo, 'balance' => $balance];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('ServiceInvoice::createInvoice failed: ' . $e->getMessage());
            return ['error' => 'Could not save invoice. Please try again.'];
        }
    }

    public function recordPayment(int $orgId, int $id, float $amount): bool {
        $invoice = $this->find($orgId, $id);
        if (!$invoice) return false;
        $newBalance = max(0, (float) $invoice['balance_amount'] - $amount);
        $newPaid = (float) $invoice['paid_amount'] + $amount;
        $status = $newBalance <= 0 ? 'PAID' : 'PARTIAL';
        return $this->execute(
            'UPDATE service_invoices SET paid_amount = ?, balance_amount = ?, status = ? WHERE org_id = ? AND id = ?',
            [$newPaid, $newBalance, $status, $orgId, $id]
        );
    }

    public function cancel(int $orgId, int $id, string $reason): bool {
        $invoice = $this->find($orgId, $id);
        $ok = $this->execute(
            "UPDATE service_invoices SET status = 'CANCELLED', cancel_reason = ?, cancelled_at = NOW() WHERE org_id = ? AND id = ?",
            [$reason, $orgId, $id]
        );
        if ($ok && $invoice && ($invoice['status'] ?? '') !== 'CANCELLED') {
            $itemModel = new ServiceItem();
            foreach ($invoice['items'] ?? [] as $line) {
                if (!empty($line['service_id'])) $itemModel->adjustStock($orgId, (int) $line['service_id'], (float) $line['quantity']);
            }
        }
        if ($ok && $invoice && !empty($invoice['specialized_staff_id']) && (float) $invoice['staff_commission_amount'] > 0) {
            (new SpecializedStaff())->payCommission($orgId, (int) $invoice['specialized_staff_id'], (float) $invoice['staff_commission_amount']);
        }
        return $ok;
    }

    /** Removes the invoice, its lines and payments for good. Stock and staff commission are put back first
     * (unless it was already cancelled, which did that). */
    public function deleteInvoice(int $orgId, int $id): bool {
        $invoice = $this->find($orgId, $id);
        if (!$invoice) return false;
        $this->db->beginTransaction();
        try {
            if (($invoice['status'] ?? '') !== 'CANCELLED') {
                $itemModel = new ServiceItem();
                foreach ($invoice['items'] ?? [] as $line) {
                    if (!empty($line['service_id'])) $itemModel->adjustStock($orgId, (int) $line['service_id'], (float) $line['quantity']);
                }
                if (!empty($invoice['specialized_staff_id']) && (float) $invoice['staff_commission_amount'] > 0) {
                    (new SpecializedStaff())->payCommission($orgId, (int) $invoice['specialized_staff_id'], (float) $invoice['staff_commission_amount']);
                }
            }
            $this->execute('DELETE FROM service_invoice_items WHERE invoice_id = ?', [$id]);
            $this->execute('DELETE FROM service_payments WHERE invoice_id = ?', [$id]);
            $this->execute('DELETE FROM service_invoices WHERE org_id = ? AND id = ?', [$orgId, $id]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('ServiceInvoice::deleteInvoice failed: ' . $e->getMessage());
            return false;
        }
    }

    /** Descriptive fields only (see Sale::updateDetails) — amounts, tax and payments are not editable. */
    public function updateDetails(int $orgId, int $id, array $d): bool {
        $set = 'invoice_date = ?, payment_method = ?, notes = ?';
        $params = [$d['invoice_date'], $d['payment_method'], $d['notes']];
        if (array_key_exists('customer_name', $d)) {
            $set .= ', customer_name = ?, mobile = ?';
            array_push($params, $d['customer_name'], $d['mobile']);
        }
        array_push($params, $orgId, $id);
        return $this->execute("UPDATE service_invoices SET {$set} WHERE org_id = ? AND id = ?", $params);
    }
    public function forExport(int $orgId, ?string $from, ?string $to): array {
        $sql = 'SELECT * FROM service_invoices WHERE org_id = ?'; $p = [$orgId];
        if ($from) { $sql .= ' AND invoice_date >= ?'; $p[] = $from; }
        if ($to)   { $sql .= ' AND invoice_date <= ?'; $p[] = $to; }
        return $this->query($sql . ' ORDER BY invoice_date, id', $p);
    }}
