<?php
class DashboardController {
    private Report $report;

    public function __construct() {
        $this->report = new Report();
    }

    public function indexAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);

        $sales = $this->report->salesSummary($org['id']);
        $todaySales = (float) $sales['today']['total'];
        $outstandingUdhaar = $this->report->outstandingUdhaar($org['id']);
        $todayAppointments = $this->report->todayAppointmentsCount($org['id']);
        $pendingJobCards = $this->report->pendingJobCardsCount($org['id']);
        $lowStockCount = $this->report->lowStockCount($org['id']);

        $pageTitle = 'Dashboard';
        require __DIR__ . '/../views/dashboard/index.php';
    }
}
