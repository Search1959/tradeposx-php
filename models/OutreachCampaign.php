<?php
class OutreachCampaign extends BaseModel {
    public function allForOrg(int $orgId, int $limit = 20, int $offset = 0): array {
        return $this->query('SELECT * FROM outreach_campaigns WHERE org_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?', [$orgId, $limit, $offset]);
    }

    public function countForOrg(int $orgId): int {
        return $this->count('outreach_campaigns', 'org_id = ?', [$orgId]);
    }

    public function find(int $orgId, int $id): ?array {
        return $this->queryOne('SELECT * FROM outreach_campaigns WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }

    public function create(int $orgId, array $d): int {
        return $this->insert(
            'INSERT INTO outreach_campaigns (org_id, name, campaign_type, message, recurrence, audience, status)
             VALUES (?,?,?,?,?,?,?)',
            [$orgId, $d['name'], $d['campaign_type'] ?? 'CUSTOM', $d['message'], $d['recurrence'] ?? 'MONTHLY',
             $d['audience'] ?? 'TRADE', $d['status'] ?? 'ACTIVE']
        );
    }

    public function update(int $orgId, int $id, array $d): bool {
        return $this->execute(
            'UPDATE outreach_campaigns SET name=?, campaign_type=?, message=?, recurrence=?, audience=?, status=? WHERE org_id=? AND id=?',
            [$d['name'], $d['campaign_type'] ?? 'CUSTOM', $d['message'], $d['recurrence'] ?? 'MONTHLY',
             $d['audience'] ?? 'TRADE', $d['status'] ?? 'ACTIVE', $orgId, $id]
        );
    }

    public function delete(int $orgId, int $id): bool {
        return $this->execute('DELETE FROM outreach_campaigns WHERE org_id = ? AND id = ?', [$orgId, $id]);
    }
}
