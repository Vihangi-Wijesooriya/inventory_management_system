<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/_lib.php';
require_login();

$filter = $_GET['filter'] ?? 'all';     // all | low | out
$format = $_GET['format'] ?? 'html';

$where = ['p.is_active = 1'];
if ($filter === 'low') $where[] = 'p.quantity <= p.reorder_level';
if ($filter === 'out') $where[] = 'p.quantity <= 0';
$whereSql = 'WHERE ' . implode(' AND ', $where);

$rows = db()->query(
    "SELECT p.sku, p.name, c.name AS category_name, p.quantity, p.reorder_level,
            p.cost_price, p.unit_price, (p.quantity * p.cost_price) AS stock_value
     FROM products p
     LEFT JOIN categories c ON c.category_id = p.category_id
     $whereSql
     ORDER BY p.name"
)->fetchAll();

$totalValue = array_sum(array_column($rows, 'stock_value'));
$count      = count($rows);

if ($format === 'csv') {
    $csvRows = array_map(fn($r) => [$r['sku'],$r['name'],$r['category_name'],$r['quantity'],$r['reorder_level'],$r['cost_price'],$r['unit_price'],$r['stock_value']], $rows);
    send_csv("stock_" . date('Y-m-d') . ".csv",
        ['SKU','Name','Category','Quantity','Reorder Level','Cost Price','Selling Price','Stock Value'],
        $csvRows);
}

if ($format === 'pdf' || $format === 'print') {
    $body = '<table><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th class="text-center">Qty</th><th class="text-center">Reorder</th><th class="text-right">Cost</th><th class="text-right">Price</th><th class="text-right">Value</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $body .= '<tr>';
        $body .= '<td>'.e($r['sku']).'</td><td>'.e($r['name']).'</td><td>'.e($r['category_name'] ?: '-').'</td>';
        $body .= '<td class="text-center">'.(int)$r['quantity'].'</td>';
        $body .= '<td class="text-center">'.(int)$r['reorder_level'].'</td>';
        $body .= '<td class="text-right">'.money($r['cost_price']).'</td>';
        $body .= '<td class="text-right">'.money($r['unit_price']).'</td>';
        $body .= '<td class="text-right">'.money($r['stock_value']).'</td>';
        $body .= '</tr>';
    }
    $body .= '<tr class="totals"><td colspan="7" class="text-right">Total stock value ('.$count.' products)</td><td class="text-right">'.money($totalValue).'</td></tr></tbody></table>';
    $html = report_html('Stock Report (' . ucfirst($filter) . ')', $body);
    if ($format === 'pdf') send_pdf('stock_' . date('Y-m-d') . '.pdf', $html);
    echo $html; exit;
}

$pageTitle = 'Stock Report';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small">Filter</label>
            <select name="filter" class="form-select form-select-sm">
                <option value="all" <?= $filter==='all'?'selected':'' ?>>All active products</option>
                <option value="low" <?= $filter==='low'?'selected':'' ?>>Low stock only</option>
                <option value="out" <?= $filter==='out'?'selected':'' ?>>Out of stock only</option>
            </select>
        </div>
        <div class="col-md-8 d-flex gap-2 flex-wrap">
            <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Apply</button>
            <a href="?filter=<?= e($filter) ?>&format=pdf" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-pdf"></i> PDF</a>
            <a href="?filter=<?= e($filter) ?>&format=csv" class="btn btn-sm btn-outline-success"><i class="bi bi-file-spreadsheet"></i> CSV</a>
            <a href="?filter=<?= e($filter) ?>&format=print" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print</a>
        </div>
    </form>
</div>

<div class="card p-3">
    <h6>Stock Report — <?= e(ucfirst($filter)) ?></h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>SKU</th><th>Name</th><th>Category</th>
                    <th class="text-center">Qty</th><th class="text-center">Reorder</th>
                    <th class="text-end">Cost</th><th class="text-end">Price</th><th class="text-end">Value</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-3">No products match.</td></tr><?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><code><?= e($r['sku']) ?></code></td>
                        <td><?= e($r['name']) ?></td>
                        <td class="text-muted"><?= e($r['category_name'] ?: '—') ?></td>
                        <td class="text-center"><?= (int)$r['quantity'] ?></td>
                        <td class="text-center"><?= (int)$r['reorder_level'] ?></td>
                        <td class="text-end"><?= money($r['cost_price']) ?></td>
                        <td class="text-end"><?= money($r['unit_price']) ?></td>
                        <td class="text-end"><?= money($r['stock_value']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="7" class="text-end">Total stock value (<?= $count ?> products)</th>
                    <th class="text-end"><?= money($totalValue) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
