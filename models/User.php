<?php
class User extends BaseModel {
    public function find(int $id): ?array {
        return $this->queryOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByEmailInOrg(int $orgId, string $email): ?array {
        return $this->queryOne('SELECT * FROM users WHERE org_id = ? AND email = ?', [$orgId, $email]);
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
            'INSERT INTO users (org_id, name, email, mobile, password_hash, role, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['org_id'] ?? null, $data['name'], $data['email'] ?? null, $data['mobile'] ?? null,
                password_hash($data['password'], PASSWORD_DEFAULT), $data['role'] ?? 'staff',
                $data['status'] ?? 'active',
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
