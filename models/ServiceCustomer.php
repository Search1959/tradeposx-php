<?php
class ServiceCustomer extends BaseModel {
    /** $limit stays null (no LIMIT) for every caller needing the full list to
     * populate a picker (appointment/job-card/invoice/quotation customer pickers) —
     * only ServiceCustomerController::listAction's own paginated screen passes a
     * real limit. */
    public function allForOrg(int $orgId, ?string $search = null, ?int $limit = null, int $offset = 0): array {
        $limitSql = $limit !== null ? ' LIMIT ? OFFSET ?' : '';
        $limitParams = $limit !== null ? [$limit, $offset] : [];
        if ($search) {
            $like = '%' . $search . '%';
            return $this->query('SELECT * FROM service_customers WHERE org_id = ? AND (name LIKE ? OR mobile LIKE ?) ORDER BY name' . $limitSql, array_merge([$orgId, $like, $like], $limitParams));
        }
        return $this->query('SELECT * FROM service_customers WHERE org_id = ? ORDER BY name' . $limitSql, array_merge([$orgId], $limitParams));
    }

    public function countForOrg(int $orgId, ?string $search = null): int {
        if ($search) {
            $like = '%' . $search . '%';
            return $this->count('service_customers', 'org_id = ? AND (name LIKE ? OR mobile LIKE ?)', [$orgId, $like, $like]);
        }
        return $this->count('service_customers', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM service_customers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function findByMobile(int $orgId, string $mobile): ?array {
        return $this->queryOne('SELECT * FROM service_customers WHERE org_id = ? AND mobile = ?', [$orgId, $mobile]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO service_customers (org_id, name, mobile, email, address, sector, notes) VALUES (?,?,?,?,?,?,?)',
            [$orgId, $d['name'], $d['mobile'], $d['email'] ?? null, $d['address'] ?? null, $d['sector'] ?? null, $d['notes'] ?? null]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE service_customers SET name=?, mobile=?, email=?, address=?, sector=?, notes=? WHERE org_id=? AND id=?',
            [$d['name'], $d['mobile'], $d['email'] ?? null, $d['address'] ?? null, $d['sector'] ?? null, $d['notes'] ?? null, $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM service_customers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function addSpent(int $orgId, int $id, float $amount): bool {
        return $this->execute('UPDATE service_customers SET total_spent = total_spent + ? WHERE org_id = ? AND id = ?', [$amount, $orgId, $id]);
    }

    /** Everything a shop owner wants on one screen about a customer: bills, jobs and bookings. */
    public function history(int $orgId, int $id): array {
        return [
            'invoices' => $this->query('SELECT id, invoice_no, invoice_date, grand_total, balance_amount, status FROM service_invoices WHERE org_id = ? AND customer_id = ? ORDER BY id DESC LIMIT 25', [$orgId, $id]),
            'jobs' => $this->query('SELECT id, job_no, issue_description, total_amount, balance_amount, status FROM job_cards WHERE org_id = ? AND customer_id = ? ORDER BY id DESC LIMIT 25', [$orgId, $id]),
            'appointments' => $this->query('SELECT id, appt_date, appt_time, service_name, status FROM appointments WHERE org_id = ? AND customer_id = ? ORDER BY appt_date DESC, appt_time DESC LIMIT 25', [$orgId, $id]),
        ];
    }}
