<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$search     = trim($_GET['q'] ?? '');
$catFilter  = (int)($_GET['category'] ?? 0);
$stockFilter = $_GET['stock'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 15;
$offset     = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($search !== '') {
    $where[] = '(p.name LIKE :s OR p.sku LIKE :s)';
    $params['s'] = '%' . $search . '%';
}
if ($catFilter > 0) {
    $where[] = 'p.category_id = :cid';
    $params['cid'] = $catFilter;
}
if ($stockFilter === 'low') {
    $where[] = 'p.quantity <= p.reorder_level';
} elseif ($stockFilter === 'out') {
    $where[] = 'p.quantity <= 0';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        $whereSql
        ORDER BY p.name ASC
        LIMIT $perPage OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = db()->query('SELECT category_id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();

$pageTitle = 'Products';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small text-muted">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Name or SKU..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted">Category</label>
            <select name="category" class="form-select form-select-sm">
                <option value="0">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['category_id'] ?>" <?= $catFilter == $c['category_id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted">Stock</label>
            <select name="stock" class="form-select form-select-sm">
                <option value="">All stock levels</option>
                <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low stock</option>
                <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Out of stock</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button>
            <a href="<?= url('modules/products/index.php') ?>" class="btn btn-sm btn-light">Reset</a>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small"><?= $total ?> product(s) found</div>
    <a href="<?= url('modules/products/create.php') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Product
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th class="text-end">Price</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$products): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No products found.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $p): ?>
                    <?php
                    $low  = $p['quantity'] > 0 && $p['quantity'] <= $p['reorder_level'];
                    $out  = $p['quantity'] <= 0;
                    ?>
                    <tr>
                        <td><code><?= e($p['sku']) ?></code></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($p['image_path']): ?>
                                    <img src="<?= e(UPLOAD_URL . '/' . $p['image_path']) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;">
                                <?php else: ?>
                                    <div style="width:36px;height:36px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                        <i class="bi bi-box text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong><?= e($p['name']) ?></strong>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted"><?= e($p['category_name'] ?: '—') ?></td>
                        <td class="text-end"><?= money($p['unit_price']) ?></td>
                        <td class="text-center"><?= (int)$p['quantity'] ?></td>
                        <td class="text-center">
                            <?php if ($out): ?>
                                <span class="badge bg-danger">Out of stock</span>
                            <?php elseif ($low): ?>
                                <span class="badge bg-warning text-dark">Low stock</span>
                            <?php else: ?>
                                <span class="badge bg-success">In stock</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('modules/products/edit.php?id=' . (int)$p['product_id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if (is_admin()): ?>
                                <form method="POST" action="<?= url('modules/products/delete.php') ?>" class="d-inline"
                                      onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['product_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
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
        <?php
        $qs = $_GET; unset($qs['page']);
        $base = '?' . http_build_query($qs);
        for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= $base . '&page=' . $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
