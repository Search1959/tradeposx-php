<?php
/** Lets a business owner/admin manage their own team's login accounts - there was
 * previously no way to add staff at all short of asking SuperAdmin to do it. */
class TeamController {
    private User $model;

    public function __construct() {
        $this->model = new User();
        Auth::requireRole(['owner', 'admin']);
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $members = $this->model->allForOrg($org['id']);
        $pageTitle = 'Team';
        require __DIR__ . '/../views/team/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $member = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $member = $_POST;
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'staff';

            if ($name === '' || $email === '' || strlen($password) < 6) {
                $error = 'Name, email, and a password of at least 6 characters are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (!in_array($role, ['admin', 'staff'], true)) {
                $error = 'Invalid role.';
            } elseif ($role === 'admin' && Auth::role() !== 'owner') {
                // An admin could otherwise create peer admins with no owner oversight.
                $error = 'Only the business owner can create another admin account.';
            } elseif ($this->model->findByEmailInOrg($org['id'], $email) || $this->model->findByEmailAcrossOrgs($email)) {
                $error = 'An account with this email already exists.';
            } else {
                $id = $this->model->create([
                    'org_id' => $org['id'], 'name' => $name, 'email' => $email,
                    'password' => $password, 'role' => $role, 'status' => 'active',
                ]);
                logActivity($org['id'], 'team_member_created', 'user', $id, "{$name} ({$role})");
                redirect($org['slug'] . '/team');
                return;
            }
        }

        $pageTitle = 'Add Team Member';
        require __DIR__ . '/../views/team/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $member = $this->model->find((int) $id);
        if (!$member || (int) $member['org_id'] !== (int) $org['id']) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'staff';

            if ($member['role'] === 'owner') {
                $error = "The owner's role can't be changed here.";
            } elseif ($name === '' || $email === '') {
                $error = 'Name and email are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (!in_array($role, ['admin', 'staff'], true)) {
                $error = 'Invalid role.';
            } elseif ($role === 'admin' && Auth::role() !== 'owner') {
                $error = 'Only the business owner can promote someone to admin.';
            } else {
                $existing = $this->model->findByEmailInOrg($org['id'], $email);
                if ($existing && (int) $existing['id'] !== (int) $member['id']) {
                    $error = 'Another account already uses this email.';
                } else {
                    $this->model->updateProfile($org['id'], (int) $id, $name, $email, $role);
                    logActivity($org['id'], 'team_member_updated', 'user', (int) $id, "{$name} ({$role})");
                    redirect($org['slug'] . '/team');
                    return;
                }
            }
            $member = array_merge($member, $_POST);
        }

        $pageTitle = 'Edit Team Member';
        require __DIR__ . '/../views/team/form.php';
    }

    public function toggleStatusAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $member = $this->model->find((int) $id);
        if ($member && (int) $member['org_id'] === (int) $org['id'] && $member['role'] !== 'owner' && (int) $member['id'] !== (int) Auth::user()['id']) {
            $newStatus = $member['status'] === 'active' ? 'disabled' : 'active';
            $this->model->updateStatus((int) $id, $newStatus);
            logActivity($org['id'], 'team_member_status_changed', 'user', (int) $id, "{$member['name']} -> {$newStatus}");
        }
        redirect($org['slug'] . '/team');
    }

    public function resetPasswordAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $member = $this->model->find((int) $id);
        $newPassword = $_POST['new_password'] ?? '';
        if ($member && (int) $member['org_id'] === (int) $org['id'] && strlen($newPassword) >= 6) {
            $this->model->updatePassword((int) $id, $newPassword);
            logActivity($org['id'], 'team_member_password_reset', 'user', (int) $id, $member['name']);
        }
        redirect($org['slug'] . '/team');
    }
}
