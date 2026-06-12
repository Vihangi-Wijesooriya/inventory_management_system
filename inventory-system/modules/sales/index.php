<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$search   = trim($_GET['q'] ?? '');
$fromDate = $_GET['from'] ?? '';
$toDate   = $_GET['to'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 15;
$offset   = ($page - 1) * $perPage;

$where = []; $params = [];
if ($search !== '') {
    $where[] = '(s.reference_no LIKE :s OR c.name LIKE :s)';
    $params['s'] = '%' . $search . '%';
}
if ($fromDate !== '') { $where[] = 's.sale_date >= :from'; $params['from'] = $fromDate; }
if ($toDate   !== '') { $where[] = 's.sale_date <= :to';   $params['to']   = $toDate; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM sales s LEFT JOIN customers c ON c.customer_id = s.customer_id $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT s.*, c.name AS customer_name, u.full_name AS user_name,
               (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.sale_id) AS items_count
        FROM sales s
        LEFT JOIN customers c ON c.customer_id = s.customer_id
        JOIN users u ON u.user_id = s.user_id
        $whereSql
        ORDER BY s.sale_date DESC, s.sale_id DESC
        LIMIT $perPage OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$pageTitle = 'Sales (Stock Out)';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small text-muted">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Reference or customer..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?= e($fromDate) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?= e($toDate) ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            <a href="<?= url('modules/sales/index.php') ?>" class="btn btn-sm btn-light">Reset</a>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small"><?= $total ?> sale(s)</div>
    <a href="<?= url('modules/sales/create.php') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Sale
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Total</th>
                    <th>Recorded by</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$sales): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No sales found.</td></tr>
                <?php endif; ?>
                <?php foreach ($sales as $s): ?>
                    <tr>
                        <td><code><?= e($s['reference_no']) ?></code></td>
                        <td><?= fmt_date($s['sale_date']) ?></td>
                        <td><?= e($s['customer_name'] ?: 'Walk-in') ?></td>
                        <td class="text-center"><?= (int)$s['items_count'] ?></td>
                        <td class="text-end"><strong><?= money($s['total_amount']) ?></strong></td>
                        <td class="small text-muted"><?= e($s['user_name']) ?></td>
                        <td class="text-end">
                            <a href="<?= url('modules/sales/view.php?id=' . (int)$s['sale_id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php $qs = $_GET; unset($qs['page']); $base = '?' . http_build_query($qs);
        for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= $base . '&page=' . $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
