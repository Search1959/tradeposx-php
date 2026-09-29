<?php
class SettingsController {
    private User $userModel;
    private Organization $orgModel;

    public function __construct() {
        $this->userModel = new User();
        $this->orgModel = new Organization();
    }

    public function indexAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);

        $error = null;
        $success = null;
        $currentUser = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();

            if (($_POST['form'] ?? '') === 'password') {
                $current = $_POST['current_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                if (!$this->userModel->verifyPassword($currentUser, $current)) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($new) < 6) {
                    $error = 'New password must be at least 6 characters.';
                } elseif ($new !== $confirm) {
                    $error = 'New password and confirmation do not match.';
                } else {
                    $this->userModel->updatePassword($currentUser['id'], $new);
                    $success = 'Password updated.';
                }
            } elseif (($_POST['form'] ?? '') === 'business' && in_array(Auth::role(), ['owner', 'admin'], true)) {
                $name = trim($_POST['name'] ?? '');
                if ($name === '') {
                    $error = 'Business name is required.';
                } else {
                    $this->orgModel->updateProfile($org['id'], $name, $_POST['trading_sector'] ?? null);
                    $org = $this->orgModel->find($org['id']); // refresh for the view
                    $success = 'Business details updated.';
                }
            } elseif (($_POST['form'] ?? '') === 'tax' && in_array(Auth::role(), ['owner', 'admin'], true)) {
                $gstin = trim($_POST['gstin'] ?? '') ?: null;
                if ($gstin !== null && !preg_match('/^[0-9A-Z]{15}$/', $gstin)) {
                    $error = 'GSTIN should be 15 characters (letters and numbers only), e.g. 19ABCDE1234F1Z5.';
                } else {
                    $this->orgModel->updateTaxProfile($org['id'], $gstin, trim($_POST['address'] ?? '') ?: null, trim($_POST['phone'] ?? '') ?: null);
                    $org = $this->orgModel->find($org['id']);
                    $success = 'Invoice details updated.';
                }
            }
        }

        $pageTitle = 'Settings';
        require __DIR__ . '/../views/settings/index.php';
    }

    /** Owner/admin-only: a full SQL export of just this business's own data —
     * every table scoped to this org_id, nothing from any other tenant. */
    public function exportDataAction(): void {
        $org = Tenant::current();
        Auth::requireRole(['owner', 'admin']);
        Auth::requireOrgAccess($org['id']);

        $db = Database::getInstance()->getConnection();
        $sql = BackupHelper::exportOrg($db, $org['id']);

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $org['slug'] . '-backup-' . date('Y-m-d') . '.sql"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
    }
}
