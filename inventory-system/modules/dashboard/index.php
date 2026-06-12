<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$pdo = db();

// --- Stats ---
$totalProducts  = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
$lowStockCount  = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND quantity <= reorder_level')->fetchColumn();
$outOfStock     = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND quantity <= 0')->fetchColumn();
$totalSuppliers = (int)$pdo->query('SELECT COUNT(*) FROM suppliers WHERE is_active = 1')->fetchColumn();
$totalCustomers = (int)$pdo->query('SELECT COUNT(*) FROM customers WHERE is_active = 1')->fetchColumn();

$today = date('Y-m-d');
$st = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0), COUNT(*) FROM sales WHERE sale_date = :d');
$st->execute(['d' => $today]);
[$todaySalesAmount, $todaySalesCount] = $st->fetch(PDO::FETCH_NUM);

$monthStart = date('Y-m-01');
$st = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE sale_date >= :d');
$st->execute(['d' => $monthStart]);
$monthSalesAmount = (float)$st->fetchColumn();

// --- Sales over last 30 days ---
$st = $pdo->query(
    "SELECT sale_date, COALESCE(SUM(total_amount),0) AS total
     FROM sales
     WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY sale_date
     ORDER BY sale_date"
);
$salesMap = [];
foreach ($st->fetchAll() as $row) $salesMap[$row['sale_date']] = (float)$row['total'];

$chartLabels = []; $chartData = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M j', strtotime($d));
    $chartData[]   = $salesMap[$d] ?? 0;
}

$st = $pdo->prepare(
    "SELECT p.name, SUM(si.quantity) AS qty_sold, SUM(si.subtotal) AS revenue
     FROM sale_items si
     JOIN sales s ON s.sale_id = si.sale_id
     JOIN products p ON p.product_id = si.product_id
     WHERE s.sale_date >= :d
     GROUP BY p.product_id, p.name
     ORDER BY qty_sold DESC
     LIMIT 5"
);
$st->execute(['d' => $monthStart]);
$topProducts = $st->fetchAll();

$lowItems = $pdo->query(
    'SELECT product_id, sku, name, quantity, reorder_level
     FROM products
     WHERE is_active = 1 AND quantity <= reorder_level
     ORDER BY quantity ASC
     LIMIT 8'
)->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="stat-card primary">
            <i class="bi bi-box stat-icon"></i>
            <div class="stat-label">Active Products</div>
            <div class="stat-value"><?= $totalProducts ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <i class="bi bi-exclamation-triangle stat-icon"></i>
            <div class="stat-label">Low Stock</div>
            <div class="stat-value"><?= $lowStockCount ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <i class="bi bi-cash-coin stat-icon"></i>
            <div class="stat-label">Today's Sales</div>
            <div class="stat-value"><?= money($todaySalesAmount) ?></div>
            <div class="small text-muted"><?= (int)$todaySalesCount ?> transaction(s)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card danger">
            <i class="bi bi-graph-up-arrow stat-icon"></i>
            <div class="stat-label">This Month</div>
            <div class="stat-value"><?= money($monthSalesAmount) ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card p-3">
            <h6 class="mb-3">Sales — Last 30 Days</h6>
            <canvas id="salesChart" height="100"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h6 class="mb-3">
                <i class="bi bi-exclamation-triangle-fill text-warning"></i> Low Stock Alerts
            </h6>
            <?php if (!$lowItems): ?>
                <div class="text-muted small">All products are above their reorder level.</div>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($lowItems as $li): ?>
                        <li class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <strong><?= e($li['name']) ?></strong><br>
                                <span class="text-muted small">SKU <?= e($li['sku']) ?></span>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?= $li['quantity'] <= 0 ? 'danger' : 'warning text-dark' ?>">
                                    <?= (int)$li['quantity'] ?> / <?= (int)$li['reorder_level'] ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= url('modules/products/index.php?stock=low') ?>" class="btn btn-sm btn-outline-warning mt-2">
                    View all
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card p-3">
            <h6 class="mb-3">Top Products This Month</h6>
            <?php if (!$topProducts): ?>
                <div class="text-muted small">No sales yet this month.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Product</th><th class="text-center">Sold</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                        <?php foreach ($topProducts as $tp): ?>
                            <tr>
                                <td><?= e($tp['name']) ?></td>
                                <td class="text-center"><?= (int)$tp['qty_sold'] ?></td>
                                <td class="text-end"><?= money($tp['revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3">
            <h6 class="mb-3">Quick Stats</h6>
            <div class="row text-center">
                <div class="col-4">
                    <div class="display-6"><?= $totalSuppliers ?></div>
                    <div class="text-muted small">Suppliers</div>
                </div>
                <div class="col-4">
                    <div class="display-6"><?= $totalCustomers ?></div>
                    <div class="text-muted small">Customers</div>
                </div>
                <div class="col-4">
                    <div class="display-6 text-danger"><?= $outOfStock ?></div>
                    <div class="text-muted small">Out of stock</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Daily Sales',
            data: <?= json_encode($chartData) ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
        }],
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => '<?= e(CURRENCY_SYMBOL) ?> ' + v.toLocaleString() }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
