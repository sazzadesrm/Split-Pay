<?php
declare(strict_types=1);

namespace App\Services;

final class CsvExportService
{
    /**
     * Streams rows to the client as a CSV download. $rows is any iterable
     * (array or generator) of associative arrays; $headers defines column
     * order and titles. Formula-injection protection is applied to every
     * string cell. Up to 50,000 rows without a background job.
     */
    public function stream(string $filename, array $headers, iterable $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for spreadsheet compatibility
        fputcsv($out, array_values($headers));

        $count = 0;
        foreach ($rows as $row) {
            $line = [];
            foreach (array_keys($headers) as $key) {
                $value = $row[$key] ?? '';
                $line[] = is_string($value) ? csv_safe($value) : $value;
            }
            fputcsv($out, $line);
            $count++;
            if ($count % 500 === 0) {
                flush();
            }
            if ($count >= 50000) {
                break;
            }
        }
        fclose($out);
        exit;
    }
}
