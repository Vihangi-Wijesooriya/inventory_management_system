<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { flash('danger','User not specified.'); redirect('modules/users/index.php'); }

$stmt = db()->prepare('SELECT * FROM users WHERE user_id = :id');
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();
if (!$user) { flash('danger','User not found.'); redirect('modules/users/index.php'); }

$isSelf = ((int)$user['user_id'] === (int)current_user()['id']);
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

    // Self-protection: prevent locking yourself out
    if ($isSelf) {
        $role = $user['role'];      // can't change own role
        $isActive = 1;              // can't deactivate yourself
    }

    if ($username === '')                                            $errors[] = 'Username is required.';
    if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username))                $errors[] = 'Invalid username characters.';
    if ($fullName === '')                                            $errors[] = 'Full name is required.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if (!in_array($role, ['admin','staff'], true))                   $errors[] = 'Invalid role.';
    if ($password !== '' && strlen($password) < 6)                   $errors[] = 'Password must be at least 6 characters.';
    if ($password !== '' && $password !== $confirm)                  $errors[] = 'Password confirmation does not match.';

    if (!$errors) {
        try {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare(
                    'UPDATE users SET username=:u, password_hash=:h, full_name=:fn, email=:em, role=:r, is_active=:act
                     WHERE user_id=:id'
                );
                $stmt->execute(['u'=>$username,'h'=>$hash,'fn'=>$fullName,'em'=>$email?:null,'r'=>$role,'act'=>$isActive,'id'=>$id]);
            } else {
                $stmt = db()->prepare(
                    'UPDATE users SET username=:u, full_name=:fn, email=:em, role=:r, is_active=:act
                     WHERE user_id=:id'
                );
                $stmt->execute(['u'=>$username,'fn'=>$fullName,'em'=>$email?:null,'r'=>$role,'act'=>$isActive,'id'=>$id]);
            }
            flash('success', 'User updated.');
            redirect('modules/users/index.php');
        } catch (PDOException $e) {
            $errors[] = $e->getCode() === '23000'
                ? 'Another user with that username already exists.'
                : 'Database error: ' . $e->getMessage();
        }
    }
    $user = array_merge($user, $_POST);
}

$pageTitle = 'Edit User';
include __DIR__ . '/../../includes/header.php';
?>

<div class="card p-4" style="max-width: 720px;">
    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

    <?php if ($isSelf): ?>
        <div class="alert alert-info py-2 small">
            <i class="bi bi-info-circle"></i> You are editing your own account. Role and active status are locked to prevent lockout.
        </div>
    <?php endif; ?>

    <form method="POST" novalidate autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" required value="<?= e($user['username']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" required value="<?= e($user['full_name']) ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Role *</label>
                <select name="role" class="form-select" required <?= $isSelf ? 'disabled' : '' ?>>
                    <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <?php if ($isSelf): ?>
                    <input type="hidden" name="role" value="<?= e($user['role']) ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" minlength="6">
                <div class="form-text small">Leave empty to keep current password.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirm" class="form-control" minlength="6">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="active"
                           <?= $user['is_active'] ? 'checked' : '' ?>
                           <?= $isSelf ? 'disabled' : '' ?>>
                    <label for="active" class="form-check-label">Active</label>
                    <?php if ($isSelf): ?>
                        <input type="hidden" name="is_active" value="1">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <hr>
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
        <a href="<?= url('modules/users/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
