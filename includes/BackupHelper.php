<?php
/**
 * Pure-PHP SQL backup generator — no shell_exec()/mysqldump dependency, since
 * shared hosting commonly disables exec()-family functions entirely. Builds
 * plain INSERT statements table by table via PDO, so the output is a normal
 * .sql file any phpMyAdmin can import back in, on this host or any other.
 *
 * Exists because this app replaced one whose data was lost with no working
 * backup — every business using this platform should be able to pull their
 * own data out at any time without depending on anyone else.
 */
class BackupHelper {
    /** All tables scoped by org_id, in FK-safe order (parents before children) — kept
     * in sync with sql/schema.sql. organizations/users are handled separately below
     * since they're keyed by id/org_id rather than uniformly by org_id. */
    private const ORG_SCOPED_TABLES = [
        'suppliers', 'products', 'customers', 'customer_transactions', 'purchases', 'purchase_items',
        'sales', 'sale_items', 'expenses', 'inventory_transactions',
        'service_items', 'service_customers', 'service_staff', 'appointments', 'job_cards', 'job_card_parts',
        'service_invoices', 'service_invoice_items', 'service_quotations', 'service_payments',
        'notifications', 'activity_log',
    ];

    /** One organization's complete data — safe for an org owner to download
     * themselves (never includes any other tenant's rows). */
    public static function exportOrg(PDO $db, int $orgId): string {
        $sql = self::header("Backup for organization #{$orgId}");
        $sql .= self::dumpRows($db, 'organizations', 'id = ?', [$orgId]);
        $sql .= self::dumpRows($db, 'users', 'org_id = ?', [$orgId]);
        foreach (self::ORG_SCOPED_TABLES as $table) {
            $sql .= self::dumpRows($db, $table, 'org_id = ?', [$orgId]);
        }
        return $sql . self::footer();
    }

    /** Every organization's data — SuperAdmin only, for full-platform disaster
     * recovery. Never exposed to a tenant-level user. */
    public static function exportFullDatabase(PDO $db): string {
        $sql = self::header('Full platform backup');
        $sql .= self::dumpRows($db, 'organizations');
        $sql .= self::dumpRows($db, 'users');
        foreach (self::ORG_SCOPED_TABLES as $table) {
            $sql .= self::dumpRows($db, $table);
        }
        return $sql . self::footer();
    }

    private static function header(string $label): string {
        return "-- {$label}\n-- Generated " . date('Y-m-d H:i:s') . " by " . APP_NAME . "\n"
             . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    }

    private static function footer(): string {
        return "\nSET FOREIGN_KEY_CHECKS=1;\n";
    }

    private static function dumpRows(PDO $db, string $table, ?string $where = null, array $params = []): string {
        $sql = $where ? "SELECT * FROM `{$table}` WHERE {$where}" : "SELECT * FROM `{$table}`";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        if (empty($rows)) return '';

        $columns = array_keys($rows[0]);
        $columnList = '`' . implode('`, `', $columns) . '`';
        $out = "-- {$table} (" . count($rows) . " rows)\n";
        foreach ($rows as $row) {
            $values = array_map(fn($v) => self::quote($db, $v), $row);
            $out .= "INSERT INTO `{$table}` ({$columnList}) VALUES (" . implode(', ', $values) . ");\n";
        }
        return $out . "\n";
    }

    private static function quote(PDO $db, $value): string {
        return $value === null ? 'NULL' : $db->quote((string) $value);
    }
}
