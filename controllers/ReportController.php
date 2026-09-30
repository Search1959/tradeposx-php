<?php
class ReportController {
    private Report $report;
    private Expense $expenseModel;

    public function __construct() {
        $this->report = new Report();
        $this->expenseModel = new Expense();
    }

    public function indexAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);

        $days = in_array((int) ($_GET['days'] ?? 30), [7, 30, 90], true) ? (int) ($_GET['days'] ?? 30) : 30;
        $sales = $this->report->salesSummary($org['id']);
        $topProducts = $this->report->topProducts($org['id'], $days);
        $margin = $this->report->marginSummary($org['id'], $days);
        $expensesTotal = $this->expenseModel->totalForOrg($org['id']);
        $outstandingUdhaar = $this->report->outstandingUdhaar($org['id']);
        $serviceOutstanding = $this->report->serviceOutstanding($org['id']);
        $netProfit = $margin['margin'] - $expensesTotal;

        // Income & Expenditure / Profit Analysis — both scoped to the same $days
        // window (unlike the summary cards above, which mix a period margin with
        // an all-time expense total), so these two read as one consistent period.
        $income = $this->report->combinedIncome($org['id'], $days);
        $expensesByHead = $this->report->expensesByHead($org['id'], $days);
        $periodExpenses = $this->report->expensesTotalForPeriod($org['id'], $days);
        $cogs = $this->report->cogsForPeriod($org['id'], $days);
        $periodNetProfit = $income['total'] - $cogs - $periodExpenses;

        $pageTitle = 'Reports';
        require __DIR__ . '/../views/trade/reports/index.php';
    }
}
