<?php
/**
 * Shared page header. Include AFTER bootstrap.php and a require_login() guard.
 * Pages should set $pageTitle before including this file.
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-box-seam-fill"></i>
            <span>Sell Smart</span>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= url('modules/dashboard/index.php') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="<?= url('modules/products/index.php') ?>"><i class="bi bi-box"></i> Products</a>
            <a href="<?= url('modules/categories/index.php') ?>"><i class="bi bi-tags"></i> Categories</a>
            <a href="<?= url('modules/purchases/index.php') ?>"><i class="bi bi-truck"></i> Purchases</a>
            <a href="<?= url('modules/sales/index.php') ?>"><i class="bi bi-cart-check"></i> Sales</a>
            <a href="<?= url('modules/suppliers/index.php') ?>"><i class="bi bi-building"></i> Suppliers</a>
            <a href="<?= url('modules/customers/index.php') ?>"><i class="bi bi-people"></i> Customers</a>
            <a href="<?= url('modules/reports/index.php') ?>"><i class="bi bi-graph-up"></i> Reports</a>
            <?php if (is_admin()): ?>
                <a href="<?= url('modules/users/index.php') ?>"><i class="bi bi-shield-lock"></i> Users</a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main area -->
    <div class="main">
        <header class="topbar">
            <button class="btn btn-sm btn-light d-md-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
            <div class="user-menu dropdown">
                <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <?= e($user['full_name']) ?>
                    <span class="badge bg-secondary"><?= e($user['role']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= url('modules/auth/logout.php') ?>">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a></li>
                </ul>
            </div>
        </header>

        <main class="content">
            <?php foreach (take_flash() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($f['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
