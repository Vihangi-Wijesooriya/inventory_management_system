<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/_lib.php';
require_login();

$from   = $_GET['from']   ?? date('Y-m-01');
$to     = $_GET['to']     ?? date('Y-m-d');
$format = $_GET['format'] ?? 'html';

$stmt = db()->prepare(
    'SELECT s.reference_no, s.sale_date, s.total_amount,
            COALESCE(c.name, "Walk-in") AS customer_name, u.full_name AS user_name
     FROM sales s
     LEFT JOIN customers c ON c.customer_id = s.customer_id
     JOIN users u ON u.user_id = s.user_id
     WHERE s.sale_date BETWEEN :f AND :t
     ORDER BY s.sale_date, s.sale_id'
);
$stmt->execute(['f'=>$from,'t'=>$to]);
$rows = $stmt->fetchAll();

$total = array_sum(array_column($rows, 'total_amount'));
$count = count($rows);

// --- Export branches ---
if ($format === 'csv') {
    $csvRows = array_map(fn($r) => [$r['reference_no'], $r['sale_date'], $r['customer_name'], $r['user_name'], $r['total_amount']], $rows);
    send_csv("sales_{$from}_{$to}.csv",
        ['Reference','Date','Customer','Recorded By','Total'],
        $csvRows);
}

if ($format === 'pdf' || $format === 'print') {
    $body = '<table><thead><tr><th>Reference</th><th>Date</th><th>Customer</th><th>Recorded By</th><th class="text-right">Total</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $body .= '<tr>';
        $body .= '<td>' . e($r['reference_no']) . '</td>';
        $body .= '<td>' . fmt_date($r['sale_date']) . '</td>';
        $body .= '<td>' . e($r['customer_name']) . '</td>';
        $body .= '<td>' . e($r['user_name']) . '</td>';
        $body .= '<td class="text-right">' . money($r['total_amount']) . '</td>';
        $body .= '</tr>';
    }
    $body .= '<tr class="totals"><td colspan="4" class="text-right">Total (' . $count . ' transactions)</td><td class="text-right">' . money($total) . '</td></tr>';
    $body .= '</tbody></table>';

    $html = report_html("Sales Report ($from to $to)", $body);

    if ($format === 'pdf') send_pdf("sales_{$from}_{$to}.pdf", $html);
    // print = display HTML inline
    echo $html; exit;
}

$pageTitle = 'Sales Report';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
        </div>
        <div class="col-md-6 d-flex gap-2">
            <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Generate</button>
            <a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&format=pdf" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-pdf"></i> PDF
            </a>
            <a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&format=csv" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-spreadsheet"></i> CSV
            </a>
            <a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&format=print" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer"></i> Print
            </a>
        </div>
    </form>
</div>

<div class="card p-3">
    <h6>Sales Report — <?= fmt_date($from) ?> to <?= fmt_date($to) ?></h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Reference</th><th>Date</th><th>Customer</th><th>Recorded By</th><th class="text-end">Total</th></tr></thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No sales in this range.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><code><?= e($r['reference_no']) ?></code></td>
                        <td><?= fmt_date($r['sale_date']) ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td class="small text-muted"><?= e($r['user_name']) ?></td>
                        <td class="text-end"><?= money($r['total_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total (<?= $count ?> transactions)</th>
                    <th class="text-end"><?= money($total) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
