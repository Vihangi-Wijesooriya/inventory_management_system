<?php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Inventory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  body { min-height: 100vh; display: grid; place-items: center; background: #1b2430; }
  .login-card { width: 100%; max-width: 380px; border-radius: 1rem; }
</style>
</head>
<body>
<div class="card login-card shadow-lg">
  <div class="card-body p-4">
    <div class="text-center mb-4">
      <i class="bi bi-boxes fs-1 text-primary"></i>
      <h1 class="h5 mt-2 mb-0">Inventory Management</h1>
      <div class="text-secondary small">Sign in to continue</div>
    </div>
    <div id="loginError" class="alert alert-danger py-2 small d-none"></div>
    <div class="mb-3">
      <label class="form-label small">Username</label>
      <input id="username" class="form-control" autocomplete="username" autofocus>
    </div>
    <div class="mb-4">
      <label class="form-label small">Password</label>
      <input id="password" type="password" class="form-control" autocomplete="current-password">
    </div>
    <button id="loginBtn" class="btn btn-primary w-100">
      <span class="spinner-border spinner-border-sm d-none" id="loginSpinner"></span>
      Sign in
    </button>
  </div>
</div>
<script>
const btn = document.getElementById('loginBtn');
const err = document.getElementById('loginError');
const spinner = document.getElementById('loginSpinner');

async function login() {
  err.classList.add('d-none');
  btn.disabled = true;
  spinner.classList.remove('d-none');
  try {
    const res = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' },
      credentials: 'same-origin',
      body: JSON.stringify({
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
      }),
    });
    const data = await res.json().catch(() => null);
    if (!res.ok) throw new Error(data?.error || 'Login failed.');
    window.location.href = 'dashboard.php';
  } catch (e) {
    err.textContent = e.message;
    err.classList.remove('d-none');
    btn.disabled = false;
    spinner.classList.add('d-none');
  }
}
btn.addEventListener('click', login);
document.addEventListener('keydown', (e) => { if (e.key === 'Enter') login(); });
</script>
</body>
</html>
