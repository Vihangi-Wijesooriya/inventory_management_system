<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$pageTitle = 'Reports';
include __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3">
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 h-100">
            <div class="mb-2"><i class="bi bi-cart-check fs-1 text-primary"></i></div>
            <h6>Sales Report</h6>
            <p class="text-muted small">Daily, weekly or monthly sales summary with line item detail.</p>
            <a href="<?= url('modules/reports/sales.php') ?>" class="btn btn-sm btn-primary">Generate</a>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 h-100">
            <div class="mb-2"><i class="bi bi-truck fs-1 text-success"></i></div>
            <h6>Purchase Report</h6>
            <p class="text-muted small">Stock-in transactions filtered by date range and/or supplier.</p>
            <a href="<?= url('modules/reports/purchases.php') ?>" class="btn btn-sm btn-success">Generate</a>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 h-100">
            <div class="mb-2"><i class="bi bi-box-seam fs-1 text-warning"></i></div>
            <h6>Stock Report</h6>
            <p class="text-muted small">Current stock levels, valuation, and low/out-of-stock items.</p>
            <a href="<?= url('modules/reports/stock.php') ?>" class="btn btn-sm btn-warning">Generate</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
