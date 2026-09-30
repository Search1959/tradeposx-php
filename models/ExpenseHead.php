<?php
/** Custom expense categories per org — replaces what used to be a hardcoded
 * PHP array on the Expenses view, so an owner can add their own heads (e.g.
 * "Vehicle Fuel") instead of being stuck with the fixed built-in list.
 * expenses.category stays a plain string (not a FK) — deleting a head here
 * never touches past expense records, it only removes it from the picker. */
class ExpenseHead extends BaseModel {
    private const DEFAULTS = [
        'Rent', 'Electricity', 'Delivery/Transport', 'Staff Salary', 'Packaging', 'Maintenance',
        'Tea & Snacks', 'Wi-Fi & Telecom', 'Taxes & Licenses', 'Pest Control & Cleaning',
        'Marketing & Signboard', 'Other',
    ];

    public function allForOrg(int $orgId): array {
        return $this->query('SELECT * FROM expense_heads WHERE org_id = ? ORDER BY name', [$orgId]);
    }

    public function nameExists(int $orgId, string $name): bool {
        return $this->count('expense_heads', 'org_id = ? AND LOWER(name) = LOWER(?)', [$orgId, $name]) > 0;
    }

    public function create(int $orgId, string $name): int {
        return $this->insert('INSERT INTO expense_heads (org_id, name) VALUES (?, ?)', [$orgId, $name]);
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM expense_heads WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    /** Called once, right after a new org is created (see Organization::create()) —
     * every business starts with the same familiar 12 heads, editable from there. */
    public function seedDefaults(int $orgId): void {
        foreach (self::DEFAULTS as $name) {
            $this->create($orgId, $name);
        }
    }
}
