<?php
class ExpenseController {
    private Expense $model;
    private ExpenseHead $headModel;

    public function __construct() {
        $this->model = new Expense();
        $this->headModel = new ExpenseHead();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $tab = ($_GET['tab'] ?? '') === 'heads' ? 'heads' : 'expenses';

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

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $expenses = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $total = $this->model->totalForOrg($org['id']);
        $expenseHeads = $this->headModel->allForOrg($org['id']);
        $pageTitle = 'Expenses';
        require __DIR__ . '/../views/trade/expenses/list.php';
    }

    /** Adds a new expense head (category) — deliberately not a modal/full page,
     * the "Expense Heads" tab on this same screen is simple enough to be a
     * single add-form + delete-able list. */
    public function addHeadAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $name = trim($_POST['name'] ?? '');
        if ($name !== '' && !$this->headModel->nameExists($org['id'], $name)) {
            $this->headModel->create($org['id'], $name);
        }
        redirect($org['slug'] . '/expenses?tab=heads');
    }

    /** Only removes the head from the picker — expenses.category is a plain
     * string, not a foreign key, so past expenses already logged under this
     * head are completely unaffected. */
    public function deleteHeadAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->headModel->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/expenses?tab=heads');
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $expense = $this->model->find($org['id'], (int) $id);
        if (!$expense) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $category = trim($_POST['category'] ?? '');
            $amount = (float) ($_POST['amount'] ?? 0);
            if ($category === '' || $amount <= 0) {
                $error = 'Category and a positive amount are required.';
                $expense = array_merge($expense, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/expenses');
                return;
            }
        }

        $expenseHeads = $this->headModel->allForOrg($org['id']);
        $pageTitle = 'Edit Expense';
        require __DIR__ . '/../views/trade/expenses/edit.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/expenses');
    }
}
