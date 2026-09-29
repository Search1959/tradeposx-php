<?php
/**
 * One-time P5 migration importer. Reads the raw JSON exported by
 * kiranamate/scripts/export-firestore-data.ts (sql/migrate/data/*.json,
 * gitignored — real customer data) and loads it into this app's schema.
 *
 * Deliberately conservative: copies each account's already-computed current
 * balances/totals as-is rather than recomputing them from history, and
 * preserves the full transaction/audit history as historical record
 * alongside that — this is a migration, not a reconciliation.
 *
 * Safe to re-run: wipes and reloads migrated data each time (identified by
 * legacy_id / organizations.legacy_id), so mistakes can be fixed by editing
 * this script and re-running rather than hand-patching the database.
 *
 * Run with: php sql/migrate/import.php
 */

require_once __DIR__ . '/../../config/app.php';

$dataDir = __DIR__ . '/data';
$tradingAccounts = json_decode(file_get_contents($dataDir . '/trading-accounts.json'), true);
$tradeStores = json_decode(file_get_contents($dataDir . '/trade-stores.json'), true);
$serviceAccounts = json_decode(file_get_contents($dataDir . '/service-accounts.json'), true);
$serviceCompanies = json_decode(file_get_contents($dataDir . '/service-companies.json'), true);

$db = Database::getInstance()->getConnection();

/** The old app stored ISO 8601 timestamps ("...T...Z") for *At fields, which
 * MySQL DATETIME/TIMESTAMP columns reject outright — this normalizes both
 * that and plain "YYYY-MM-DD" dates (passed through unchanged) to something
 * MySQL accepts, defaulting to now() only if the value is missing entirely. */
function toMysqlDateTime(?string $value): string {
    if (!$value) return date('Y-m-d H:i:s');
    $ts = strtotime($value);
    return $ts !== false ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
}

/** Same normalization for DATE columns — accepts null (MySQL DATE nullable
 * columns get NULL rather than a fabricated date, unlike the *At fallback above). */
function toMysqlDate(?string $value): ?string {
    if (!$value) return null;
    $ts = strtotime($value);
    return $ts !== false ? date('Y-m-d', $ts) : null;
}

function insertReturningId(PDO $db, string $sql, array $params): int {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int) $db->lastInsertId();
}

/** Re-running the script must not create duplicates — every migrated org's
 * children get removed (via ON DELETE CASCADE on org_id) before reloading it. */
