<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Already logged in? send to dashboard.
if (is_logged_in()) {
    redirect('modules/dashboard/index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (attempt_login($username, $password)) {
        flash('success', 'Welcome back, ' . current_user()['full_name'] . '!');
        redirect('modules/dashboard/index.php');
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="brand">
        <i class="bi bi-box-seam-fill"></i>
        <div class="text-muted small mt-1"><?= e(APP_NAME) ?></div>
    </div>
    <h1>Sign In</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" autofocus required
                   value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right"></i> Sign In
        </button>
    </form>

    <div class="text-muted small text-center mt-4">
        Demo: <code>admin / Admin@123</code>
    </div>
</div>
</body>
</html>
