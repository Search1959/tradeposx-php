<?php
class ActivityLogController {
    private ActivityLog $model;

    public function __construct() {
        $this->model = new ActivityLog();
        Auth::requireRole(['owner', 'admin']);
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $entries = $this->model->forOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $total = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $pageTitle = 'Activity Log';
        require __DIR__ . '/../views/activity-log/list.php';
    }
}
