<?php
/**
 * /api/reports/{sales|purchases|inventory}?from=&to=&format=csv
 * JSON by default; ?format=csv streams a download.
 */

function handle(string $method, ?int $id, ?string $action): void
{
    if ($method !== 'GET') {
        json_error('Method not allowed.', 405);
    }

    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    [$title, $rows, $summary] = match ($action) {
        'sales'     => report_sales($from, $to),
        'purchases' => report_purchases($from, $to),
        'inventory' => report_inventory(),
        default     => json_error('Unknown report. Use sales, purchases or inventory.', 404),
    };

    if (($_GET['format'] ?? '') === 'csv') {
        stream_csv($title, $rows);
    }

    json_out([
        'title'   => $title,
        'from'    => $action === 'inventory' ? null : $from,
        'to'      => $action === 'inventory' ? null : $to,
        'rows'    => $rows,
        'summary' => $summary,
    ]);
}

function report_sales(string $from, string $to): array
{
    $stmt = db()->prepare('
        SELECT s.invoice_no, DATE(s.sale_date) AS date, cu.name AS customer,
               u.name AS sold_by, s.total
        FROM sales s
        LEFT JOIN customers cu ON cu.id = s.customer_id
        LEFT JOIN users u ON u.id = s.user_id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        ORDER BY s.sale_date');
    $stmt->execute([$from, $to]);
    $rows = $stmt->fetchAll();
    $total = array_sum(array_column($rows, 'total'));
    return ['Sales Report', $rows, ['count' => count($rows), 'total' => round($total, 2)]];
}

function report_purchases(string $from, string $to): array
{
    $stmt = db()->prepare('
        SELECT pu.invoice_no, DATE(pu.purchase_date) AS date, su.name AS supplier,
               u.name AS recorded_by, pu.total
        FROM purchases pu
        LEFT JOIN suppliers su ON su.id = pu.supplier_id
        LEFT JOIN users u ON u.id = pu.user_id
        WHERE DATE(pu.purchase_date) BETWEEN ? AND ?
        ORDER BY pu.purchase_date');
    $stmt->execute([$from, $to]);
    $rows = $stmt->fetchAll();
    $total = array_sum(array_column($rows, 'total'));
    return ['Purchases Report', $rows, ['count' => count($rows), 'total' => round($total, 2)]];
}

function report_inventory(): array
{
    $rows = db()->query('
        SELECT p.sku, p.name, c.name AS category, p.quantity, p.reorder_level,
               p.price, ROUND(p.quantity * p.price, 2) AS stock_value
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        ORDER BY p.name')->fetchAll();
    $value = array_sum(array_column($rows, 'stock_value'));
    return ['Inventory Report', $rows, ['count' => count($rows), 'stock_value' => round($value, 2)]];
}

function stream_csv(string $title, array $rows): never
{
    $filename = strtolower(str_replace(' ', '_', $title)) . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    $out = fopen('php://output', 'w');
    if ($rows) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}
