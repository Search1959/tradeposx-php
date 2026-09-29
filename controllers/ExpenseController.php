<?php
class ExpenseController {
    private Expense $model;

    public function __construct() {
        $this->model = new Expense();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $category = trim($_POST['category'] ?? '');
            $amount = (float) ($_POST['amount'] ?? 0);
            if ($category === '' || $amount <= 0) {
                $error = 'Category and a positive amount are required.';
            } else {
                $this->model->create($org['id'], $_POST);
                redirect($org['slug'] . '/expenses');
                return;
            }
        }

        $expenses = $this->model->allForOrg($org['id']);
        $total = $this->model->totalForOrg($org['id']);
        $pageTitle = 'Expenses';
        require __DIR__ . '/../views/trade/expenses/list.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/expenses');
    }
}