function wipeExistingOrgByLegacyId(PDO $db, string $legacyId): void {
    $stmt = $db->prepare('SELECT id FROM organizations WHERE legacy_id = ?');
    $stmt->execute([$legacyId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $db->prepare('DELETE FROM organizations WHERE id = ?')->execute([$existing['id']]);
    }
}

$summary = [];

// ─────────────────────────────────────────────────────────────────────────
// TradeERP
// ─────────────────────────────────────────────────────────────────────────
// storeId -> username(s) that log into it (usually 1:1, occasionally a store
// has a second staff account under the same storeId).
$usersByStoreId = [];
foreach ($tradingAccounts as $acct) {
    if (!empty($acct['storeId'])) $usersByStoreId[$acct['storeId']][] = $acct['username'];
}

foreach ($tradeStores as $storeId => $store) {
    wipeExistingOrgByLegacyId($db, $storeId);

    $settings = $store['settings'] ?? [];
    $name = $settings['storeName'] ?? ('Trade Store ' . $storeId);
    $slugBase = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
    $slug = trim($slugBase, '-') ?: 'store-' . substr(md5($storeId), 0, 8);
    // A slug collision across two different old stores is possible (two shops
    // named similarly) — disambiguate with a short suffix from the legacy id.
    $slugCheck = $db->prepare('SELECT id FROM organizations WHERE slug = ?');
    $slugCheck->execute([$slug]);
    if ($slugCheck->fetch()) $slug .= '-' . substr(md5($storeId), 0, 5);

    $orgId = insertReturningId($db,
        'INSERT INTO organizations (slug, name, business_type, trading_sector, status, legacy_id) VALUES (?,?,?,?,?,?)',
        [$slug, $name, 'trade', $settings['sector'] ?? null, 'active', $storeId]
    );

    // ---- Owner/staff users (existing bcrypt hash carried over as-is) ----
    foreach (($store['users'] ?? []) as $u) {
        if (empty($u['passwordHash'])) continue; // no usable credential — skip rather than lock the account with a fake one
        $db->prepare(
            'INSERT INTO users (org_id, name, username, mobile, password_hash, role, status, legacy_id) VALUES (?,?,?,?,?,?,?,?)'
        )->execute([
            $orgId, $u['name'] ?? $u['username'] ?? 'Owner', $u['username'] ?? null, $u['mobile'] ?? null,
            $u['passwordHash'], ($u['role'] ?? 'owner') === 'owner' ? 'owner' : 'staff', 'active', $u['id'] ?? null,
        ]);
    }

    // ---- Suppliers ----
    $supplierIdMap = [];
    foreach (($store['suppliers'] ?? []) as $s) {
        $newId = insertReturningId($db,
            'INSERT INTO suppliers (org_id, name, contact_person, mobile, company_name, category, address, city, outstanding_balance, legacy_id)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $s['name'], $s['contactPerson'] ?? null, $s['mobile'] ?? null, $s['companyName'] ?? null,
             $s['category'] ?? null, $s['address'] ?? null, $s['city'] ?? null, $s['outstandingBalance'] ?? 0, $s['id']]
        );
        $supplierIdMap[$s['id']] = $newId;
    }

    // ---- Products ----
    $productIdMap = [];
    foreach (($store['products'] ?? []) as $p) {
        $newId = insertReturningId($db,
            'INSERT INTO products (org_id, name, sku, barcode, category, brand, unit, purchase_price, selling_price, mrp,
             current_stock, min_stock, supplier_id, gst_percent, hsn, status, legacy_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $orgId, $p['name'], $p['sku'] ?? null, $p['barcode'] ?? null, $p['category'] ?? 'Other', $p['brand'] ?? null,
                $p['unit'] ?? 'pcs', $p['purchasePrice'] ?? 0, $p['sellingPrice'] ?? 0, $p['mrp'] ?? 0,
                $p['currentStock'] ?? 0, $p['minStock'] ?? 0, $supplierIdMap[$p['supplierId'] ?? null] ?? null,
                $p['gstPercent'] ?? 0, $p['hsn'] ?? null, ($p['status'] ?? 'ACTIVE') === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE', $p['id'],
            ]
        );
        $productIdMap[$p['id']] = $newId;
    }

    // ---- Customers (balance copied as-is; full transaction history preserved separately) ----
    $customerIdMap = [];
    foreach (($store['customers'] ?? []) as $c) {
        $balance = $c['currentBalance'] ?? $c['outstandingBalance'] ?? 0;
        $newId = insertReturningId($db,
            'INSERT INTO customers (org_id, name, mobile, address, area, email, opening_balance, current_balance, credit_limit, trust_rating, legacy_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $c['name'], $c['mobile'] ?? '', $c['address'] ?? null, $c['area'] ?? null, $c['email'] ?? null,
             $c['openingBalance'] ?? 0, $balance, $c['creditLimit'] ?? null, $c['trustRating'] ?? null, $c['id']]
        );
        $customerIdMap[$c['id']] = $newId;
    }
    foreach (($store['customerTransactions'] ?? []) as $t) {
        $custId = $customerIdMap[$t['customerId'] ?? null] ?? null;
        if (!$custId) continue;
        $db->prepare(
            'INSERT INTO customer_transactions (org_id, customer_id, type, amount, balance_after, payment_method, reference_id, notes, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $orgId, $custId, $t['type'] ?? 'OPENING_BALANCE', $t['amount'] ?? 0, $t['balanceAfter'] ?? 0,
            $t['paymentMethod'] ?? null, $t['referenceId'] ?? null, $t['notes'] ?? null, $t['createdBy'] ?? null,
            toMysqlDateTime($t['createdAt'] ?? null),
        ]);
    }

    // ---- Purchases ----
    foreach (($store['purchases'] ?? []) as $pur) {
        $grandTotal = $pur['grandTotal'] ?? $pur['totalAmount'] ?? 0;
        $paidAmount = $pur['paidAmount'] ?? $grandTotal;
        $purchaseId = insertReturningId($db,
            'INSERT INTO purchases (org_id, purchase_number, supplier_id, supplier_name, invoice_number, purchase_date,
             subtotal, tax_amount, discount_amount, freight_charges, grand_total, paid_amount, payment_status, payment_method, notes, legacy_id, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $orgId, $pur['purchaseNumber'] ?? ('PU-' . $pur['id']), $supplierIdMap[$pur['supplierId'] ?? null] ?? null,
                $pur['supplierName'] ?? null, $pur['invoiceNumber'] ?? null, toMysqlDate($pur['createdAt'] ?? null),
                $pur['subtotal'] ?? $grandTotal, $pur['taxAmount'] ?? 0, $pur['discountAmount'] ?? 0, $pur['freightCharges'] ?? 0,
                $grandTotal, $paidAmount, $pur['paymentStatus'] ?? 'PAID', $pur['paymentMethod'] ?? 'CASH',
                $pur['notes'] ?? null, $pur['id'], toMysqlDateTime($pur['createdAt'] ?? null),
            ]
        );
        foreach (($pur['items'] ?? []) as $item) {
            $db->prepare(
                'INSERT INTO purchase_items (org_id, purchase_id, product_id, product_name, quantity, purchase_price, gst_percent, gst_amount, total_price, hsn, batch_number, expiry_date)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $orgId, $purchaseId, $productIdMap[$item['productId'] ?? null] ?? null, $item['productName'] ?? 'Unknown',
                $item['quantity'] ?? 0, $item['purchasePrice'] ?? 0, $item['gstPercent'] ?? null, $item['gstAmount'] ?? null,
                $item['totalPrice'] ?? 0, $item['hsn'] ?? null, $item['batchNumber'] ?? null, toMysqlDate($item['expiryDate'] ?? null),
            ]);
        }
    }

    // ---- Sales ----
    foreach (($store['sales'] ?? []) as $sale) {
        $grandTotal = $sale['grandTotal'] ?? 0;
        $status = $sale['paymentStatus'] ?? 'PAID';
        // Old export doesn't always carry an explicit receivedAmount — a PAID
        // sale with none recorded is assumed fully received (matches the old
        // app's own display logic); anything else with none recorded is
        // conservatively assumed unpaid rather than guessing a split.
        $receivedAmount = $sale['receivedAmount'] ?? ($status === 'PAID' ? $grandTotal : 0);
        $saleId = insertReturningId($db,
            'INSERT INTO sales (org_id, sale_number, customer_id, customer_name, customer_mobile, subtotal, discount,
             gst_rate, total_tax_amount, grand_total, payment_method, payment_status, received_amount, notes, status,
             cancel_reason, legacy_id, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $orgId, $sale['saleNumber'] ?? ('SL-' . $sale['id']), $customerIdMap[$sale['customerId'] ?? null] ?? null,
                $sale['customerName'] ?? null, $sale['customerMobile'] ?? null, $sale['subtotal'] ?? $grandTotal,
                $sale['discount'] ?? 0, $sale['gstRate'] ?? null, $sale['totalTaxAmount'] ?? null, $grandTotal,
                in_array($sale['paymentMethod'] ?? 'CASH', ['CASH','UPI','BANK','OTHER','CREDIT'], true) ? $sale['paymentMethod'] : 'CASH',
                in_array($status, ['PAID','PENDING','PARTIAL'], true) ? $status : 'PAID', $receivedAmount, $sale['notes'] ?? null,
                ($sale['status'] ?? null) === 'CANCELLED' ? 'CANCELLED' : null, $sale['cancelReason'] ?? null, $sale['id'],
                toMysqlDateTime($sale['createdAt'] ?? null),
            ]
        );
        foreach (($sale['items'] ?? []) as $item) {
            $db->prepare(
                'INSERT INTO sale_items (org_id, sale_id, product_id, product_name, quantity, unit_price, mrp, total_price, gst_rate, gst_amount, hsn, batch_number, expiry_date)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $orgId, $saleId, $productIdMap[$item['productId'] ?? null] ?? null, $item['productName'] ?? 'Unknown',
                $item['quantity'] ?? 0, $item['unitPrice'] ?? 0, $item['mrp'] ?? null, $item['totalPrice'] ?? 0,
                $item['gstRate'] ?? null, $item['gstAmount'] ?? null, $item['hsn'] ?? null, $item['batchNumber'] ?? null, toMysqlDate($item['expiryDate'] ?? null),
            ]);
        }
    }

    // ---- Expenses ----
    foreach (($store['expenses'] ?? []) as $e) {
        $db->prepare(
            'INSERT INTO expenses (org_id, category, amount, notes, legacy_id, created_at) VALUES (?,?,?,?,?,?)'
        )->execute([$orgId, $e['category'] ?? 'Other', $e['amount'] ?? 0, $e['notes'] ?? null, $e['id'] ?? null, toMysqlDateTime($e['createdAt'] ?? null)]);
    }

    // ---- Inventory audit trail ----
    foreach (($store['inventoryTransactions'] ?? []) as $it) {
        $prodId = $productIdMap[$it['productId'] ?? null] ?? null;
        if (!$prodId) continue;
        $db->prepare(
            'INSERT INTO inventory_transactions (org_id, product_id, type, quantity, reference_id, notes, created_at) VALUES (?,?,?,?,?,?,?)'
        )->execute([$orgId, $prodId, $it['type'] ?? 'MANUAL_ADD', $it['quantity'] ?? 0, $it['referenceId'] ?? null, $it['notes'] ?? null, toMysqlDateTime($it['createdAt'] ?? null)]);
    }

    $summary[] = [
        'type' => 'trade', 'name' => $name, 'slug' => $slug,
        'products' => count($store['products'] ?? []), 'customers' => count($store['customers'] ?? []),
        'sales' => count($store['sales'] ?? []), 'purchases' => count($store['purchases'] ?? []),
    ];
}

