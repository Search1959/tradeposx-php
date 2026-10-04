<?php
// Ad-hoc aggregation queries for the dashboard and Reports page — deliberately
// not per-row-scoped like the other models (no single "record" is being
// fetched/saved), so these live here rather than being bolted onto Sale/Product.
// Every query excludes cancelled sales (status IS NULL means "not cancelled" —
// see sales.status in sql/schema.sql) so a voided sale never inflates real numbers.
class Report extends BaseModel {
    public function salesSummary(int $orgId): array {
        $today = $this->queryOne(
            "SELECT COALESCE(SUM(grand_total),0) as total, COUNT(*) as cnt FROM sales WHERE org_id = ? AND status IS NULL AND DATE(created_at) = CURDATE()",
            [$orgId]
        );
        $week = $this->queryOne(
            "SELECT COALESCE(SUM(grand_total),0) as total, COUNT(*) as cnt FROM sales WHERE org_id = ? AND status IS NULL AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)",
            [$orgId]
        );
        $month = $this->queryOne(
            "SELECT COALESCE(SUM(grand_total),0) as total, COUNT(*) as cnt FROM sales WHERE org_id = ? AND status IS NULL AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())",
            [$orgId]
        );
        return ['today' => $today, 'week' => $week, 'month' => $month];
    }

    /** Real fast-moving-item ranking, computed from actual sale_items — no
     * hardcoded/fake stats (that was a bug fixed in the previous KiranaMate app). */
    public function topProducts(int $orgId, int $days = 30, int $limit = 10): array {
        return $this->query(
            "SELECT si.product_name, SUM(si.quantity) as qty_sold, SUM(si.total_price) as revenue
             FROM sale_items si JOIN sales s ON s.id = si.sale_id
             WHERE si.org_id = ? AND s.status IS NULL AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY si.product_name ORDER BY revenue DESC LIMIT ?",
            [$orgId, $days, $limit]
        );
    }

