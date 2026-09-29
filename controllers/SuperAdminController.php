<?php
class SuperAdminController {
    public function indexAction(): void {
        Auth::requireSuperAdmin();
        $orgModel = new Organization();
        $organizations = $orgModel->all();
        require __DIR__ . '/../views/superadmin/index.php';
    }
}
