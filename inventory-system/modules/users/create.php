<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? 'staff';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($username === '')                                            $errors[] = 'Username is required.';
    if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username))                $errors[] = 'Username may contain only letters, numbers, dot, and underscore.';
    if ($fullName === '')                                            $errors[] = 'Full name is required.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if (!in_array($role, ['admin','staff'], true))                   $errors[] = 'Invalid role.';
    if (strlen($password) < 6)                                       $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                                      $errors[] = 'Password confirmation does not match.';

    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare(
                'INSERT INTO users (username, password_hash, full_name, email, role, is_active)
                 VALUES (:u,:h,:fn,:em,:r,:act)'
            );
            $stmt->execute(['u'=>$username,'h'=>$hash,'fn'=>$fullName,'em'=>$email?:null,'r'=>$role,'act'=>$isActive]);
            flash('success', 'User created.');
            redirect('modules/users/index.php');
        } catch (PDOException $e) {
            $errors[] = $e->getCode() === '23000'
                ? 'A user with that username already exists.'
                : 'Database error: ' . $e->getMessage();
        }
    }
    flash_old($_POST);
}

$pageTitle = 'New User';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4" style="max-width: 720px;">
    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

    <form method="POST" novalidate autocomplete="off">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" required maxlength="50" value="<?= old('username') ?>">
                <div class="form-text small">Letters, numbers, dot, underscore only.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" required value="<?= old('full_name') ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= old('email') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Role *</label>
                <select name="role" class="form-select" required>
                    <option value="staff" <?= old('role') === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm Password *</label>
                <input type="password" name="password_confirm" class="form-control" required minlength="6">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="active" checked>
                    <label for="active" class="form-check-label">Active</label>
                </div>
            </div>
        </div>
        <hr>
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
        <a href="<?= url('modules/users/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
