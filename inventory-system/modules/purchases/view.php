<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { flash('danger','Purchase not specified.'); redirect('modules/purchases/index.php'); }

$stmt = db()->prepare(
    'SELECT p.*, s.name AS supplier_name, s.address AS supplier_address, s.phone AS supplier_phone,
            u.full_name AS user_name
     FROM purchases p
     JOIN suppliers s ON s.supplier_id = p.supplier_id
     JOIN users u ON u.user_id = p.user_id
     WHERE p.purchase_id = :id'
);
$stmt->execute(['id'=>$id]);
$purchase = $stmt->fetch();
if (!$purchase) { flash('danger','Purchase not found.'); redirect('modules/purchases/index.php'); }

$itemStmt = db()->prepare(
    'SELECT pi.*, pr.sku, pr.name AS product_name
     FROM purchase_items pi
     JOIN products pr ON pr.product_id = pi.product_id
     WHERE pi.purchase_id = :id'
);
$itemStmt->execute(['id'=>$id]);
$items = $itemStmt->fetchAll();

$pageTitle = 'Purchase ' . $purchase['reference_no'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h5 class="mb-0">Purchase <code><?= e($purchase['reference_no']) ?></code></h5>
            <div class="text-muted small">Recorded by <?= e($purchase['user_name']) ?> on <?= fmt_datetime($purchase['created_at']) ?></div>
        </div>
        <a href="<?= url('modules/purchases/index.php') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <strong>Supplier:</strong> <?= e($purchase['supplier_name']) ?><br>
            <span class="text-muted small">
                <?= e($purchase['supplier_address'] ?: '') ?><br>
                <?= e($purchase['supplier_phone'] ?: '') ?>
            </span>
        </div>
        <div class="col-md-6 text-md-end">
            <strong>Date:</strong> <?= fmt_date($purchase['purchase_date']) ?><br>
            <?php if ($purchase['notes']): ?>
                <strong>Notes:</strong> <?= e($purchase['notes']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>SKU</th><th>Product</th><th class="text-center">Qty</th><th class="text-end">Unit Cost</th><th class="text-end">Subtotal</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                    <tr>
                        <td><code><?= e($i['sku']) ?></code></td>
                        <td><?= e($i['product_name']) ?></td>
                        <td class="text-center"><?= (int)$i['quantity'] ?></td>
                        <td class="text-end"><?= money($i['unit_cost']) ?></td>
                        <td class="text-end"><?= money($i['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th class="text-end"><?= money($purchase['total_amount']) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
