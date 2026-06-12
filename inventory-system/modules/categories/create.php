<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive    = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '')                 $errors[] = 'Name is required.';
    if (mb_strlen($name) > 80)        $errors[] = 'Name must be 80 characters or less.';
    if (mb_strlen($description) > 255) $errors[] = 'Description must be 255 characters or less.';

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO categories (name, description, is_active) VALUES (:n, :d, :a)');
            $stmt->execute(['n' => $name, 'd' => $description ?: null, 'a' => $isActive]);
            flash('success', 'Category created.');
            redirect('modules/categories/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'A category with that name already exists.';
            } else {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
    flash_old($_POST);
}

$pageTitle = 'New Category';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4" style="max-width: 600px;">
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required maxlength="80" value="<?= old('name') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" maxlength="255"><?= old('description') ?></textarea>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="is_active" class="form-check-input" id="active" checked>
            <label class="form-check-label" for="active">Active</label>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            <a href="<?= url('modules/categories/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
