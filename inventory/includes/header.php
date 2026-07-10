<?php
/** Expects: $pageTitle, $currentUser, $isAdmin (from auth_check.php). */
$nav = [
    'dashboard'  => ['Dashboard',  'bi-speedometer2'],
    'products'   => ['Products',   'bi-box-seam'],
    'categories' => ['Categories', 'bi-tags'],
    'purchases'  => ['Purchases',  'bi-cart-plus'],
    'sales'      => ['Sales',      'bi-cash-coin'],
    'suppliers'  => ['Suppliers',  'bi-truck'],
    'customers'  => ['Customers',  'bi-people'],
    'reports'    => ['Reports',    'bi-file-earmark-bar-graph'],
];
if ($isAdmin) {
    $nav['users'] = ['Users', 'bi-person-gear'];
}
$active = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — Inventory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <aside class="sidebar d-flex flex-column flex-shrink-0 p-3">
    <a href="dashboard.php" class="sidebar-brand d-flex align-items-center mb-3 text-decoration-none">
      <i class="bi bi-boxes fs-4 me-2"></i><span class="fs-5 fw-semibold">Inventory</span>
    </a>
    <ul class="nav nav-pills flex-column mb-auto">
      <?php foreach ($nav as $page => [$label, $icon]): ?>
      <li class="nav-item">
        <a href="<?= $page ?>.php" class="nav-link <?= $active === $page ? 'active' : '' ?>">
          <i class="bi <?= $icon ?> me-2"></i><?= $label ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <hr>
    <div class="d-flex align-items-center justify-content-between">
      <div class="small">
        <div class="fw-semibold"><?= htmlspecialchars($currentUser['name']) ?></div>
        <div class="text-secondary text-capitalize"><?= htmlspecialchars($currentUser['role']) ?></div>
      </div>
      <button id="logoutBtn" class="btn btn-sm btn-outline-light" title="Log out">
        <i class="bi bi-box-arrow-right"></i>
      </button>
    </div>
  </aside>
  <main class="flex-grow-1 p-4">
    <h1 class="h4 mb-4"><?= htmlspecialchars($pageTitle) ?></h1>
