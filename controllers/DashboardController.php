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

    /** Shown once right after login for a "both" business — after picking a
     * side, the full sidebar (with both sections) is still available, this
     * only decides where they land first. */
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
