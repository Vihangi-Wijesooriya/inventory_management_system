<?php
require __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Reports';
$pageScripts = ['pages/reports.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-2 align-items-end mb-4 no-print">
      <div>
        <label class="form-label small">Report</label>
        <select id="reportType" class="form-select">
          <option value="sales">Sales</option>
          <option value="purchases">Purchases</option>
          <option value="inventory">Inventory (current stock)</option>
        </select>
      </div>
      <div id="dateFilters" class="d-flex gap-2">
        <div><label class="form-label small">From</label>
          <input id="fromDate" type="date" class="form-control"></div>
        <div><label class="form-label small">To</label>
          <input id="toDate" type="date" class="form-control"></div>
      </div>
      <button id="runBtn" class="btn btn-primary"><i class="bi bi-play-fill me-1"></i>Run</button>
      <button id="csvBtn" class="btn btn-outline-secondary"><i class="bi bi-filetype-csv me-1"></i>CSV</button>
      <button id="printBtn" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
    <div id="reportArea">
      <div class="text-secondary text-center py-5">Choose a report and click Run.</div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
