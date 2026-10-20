<?php
class Organization extends BaseModel {
    private static bool $upiChecked = false;

    /** The business's own UPI ID (for the pay-by-QR window). Added on first use so a live database needs no manual migration. */
    public function __construct() {
        parent::__construct();
        if (!self::$upiChecked) {
            self::$upiChecked = true;
            $cols = array_column($this->query('SHOW COLUMNS FROM organizations'), 'Field');
            if (!in_array('upi_id', $cols, true)) {
                $this->db->exec('ALTER TABLE organizations ADD COLUMN upi_id VARCHAR(100) NULL');
            }
        }
    }

    public function updateUpi(int $id, ?string $upiId): bool {
        return $this->execute('UPDATE organizations SET upi_id = ? WHERE id = ?', [$upiId, $id]);
    }

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
        $id = $this->insert(
            'INSERT INTO organizations (slug, name, business_type, trading_sector, theme_color, status)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['slug'], $data['name'], $data['business_type'] ?? 'both',
                $data['trading_sector'] ?? null, $data['theme_color'] ?? null,
                $data['status'] ?? 'active',
            ]
        );
        (new ExpenseHead())->seedDefaults($id);
        return $id;
    }

    /** trading_sector is intentionally not editable here — it's chosen once at
     * account creation (AuthController's signup flow / SuperAdminController's
     * new-org form), never changed afterward. */
    public function updateProfile(int $id, string $name, ?string $address): bool {
        return $this->execute('UPDATE organizations SET name = ?, address = ? WHERE id = ?', [$name, $address, $id]);
    }

    /** GSTIN/phone — the remaining seller details a tax invoice needs in its header
     * (address lives on the Business Details form instead, see updateProfile()). */
    public function updateTaxProfile(int $id, ?string $gstin, ?string $phone): bool {
        return $this->execute('UPDATE organizations SET gstin = ?, phone = ? WHERE id = ?', [$gstin, $phone, $id]);
    }

    public function updateStatus(int $id, string $status): bool {
        return $this->execute('UPDATE organizations SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function slugExists(string $slug): bool {
        // Platform URLs a business must never be able to claim as its own workspace.
        if (in_array(strtolower($slug), ['login', 'logout', 'superadmin', 'privacy', 'terms', 'support', 'deletion', 'cashmemo', 'assets', 'api', 'admin', 'uploads', 'review', 'reviewdesk'], true)) return true;
        return $this->count('organizations', 'slug = ?', [$slug]) > 0;
    }
    /** Every business with its user count and primary owner contact — one query for the System Admin registry. */
    public function allWithOwner(): array {
        return $this->query(
            "SELECT o.*,
                    (SELECT COUNT(*) FROM users u WHERE u.org_id = o.id) AS user_count,
                    ow.name AS owner_name, ow.email AS owner_email, ow.mobile AS owner_mobile
             FROM organizations o
             LEFT JOIN users ow ON ow.id = (SELECT id FROM users WHERE org_id = o.id AND role = 'owner' ORDER BY id LIMIT 1)
             ORDER BY o.created_at DESC"
        );
    }
}