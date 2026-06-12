<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) {
    flash('danger', 'Category not specified.');
    redirect('modules/categories/index.php');
}

$stmt = db()->prepare('SELECT * FROM categories WHERE category_id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();
if (!$category) {
    flash('danger', 'Category not found.');
    redirect('modules/categories/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive    = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '')                  $errors[] = 'Name is required.';
    if (mb_strlen($name) > 80)         $errors[] = 'Name must be 80 characters or less.';

    if (!$errors) {
        try {
            $upd = db()->prepare('UPDATE categories SET name=:n, description=:d, is_active=:a WHERE category_id=:id');
            $upd->execute(['n' => $name, 'd' => $description ?: null, 'a' => $isActive, 'id' => $id]);
            flash('success', 'Category updated.');
            redirect('modules/categories/index.php');
        } catch (PDOException $e) {
            $errors[] = $e->getCode() === '23000'
                ? 'Another category with that name already exists.'
                : 'Database error: ' . $e->getMessage();
        }
    }
    $category = array_merge($category, $_POST);
}

$pageTitle = 'Edit Category';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4" style="max-width: 600px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <div class="mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required maxlength="80" value="<?= e($category['name']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" maxlength="255"><?= e($category['description']) ?></textarea>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="is_active" class="form-check-input" id="active" <?= $category['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="active">Active</label>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
            <a href="<?= url('modules/categories/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
