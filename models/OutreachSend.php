<?php
class OutreachSend extends BaseModel {
    /** One query for the whole Run screen instead of one-per-customer-row —
     * returns [customer_id => last sent_at] for every customer ever messaged
     * under this campaign. */
    public function lastSentMap(int $orgId, int $campaignId): array {
        $rows = $this->query(
            'SELECT customer_id, MAX(sent_at) as last_sent FROM outreach_sends WHERE org_id = ? AND campaign_id = ? GROUP BY customer_id',
            [$orgId, $campaignId]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['customer_id']] = $r['last_sent'];
        }
        return $map;
    }

    public function record(int $orgId, int $campaignId, int $customerId): int {
        return $this->insert(
            'INSERT INTO outreach_sends (org_id, campaign_id, customer_id) VALUES (?,?,?)',
            [$orgId, $campaignId, $customerId]
        );
    }

    public function countSentForCampaign(int $orgId, int $campaignId): int {
        return $this->count('outreach_sends', 'org_id = ? AND campaign_id = ?', [$orgId, $campaignId]);
    }
}
