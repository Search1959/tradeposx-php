<?php
/** Customer Outreach — review-request / promotion WhatsApp campaigns, shared by
 * both TradeERP and ServiceERP. Same as every other WhatsApp touchpoint in this
 * app (see config/app.php's whatsappLink()), there is no WhatsApp Business API
 * configured, so nothing sends unattended — a campaign's "recurrence" only
 * decides who the Run screen flags as due again; the owner still clicks Send
 * per customer, same as the existing Udhaar/balance reminder links elsewhere. */
class OutreachController {
    private OutreachCampaign $model;
    private OutreachSend $sendModel;
    private Customer $customerModel;
    private ServiceCustomer $serviceCustomerModel;

    /** Recurrence -> days before a customer is "due" again. ONE_TIME has no
     * entry — a one-time campaign is only ever due before its first send. */
    private const RECURRENCE_DAYS = ['WEEKLY' => 7, 'EVERY_15_DAYS' => 15, 'MONTHLY' => 30];

    public function __construct() {
        $this->model = new OutreachCampaign();
        $this->sendModel = new OutreachSend();
        $this->customerModel = new Customer();
        $this->serviceCustomerModel = new ServiceCustomer();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $campaigns = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $sentCounts = [];
        foreach ($campaigns as $c) {
            $sentCounts[$c['id']] = $this->sendModel->countSentForCampaign($org['id'], (int) $c['id']);
        }
        $pageTitle = 'Customer Outreach';
        require __DIR__ . '/../views/outreach/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        Auth::requireRole(['owner', 'admin']);
        $error = null;
        $campaign = ['audience' => $this->defaultAudience($org)];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            $message = trim($_POST['message'] ?? '');
            if ($name === '' || $message === '') {
                $error = 'Name and message are required.';
                $campaign = array_merge($campaign, $_POST);
            } else {
                $id = $this->model->create($org['id'], $_POST);
                logActivity($org['id'], 'outreach_campaign_create', 'outreach_campaign', $id, $name);
                redirect($org['slug'] . '/outreach');
                return;
            }
        }

        $pageTitle = 'New Outreach Campaign';
        require __DIR__ . '/../views/outreach/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        Auth::requireRole(['owner', 'admin']);
        $campaign = $this->model->find($org['id'], (int) $id);
        if (!$campaign) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            $message = trim($_POST['message'] ?? '');
            if ($name === '' || $message === '') {
                $error = 'Name and message are required.';
                $campaign = array_merge($campaign, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/outreach');
                return;
            }
        }

        $pageTitle = 'Edit Outreach Campaign';
        require __DIR__ . '/../views/outreach/form.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        Auth::requireRole(['owner', 'admin']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/outreach');
    }

    /** The actual send screen — one row per customer with a due/not-due badge
     * and a WhatsApp link personalized with {name}/{business}. */
    public function runAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $campaign = $this->model->find($org['id'], (int) $id);
        if (!$campaign) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $customers = $campaign['audience'] === 'SERVICE'
            ? $this->serviceCustomerModel->allForOrg($org['id'])
            : $this->customerModel->allForOrg($org['id']);
        $lastSentMap = $this->sendModel->lastSentMap($org['id'], (int) $id);
        $thresholdDays = self::RECURRENCE_DAYS[$campaign['recurrence']] ?? null;

        $rows = [];
        foreach ($customers as $cust) {
            if (empty($cust['mobile'])) continue; // nothing to send to
            $lastSent = $lastSentMap[(int) $cust['id']] ?? null;
            $daysSince = $lastSent ? (int) floor((time() - strtotime($lastSent)) / 86400) : null;
            $due = $lastSent === null || ($thresholdDays !== null && $daysSince >= $thresholdDays);
            $rows[] = [
                'customer' => $cust,
                'last_sent' => $lastSent,
                'days_since' => $daysSince,
                'due' => $due,
                'message' => str_replace(['{name}', '{business}'], [$cust['name'], $org['name']], $campaign['message']),
            ];
        }
        // Due customers first, then most-overdue-first among the rest.
        usort($rows, fn($a, $b) => $b['due'] <=> $a['due'] ?: ($b['days_since'] ?? PHP_INT_MAX) <=> ($a['days_since'] ?? PHP_INT_MAX));

        $pageTitle = 'Run: ' . $campaign['name'];
        require __DIR__ . '/../views/outreach/run.php';
    }

    /** Logs the send, then hands off to WhatsApp — the actual message send is
     * still the owner's own click inside WhatsApp, same as every other wa.me
     * link in this app; this just remembers it happened for next time's due-check.
     * The router only ever passes one path param, so the customer comes from
     * ?customer= instead of a second path segment. */
    public function sendAction(?string $campaignId = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $campaign = $this->model->find($org['id'], (int) $campaignId);
        if (!$campaign) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $customerId = $_GET['customer'] ?? null;

        $customer = $campaign['audience'] === 'SERVICE'
            ? $this->serviceCustomerModel->find($org['id'], (int) $customerId)
            : $this->customerModel->find($org['id'], (int) $customerId);
        if (!$customer || empty($customer['mobile'])) {
            redirect($org['slug'] . '/outreach/run/' . (int) $campaignId);
            return;
        }

        $this->sendModel->record($org['id'], (int) $campaignId, (int) $customerId);
        $message = str_replace(['{name}', '{business}'], [$customer['name'], $org['name']], $campaign['message']);
        header('Location: ' . whatsappLink($customer['mobile'], $message));
        exit;
    }

    private function defaultAudience(array $org): string {
        return ($org['business_type'] ?? 'both') === 'service' ? 'SERVICE' : 'TRADE';
    }
}
