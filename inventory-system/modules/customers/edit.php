<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { flash('danger','Customer not specified.'); redirect('modules/customers/index.php'); }

$stmt = db()->prepare('SELECT * FROM customers WHERE customer_id = :id');
$stmt->execute(['id'=>$id]);
$customer = $stmt->fetch();
if (!$customer) { flash('danger','Customer not found.'); redirect('modules/customers/index.php'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '')                                                $errors[] = 'Name is required.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';

    if (!$errors) {
        $upd = db()->prepare(
            'UPDATE customers SET name=:n, phone=:p, email=:e, address=:a, is_active=:act WHERE customer_id=:id'
        );
        $upd->execute(['n'=>$name,'p'=>$phone?:null,'e'=>$email?:null,'a'=>$address?:null,'act'=>$isActive,'id'=>$id]);
        flash('success','Customer updated.');
        redirect('modules/customers/index.php');
    }
    $customer = array_merge($customer, $_POST);
}

$pageTitle = 'Edit Customer';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4" style="max-width: 720px;">
    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
    <form method="POST" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= e($customer['name']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= e($customer['phone']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($customer['email']) ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="active" <?= $customer['is_active'] ? 'checked' : '' ?>>
                    <label for="active" class="form-check-label">Active</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2"><?= e($customer['address']) ?></textarea>
            </div>
        </div>
        <hr>
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
        <a href="<?= url('modules/customers/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
