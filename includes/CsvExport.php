<?php
/** Streams a CSV that opens cleanly in Excel (UTF-8 BOM so names and ₹ survive) and neutralises
 * spreadsheet-formula injection: a customer called "=HYPERLINK(...)" must stay plain text. */
class CsvExport {
    public static function send(string $filename, array $header, array $rows): void {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename) . '"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'cell'], $row));
        }
        fclose($out);
    }

    private static function cell($v) {
        if (!is_string($v)) return $v;
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    }

    /** A YYYY-MM-DD from a query string, or null when absent/invalid. */
    public static function dateParam(?string $v): ?string {
        $v = trim((string) $v);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false ? $v : null;
    }
}
