<?php

namespace App\Http\Controllers\Api\Finance\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared PDF/CSV export for the fee reports required by spec §7/§8. Each report
 * controller method calls one of these once it has its data, keyed off a
 * `format=pdf|csv` query param — the JSON response stays the default so nothing
 * about the existing on-screen reports changes.
 */
trait ExportsReports
{
    protected function exportPdf(string $view, array $data, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        $data['generated_at'] = now()->format('d M Y, H:i');
        return Pdf::loadView($view, $data)->download($filename);
    }

    protected function exportCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
