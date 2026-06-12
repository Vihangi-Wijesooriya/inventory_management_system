<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { flash('danger', 'Supplier not specified.'); redirect('modules/suppliers/index.php'); }

$stmt = db()->prepare('SELECT * FROM suppliers WHERE supplier_id = :id');
$stmt->execute(['id' => $id]);
$supplier = $stmt->fetch();
if (!$supplier) { flash('danger', 'Supplier not found.'); redirect('modules/suppliers/index.php'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name           = trim($_POST['name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $isActive       = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '')                                                $errors[] = 'Name is required.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';

    if (!$errors) {
        $upd = db()->prepare(
            'UPDATE suppliers SET name=:n, contact_person=:cp, phone=:p, email=:e, address=:a, is_active=:act
             WHERE supplier_id=:id'
        );
        $upd->execute([
            'n' => $name, 'cp' => $contact_person ?: null, 'p' => $phone ?: null,
            'e' => $email ?: null, 'a' => $address ?: null, 'act' => $isActive, 'id' => $id,
        ]);
        flash('success', 'Supplier updated.');
        redirect('modules/suppliers/index.php');
    }
    $supplier = array_merge($supplier, $_POST);
}

$pageTitle = 'Edit Supplier';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4" style="max-width: 720px;">
    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
    <form method="POST" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label">Company Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= e($supplier['name']) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" value="<?= e($supplier['contact_person']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= e($supplier['phone']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($supplier['email']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2"><?= e($supplier['address']) ?></textarea>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="active" <?= $supplier['is_active'] ? 'checked' : '' ?>>
                    <label for="active" class="form-check-label">Active</label>
                </div>
            </div>
        </div>
        <hr>
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
        <a href="<?= url('modules/suppliers/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