// ─────────────────────────────────────────────────────────────────────────
// ServiceERP
// ─────────────────────────────────────────────────────────────────────────
foreach ($serviceCompanies as $companyId => $company) {
    wipeExistingOrgByLegacyId($db, $companyId);

    // The matching serviceAccounts entry (login credentials + business profile)
    // — a company can in principle have more than one, but in this export
    // every companyId maps to exactly one owner account.
    $account = null;
    foreach ($serviceAccounts as $a) { if (($a['companyId'] ?? null) === $companyId) { $account = $a; break; } }

    $name = $account['businessName'] ?? ('Service Business ' . $companyId);
    $slugBase = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
    $slug = trim($slugBase, '-') ?: 'service-' . substr(md5($companyId), 0, 8);
    $slugCheck = $db->prepare('SELECT id FROM organizations WHERE slug = ?');
    $slugCheck->execute([$slug]);
    if ($slugCheck->fetch()) $slug .= '-' . substr(md5($companyId), 0, 5);

    $orgId = insertReturningId($db,
        'INSERT INTO organizations (slug, name, business_type, trading_sector, status, legacy_id) VALUES (?,?,?,?,?,?)',
        [$slug, $name, 'service', $account['sector'] ?? $company['activeSector'] ?? null, 'active', $companyId]
    );

    if ($account) {
        // Old ServiceERP stored the password in PLAINTEXT (a pre-existing issue in
        // the old app, not carried forward) — hashed fresh here so the same
        // password keeps working but is now stored the way it should be.
        $hash = !empty($account['password']) ? password_hash($account['password'], PASSWORD_DEFAULT) : password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $db->prepare(
            'INSERT INTO users (org_id, name, username, mobile, password_hash, role, status, legacy_id) VALUES (?,?,?,?,?,?,?,?)'
        )->execute([$orgId, $account['ownerName'] ?? $account['username'], $account['username'], $account['mobile'] ?? null, $hash, 'owner', 'active', $companyId]);
    }

    // ---- Staff ----
    $staffIdMap = [];
    foreach (($company['staff'] ?? []) as $s) {
        $newId = insertReturningId($db,
            'INSERT INTO service_staff (org_id, name, role, mobile, specialty, rating, total_jobs_completed, commission_percent, daily_target, status, commission_payable, legacy_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $s['name'], $s['role'] ?? null, $s['mobile'] ?? null, $s['specialty'] ?? null, $s['rating'] ?? null,
             $s['totalJobsCompleted'] ?? 0, $s['commissionPercent'] ?? 0, $s['dailyTarget'] ?? null,
             in_array($s['status'] ?? 'Active', ['Active','On-Leave','Busy'], true) ? $s['status'] : 'Active',
             $s['commissionPayable'] ?? 0, $s['id']]
        );
        $staffIdMap[$s['id']] = $newId;
    }

    // ---- Service items ----
    $serviceItemIdMap = [];
    foreach (($company['services'] ?? []) as $svc) {
        $newId = insertReturningId($db,
            'INSERT INTO service_items (org_id, name, category, price, duration_minutes, gst_percent, sector, description, is_popular, legacy_id)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $svc['name'], $svc['category'] ?? null, $svc['price'] ?? 0, $svc['durationMinutes'] ?? null,
             $svc['gstPercent'] ?? 0, $svc['sector'] ?? $company['activeSector'] ?? 'General', $svc['description'] ?? null,
             !empty($svc['isPopular']) ? 1 : 0, $svc['id']]
        );
        $serviceItemIdMap[$svc['id']] = $newId;
    }

    // ---- Service customers ----
    $svcCustomerIdMap = [];
    foreach (($company['customers'] ?? []) as $c) {
        $newId = insertReturningId($db,
            'INSERT INTO service_customers (org_id, name, mobile, email, address, sector, total_spent, amc_status, amc_expiry_date, notes, legacy_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $c['name'], $c['mobile'] ?? '', $c['email'] ?? null, $c['address'] ?? null, $c['sector'] ?? null,
             $c['totalSpent'] ?? 0, $c['amcStatus'] ?? null, toMysqlDate($c['amcExpiryDate'] ?? null), $c['notes'] ?? null, $c['id']]
        );
        $svcCustomerIdMap[$c['id']] = $newId;
    }

    // ---- Appointments ----
    foreach (($company['appointments'] ?? []) as $appt) {
        $db->prepare(
            'INSERT INTO appointments (org_id, customer_id, customer_name, mobile, service_id, service_name, service_sector,
             staff_id, staff_name, appt_date, appt_time, status, notes, total_amount, paid_amount, legacy_id, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $orgId, $svcCustomerIdMap[$appt['customerId'] ?? null] ?? null, $appt['customerName'] ?? null, $appt['mobile'] ?? null,
            $serviceItemIdMap[$appt['serviceId'] ?? null] ?? null, $appt['serviceName'] ?? null, $appt['serviceSector'] ?? null,
            $staffIdMap[$appt['staffId'] ?? null] ?? null, $appt['staffName'] ?? null, toMysqlDate($appt['date'] ?? null) ?? date('Y-m-d'),
            $appt['time'] ?? '10:00', in_array($appt['status'] ?? 'Scheduled', ['Scheduled','In-Progress','Completed','Cancelled','No-Show'], true) ? $appt['status'] : 'Scheduled',
            $appt['notes'] ?? null, $appt['totalAmount'] ?? 0, $appt['paidAmount'] ?? 0, $appt['id'], toMysqlDateTime($appt['createdAt'] ?? null),
        ]);
    }

    // ---- Job cards (+ parts) ----
    foreach (($company['jobCards'] ?? []) as $job) {
        $jobId = insertReturningId($db,
            'INSERT INTO job_cards (org_id, job_no, customer_id, customer_name, mobile, service_sector, assigned_staff_id,
             assigned_staff_name, status, priority, issue_description, device_or_vehicle_info, labour_charges, material_charges,
             total_amount, paid_amount, balance_amount, estimated_completion_date, actual_completion_date, remarks, legacy_id, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $orgId, $job['jobNo'] ?? ('JOB-' . $job['id']), $svcCustomerIdMap[$job['customerId'] ?? null] ?? null,
                $job['customerName'] ?? null, $job['mobile'] ?? null, $job['serviceSector'] ?? null,
                $staffIdMap[$job['assignedStaffId'] ?? null] ?? null, $job['assignedStaffName'] ?? null,
                in_array($job['status'] ?? 'Pending', ['Pending','In-Progress','Completed','Delivered','Cancelled'], true) ? $job['status'] : 'Pending',
                in_array($job['priority'] ?? 'Medium', ['Low','Medium','High','Urgent'], true) ? $job['priority'] : 'Medium',
                $job['issueDescription'] ?? '', $job['deviceOrVehicleInfo'] ?? null, $job['labourCharges'] ?? 0,
                $job['materialCharges'] ?? 0, $job['totalAmount'] ?? 0, $job['paidAmount'] ?? 0, $job['balanceAmount'] ?? 0,
                toMysqlDate($job['estimatedCompletionDate'] ?? null), toMysqlDate($job['actualCompletionDate'] ?? null), $job['remarks'] ?? null,
                $job['id'], toMysqlDateTime($job['createdAt'] ?? null),
            ]
        );
        foreach (($job['partsUsed'] ?? []) as $part) {
            $db->prepare(
                'INSERT INTO job_card_parts (org_id, job_card_id, name, quantity, unit_price, total_price) VALUES (?,?,?,?,?,?)'
            )->execute([$orgId, $jobId, $part['name'], $part['quantity'] ?? 1, $part['unitPrice'] ?? 0, $part['totalPrice'] ?? 0]);
        }
    }

    // ---- Service invoices (+ items) ----
    foreach (($company['invoices'] ?? []) as $inv) {
        $grandTotal = $inv['grandTotal'] ?? 0;
        $status = in_array($inv['status'] ?? 'PAID', ['PAID','PARTIAL','UNPAID','CANCELLED'], true) ? $inv['status'] : 'PAID';
        $invoiceId = insertReturningId($db,
            'INSERT INTO service_invoices (org_id, invoice_no, customer_id, customer_name, mobile, invoice_date,
             labour_charges, material_charges, discount, gst_amount, grand_total, paid_amount, balance_amount,
             payment_method, status, notes, legacy_id, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $orgId, $inv['invoiceNo'] ?? ('SVI-' . $inv['id']), $svcCustomerIdMap[$inv['customerId'] ?? null] ?? null,
                $inv['customerName'] ?? null, $inv['mobile'] ?? null, toMysqlDate($inv['date'] ?? null) ?? date('Y-m-d'), $inv['labourCharges'] ?? 0,
                $inv['materialCharges'] ?? 0, $inv['discount'] ?? 0, $inv['gstAmount'] ?? 0, $grandTotal,
                $inv['paidAmount'] ?? $grandTotal, $inv['balanceAmount'] ?? 0,
                in_array($inv['paymentMethod'] ?? 'CASH', ['CASH','UPI','BANK','OTHER'], true) ? $inv['paymentMethod'] : 'CASH',
                $status, $inv['notes'] ?? null, $inv['id'], toMysqlDateTime($inv['createdAt'] ?? null),
            ]
        );
        foreach (($inv['items'] ?? []) as $item) {
            $db->prepare(
                'INSERT INTO service_invoice_items (org_id, invoice_id, service_id, name, item_type, price, quantity, total, staff_id)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([
                $orgId, $invoiceId, $serviceItemIdMap[$item['serviceId'] ?? null] ?? null, $item['name'] ?? 'Service',
                in_array($item['type'] ?? 'SERVICE', ['SERVICE','MATERIAL','LABOUR','PACKAGE'], true) ? $item['type'] : 'SERVICE',
                $item['price'] ?? 0, $item['quantity'] ?? 1, $item['total'] ?? 0, $staffIdMap[$item['staffId'] ?? null] ?? null,
            ]);
        }
    }

    // ---- Expenses ----
    foreach (($company['expenses'] ?? []) as $e) {
        $db->prepare('INSERT INTO expenses (org_id, category, amount, notes, created_at) VALUES (?,?,?,?,?)')
            ->execute([$orgId, $e['category'] ?? 'Other', $e['amount'] ?? 0, $e['notes'] ?? null, toMysqlDateTime($e['createdAt'] ?? null)]);
    }

    $summary[] = [
        'type' => 'service', 'name' => $name, 'slug' => $slug,
        'services' => count($company['services'] ?? []), 'customers' => count($company['customers'] ?? []),
        'invoices' => count($company['invoices'] ?? []), 'jobCards' => count($company['jobCards'] ?? []),
    ];
}

echo "\nMigration complete:\n";
foreach ($summary as $row) {
    echo '  [' . $row['type'] . '] ' . $row['name'] . ' -> /' . $row['slug'] . '/  (' . json_encode(array_diff_key($row, ['type'=>0,'name'=>0,'slug'=>0])) . ")\n";
}
echo "\nTotal organizations migrated: " . count($summary) . "\n";
