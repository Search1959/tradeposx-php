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
        $customers = $this->model->allForOrg($org['id'], $search ?: null);
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
