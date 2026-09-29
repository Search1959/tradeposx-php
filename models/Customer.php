<?php
class Customer extends BaseModel {
    public function allForOrg(int $orgId, ?string $search = null): array {
        if ($search) {
            $like = '%' . $search . '%';
            return $this->query('SELECT * FROM customers WHERE org_id = ? AND (name LIKE ? OR mobile LIKE ?) ORDER BY name', [$orgId, $like, $like]);
        }
        return $this->query('SELECT * FROM customers WHERE org_id = ? ORDER BY name', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM customers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function findByMobile(int $orgId, string $mobile): ?array {
        return $this->queryOne('SELECT * FROM customers WHERE org_id = ? AND mobile = ?', [$orgId, $mobile]);
    }

    public function withOutstanding(int $orgId): array {
        return $this->query('SELECT * FROM customers WHERE org_id = ? AND current_balance > 0 ORDER BY current_balance DESC', [$orgId]);
    }

    /** Creates the customer, and — if an opening balance was supplied — writes the
     * matching OPENING_BALANCE ledger row so current_balance and the transaction
     * history never disagree from day one. */
    public function create(int $orgId, array $d): int {
        $opening = (float) ($d['opening_balance'] ?? 0);
        $id = $this->insert(
            'INSERT INTO customers (org_id, name, mobile, address, area, email, opening_balance, current_balance, credit_limit, trust_rating, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $d['name'], $d['mobile'], $d['address'] ?? null, $d['area'] ?? null, $d['email'] ?? null,
             $opening, $opening, $d['credit_limit'] ?: null, $d['trust_rating'] ?? null, $d['notes'] ?? null]
        );
        if ($opening != 0 && $id) {
            (new CustomerTransaction())->record($orgId, $id, 'OPENING_BALANCE', $opening, $opening, null, null, 'Opening balance');
        }
        return $id;
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE customers SET name=?, mobile=?, address=?, area=?, email=?, credit_limit=?, trust_rating=?, notes=? WHERE org_id=? AND id=?',
            [$d['name'], $d['mobile'], $d['address'] ?? null, $d['area'] ?? null, $d['email'] ?? null,
             $d['credit_limit'] ?: null, $d['trust_rating'] ?? null, $d['notes'] ?? null, $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM customers WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    /** The only place current_balance ever changes after creation — always paired
     * with a CustomerTransaction row (see Customer::create, SaleController) so the
     * running balance and the ledger can never drift apart. Positive $delta = more
     * owed (credit sale); negative = balance reduced (payment received). */
    public function adjustBalance(int $orgId, int $id, float $delta): float {
        $this->execute('UPDATE customers SET current_balance = current_balance + ? WHERE org_id = ? AND id = ?', [$delta, $orgId, $id]);
        $row = $this->queryOne('SELECT current_balance FROM customers WHERE org_id = ? AND id = ?', [$orgId, $id]);
        return (float) ($row['current_balance'] ?? 0);
    }
}
