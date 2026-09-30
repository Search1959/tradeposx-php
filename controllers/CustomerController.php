<?php
class CustomerController {
    private Customer $model;
    private CustomerTransaction $txnModel;

    public function __construct() {
        $this->model = new Customer();
        $this->txnModel = new CustomerTransaction();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $customers = $this->model->allForOrg($org['id'], $search ?: null, $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id'], $search ?: null);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        // Keyed by customer id, only loaded for those actually owing something -
        // feeds the itemized breakdown in the WhatsApp reminder link on this page.
        $recentCredits = [];
        foreach ($customers as $c) {
            if ((float) $c['current_balance'] > 0) {
                $recentCredits[$c['id']] = $this->txnModel->recentCredits($org['id'], (int) $c['id'], 3);
            }
        }
        $pageTitle = 'Customers';
        require __DIR__ . '/../views/trade/customers/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $customer = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            if ($name === '' || $mobile === '') {
                $error = 'Name and mobile are required.';
                $customer = $_POST;
            } else {
                $id = $this->model->create($org['id'], $_POST);
                logActivity($org['id'], 'customer_create', 'customer', $id, $name);
                redirect($org['slug'] . '/customers');
                return;
            }
        }

        $pageTitle = 'New Customer';
        require __DIR__ . '/../views/trade/customers/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $customer = $this->model->find($org['id'], (int) $id);
        if (!$customer) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            if ($name === '' || $mobile === '') {
                $error = 'Name and mobile are required.';
                $customer = array_merge($customer, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/customers');
                return;
            }
        }

        $pageTitle = 'Edit Customer';
        require __DIR__ . '/../views/trade/customers/form.php';
    }

    /** The Udhaar ledger — current balance plus every transaction that produced it. */
    public function statementAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $customer = $this->model->find($org['id'], (int) $id);
        if (!$customer) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $transactions = $this->txnModel->forCustomer($org['id'], (int) $id);
        $recentCredits = array_slice(array_values(array_filter(
            $transactions, fn($t) => in_array($t['type'], ['CREDIT_SALE', 'OPENING_BALANCE'], true)
        )), 0, 5);
        $pageTitle = 'Udhaar Statement';
        require __DIR__ . '/../views/trade/customers/statement.php';
    }

    /** Records a payment received against Udhaar — reduces current_balance and
     * appends a PAYMENT_RECEIVED ledger row in one step, so the two can't drift. */
    public function recordPaymentAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $customer = $this->model->find($org['id'], (int) $id);
        if (!$customer) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $amount = (float) ($_POST['amount'] ?? 0);
        if ($amount > 0) {
            $balanceAfter = $this->model->adjustBalance($org['id'], (int) $id, -$amount);
            $this->txnModel->record(
                $org['id'], (int) $id, 'PAYMENT_RECEIVED', $amount, $balanceAfter,
                $_POST['payment_method'] ?? 'CASH', null, $_POST['notes'] ?? null
            );
            logActivity($org['id'], 'customer_payment_recorded', 'customer', (int) $id, "{$customer['name']} — " . formatCurrency($amount));
        }
        redirect($org['slug'] . '/customers/statement/' . (int) $id);
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/customers');
    }
}
