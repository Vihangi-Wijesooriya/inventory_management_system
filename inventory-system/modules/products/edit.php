<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/_helpers.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) {
    flash('danger', 'Product not specified.');
    redirect('modules/products/index.php');
}

$stmt = db()->prepare('SELECT * FROM products WHERE product_id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();
if (!$product) {
    flash('danger', 'Product not found.');
    redirect('modules/products/index.php');
}

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
    $removeImage   = isset($_POST['remove_image']);

    if ($sku === '')      $errors[] = 'SKU is required.';
    if ($name === '')     $errors[] = 'Name is required.';
    if ($unitPrice < 0)   $errors[] = 'Unit price cannot be negative.';
    if ($quantity < 0)    $errors[] = 'Quantity cannot be negative.';

    $newImage = null;
    if (!$errors && !empty($_FILES['image']['name'])) {
        try { $newImage = save_product_image($_FILES['image']); }
        catch (RuntimeException $e) { $errors[] = $e->getMessage(); }
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $imagePath = $product['image_path'];
            if ($newImage) {
                delete_product_image($product['image_path']);
                $imagePath = $newImage;
            } elseif ($removeImage) {
                delete_product_image($product['image_path']);
                $imagePath = null;
            }

            $upd = $pdo->prepare(
                'UPDATE products SET sku=:sku, name=:name, description=:desc, category_id=:cid,
                                     unit_price=:up, cost_price=:cp, quantity=:q, reorder_level=:rl,
                                     expiry_date=:exp, image_path=:img, is_active=:act
                 WHERE product_id=:id'
            );
            $upd->execute([
                'sku' => $sku, 'name' => $name, 'desc' => $description ?: null,
                'cid' => $categoryId, 'up' => $unitPrice, 'cp' => $costPrice,
                'q' => $quantity, 'rl' => $reorderLevel,
                'exp' => $expiryDate, 'img' => $imagePath, 'act' => $isActive,
                'id'  => $id,
            ]);

            // Log a manual adjustment if quantity changed
            $diff = $quantity - (int)$product['quantity'];
            if ($diff !== 0) {
                $sm = $pdo->prepare(
                    'INSERT INTO stock_movements
                        (product_id, movement_type, quantity_change, resulting_qty, user_id, note)
                     VALUES (:p, "adjustment", :diff, :rq, :u, "Manual adjustment via product edit")'
                );
                $sm->execute(['p' => $id, 'diff' => $diff, 'rq' => $quantity, 'u' => current_user()['id']]);
            }

            $pdo->commit();
            flash('success', 'Product updated.');
            redirect('modules/products/index.php');
        } catch (PDOException $e) {
            if (db()->inTransaction()) db()->rollBack();
            delete_product_image($newImage);
            $errors[] = $e->getCode() === '23000'
                ? 'Another product with that SKU already exists.'
                : 'Database error: ' . $e->getMessage();
        }
    }
    $product = array_merge($product, $_POST);
}

$categories = db()->query('SELECT category_id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();

$pageTitle = 'Edit Product';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4" style="max-width: 800px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">SKU *</label>
                <input type="text" name="sku" class="form-control" required value="<?= e($product['sku']) ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= e($product['name']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="0">— None —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['category_id'] ?>" <?= $product['category_id'] == $c['category_id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Expiry Date</label>
                <input type="date" name="expiry_date" class="form-control" value="<?= e($product['expiry_date']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cost Price</label>
                <div class="input-group">
                    <span class="input-group-text"><?= e(CURRENCY_SYMBOL) ?></span>
                    <input type="number" step="0.01" min="0" name="cost_price" class="form-control" value="<?= e($product['cost_price']) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Selling Price *</label>
                <div class="input-group">
                    <span class="input-group-text"><?= e(CURRENCY_SYMBOL) ?></span>
                    <input type="number" step="0.01" min="0" name="unit_price" class="form-control" required value="<?= e($product['unit_price']) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantity</label>
                <input type="number" min="0" name="quantity" class="form-control" value="<?= e($product['quantity']) ?>">
                <div class="form-text small">Changes logged as adjustment</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Reorder Level</label>
                <input type="number" min="0" name="reorder_level" class="form-control" value="<?= e($product['reorder_level']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($product['description']) ?></textarea>
            </div>
            <div class="col-md-8">
                <label class="form-label">Replace Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <?php if ($product['image_path']): ?>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <img src="<?= e(UPLOAD_URL . '/' . $product['image_path']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:6px;">
                        <div class="form-check">
                            <input type="checkbox" name="remove_image" id="rm_img" class="form-check-input">
                            <label for="rm_img" class="form-check-label small">Remove current image</label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="active" <?= $product['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">Active</label>
                </div>
            </div>
        </div>
        <hr>
        <div class="d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
            <a href="<?= url('modules/products/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
