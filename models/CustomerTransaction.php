<?php
class CustomerTransaction extends BaseModel {
    public function record(int $orgId, int $customerId, string $type, float $amount, float $balanceAfter,
                            ?string $paymentMethod = null, ?string $referenceId = null, ?string $notes = null): int {
        return $this->insert(
            'INSERT INTO customer_transactions (org_id, customer_id, type, amount, balance_after, payment_method, reference_id, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [$orgId, $customerId, $type, $amount, $balanceAfter, $paymentMethod, $referenceId, $notes, Auth::user()['name'] ?? null]
        );
    }

    public function forCustomer(int $orgId, int $customerId): array {
        return $this->query(
            'SELECT * FROM customer_transactions WHERE org_id = ? AND customer_id = ? ORDER BY created_at DESC',
            [$orgId, $customerId]
        );
    }
}
