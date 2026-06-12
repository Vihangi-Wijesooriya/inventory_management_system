<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $sku           = trim($_POST['sku'] ?? '');
    $name          = trim($_POST['name'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $categoryId    = (int)($_POST['category_id'] ?? 0) ?: null;
    $unitPrice     = (float)($_POST['unit_price'] ?? 0);
    $costPrice     = (float)($_POST['cost_price'] ?? 0);
    $quantity      = (int)($_POST['quantity'] ?? 0);
    $reorderLevel  = (int)($_POST['reorder_level'] ?? LOW_STOCK_DEFAULT_THRESHOLD);
    $expiryDate    = $_POST['expiry_date'] ?: null;
    $isActive      = isset($_POST['is_active']) ? 1 : 0;

    if ($sku === '')                  $errors[] = 'SKU is required.';
    if ($name === '')                 $errors[] = 'Name is required.';
    if ($unitPrice < 0)               $errors[] = 'Unit price cannot be negative.';
    if ($costPrice < 0)               $errors[] = 'Cost price cannot be negative.';
    if ($quantity < 0)                $errors[] = 'Quantity cannot be negative.';
    if ($reorderLevel < 0)            $errors[] = 'Reorder level cannot be negative.';

    $imageName = null;
    if (!$errors && !empty($_FILES['image']['name'])) {
        try {
            $imageName = save_product_image($_FILES['image']);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO products
                    (sku, name, description, category_id, unit_price, cost_price,
                     quantity, reorder_level, expiry_date, image_path, is_active)
                 VALUES (:sku,:name,:desc,:cid,:up,:cp,:q,:rl,:exp,:img,:act)'
            );
            $stmt->execute([
                'sku' => $sku, 'name' => $name, 'desc' => $description ?: null,
                'cid' => $categoryId, 'up' => $unitPrice, 'cp' => $costPrice,
                'q' => $quantity, 'rl' => $reorderLevel,
                'exp' => $expiryDate, 'img' => $imageName, 'act' => $isActive,
            ]);
            $newId = (int)$pdo->lastInsertId();

            // If initial quantity > 0, log as adjustment
            if ($quantity > 0) {
                $sm = $pdo->prepare(
                    'INSERT INTO stock_movements
                        (product_id, movement_type, quantity_change, resulting_qty, user_id, note)
                     VALUES (:p, "adjustment", :q, :rq, :u, "Initial stock on creation")'
                );
                $sm->execute(['p' => $newId, 'q' => $quantity, 'rq' => $quantity, 'u' => current_user()['id']]);
            }

            $pdo->commit();
            flash('success', 'Product created.');
            redirect('modules/products/index.php');
        } catch (PDOException $e) {
            if (db()->inTransaction()) db()->rollBack();
            delete_product_image($imageName);
            $errors[] = $e->getCode() === '23000'
                ? 'A product with that SKU already exists.'
                : 'Database error: ' . $e->getMessage();
        }
    }
    flash_old($_POST);
}

$categories = db()->query('SELECT category_id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();

$pageTitle = 'New Product';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4" style="max-width: 800px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">SKU *</label>
                <input type="text" name="sku" class="form-control" required maxlength="50" value="<?= old('sku') ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required maxlength="150" value="<?= old('name') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="0">— None —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['category_id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Expiry Date</label>
                <input type="date" name="expiry_date" class="form-control" value="<?= old('expiry_date') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cost Price</label>
                <div class="input-group">
                    <span class="input-group-text"><?= e(CURRENCY_SYMBOL) ?></span>
                    <input type="number" step="0.01" min="0" name="cost_price" class="form-control" value="<?= old('cost_price', '0.00') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Selling Price *</label>
                <div class="input-group">
                    <span class="input-group-text"><?= e(CURRENCY_SYMBOL) ?></span>
                    <input type="number" step="0.01" min="0" name="unit_price" class="form-control" required value="<?= old('unit_price', '0.00') ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Initial Qty</label>
                <input type="number" min="0" name="quantity" class="form-control" value="<?= old('quantity', '0') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Reorder Level</label>
                <input type="number" min="0" name="reorder_level" class="form-control" value="<?= old('reorder_level', LOW_STOCK_DEFAULT_THRESHOLD) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= old('description') ?></textarea>
            </div>
            <div class="col-md-8">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <div class="form-text">JPG, PNG, GIF or WebP. Max 2 MB.</div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="active" checked>
                    <label class="form-check-label" for="active">Active</label>
                </div>
            </div>
        </div>
        <hr>
        <div class="d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="<?= url('modules/products/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
