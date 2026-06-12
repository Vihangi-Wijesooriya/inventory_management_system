<?php
/**
 * Shared helpers for the reports module.
 * Supports three output formats:
 *   - html : default in-page rendering (with print button)
 *   - pdf  : via DomPDF (requires composer install)
 *   - csv  : streamed CSV download
 */

/** Render a report as a CSV download. */
function send_csv(string $filename, array $headers, iterable $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

/**
 * Render a report as a PDF using DomPDF.
 * Falls back to displaying the HTML if DomPDF isn't installed (user hasn't run composer install).
 */
function send_pdf(string $filename, string $html): void
{
    $autoload = BASE_PATH . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        // Friendly fallback
        echo '<div style="padding:30px;font-family:sans-serif">';
        echo '<h3>PDF export requires DomPDF.</h3>';
        echo '<p>From the project folder, run:</p>';
        echo '<pre>composer install</pre>';
        echo '<p>Or open the HTML version and use your browser\'s "Save as PDF" print option.</p>';
        echo '</div>';
        echo $html;
        exit;
    }
    require_once $autoload;
    $dompdf = new Dompdf\Dompdf(['isRemoteEnabled' => true]);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

/** Wrap a report body in a standard printable HTML shell. */
function report_html(string $title, string $body): string
{
    $css = '
    body { font-family: Arial, sans-serif; color: #1f2937; font-size: 12px; }
    h1 { margin: 0 0 4px; font-size: 18px; }
    .meta { color: #6b7280; font-size: 11px; margin-bottom: 18px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    th, td { border: 1px solid #d1d5db; padding: 6px 8px; }
    th { background: #f3f4f6; text-align: left; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .totals { font-weight: bold; }
    ';
    $appName = e(APP_NAME);
    return "<!DOCTYPE html><html><head><meta charset='utf-8'><title>$title</title><style>$css</style></head><body>
        <h1>$appName</h1>
        <div class='meta'>$title — generated " . date('d M Y, H:i') . "</div>
        $body
        </body></html>";
}
