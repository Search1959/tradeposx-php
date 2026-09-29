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
}
