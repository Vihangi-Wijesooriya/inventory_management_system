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
    $where[] = '(p.reference_no LIKE :s OR s.name LIKE :s)';
    $params['s'] = '%' . $search . '%';
}
if ($fromDate !== '') { $where[] = 'p.purchase_date >= :from'; $params['from'] = $fromDate; }
if ($toDate   !== '') { $where[] = 'p.purchase_date <= :to';   $params['to']   = $toDate; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM purchases p JOIN suppliers s ON s.supplier_id = p.supplier_id $whereSql";
$countStmt = db()->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT p.*, s.name AS supplier_name, u.full_name AS user_name,
               (SELECT COUNT(*) FROM purchase_items pi WHERE pi.purchase_id = p.purchase_id) AS items_count
        FROM purchases p
        JOIN suppliers s ON s.supplier_id = p.supplier_id
        JOIN users u ON u.user_id = p.user_id
        $whereSql
        ORDER BY p.purchase_date DESC, p.purchase_id DESC
        LIMIT $perPage OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$purchases = $stmt->fetchAll();

$pageTitle = 'Purchases (Stock In)';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small text-muted">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Reference or supplier..." value="<?= e($search) ?>">
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
            <a href="<?= url('modules/purchases/index.php') ?>" class="btn btn-sm btn-light">Reset</a>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small"><?= $total ?> purchase(s)</div>
    <a href="<?= url('modules/purchases/create.php') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Purchase
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Total</th>
                    <th>Recorded by</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$purchases): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No purchases found.</td></tr>
                <?php endif; ?>
                <?php foreach ($purchases as $p): ?>
                    <tr>
                        <td><code><?= e($p['reference_no']) ?></code></td>
                        <td><?= fmt_date($p['purchase_date']) ?></td>
                        <td><?= e($p['supplier_name']) ?></td>
                        <td class="text-center"><?= (int)$p['items_count'] ?></td>
                        <td class="text-end"><strong><?= money($p['total_amount']) ?></strong></td>
                        <td class="small text-muted"><?= e($p['user_name']) ?></td>
                        <td class="text-end">
                            <a href="<?= url('modules/purchases/view.php?id=' . (int)$p['purchase_id']) ?>" class="btn btn-sm btn-outline-secondary">
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
