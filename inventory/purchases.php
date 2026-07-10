<?php
require __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Purchases';
$pageScripts = ['pages/txn.js', 'pages/purchases.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
      <div class="d-flex flex-wrap gap-2">
        <input id="searchInput" class="form-control" style="max-width:220px" placeholder="Invoice or supplier...">
        <input id="fromDate" type="date" class="form-control" style="max-width:170px">
        <input id="toDate" type="date" class="form-control" style="max-width:170px">
      </div>
      <button id="addBtn" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Purchase</button>
    </div>
    <div class="table-responsive">
      <table class="table table-hover" id="dataTable">
        <thead><tr>
          <th>Invoice</th><th>Date</th><th>Supplier</th><th>Recorded by</th>
          <th class="text-center">Items</th><th class="text-end">Total</th><th class="text-end">Actions</th>
        </tr></thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end" id="pagination"></div>
  </div>
</div>

<div class="modal fade" id="saleModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Purchase</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Supplier *</label>
            <select id="saleCustomer" class="form-select"><option value="">Select supplier...</option></select>
          </div>
        </div>
        <div class="d-flex gap-2 mb-2">
          <select id="saleProduct" class="form-select"></select>
          <input id="saleQty" type="number" min="1" value="1" class="form-control" style="max-width:100px" placeholder="Qty">
          <input id="unitCost" type="number" min="0" step="0.01" class="form-control" style="max-width:130px" placeholder="Unit cost">
          <button id="addItemBtn" class="btn btn-outline-primary text-nowrap"><i class="bi bi-plus-lg"></i> Add</button>
        </div>
        <table class="table table-sm" id="itemsTable">
          <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Unit cost</th><th class="text-end">Subtotal</th><th></th></tr></thead>
          <tbody></tbody>
          <tfoot><tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end" id="saleTotal">Rs. 0.00</td><td></td></tr></tfoot>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="saveSaleBtn" class="btn btn-primary">Save Purchase</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Purchase Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailBody"></div>
      <div class="modal-footer no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
