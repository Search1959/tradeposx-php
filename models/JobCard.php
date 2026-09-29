<?php
class JobCard extends BaseModel {
    public function allForOrg(int $orgId): array {
        return $this->query('SELECT * FROM job_cards WHERE org_id = ? ORDER BY created_at DESC', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        $job = $this->queryOne('SELECT * FROM job_cards WHERE org_id = ? AND id = ?', [$orgId, $id]);
        if ($job) {
            $job['parts'] = $this->query('SELECT * FROM job_card_parts WHERE job_card_id = ?', [$id]);
        }
        return $job;
    }

    public function nextJobNo(int $orgId): string {
        $count = $this->count('job_cards', 'org_id = ?', [$orgId]);
        return 'JOB-' . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function create(int $orgId, array $d, array $parts = []): int {
        $materialCharges = array_reduce($parts, fn($sum, $p) => $sum + (float)$p['total_price'], 0.0);
        $labourCharges = (float) ($d['labour_charges'] ?? 0);
        $totalAmount = round($labourCharges + $materialCharges, 2);

        $jobNo = $this->nextJobNo($orgId);
        $jobId = $this->insert(
            'INSERT INTO job_cards (org_id, job_no, customer_id, customer_name, mobile, service_sector,
             assigned_staff_id, assigned_staff_name, priority, issue_description, device_or_vehicle_info,
             warranty_status, warranty_expiry_date, labour_charges, material_charges, total_amount, balance_amount,
             estimated_completion_date)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $orgId, $jobNo, $d['customer_id'] ?: null, $d['customer_name'] ?? null, $d['mobile'] ?? null,
                $d['service_sector'] ?? null, $d['assigned_staff_id'] ?: null, $d['assigned_staff_name'] ?? null,
                $d['priority'] ?? 'Medium', $d['issue_description'] ?? null, $d['device_or_vehicle_info'] ?? null,
                $d['warranty_status'] ?? 'None', $d['warranty_expiry_date'] ?: null,
                $labourCharges, $materialCharges, $totalAmount, $totalAmount, $d['estimated_completion_date'] ?: null,
            ]
        );

        foreach ($parts as $p) {
            $this->insert(
                'INSERT INTO job_card_parts (org_id, job_card_id, name, quantity, unit_price, total_price) VALUES (?,?,?,?,?,?)',
                [$orgId, $jobId, $p['name'], $p['quantity'], $p['unit_price'], $p['total_price']]
            );
        }

        return $jobId;
    }

    public function updateStatus(int $orgId, int $id, string $status): bool {
        $sql = 'UPDATE job_cards SET status = ?';
        $params = [$status];
        if ($status === 'Delivered' || $status === 'Completed') {
            $sql .= ', actual_completion_date = COALESCE(actual_completion_date, CURDATE())';
        }
        $sql .= ' WHERE org_id = ? AND id = ?';
        $params[] = $orgId;
        $params[] = $id;
        return $this->execute($sql, $params);
    }

    /** Grants (or replaces) a service warranty on this specific repair —
     * separate from whatever manufacturer/extended warranty was recorded at
     * intake, since after a repair the most relevant coverage for THIS issue
     * is the shop's own guarantee on the work just done. */
    public function grantServiceWarranty(int $orgId, int $id, int $days): bool {
        return $this->execute(
            "UPDATE job_cards SET warranty_status = 'Service Warranty', warranty_expiry_date = DATE_ADD(CURDATE(), INTERVAL ? DAY) WHERE org_id = ? AND id = ?",
            [$days, $orgId, $id]
        );
    }

    /** Records a payment against this job card's balance — mirrors ServiceInvoice's
     * own paid/balance tracking (Service ERP has no customer-level running ledger
     * like Trading's Udhaar; balance lives on the job card / invoice itself). */
    public function recordPayment(int $orgId, int $id, float $amount): bool {
        return $this->execute(
            'UPDATE job_cards SET paid_amount = paid_amount + ?, balance_amount = GREATEST(0, balance_amount - ?) WHERE org_id = ? AND id = ?',
            [$amount, $amount, $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM job_cards WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }
}
