<?php
class Organization extends BaseModel {
    public function find(int $id): ?array {
        return $this->queryOne('SELECT * FROM organizations WHERE id = ?', [$id]);
    }

    public function findBySlug(string $slug): ?array {
        return $this->queryOne('SELECT * FROM organizations WHERE slug = ?', [$slug]);
    }

    public function all(): array {
        return $this->query('SELECT * FROM organizations ORDER BY created_at DESC');
    }

    public function create(array $data): int {
        return $this->insert(
            'INSERT INTO organizations (slug, name, business_type, trading_sector, theme_color, status)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['slug'], $data['name'], $data['business_type'] ?? 'both',
                $data['trading_sector'] ?? null, $data['theme_color'] ?? null,
                $data['status'] ?? 'active',
            ]
        );
    }

    public function updateProfile(int $id, string $name, ?string $tradingSector): bool {
        return $this->execute('UPDATE organizations SET name = ?, trading_sector = ? WHERE id = ?', [$name, $tradingSector, $id]);
    }

    /** GSTIN/address/phone — the seller details a tax invoice needs in its header. */
    public function updateTaxProfile(int $id, ?string $gstin, ?string $address, ?string $phone): bool {
        return $this->execute('UPDATE organizations SET gstin = ?, address = ?, phone = ? WHERE id = ?', [$gstin, $address, $phone, $id]);
    }

    public function updateStatus(int $id, string $status): bool {
        return $this->execute('UPDATE organizations SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function slugExists(string $slug): bool {
        return $this->count('organizations', 'slug = ?', [$slug]) > 0;
    }
}
