<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { flash('danger','Sale not specified.'); redirect('modules/sales/index.php'); }

$stmt = db()->prepare(
    'SELECT s.*, c.name AS customer_name, c.phone AS customer_phone,
            u.full_name AS user_name
     FROM sales s
     LEFT JOIN customers c ON c.customer_id = s.customer_id
     JOIN users u ON u.user_id = s.user_id
     WHERE s.sale_id = :id'
);
$stmt->execute(['id'=>$id]);
$sale = $stmt->fetch();
if (!$sale) { flash('danger','Sale not found.'); redirect('modules/sales/index.php'); }

$itemStmt = db()->prepare(
    'SELECT si.*, pr.sku, pr.name AS product_name
     FROM sale_items si
     JOIN products pr ON pr.product_id = si.product_id
     WHERE si.sale_id = :id'
);
$itemStmt->execute(['id'=>$id]);
$items = $itemStmt->fetchAll();

$pageTitle = 'Sale ' . $sale['reference_no'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h5 class="mb-0">Sale <code><?= e($sale['reference_no']) ?></code></h5>
            <div class="text-muted small">Recorded by <?= e($sale['user_name']) ?> on <?= fmt_datetime($sale['created_at']) ?></div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Print</button>
            <a href="<?= url('modules/sales/index.php') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <strong>Customer:</strong> <?= e($sale['customer_name'] ?: 'Walk-in') ?><br>
            <?php if ($sale['customer_phone']): ?>
                <span class="text-muted small"><?= e($sale['customer_phone']) ?></span>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-md-end">
            <strong>Date:</strong> <?= fmt_date($sale['sale_date']) ?><br>
            <?php if ($sale['notes']): ?>
                <strong>Notes:</strong> <?= e($sale['notes']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>SKU</th><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                    <tr>
                        <td><code><?= e($i['sku']) ?></code></td>
                        <td><?= e($i['product_name']) ?></td>
                        <td class="text-center"><?= (int)$i['quantity'] ?></td>
                        <td class="text-end"><?= money($i['unit_price']) ?></td>
                        <td class="text-end"><?= money($i['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th class="text-end"><?= money($sale['total_amount']) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
