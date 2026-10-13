<?php
/** Builds the data the "send bill as picture / PDF" component draws (see bill-share.php). Functions only. */
if (!function_exists('billShareFromSale')) {
    /** @param array<string,mixed> $org @param array<string,mixed> $sale */
    function billShareFromSale(array $org, array $sale, bool $isGst, string $docTitle, float $taxableDisplay, array $gstBreakdown, float $cgst, float $sgst, float $owed, string $upi): array {
        $lines = [];
        foreach ($sale['items'] as $it) {
            $lines[] = ['name' => (string) $it['product_name'], 'hsn' => (string) ($it['hsn'] ?? ''), 'qty' => (float) $it['quantity'], 'rate' => (float) $it['unit_price'],
                        'gst' => (float) ($it['gst_rate'] ?? 0), 'amount' => (float) $it['total_price']];
        }
        $totals = [['label' => $isGst ? 'Taxable Amount' : 'Subtotal', 'value' => $isGst ? $taxableDisplay : (float) $sale['subtotal']]];
        if ($isGst && $gstBreakdown) {
            foreach ($gstBreakdown as $g) {
                $totals[] = ['label' => 'CGST @ ' . halfGstRate($g['rate']) . '%', 'value' => $g['tax'] / 2];
                $totals[] = ['label' => 'SGST @ ' . halfGstRate($g['rate']) . '%', 'value' => $g['tax'] / 2];
            }
        } elseif ($isGst) {
            $totals[] = ['label' => 'CGST', 'value' => $cgst];
            $totals[] = ['label' => 'SGST', 'value' => $sgst];
        }
        if ((float) $sale['discount'] > 0) {
            $pct = $sale['discount_percent'] !== null ? ' @ ' . rtrim(rtrim(number_format((float) $sale['discount_percent'], 2), '0'), '.') . '%' : '';
            $totals[] = ['label' => 'Discount' . $pct, 'value' => -(float) $sale['discount']];
        }
        return [
            'shop' => ['name' => (string) $org['name'], 'address' => (string) ($org['address'] ?? ''), 'phone' => (string) ($org['phone'] ?? ''), 'gstin' => (string) ($org['gstin'] ?? ''), 'upi' => $upi, 'footer' => 'Thank you for your business!'],
            'title' => $docTitle, 'no' => (string) $sale['sale_number'], 'date' => formatDateTime($sale['created_at']),
            'customer' => (string) ($sale['customer_name'] ?: 'Walk-in Customer'), 'mobile' => (string) ($sale['customer_mobile'] ?? ''), 'custGstin' => (string) ($sale['customer_gstin'] ?? ''),
            'cancelled' => ($sale['status'] ?? '') === 'CANCELLED', 'showGst' => $isGst, 'lines' => $lines, 'totals' => $totals,
            'grand' => (float) $sale['grand_total'], 'words' => amountInWordsINR((float) $sale['grand_total']),
            'paidLabel' => 'Payment (' . $sale['payment_method'] . ')', 'paid' => (float) $sale['received_amount'],
            'balanceLabel' => 'Udhaar (Balance Due)', 'balance' => $owed,
        ];
    }
    /** @param array<string,mixed> $org @param array<string,mixed> $inv */
    function billShareFromInvoice(array $org, array $inv, bool $isGst, string $docTitle, array $gstBreakdown, string $upi): array {
        $lines = [];
        foreach ($inv['items'] as $it) {
            $lines[] = ['name' => (string) $it['name'], 'hsn' => '', 'qty' => (float) $it['quantity'], 'rate' => (float) $it['price'], 'gst' => (float) ($it['gst_rate'] ?? 0), 'amount' => (float) $it['total']];
        }
        $itemsTotal = array_sum(array_map(fn($i) => (float) $i['total'], $inv['items']));
        $totals = [['label' => $isGst ? 'Taxable Amount' : 'Service Total', 'value' => $isGst && $inv['taxable_amount'] !== null ? (float) $inv['taxable_amount'] : $itemsTotal]];
        if ($isGst && $gstBreakdown) {
            foreach ($gstBreakdown as $g) {
                $totals[] = ['label' => 'CGST @ ' . halfGstRate($g['rate']) . '%', 'value' => $g['tax'] / 2];
                $totals[] = ['label' => 'SGST @ ' . halfGstRate($g['rate']) . '%', 'value' => $g['tax'] / 2];
            }
        } elseif ($isGst) {
            $effTaxable = (float) ($inv['taxable_amount'] ?? 0);
            $effRate = $effTaxable > 0 ? round((float) $inv['gst_amount'] / $effTaxable * 100, 2) : 0;
            $totals[] = ['label' => 'CGST @ ' . halfGstRate($effRate) . '%', 'value' => (float) ($inv['cgst_amount'] ?? $inv['gst_amount'] / 2)];
            $totals[] = ['label' => 'SGST @ ' . halfGstRate($effRate) . '%', 'value' => (float) ($inv['sgst_amount'] ?? $inv['gst_amount'] / 2)];
        }
        if ((float) $inv['labour_charges'] > 0) $totals[] = ['label' => 'Labour', 'value' => (float) $inv['labour_charges']];
        if ((float) $inv['discount'] > 0) {
            $pct = $inv['discount_percent'] !== null ? ' @ ' . rtrim(rtrim(number_format((float) $inv['discount_percent'], 2), '0'), '.') . '%' : '';
            $totals[] = ['label' => 'Discount' . $pct, 'value' => -(float) $inv['discount']];
        }
        return [
            'shop' => ['name' => (string) $org['name'], 'address' => (string) ($org['address'] ?? ''), 'phone' => (string) ($org['phone'] ?? ''), 'gstin' => (string) ($org['gstin'] ?? ''), 'upi' => $upi, 'footer' => 'Thank you for your business!'],
            'title' => $docTitle, 'no' => (string) $inv['invoice_no'], 'date' => formatDate($inv['invoice_date']),
            'customer' => (string) ($inv['customer_name'] ?: 'Walk-in Customer'), 'mobile' => (string) ($inv['mobile'] ?? ''), 'custGstin' => '',
            'cancelled' => ($inv['status'] ?? '') === 'CANCELLED', 'showGst' => $isGst, 'lines' => $lines, 'totals' => $totals,
            'grand' => (float) $inv['grand_total'], 'words' => amountInWordsINR((float) $inv['grand_total']),
            'paidLabel' => 'Paid' . ($inv['payment_method'] ? ' (' . $inv['payment_method'] . ')' : ''), 'paid' => (float) $inv['paid_amount'],
            'balanceLabel' => 'Balance Due', 'balance' => (float) $inv['balance_amount'],
        ];
    }
}

