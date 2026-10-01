<?php
class ServiceItemController {
    private ServiceItem $model;

    public function __construct() {
        $this->model = new ServiceItem();
    }

    public function listAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = LIST_PAGE_SIZE;
        $items = $this->model->allForOrg($org['id'], $perPage, ($page - 1) * $perPage);
        $totalCount = $this->model->countForOrg($org['id']);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $pageTitle = 'Service Items';
        require __DIR__ . '/../views/service/items/list.php';
    }

    public function newAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $item = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $item = $_POST;
            } else {
                $this->model->create($org['id'], $_POST);
                redirect($org['slug'] . '/service-items');
                return;
            }
        }

        $pageTitle = 'New Service Item';
        require __DIR__ . '/../views/service/items/form.php';
    }

    public function editAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $item = $this->model->find($org['id'], (int) $id);
        if (!$item) { http_response_code(404); include __DIR__ . '/../views/404.php'; return; }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfHelper::validate();
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $error = 'Name is required.';
                $item = array_merge($item, $_POST);
            } else {
                $this->model->update($org['id'], (int) $id, $_POST);
                redirect($org['slug'] . '/service-items');
                return;
            }
        }

        $pageTitle = 'Edit Service Item';
        require __DIR__ . '/../views/service/items/form.php';
    }

    public function deleteAction(?string $id = null): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $this->model->delete($org['id'], (int) $id);
        redirect($org['slug'] . '/service-items');
    }

    /** Cleans up a botched bulk import (mismatched column names landing every
     * row at ₹0.00) without clicking Delete 50+ times — same pattern as
     * ProductController::bulkDeleteAction(). */
    public function bulkDeleteAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        CsrfHelper::validate();
        $ids = array_filter(array_map('intval', $_POST['ids'] ?? []));
        foreach ($ids as $id) {
            $this->model->delete($org['id'], $id);
        }
        redirect($org['slug'] . '/service-items');
    }

    /** A ready-to-fill CSV — the header row IS the contract for bulkImportAction
     * below, so keep the two in sync if this ever changes. */
    public function templateAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="service_item_import_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['name', 'category', 'sector', 'price', 'duration_minutes', 'gst_percent', 'warranty_days', 'description']);
        fputcsv($out, ['Haircut (Men)', 'Hair', 'SALON', '200', '30', '18', '0', 'Standard men\'s haircut']);
        fclose($out);
    }

    /** Sample starter service items per sector — downloadable straight from the
     * Bulk Import screen so a new business doesn't have to type its whole menu
     * from scratch. Whitelisted against the real sector taxonomy (SectorHelper)
     * so a bogus path segment can't be used to read an arbitrary file, then
     * checked again against what actually exists under demodata/. */
    public function demoAction(?string $sector = null): void {
        Auth::requireOrgAccess(Tenant::current()['id']);
        $sector = strtoupper($sector ?? '');
        if (!isset(SectorHelper::serviceSectors()[$sector])) {
            http_response_code(404);
            include __DIR__ . '/../views/404.php';
            return;
        }
        $file = ROOT_PATH . '/demodata/service-items/' . $sector . '.csv';
        if (!is_file($file)) {
            http_response_code(404);
            include __DIR__ . '/../views/404.php';
            return;
        }
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . strtolower($sector) . '_service_items.csv"');
        readfile($file);
    }

    /** Matches each row to an existing item by name (service items have no
     * SKU/barcode) - matched rows are updated (blank cells leave the existing
     * value alone), unmatched rows create a new item. Same BOM/delimiter
     * handling as ProductController::bulkImportAction() - see there for why. */
    public function bulkImportAction(): void {
        $org = Tenant::current();
        Auth::requireOrgAccess($org['id']);
        $error = null;
        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['csv_file']['tmp_name'])) {
            CsrfHelper::validate();
            $content = file_get_contents($_FILES['csv_file']['tmp_name']);
            $content = preg_replace('/^\xEF\xBB\xBF/', '', (string) $content);
            $firstLine = strtok($content, "\r\n");
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            $handle = fopen('php://memory', 'r+');
            if ($content === '' || $handle === false) {
                $error = 'Could not read the uploaded file.';
            } else {
                fwrite($handle, $content);
                rewind($handle);
                $header = fgetcsv($handle, 0, $delimiter);
                if (!$header) {
                    $error = 'File appears to be empty.';
                } elseif (!in_array('name', array_map(fn($h) => strtolower(trim($h)), $header), true)) {
                    $preview = implode(', ', array_slice(array_map('trim', $header), 0, 5));
                    $error = "This doesn't look like a service item import file — no \"name\" column in its first row (found: {$preview}). "
                        . 'Make sure you\'re uploading the CSV, not another file, and that its header row matches the "⬇ Download CSV template" above.';
                } else {
                    $header = array_map(fn($h) => strtolower(trim($h)), $header);
                    $created = 0; $updated = 0; $skipped = [];
                    $rowNum = 1;
                    while (($rawRow = fgetcsv($handle, 0, $delimiter)) !== false) {
                        $rowNum++;
                        if (count(array_filter($rawRow, fn($v) => trim((string)$v) !== '')) === 0) continue; // blank line
                        $row = [];
                        foreach ($header as $i => $col) { $row[$col] = trim($rawRow[$i] ?? ''); }

                        $name = $row['name'] ?? '';
                        if ($name === '') {
                            $preview = implode(' | ', array_slice(array_map('trim', $rawRow), 0, 3));
                            $skipped[] = "Row {$rowNum}: missing name" . ($preview !== '' ? " (found: {$preview})" : '');
                            continue;
                        }

                        $priceRaw = $row['price'] ?? '';
                        $durationRaw = $row['duration_minutes'] ?? '';
                        $gstRaw = $row['gst_percent'] ?? '';
                        $warrantyRaw = $row['warranty_days'] ?? '';
                        $data = [
                            'name' => $name,
                            'category' => $row['category'] ?? '',
                            'sector' => $row['sector'] ?? '',
                            'price' => $priceRaw !== '' ? (float) $priceRaw : null,
                            'duration_minutes' => $durationRaw !== '' ? (int) $durationRaw : null,
                            'gst_percent' => $gstRaw !== '' ? (float) $gstRaw : null,
                            'warranty_days' => $warrantyRaw !== '' ? (int) $warrantyRaw : null,
                            'description' => $row['description'] ?? '',
                        ];

                        $existing = $this->model->findForImportMatch($org['id'], $name);
                        if ($existing) {
                            $this->model->updateFromImportMerge($org['id'], (int) $existing['id'], $existing, $data);
                            $updated++;
                        } else {
                            $this->model->create($org['id'], [
                                'name' => $name,
                                'category' => $data['category'] ?: null,
                                'sector' => $data['sector'] ?: 'General',
                                'price' => $data['price'] ?? 0,
                                'duration_minutes' => $data['duration_minutes'],
                                'gst_percent' => $data['gst_percent'] ?? 0,
                                'warranty_days' => $data['warranty_days'] ?? 0,
                                'description' => $data['description'] ?: null,
                            ]);
                            $created++;
                        }
                    }
                    fclose($handle);
                    $result = ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
                    logActivity($org['id'], 'service_item_bulk_import', 'service_item', 0, "created={$created} updated={$updated}");
                }
            }
        }

        $pageTitle = 'Bulk Import Service Items';
        require __DIR__ . '/../views/service/items/bulk-import.php';
    }
}
