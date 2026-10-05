<?php
class User extends BaseModel {
    public function find(int $id): ?array {
        return $this->queryOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByEmailInOrg(int $orgId, string $email): ?array {
        return $this->queryOne('SELECT * FROM users WHERE org_id = ? AND email = ?', [$orgId, $email]);
    }

    /** Login accepts either — migrated accounts only ever have a username
     * (the old app had no email-based login at all), new signups only an email. */
    public function findByLoginInOrg(int $orgId, string $identifier): ?array {
        return $this->queryOne('SELECT * FROM users WHERE org_id = ? AND (email = ? OR username = ?)', [$orgId, $identifier, $identifier]);
    }

    /** Platform superadmin login has no org — email must be globally unique among org_id IS NULL rows. */
    public function findSuperAdminByEmail(string $email): ?array {
        return $this->queryOne("SELECT * FROM users WHERE org_id IS NULL AND role = 'superadmin' AND email = ?", [$email]);
    }

    /** Makes sure the configured System Admin exists in the users table with the configured password hash,
     * so normal session handling keeps working. Returns null if the email belongs to a business account —
     * a business owner is never silently promoted to platform admin. */
    public function ensureSuperAdmin(string $email, string $hash): ?array {
        $existing = $this->queryOne('SELECT * FROM users WHERE email = ?', [$email]);
        if ($existing) {
            if ($existing['org_id'] !== null || $existing['role'] !== 'superadmin') return null;
            $this->execute("UPDATE users SET password_hash = ?, status = 'active' WHERE id = ?", [$hash, $existing['id']]);
        } else {
            $first = $this->queryOne("SELECT id FROM users WHERE org_id IS NULL AND role = 'superadmin' ORDER BY id LIMIT 1");
            if ($first) {
                $this->execute("UPDATE users SET email = ?, password_hash = ?, status = 'active' WHERE id = ?", [$email, $hash, $first['id']]);
            } else {
                $this->execute("INSERT INTO users (org_id, name, email, password_hash, role, status) VALUES (NULL, 'System Admin', ?, ?, 'superadmin', 'active')", [$email, $hash]);
            }
        }
        return $this->queryOne("SELECT * FROM users WHERE org_id IS NULL AND role = 'superadmin' AND email = ?", [$email]);
    }

    /** Platform-level /login "Login" tab has no org-slug in the URL to scope by —
     * looks a tenant user up by email alone, relying on uniq_users_email. */
    public function findByEmailAcrossOrgs(string $email): ?array {
        return $this->queryOne('SELECT * FROM users WHERE org_id IS NOT NULL AND email = ?', [$email]);
    }

    public function allForOrg(int $orgId): array {
        return $this->query('SELECT * FROM users WHERE org_id = ? ORDER BY created_at DESC', [$orgId]);
    }

    public function create(array $data): int {
        return $this->insert(
            'INSERT INTO users (org_id, name, email, username, mobile, password_hash, role, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['org_id'] ?? null, $data['name'], $data['email'] ?? null, $data['username'] ?? null, $data['mobile'] ?? null,
                password_hash($data['password'], PASSWORD_DEFAULT), $data['role'] ?? 'staff',
                $data['status'] ?? 'active',
            ]
        );
    }

    /** For migrated accounts only — takes an already-computed password_hash
     * (the old app's own bcrypt hash, or one freshly hashed from a plaintext
     * password recovered during export) instead of hashing a raw password,
     * so a migrated user's exact existing credential keeps working. */
    public function createWithHash(array $data): int {
        return $this->insert(
            'INSERT INTO users (org_id, name, email, username, mobile, password_hash, role, status, legacy_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['org_id'], $data['name'], $data['email'] ?? null, $data['username'] ?? null, $data['mobile'] ?? null,
                $data['password_hash'], $data['role'] ?? 'owner', $data['status'] ?? 'active', $data['legacy_id'] ?? null,
            ]
        );
    }

    public function verifyPassword(array $user, string $password): bool {
        return password_verify($password, $user['password_hash']);
    }

    public function updateStatus(int $id, string $status): bool {
        return $this->execute('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function updateProfile(int $orgId, int $id, string $name, ?string $email, string $role): bool {
        return $this->execute('UPDATE users SET name = ?, email = ?, role = ? WHERE org_id = ? AND id = ?', [$name, $email, $role, $orgId, $id]);
    }

    public function countByRole(int $orgId, string $role): int {
        return (int) $this->count('users', 'org_id = ? AND role = ?', [$orgId, $role]);
    }

    public function updatePassword(int $id, string $newPassword): bool {
        return $this->execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($newPassword, PASSWORD_DEFAULT), $id]);
    }

    /** Every account on the platform with the business it belongs to — System Admin "All Users" tab. */
    public function allWithOrg(): array {
        return $this->query(
            'SELECT u.id, u.name, u.email, u.mobile, u.role, u.status, u.created_at, o.name AS org_name, o.slug AS org_slug
             FROM users u LEFT JOIN organizations o ON o.id = u.org_id ORDER BY u.created_at DESC, u.id DESC'
        );
    }
}
