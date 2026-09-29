<?php
class DashboardController {
    public function indexAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);

        $pageTitle = 'Dashboard';
        require __DIR__ . '/../views/dashboard/index.php';
    }
}