    /** Real margin = (selling price actually charged − product's purchase cost)
     * × quantity, summed across the period — not a hardcoded ratio. */
    public function marginSummary(int $orgId, int $days = 30): array {
        $row = $this->queryOne(
            "SELECT COALESCE(SUM(si.total_price),0) as revenue,
                    COALESCE(SUM(p.purchase_price * si.quantity),0) as cost
             FROM sale_items si JOIN sales s ON s.id = si.sale_id
             LEFT JOIN products p ON p.id = si.product_id
             WHERE si.org_id = ? AND s.status IS NULL AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$orgId, $days]
        );
        $revenue = (float) ($row['revenue'] ?? 0);
        $cost = (float) ($row['cost'] ?? 0);
        $margin = $revenue - $cost;
        return [
            'revenue' => $revenue, 'cost' => $cost, 'margin' => $margin,
            'marginPercent' => $revenue > 0 ? round($margin / $revenue * 100, 1) : 0,
        ];
    }

    public function outstandingUdhaar(int $orgId): float {
        $row = $this->queryOne('SELECT COALESCE(SUM(current_balance),0) as total FROM customers WHERE org_id = ?', [$orgId]);
        return (float) ($row['total'] ?? 0);
    }

    public function lowStockCount(int $orgId): int {
        return $this->count('products', "org_id = ? AND status = 'ACTIVE' AND current_stock <= min_stock", [$orgId]);
    }

    public function todayAppointmentsCount(int $orgId): int {
        return $this->count('appointments', "org_id = ? AND appt_date = CURDATE()", [$orgId]);
    }

    public function pendingJobCardsCount(int $orgId): int {
        return $this->count('job_cards', "org_id = ? AND status IN ('Pending','In-Progress')", [$orgId]);
    }

    public function serviceRevenueSummary(int $orgId): array {
        $today = $this->queryOne(
            "SELECT COALESCE(SUM(grand_total),0) as total, COUNT(*) as cnt FROM service_invoices WHERE org_id = ? AND status != 'CANCELLED' AND DATE(created_at) = CURDATE()",
            [$orgId]
        );
        $week = $this->queryOne(
            "SELECT COALESCE(SUM(grand_total),0) as total, COUNT(*) as cnt FROM service_invoices WHERE org_id = ? AND status != 'CANCELLED' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)",
            [$orgId]
        );
        $month = $this->queryOne(
            "SELECT COALESCE(SUM(grand_total),0) as total, COUNT(*) as cnt FROM service_invoices WHERE org_id = ? AND status != 'CANCELLED' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())",
            [$orgId]
        );
        return ['today' => $today, 'week' => $week, 'month' => $month];
    }

    public function upcomingAppointmentsCount(int $orgId): int {
        return $this->count('appointments', "org_id = ? AND appt_date >= CURDATE() AND status NOT IN ('Completed','Cancelled')", [$orgId]);
    }

    public function serviceOutstanding(int $orgId): float {
        $invRow = $this->queryOne("SELECT COALESCE(SUM(balance_amount),0) as total FROM service_invoices WHERE org_id = ? AND status != 'CANCELLED'", [$orgId]);
        $jobRow = $this->queryOne("SELECT COALESCE(SUM(balance_amount),0) as total FROM job_cards WHERE org_id = ? AND status != 'Cancelled'", [$orgId]);
        return (float) ($invRow['total'] ?? 0) + (float) ($jobRow['total'] ?? 0);
    }

    /** Combined TradeERP + ServiceERP revenue for the period — an org running
     * only one module simply has 0 on the other side, so this reads correctly
     * for every business_type without a separate code path per type. */
    public function combinedIncome(int $orgId, int $days): array {
        $trade = $this->queryOne(
            "SELECT COALESCE(SUM(grand_total),0) as total FROM sales WHERE org_id = ? AND status IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$orgId, $days]
        );
        $service = $this->queryOne(
            "SELECT COALESCE(SUM(grand_total),0) as total FROM service_invoices WHERE org_id = ? AND status != 'CANCELLED' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$orgId, $days]
        );
        $tradeTotal = (float) ($trade['total'] ?? 0);
        $serviceTotal = (float) ($service['total'] ?? 0);
        return ['trade' => $tradeTotal, 'service' => $serviceTotal, 'total' => $tradeTotal + $serviceTotal];
    }

    /** Expense breakdown by head for the period — the summary cards' own
     * expensesTotal is deliberately all-time, but a period report needs a
     * window that matches its own revenue figures. */
    public function expensesByHead(int $orgId, int $days): array {
        return $this->query(
            "SELECT category, COALESCE(SUM(amount),0) as total FROM expenses
             WHERE org_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY category ORDER BY total DESC",
            [$orgId, $days]
        );
    }

    public function expensesTotalForPeriod(int $orgId, int $days): float {
        $row = $this->queryOne(
            "SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE org_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$orgId, $days]
        );
        return (float) ($row['total'] ?? 0);
    }

    /** TradeERP's own cost of goods sold for the period — ServiceERP has no
     * equivalent per-item cost tracked (see marginSummary's own note), so
     * Profit Analysis only ever subtracts this side. */
    public function cogsForPeriod(int $orgId, int $days): float {
        $row = $this->queryOne(
            "SELECT COALESCE(SUM(p.purchase_price * si.quantity),0) as cost
             FROM sale_items si JOIN sales s ON s.id = si.sale_id
             LEFT JOIN products p ON p.id = si.product_id
             WHERE si.org_id = ? AND s.status IS NULL AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$orgId, $days]
        );
        return (float) ($row['cost'] ?? 0);
    }

    // ── Dashboard series/lists (zero-filled so a quiet day still shows as a bar) ──

    /** @return array{labels:string[],values:float[]} last $days days incl. today */
    private function dailySeries(string $table, string $where, int $orgId, int $days): array {
        $rows = $this->query(
            "SELECT DATE(created_at) AS d, COALESCE(SUM(grand_total),0) AS t FROM {$table}
             WHERE org_id = ? AND {$where} AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(created_at)",
            [$orgId, $days - 1]
        );
        $byDay = [];
        foreach ($rows as $r) { $byDay[$r['d']] = (float) $r['t']; }
        $labels = []; $values = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} day"));
            $labels[] = date('D', strtotime($d));
            $values[] = $byDay[$d] ?? 0.0;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /** @return array{labels:string[],values:float[]} last $months calendar months incl. this one */
    private function monthlySeries(string $table, string $where, int $orgId, int $months): array {
        $start = date('Y-m-01', strtotime('-' . ($months - 1) . ' month'));
        $rows = $this->query(
            "SELECT DATE_FORMAT(created_at,'%Y-%m') AS m, COALESCE(SUM(grand_total),0) AS t FROM {$table}
             WHERE org_id = ? AND {$where} AND created_at >= ? GROUP BY DATE_FORMAT(created_at,'%Y-%m')",
            [$orgId, $start]
        );
        $byMonth = [];
        foreach ($rows as $r) { $byMonth[$r['m']] = (float) $r['t']; }
        $labels = []; $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -{$i} month");
            $labels[] = date('M', $ts);
            $values[] = $byMonth[date('Y-m', $ts)] ?? 0.0;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    public function salesByDay(int $orgId, int $days = 7): array { return $this->dailySeries('sales', 'status IS NULL', $orgId, $days); }
    public function salesByMonth(int $orgId, int $months = 6): array { return $this->monthlySeries('sales', 'status IS NULL', $orgId, $months); }
    public function serviceRevenueByDay(int $orgId, int $days = 7): array { return $this->dailySeries('service_invoices', "status != 'CANCELLED'", $orgId, $days); }
    public function serviceRevenueByMonth(int $orgId, int $months = 6): array { return $this->monthlySeries('service_invoices', "status != 'CANCELLED'", $orgId, $months); }

    /** @return array{ok:int,low:int,out:int} active products only */
    public function stockStatus(int $orgId): array {
        $r = $this->queryOne(
            "SELECT COALESCE(SUM(current_stock <= 0),0) AS out_n,
                    COALESCE(SUM(current_stock > 0 AND current_stock <= min_stock),0) AS low_n,
                    COALESCE(SUM(current_stock > min_stock),0) AS ok_n
             FROM products WHERE org_id = ? AND status = 'ACTIVE'",
            [$orgId]
        );
        return ['ok' => (int) ($r['ok_n'] ?? 0), 'low' => (int) ($r['low_n'] ?? 0), 'out' => (int) ($r['out_n'] ?? 0)];
    }

    public function topUdhaarCustomers(int $orgId, int $limit = 5): array {
        return $this->query(
            'SELECT id, name, mobile, current_balance FROM customers WHERE org_id = ? AND current_balance > 0 ORDER BY current_balance DESC LIMIT ?',
            [$orgId, $limit]
        );
    }

    public function recentSales(int $orgId, int $limit = 5): array {
        return $this->query(
            'SELECT id, sale_number, customer_name, grand_total, payment_status, created_at FROM sales WHERE org_id = ? AND status IS NULL ORDER BY id DESC LIMIT ?',
            [$orgId, $limit]
        );
    }

    /** @return array<string,int> job card counts keyed by status */
    public function jobCardsByStatus(int $orgId): array {
        $out = ['Pending' => 0, 'In-Progress' => 0, 'Completed' => 0, 'Delivered' => 0];
        foreach ($this->query("SELECT status, COUNT(*) AS n FROM job_cards WHERE org_id = ? AND status != 'Cancelled' GROUP BY status", [$orgId]) as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        return $out;
    }

    public function todaysAppointments(int $orgId, int $limit = 5): array {
        return $this->query(
            "SELECT id, customer_name, service_name, staff_name, appt_time, status FROM appointments
             WHERE org_id = ? AND appt_date = CURDATE() AND status NOT IN ('Cancelled') ORDER BY appt_time LIMIT ?",
            [$orgId, $limit]
        );
    }

    public function unpaidInvoices(int $orgId, int $limit = 5): array {
        return $this->query(
            "SELECT id, invoice_no, customer_name, balance_amount FROM service_invoices
             WHERE org_id = ? AND status IN ('UNPAID','PARTIAL') AND balance_amount > 0 ORDER BY balance_amount DESC LIMIT ?",
            [$orgId, $limit]
        );
    }
}
