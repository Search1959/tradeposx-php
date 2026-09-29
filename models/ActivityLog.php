<?php
class ActivityLog extends BaseModel {
    public function forOrg(int $orgId, int $limit = 50, int $offset = 0): array {
        return $this->query(
            'SELECT al.*, u.name AS user_name FROM activity_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.org_id = ? ORDER BY al.created_at DESC LIMIT ? OFFSET ?',
            [$orgId, $limit, $offset]
        );
    }

    public function countForOrg(int $orgId): int {
        return $this->count('activity_log', 'org_id = ?', [$orgId]);
    }
}
