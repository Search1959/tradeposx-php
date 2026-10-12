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
            } elseif (($_POST['form'] ?? '') === 'profile') {
                // Name and login email. Changing the email (which is the login) needs the current password.
                $name = trim($_POST['name'] ?? '');
                $email = strtolower(trim($_POST['email'] ?? ''));
                $hasUsername = trim((string) ($currentUser['username'] ?? '')) !== '';
                $emailChanged = $email !== strtolower((string) ($currentUser['email'] ?? ''));
                if ($name === '' || mb_strlen($name) > 120) {
                    $error = 'Please enter your name.';
                } elseif ($email === '' && !$hasUsername) {
                    $error = 'An email is needed so you can log in.';
                } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address, like name@example.com.';
                } elseif ($emailChanged && ($_POST['current_password'] ?? '') === '') {
                    $error = 'To change your login email, enter your current password.';
                } elseif ($emailChanged && !$this->userModel->verifyPassword($currentUser, $_POST['current_password'] ?? '')) {
                    $error = 'Current password is incorrect.';
                } else {
                    $taken = ($emailChanged && $email !== '') ? ($this->userModel->findByEmailAcrossOrgs($email) ?: $this->userModel->findSuperAdminByEmail($email)) : null;
                    if ($taken && (int) $taken['id'] !== (int) $currentUser['id']) {
                        $error = 'That email is already used by another account.';
                    } elseif (!$this->userModel->updateOwnProfile((int) $currentUser['id'], $name, $email !== '' ? $email : null)) {
                        $error = 'Could not save. That email may already be in use.';
                    } else {
                        logActivity($org['id'], 'profile_updated', 'user', (int) $currentUser['id'], $emailChanged ? 'name and login email' : 'name');
                        $currentUser = Auth::user();
                        $success = $emailChanged ? 'Saved. From now on log in with ' . $email . '.' : 'Your name was updated.';
                    }
                }
            } elseif (($_POST['form'] ?? '') === 'business' && in_array(Auth::role(), ['owner', 'admin'], true)) {
                $name = trim($_POST['name'] ?? '');
                if ($name === '') {
                    $error = 'Business name is required.';
                } else {
                    $this->orgModel->updateProfile($org['id'], $name, trim($_POST['address'] ?? '') ?: null);
                    $org = $this->orgModel->find($org['id']); // refresh for the view
                    $success = 'Business details updated.';
                }
            } elseif (($_POST['form'] ?? '') === 'upi' && in_array(Auth::role(), ['owner', 'admin'], true)) {
                $upi = trim($_POST['upi_id'] ?? '');
                if ($upi !== '' && !preg_match('/^[A-Za-z0-9._\-]{2,100}@[A-Za-z][A-Za-z0-9.\-]{1,40}$/', $upi)) {
                    $error = 'That does not look like a UPI ID. It looks like name@bank, e.g. 9876543210@ybl or shopname@okhdfcbank.';
                } else {
                    $this->orgModel->updateUpi($org['id'], $upi !== '' ? $upi : null);
                    $org = $this->orgModel->find($org['id']);
                    $success = $upi !== '' ? 'UPI ID saved. Customers can now pay by scanning a QR code at checkout.' : 'UPI ID removed.';
                }
            } elseif (($_POST['form'] ?? '') === 'tax' && in_array(Auth::role(), ['owner', 'admin'], true)) {
                $gstin = trim($_POST['gstin'] ?? '') ?: null;
                if ($gstin !== null && !preg_match('/^[0-9A-Z]{15}$/', $gstin)) {
                    $error = 'GSTIN should be 15 characters (letters and numbers only), e.g. 19ABCDE1234F1Z5.';
                } else {
                    $this->orgModel->updateTaxProfile($org['id'], $gstin, trim($_POST['phone'] ?? '') ?: null);
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
