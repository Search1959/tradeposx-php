<?php
/**
 * Generates one richly-populated demo organization — "SmartTech Solutions",
 * an electronics store that also runs a repair counter — covering both
 * TradeERP and ServiceERP with realistic products, customers (including a
 * few real Udhaar balances), a month of backdated sales/invoices so Reports
 * and the dashboard show real trends (not a flat empty account), job cards
 * in every status, and a quotation converted to an invoice.
 *
 * Deliberately runs as a server-side action (SuperAdminController::seedDemoAction)
 * rather than a static SQL dump: every id here comes from PDO::lastInsertId()
 * on whichever database this actually runs against, so it can never collide
 * with existing rows — a hand-crafted SQL dump with hardcoded ids from a
 * different (e.g. local dev) database doesn't have that guarantee.
 *
 * Safe to re-run: deletes any existing org with the same slug first.
 */
class DemoSeeder {
    private const SLUG = 'smarttech-demo';

    public static function seed(PDO $db): array {
        $existing = $db->prepare('SELECT id FROM organizations WHERE slug = ?');
        $existing->execute([self::SLUG]);
        $removed = false;
        if ($row = $existing->fetch()) {
            $db->prepare('DELETE FROM organizations WHERE id = ?')->execute([$row['id']]);
            $removed = true;
        }

        $insert = fn(string $sql, array $params) => self::insertReturningId($db, $sql, $params);
        $daysAgo = fn(int $days, ?int $hour = null) => self::daysAgo($days, $hour);

        // ── Organization + owner ────────────────────────────────────────
        $orgId = $insert(
            'INSERT INTO organizations (slug, name, business_type, trading_sector, theme_color, status) VALUES (?,?,?,?,?,?)',
            [self::SLUG, 'SmartTech Solutions', 'both', 'Electronics & Repair', '#4f46e5', 'active']
        );
        $db->prepare('INSERT INTO users (org_id, name, email, password_hash, role, status) VALUES (?,?,?,?,?,?)')
           ->execute([$orgId, 'Demo Owner', 'demo@tradeposx.com', password_hash('Demo@2026', PASSWORD_DEFAULT), 'owner', 'active']);

        // ── TradeERP: Suppliers ─────────────────────────────────────────
        $supplierNames = ['Mobile World Distributors', 'TechGear Wholesale', 'Digital Accessories Hub'];
        $supplierIds = [];
        foreach ($supplierNames as $i => $name) {
            $supplierIds[] = $insert(
                'INSERT INTO suppliers (org_id, name, contact_person, mobile, city, outstanding_balance) VALUES (?,?,?,?,?,?)',
                [$orgId, $name, 'Contact Person ' . ($i+1), '98765400' . (10+$i), 'Kolkata', 0]
            );
        }

        // ── TradeERP: Products ───────────────────────────────────────────
        $productDefs = [
            ['Samsung Galaxy Earbuds', 'Audio', 1200, 1800, 1999, 25, 8],
            ['Boat Rockerz Bluetooth Headphone', 'Audio', 900, 1400, 1499, 30, 8],
            ['Type-C Fast Charger 25W', 'Chargers & Cables', 350, 550, 599, 50, 10],
            ['Lightning Cable 1m', 'Chargers & Cables', 150, 300, 349, 60, 10],
            ['10000mAh Power Bank', 'Power Banks', 700, 1100, 1199, 20, 6],
            ['Tempered Glass Screen Protector', 'Protection', 40, 99, 129, 100, 20],
            ['Mobile Back Cover (Assorted)', 'Protection', 60, 150, 199, 80, 15],
            ['Bluetooth Speaker Mini', 'Audio', 500, 850, 999, 15, 5],
            ['Smart Watch Fitness Band', 'Wearables', 1500, 2500, 2999, 12, 4],
            ['USB OTG Adapter', 'Accessories', 50, 100, 149, 40, 10],
            ['Car Mobile Holder', 'Accessories', 120, 250, 299, 25, 8],
            ['Memory Card 64GB', 'Storage', 400, 650, 699, 30, 8],
            ['Laptop Cooling Pad', 'Laptop Accessories', 600, 999, 1199, 10, 3],
            ['Wireless Mouse', 'Laptop Accessories', 250, 450, 499, 20, 5],
            ['HDMI Cable 1.5m', 'Chargers & Cables', 100, 220, 249, 35, 8],
        ];
        $productIds = [];
        foreach ($productDefs as $i => [$name, $cat, $pp, $sp, $mrp, $stock, $min]) {
            $productIds[] = $insert(
                'INSERT INTO products (org_id, name, sku, category, unit, purchase_price, selling_price, mrp, current_stock, min_stock, gst_percent, supplier_id, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$orgId, $name, 'ST-' . str_pad($i+1, 3, '0', STR_PAD_LEFT), $cat, 'pcs', $pp, $sp, $mrp, $stock, $min, 18, $supplierIds[$i % 3], 'ACTIVE']
            );
            $db->prepare('INSERT INTO inventory_transactions (org_id, product_id, type, quantity, notes, created_at) VALUES (?,?,?,?,?,?)')
               ->execute([$orgId, end($productIds), 'INITIAL_STOCK', $stock, 'Opening stock', $daysAgo(35)]);
        }

        // ── TradeERP: Customers (with real Udhaar balances) ─────────────
        $customerDefs = [
            ['Rohit Sharma', '9876540101', 0], ['Priya Patel', '9876540102', 850], ['Amit Verma', '9876540103', 0],
            ['Sneha Reddy', '9876540104', 2200], ['Vikram Singh', '9876540105', 0], ['Anjali Gupta', '9876540106', 450],
        ];
        $customerIds = [];
        foreach ($customerDefs as [$name, $mobile, $balance]) {
            $id = $insert('INSERT INTO customers (org_id, name, mobile, area, current_balance) VALUES (?,?,?,?,?)',
                [$orgId, $name, $mobile, 'Salt Lake, Kolkata', $balance]);
            $customerIds[] = $id;
            if ($balance > 0) {
                $db->prepare('INSERT INTO customer_transactions (org_id, customer_id, type, amount, balance_after, payment_method, notes, created_at) VALUES (?,?,?,?,?,?,?,?)')
                   ->execute([$orgId, $id, 'CREDIT_SALE', $balance, $balance, 'CASH', 'Udhaar for purchase', $daysAgo(rand(3,15))]);
            }
        }

        // ── TradeERP: ~26 sales spread over the last 30 days ────────────
        $saleCount = 0;
        for ($d = 29; $d >= 0; $d--) {
            $salesToday = rand(0, 2);
            for ($s = 0; $s < $salesToday; $s++) {
                $saleCount++;
                $lineCount = rand(1, 3);
                $items = []; $subtotal = 0; $tax = 0;
                for ($l = 0; $l < $lineCount; $l++) {
                    $pIdx = array_rand($productIds);
                    $prod = self::fetchOne($db, 'products', $productIds[$pIdx]);
                    $qty = rand(1, 3);
                    $lineTotal = round($qty * (float)$prod['selling_price'], 2);
                    $gstAmt = round($lineTotal * (float)$prod['gst_percent'] / 100, 2);
                    $subtotal += $lineTotal; $tax += $gstAmt;
                    $items[] = ['product_id' => $prod['id'], 'name' => $prod['name'], 'qty' => $qty, 'price' => $prod['selling_price'], 'total' => $lineTotal, 'gst_rate' => $prod['gst_percent'], 'gst_amt' => $gstAmt];
                }
                $grandTotal = round($subtotal + $tax, 2);
                $isCredit = rand(1, 10) === 1;
                $custIdx = rand(0, count($customerIds) - 1);
                $paymentMethod = $isCredit ? 'CASH' : (rand(0,1) ? 'CASH' : 'UPI');
                $received = $isCredit ? round($grandTotal * 0.4, 2) : $grandTotal;
                $status = $isCredit ? 'PARTIAL' : 'PAID';
                $createdAt = $daysAgo($d);
                $saleNo = 'SL-' . str_pad($saleCount, 5, '0', STR_PAD_LEFT);

                $saleId = $insert(
                    'INSERT INTO sales (org_id, sale_number, customer_id, customer_name, customer_mobile, subtotal, total_tax_amount, grand_total, payment_method, payment_status, received_amount, created_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$orgId, $saleNo, $customerIds[$custIdx], $customerDefs[$custIdx][0], $customerDefs[$custIdx][1],
                     $subtotal, $tax, $grandTotal, $paymentMethod, $status, $received, $createdAt]
                );
                foreach ($items as $it) {
                    $db->prepare('INSERT INTO sale_items (org_id, sale_id, product_id, product_name, quantity, unit_price, total_price, gst_rate, gst_amount) VALUES (?,?,?,?,?,?,?,?,?)')
                       ->execute([$orgId, $saleId, $it['product_id'], $it['name'], $it['qty'], $it['price'], $it['total'], $it['gst_rate'], $it['gst_amt']]);
                    $db->prepare('UPDATE products SET current_stock = current_stock - ? WHERE id = ?')->execute([$it['qty'], $it['product_id']]);
                    $db->prepare('INSERT INTO inventory_transactions (org_id, product_id, type, quantity, reference_id, created_at) VALUES (?,?,?,?,?,?)')
                       ->execute([$orgId, $it['product_id'], 'STOCK_OUT_SALE', $it['qty'], $saleNo, $createdAt]);
                }
                if ($isCredit) {
                    $owed = round($grandTotal - $received, 2);
                    $db->prepare('UPDATE customers SET current_balance = current_balance + ? WHERE id = ?')->execute([$owed, $customerIds[$custIdx]]);
                    $newBal = self::fetchOne($db, 'customers', $customerIds[$custIdx])['current_balance'];
                    $db->prepare('INSERT INTO customer_transactions (org_id, customer_id, type, amount, balance_after, payment_method, reference_id, created_at) VALUES (?,?,?,?,?,?,?,?)')
                       ->execute([$orgId, $customerIds[$custIdx], 'CREDIT_SALE', $owed, $newBal, $paymentMethod, $saleNo, $createdAt]);
                }
            }
        }

