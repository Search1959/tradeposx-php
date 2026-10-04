<?php
class DashboardController {
    private Report $report;

    public function __construct() {
        $this->report = new Report();
    }

    /** TradeERP dashboard. */
    public function indexAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $_SESSION['workspace'] = 'trade';

        $sales = $this->report->salesSummary($org['id']);
        $todaySales = (float) $sales['today']['total'];
        $outstandingUdhaar = $this->report->outstandingUdhaar($org['id']);
        $lowStockCount = $this->report->lowStockCount($org['id']);
        $salesWeek = $this->report->salesByDay($org['id'], 7);
        $salesMonths = $this->report->salesByMonth($org['id'], 6);
        $stockStatus = $this->report->stockStatus($org['id']);
        $topUdhaar = $this->report->topUdhaarCustomers($org['id'], 5);
        $recentSales = $this->report->recentSales($org['id'], 5);
        $topProducts = $this->report->topProducts($org['id'], 30, 5);

        $pageTitle = 'Dashboard';
        require __DIR__ . '/../views/dashboard/index.php';
    }

    /** ServiceERP dashboard — its own KPIs, not TradeERP's mixed in. */
    public function serviceAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $_SESSION['workspace'] = 'service';

        $revenue = $this->report->serviceRevenueSummary($org['id']);
        $todayRevenue = (float) $revenue['today']['total'];
        $serviceOutstanding = $this->report->serviceOutstanding($org['id']);
        $todayAppointments = $this->report->todayAppointmentsCount($org['id']);
        $upcomingAppointments = $this->report->upcomingAppointmentsCount($org['id']);
        $pendingJobCards = $this->report->pendingJobCardsCount($org['id']);
        $revenueWeek = $this->report->serviceRevenueByDay($org['id'], 7);
        $revenueMonths = $this->report->serviceRevenueByMonth($org['id'], 6);
        $jobStatus = $this->report->jobCardsByStatus($org['id']);
        $todaysAppts = $this->report->todaysAppointments($org['id'], 5);
        $unpaidInvoices = $this->report->unpaidInvoices($org['id'], 5);

        $pageTitle = 'Dashboard';
        require __DIR__ . '/../views/dashboard/service.php';
    }

    /** Shown once right after login for a "both" business, and reachable any
     * time via "Switch Workspace" in the sidebar — picking a side here decides
     * which dashboard and sidebar section (TradeERP or ServiceERP) is active. */
    public function chooseAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);

        $todaySales = (float) $this->report->salesSummary($org['id'])['today']['total'];
        $todayAppointments = $this->report->todayAppointmentsCount($org['id']);
        $pendingJobCards = $this->report->pendingJobCardsCount($org['id']);

        $pageTitle = 'Choose Workspace';
        require __DIR__ . '/../views/dashboard/choose.php';
    }
}
