<?php
/** Custom expense categories per org — replaces what used to be a hardcoded
 * PHP array on the Expenses view, so an owner can add their own heads (e.g.
 * "Vehicle Fuel") instead of being stuck with the fixed built-in list.
 * expenses.category stays a plain string (not a FK) — deleting a head here
 * never touches past expense records, it only removes it from the picker.
 * demo_amount is a suggested/typical amount shown as a price tag on that
 * head's quick-add button (see views/trade/expenses/list.php) — tapping it
 * pre-fills Amount so adding a routine expense is a single tap, not
 * dropdown-then-type; the owner can still edit the amount before saving. */
class ExpenseHead extends BaseModel {
    private const DEFAULTS = [
        'Rent' => 5000, 'Electricity' => 1500, 'Delivery/Transport' => 300, 'Staff Salary' => 10000,
        'Packaging' => 200, 'Maintenance' => 500, 'Tea & Snacks' => 100, 'Wi-Fi & Telecom' => 800,
        'Taxes & Licenses' => 1000, 'Pest Control & Cleaning' => 600, 'Marketing & Signboard' => 1500,
        'Other' => 0,
    ];

    public function allForOrg(int $orgId): array {
        return $this->query('SELECT * FROM expense_heads WHERE org_id = ? ORDER BY name', [$orgId]);
    }

    public function nameExists(int $orgId, string $name): bool {
        return $this->count('expense_heads', 'org_id = ? AND LOWER(name) = LOWER(?)', [$orgId, $name]) > 0;
    }

    public function create(int $orgId, string $name, float $demoAmount = 0): int {
        return $this->insert('INSERT INTO expense_heads (org_id, name, demo_amount) VALUES (?, ?, ?)', [$orgId, $name, $demoAmount]);
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM expense_heads WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    /** Called right after a new org is created (see Organization::create()), and
     * defensively re-called by ExpenseController if an org somehow still has
     * zero heads (e.g. one created before this feature existed, on a server
     * that hadn't picked up the retroactive seeding migration yet) — every
     * business ends up with the same familiar 12 heads either way, editable
     * from there. A second call is harmless: nameExists() is checked by the
     * caller, or the unique (org_id, name) constraint simply no-ops a repeat. */
    public function seedDefaults(int $orgId): void {
        foreach (self::DEFAULTS as $name => $demoAmount) {
            if (!$this->nameExists($orgId, $name)) {
                $this->create($orgId, $name, $demoAmount);
            }
        }
    }
}
