<?php
require __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Dashboard';
$pageScripts = ['pages/dashboard.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-4" id="statCards">
  <?php
  $cards = [
      ['sales_today',  'Sales Today',      'bi-cash-coin',   'text-bg-success', true],
      ['sales_month',  'Sales This Month', 'bi-graph-up',    'text-bg-primary', true],
      ['stock_value',  'Stock Value',      'bi-safe',        'text-bg-warning', true],
      ['products',     'Products',         'bi-box-seam',    'text-bg-info',    false],
  ];
  foreach ($cards as [$key, $label, $icon, $bg, $isMoney]): ?>
  <div class="col-6 col-xl-3">
    <div class="card stat-card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon <?= $bg ?>"><i class="bi <?= $icon ?>"></i></div>
        <div>
          <div class="text-secondary small"><?= $label ?></div>
          <div class="fs-5 fw-semibold" data-stat="<?= $key ?>" data-money="<?= (int)$isMoney ?>">—</div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <h2 class="h6 mb-3">Sales — Last 7 Days</h2>
        <canvas id="salesChart" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h2 class="h6 mb-3">Low Stock <span class="badge text-bg-danger" id="lowCount"></span></h2>
        <ul class="list-group list-group-flush" id="lowStockList">
          <li class="list-group-item text-secondary">Loading...</li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <h2 class="h6 mb-3">Top Products — Last 30 Days</h2>
        <canvas id="topChart" height="160"></canvas>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
