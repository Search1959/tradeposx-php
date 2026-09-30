<?php
class Appointment extends BaseModel {
    public function allForOrg(int $orgId, int $limit = 20, int $offset = 0): array {
        return $this->query('SELECT * FROM appointments WHERE org_id = ? ORDER BY appt_date DESC, appt_time DESC LIMIT ? OFFSET ?', [$orgId, $limit, $offset]);
    }

    public function countForOrg(int $orgId): int {
        return $this->count('appointments', 'org_id = ?', [$orgId]);
    }

    public function todayForOrg(int $orgId): array {
        return $this->query("SELECT * FROM appointments WHERE org_id = ? AND appt_date = CURDATE() ORDER BY appt_time", [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM appointments WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO appointments (org_id, customer_id, customer_name, mobile, service_id, service_name, service_sector,
             staff_id, staff_name, appt_date, appt_time, notes, total_amount)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $orgId, $d['customer_id'] ?: null, $d['customer_name'] ?? null, $d['mobile'] ?? null,
                $d['service_id'] ?: null, $d['service_name'] ?? null, $d['service_sector'] ?? null,
                $d['staff_id'] ?: null, $d['staff_name'] ?? null, $d['appt_date'], $d['appt_time'],
                $d['notes'] ?? null, $d['total_amount'] ?? 0,
            ]
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE appointments SET customer_id = ?, customer_name = ?, mobile = ?, service_id = ?, service_name = ?,
             service_sector = ?, staff_id = ?, staff_name = ?, appt_date = ?, appt_time = ?, notes = ?, total_amount = ?
             WHERE org_id = ? AND id = ?',
            [
                $d['customer_id'] ?: null, $d['customer_name'] ?? null, $d['mobile'] ?? null,
                $d['service_id'] ?: null, $d['service_name'] ?? null, $d['service_sector'] ?? null,
                $d['staff_id'] ?: null, $d['staff_name'] ?? null, $d['appt_date'], $d['appt_time'],
                $d['notes'] ?? null, $d['total_amount'] ?? 0, $orgId, $id,
            ]
        );
    }

    public function updateStatus(int $orgId, int $id, string $status): bool {
        return $this->execute('UPDATE appointments SET status = ? WHERE org_id = ? AND id = ?', [$status, $orgId, $id]);
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM appointments WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }
}
