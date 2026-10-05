<?php
/** Platform-level billing ledger for System Admin: what each business owes TradePOSX.
 * Tables create themselves on first use (same self-healing approach as the rest of the app),
 * so deploying needs no manual SQL. */
class OrgSubscription extends BaseModel {
    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
    }

    private function ensureSchema(): void {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS org_subscriptions (
               org_id        INT NOT NULL PRIMARY KEY,
               monthly_fee   DECIMAL(10,2) NOT NULL DEFAULT 0,
               billing_start DATE NULL,
               updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
             ) ENGINE=InnoDB'
        );
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS org_payments (
               id         INT AUTO_INCREMENT PRIMARY KEY,
               org_id     INT NOT NULL,
               amount     DECIMAL(12,2) NOT NULL,
               note       VARCHAR(150) NULL,
               paid_on    DATE NOT NULL,
               created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               INDEX idx_orgpay_org (org_id)
             ) ENGINE=InnoDB'
        );
    }

    /** @return array<int,array{monthly_fee:float,billing_start:?string,paid:float}> keyed by org_id */
    public function allByOrg(): array {
        $out = [];
        foreach ($this->query('SELECT org_id, monthly_fee, billing_start FROM org_subscriptions') as $r) {
            $out[(int) $r['org_id']] = ['monthly_fee' => (float) $r['monthly_fee'], 'billing_start' => $r['billing_start'], 'paid' => 0.0];
        }
        foreach ($this->query('SELECT org_id, COALESCE(SUM(amount),0) AS paid FROM org_payments GROUP BY org_id') as $r) {
            $id = (int) $r['org_id'];
            $out[$id] ??= ['monthly_fee' => 0.0, 'billing_start' => null, 'paid' => 0.0];
            $out[$id]['paid'] = (float) $r['paid'];
        }
        return $out;
    }

    public function setFee(int $orgId, float $fee): bool {
        return $this->execute(
            'INSERT INTO org_subscriptions (org_id, monthly_fee) VALUES (?, ?) ON DUPLICATE KEY UPDATE monthly_fee = VALUES(monthly_fee)',
            [$orgId, max(0, $fee)]
        );
    }

    public function recordPayment(int $orgId, float $amount, ?string $note): bool {
        if ($amount <= 0) return false;
        return $this->execute(
            'INSERT INTO org_payments (org_id, amount, note, paid_on) VALUES (?, ?, ?, CURDATE())',
            [$orgId, $amount, $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 150) : null]
        );
    }

    /** Calendar months billed so far, counting the start month: signing up in March and checking in May = 3. */
    public static function monthsBilled(string $startDate): int {
        $s = new DateTime($startDate);
        $n = new DateTime('now');
        $months = ((int) $n->format('Y') - (int) $s->format('Y')) * 12 + ((int) $n->format('n') - (int) $s->format('n')) + 1;
        return max(1, $months);
    }
}
