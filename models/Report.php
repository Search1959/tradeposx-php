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
}