        // ── TradeERP: Purchases + Expenses ───────────────────────────────
        for ($i = 0; $i < 4; $i++) {
            $pIdx = array_rand($productIds, 3);
            $items = []; $subtotal = 0; $tax = 0;
            foreach ($pIdx as $idx) {
                $prod = self::fetchOne($db, 'products', $productIds[$idx]);
                $qty = rand(10, 30);
                $lineTotal = round($qty * (float)$prod['purchase_price'], 2);
                $gstAmt = round($lineTotal * (float)$prod['gst_percent'] / 100, 2);
                $subtotal += $lineTotal; $tax += $gstAmt;
                $items[] = ['product_id' => $prod['id'], 'name' => $prod['name'], 'qty' => $qty, 'price' => $prod['purchase_price'], 'total' => $lineTotal, 'gst' => $gstAmt, 'gst_pct' => $prod['gst_percent']];
            }
            $grand = round($subtotal + $tax, 2);
            $createdAt = $daysAgo(28 - $i * 7);
            $purNo = 'PU-' . str_pad($i+1, 5, '0', STR_PAD_LEFT);
            $purId = $insert(
                'INSERT INTO purchases (org_id, purchase_number, supplier_id, supplier_name, invoice_number, purchase_date, subtotal, tax_amount, grand_total, paid_amount, payment_status, payment_method, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$orgId, $purNo, $supplierIds[$i % 3], $supplierNames[$i % 3], 'INV-' . rand(10000,99999),
                 date('Y-m-d', strtotime($createdAt)), $subtotal, $tax, $grand, $grand, 'PAID', 'BANK', $createdAt]
            );
            foreach ($items as $it) {
                $db->prepare('INSERT INTO purchase_items (org_id, purchase_id, product_id, product_name, quantity, purchase_price, gst_percent, gst_amount, total_price) VALUES (?,?,?,?,?,?,?,?,?)')
                   ->execute([$orgId, $purId, $it['product_id'], $it['name'], $it['qty'], $it['price'], $it['gst_pct'], $it['gst'], $it['total']]);
                $db->prepare('UPDATE products SET current_stock = current_stock + ? WHERE id = ?')->execute([$it['qty'], $it['product_id']]);
                $db->prepare('INSERT INTO inventory_transactions (org_id, product_id, type, quantity, reference_id, created_at) VALUES (?,?,?,?,?,?)')
                   ->execute([$orgId, $it['product_id'], 'STOCK_IN_PURCHASE', $it['qty'], $purNo, $createdAt]);
            }
        }
        $expenseDefs = [['Rent', 8000, 28], ['Electricity', 2200, 20], ['Staff Salary', 9000, 15], ['Wi-Fi & Telecom', 800, 10]];
        foreach ($expenseDefs as [$cat, $amt, $daysBack]) {
            $db->prepare('INSERT INTO expenses (org_id, category, amount, created_at) VALUES (?,?,?,?)')
               ->execute([$orgId, $cat, $amt, $daysAgo($daysBack)]);
        }

        // ── ServiceERP: Staff ─────────────────────────────────────────────
        $staffDefs = [['Rakesh Kumar', 'Senior Technician', 15, 4.8], ['Suresh Yadav', 'Technician', 12, 4.5], ['Manoj Das', 'Junior Technician', 10, 4.2]];
        $staffIds = [];
        foreach ($staffDefs as $i => [$name, $role, $comm, $rating]) {
            $staffIds[] = $insert('INSERT INTO service_staff (org_id, name, role, mobile, commission_percent, rating, status) VALUES (?,?,?,?,?,?,?)',
                [$orgId, $name, $role, '98765401' . (20+$i), $comm, $rating, 'Active']);
        }

        // ── ServiceERP: Service items ───────────────────────────────────
        $serviceDefs = [
            ['Screen Replacement', 1800, 30], ['Battery Replacement', 900, 20], ['Charging Port Repair', 600, 25],
            ['Water Damage Repair', 1500, 60], ['Software / OS Reinstall', 500, 30], ['Speaker / Mic Repair', 700, 25],
        ];
        $serviceIds = [];
        foreach ($serviceDefs as [$name, $price, $dur]) {
            $serviceIds[] = $insert('INSERT INTO service_items (org_id, name, category, price, duration_minutes, gst_percent, sector) VALUES (?,?,?,?,?,?,?)',
                [$orgId, $name, 'Repair', $price, $dur, 18, 'Electronics Repair']);
        }

        // ── ServiceERP: Service customers ───────────────────────────────
        $svcCustomerDefs = [['Rajesh Nair', '9876540201'], ['Pooja Iyer', '9876540202'], ['Karan Mehta', '9876540203'], ['Divya Joshi', '9876540204'], ['Sanjay Kapoor', '9876540205']];
        $svcCustomerIds = [];
        foreach ($svcCustomerDefs as [$name, $mobile]) {
            $svcCustomerIds[] = $insert('INSERT INTO service_customers (org_id, name, mobile, sector) VALUES (?,?,?,?)',
                [$orgId, $name, $mobile, 'Electronics Repair']);
        }

        // ── ServiceERP: Appointments ─────────────────────────────────────
        $apptDefs = [[-3, 'Completed', 0], [-1, 'Completed', 1], [2, 'Scheduled', 2], [5, 'Scheduled', 3]];
        foreach ($apptDefs as [$dayOffset, $status, $idx]) {
            $svc = self::fetchOne($db, 'service_items', $serviceIds[$idx % count($serviceIds)]);
            $custIdx = $idx % count($svcCustomerIds);
            $date = date('Y-m-d', strtotime(($dayOffset >= 0 ? '+' : '') . $dayOffset . ' days'));
            $db->prepare('INSERT INTO appointments (org_id, customer_id, customer_name, mobile, service_id, service_name, service_sector, staff_id, staff_name, appt_date, appt_time, status, total_amount, paid_amount)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
               ->execute([$orgId, $svcCustomerIds[$custIdx], $svcCustomerDefs[$custIdx][0], $svcCustomerDefs[$custIdx][1],
                          $svc['id'], $svc['name'], 'Electronics Repair', $staffIds[$idx % 3], $staffDefs[$idx % 3][0],
                          $date, sprintf('%02d:00', 11 + $idx), $status, $svc['price'], $status === 'Completed' ? $svc['price'] : 0]);
        }

        // ── ServiceERP: Job cards (one of each status) ──────────────────
        $jobDefs = [
            ['Pending', 'Screen cracked, needs replacement', 'Samsung Galaxy M31', 0, 0],
            ['In-Progress', 'Not charging, suspected port issue', 'Redmi Note 10', 300, 0],
            ['Completed', 'Battery draining fast', 'iPhone 11', 400, 400],
            ['Completed', 'Water damage after rain', 'OnePlus Nord', 500, 300],
            ['Delivered', 'Speaker crackling, mic not working', 'Vivo Y21', 350, 350],
        ];
        foreach ($jobDefs as $i => [$status, $issue, $device, $labour, $paid]) {
            $custIdx = $i % count($svcCustomerIds);
            $staffIdx = $i % 3;
            $partsTotal = rand(1,3) === 1 ? 250 : 0;
            $total = $labour + $partsTotal;
            $balance = max(0, $total - $paid);
            $jobId = $insert(
                'INSERT INTO job_cards (org_id, job_no, customer_id, customer_name, mobile, service_sector, assigned_staff_id, assigned_staff_name, status, priority, issue_description, device_or_vehicle_info, labour_charges, material_charges, total_amount, paid_amount, balance_amount, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$orgId, 'JOB-' . str_pad($i+1, 5, '0', STR_PAD_LEFT), $svcCustomerIds[$custIdx], $svcCustomerDefs[$custIdx][0],
                 $svcCustomerDefs[$custIdx][1], 'Electronics Repair', $staffIds[$staffIdx], $staffDefs[$staffIdx][0],
                 $status, ['Low','Medium','High','Medium','Low'][$i], $issue, $device, $labour, $partsTotal, $total, $paid, $balance, $daysAgo(rand(2,20))]
            );
            if ($partsTotal > 0) {
                $db->prepare('INSERT INTO job_card_parts (org_id, job_card_id, name, quantity, unit_price, total_price) VALUES (?,?,?,?,?,?)')
                   ->execute([$orgId, $jobId, 'Replacement Part', 1, $partsTotal, $partsTotal]);
            }
        }

        // ── ServiceERP: Service invoices ─────────────────────────────────
        $invCount = 0;
        for ($i = 0; $i < 9; $i++) {
            $invCount++;
            $svc = self::fetchOne($db, 'service_items', $serviceIds[$i % count($serviceIds)]);
            $custIdx = $i % count($svcCustomerIds);
            $staffIdx = $i % 3;
            $lineTotal = round((float)$svc['price'], 2);
            $gstAmt = round($lineTotal * 0.18, 2);
            $grandTotal = round($lineTotal + $gstAmt, 2);
            $isPartial = $i % 4 === 0;
            $paid = $isPartial ? round($grandTotal * 0.5, 2) : $grandTotal;
            $status = $isPartial ? 'PARTIAL' : 'PAID';
            $createdAt = $daysAgo(rand(1, 21));

            $invId = $insert(
                'INSERT INTO service_invoices (org_id, invoice_no, customer_id, customer_name, mobile, invoice_date, gst_amount, grand_total, paid_amount, balance_amount, payment_method, status, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$orgId, 'SVI-' . str_pad($invCount, 5, '0', STR_PAD_LEFT), $svcCustomerIds[$custIdx], $svcCustomerDefs[$custIdx][0],
                 $svcCustomerDefs[$custIdx][1], date('Y-m-d', strtotime($createdAt)), $gstAmt, $grandTotal, $paid,
                 round($grandTotal - $paid, 2), 'UPI', $status, $createdAt]
            );
            $db->prepare('INSERT INTO service_invoice_items (org_id, invoice_id, service_id, name, item_type, price, quantity, total, staff_id) VALUES (?,?,?,?,?,?,?,?,?)')
               ->execute([$orgId, $invId, $svc['id'], $svc['name'], 'SERVICE', $svc['price'], 1, $lineTotal, $staffIds[$staffIdx]]);
            $commission = round($lineTotal * $staffDefs[$staffIdx][2] / 100, 2);
            $db->prepare('UPDATE service_staff SET commission_payable = commission_payable + ? WHERE id = ?')->execute([$commission, $staffIds[$staffIdx]]);
        }

        // ── ServiceERP: Quotations (one draft, one converted) ────────────
        $quoteId1 = $insert(
            'INSERT INTO service_quotations (org_id, quote_no, customer_id, customer_name, mobile, quote_date, valid_until, gst_amount, grand_total, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$orgId, 'QUO-00001', $svcCustomerIds[0], $svcCustomerDefs[0][0], $svcCustomerDefs[0][1], date('Y-m-d', strtotime('-2 days')),
             date('Y-m-d', strtotime('+5 days')), 324, 2124, 'Draft', $daysAgo(2)]
        );
        $db->prepare('INSERT INTO service_invoice_items (org_id, quotation_id, service_id, name, item_type, price, quantity, total) VALUES (?,?,?,?,?,?,?,?)')
           ->execute([$orgId, $quoteId1, $serviceIds[0], 'Screen Replacement', 'SERVICE', 1800, 1, 1800]);

        $quoteId2 = $insert(
            'INSERT INTO service_quotations (org_id, quote_no, customer_id, customer_name, mobile, quote_date, valid_until, gst_amount, grand_total, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$orgId, 'QUO-00002', $svcCustomerIds[1], $svcCustomerDefs[1][0], $svcCustomerDefs[1][1], date('Y-m-d', strtotime('-6 days')),
             date('Y-m-d', strtotime('+1 days')), 162, 1062, 'ConvertedToInvoice', $daysAgo(6)]
        );
        $db->prepare('INSERT INTO service_invoice_items (org_id, quotation_id, service_id, name, item_type, price, quantity, total) VALUES (?,?,?,?,?,?,?,?)')
           ->execute([$orgId, $quoteId2, $serviceIds[1], 'Battery Replacement', 'SERVICE', 900, 1, 900]);
        $convInvId = $insert(
            'INSERT INTO service_invoices (org_id, invoice_no, customer_id, customer_name, mobile, invoice_date, gst_amount, grand_total, paid_amount, balance_amount, payment_method, status, notes, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$orgId, 'SVI-00010', $svcCustomerIds[1], $svcCustomerDefs[1][0], $svcCustomerDefs[1][1], date('Y-m-d', strtotime('-5 days')),
             162, 1062, 1062, 0, 'CASH', 'PAID', 'Converted from quotation QUO-00002', $daysAgo(5)]
        );
        $db->prepare('INSERT INTO service_invoice_items (org_id, invoice_id, service_id, name, item_type, price, quantity, total) VALUES (?,?,?,?,?,?,?,?)')
           ->execute([$orgId, $convInvId, $serviceIds[1], 'Battery Replacement', 'SERVICE', 900, 1, 900]);

        return ['orgId' => $orgId, 'slug' => self::SLUG, 'removed' => $removed, 'salesCreated' => $saleCount, 'invoicesCreated' => $invCount];
    }

    private static function insertReturningId(PDO $db, string $sql, array $params): int {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $db->lastInsertId();
    }

    private static function fetchOne(PDO $db, string $table, int $id): array {
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private static function daysAgo(int $days, ?int $hour = null): string {
        $h = $hour ?? rand(9, 20);
        $m = rand(0, 59);
        $date = new DateTime();
        $date->modify("-{$days} days");
        $date->setTime($h, $m, 0);
        return $date->format('Y-m-d H:i:s');
    }
}
