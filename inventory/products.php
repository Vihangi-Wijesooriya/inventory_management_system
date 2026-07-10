<?php
require __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Products';
$pageScripts = ['pages/products.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
      <div class="d-flex flex-wrap gap-2">
        <input id="searchInput" class="form-control" style="max-width:240px" placeholder="Search name or SKU...">
        <select id="categoryFilter" class="form-select" style="max-width:200px">
          <option value="">All categories</option>
        </select>
        <div class="form-check align-self-center ms-1">
          <input class="form-check-input" type="checkbox" id="lowStockFilter">
          <label class="form-check-label" for="lowStockFilter">Low stock only</label>
        </div>
      </div>
      <button id="addBtn" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Product</button>
    </div>
    <div class="table-responsive">
      <table class="table table-hover" id="dataTable">
        <thead>
          <tr>
            <th></th><th>Name</th><th>SKU</th><th>Category</th>
            <th class="text-end">Price</th><th class="text-center">Stock</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end" id="pagination"></div>
  </div>
</div>

<div class="modal fade" id="crudModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="crudForm" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-8"><label class="form-label">Name *</label>
            <input name="name" class="form-control" required></div>
          <div class="col-4"><label class="form-label">SKU *</label>
            <input name="sku" class="form-control" required></div>
          <div class="col-6"><label class="form-label">Category *</label>
            <select name="category_id" class="form-select" id="categorySelect" required></select></div>
          <div class="col-3"><label class="form-label">Price *</label>
            <input name="price" type="number" step="0.01" min="0" class="form-control" required></div>
          <div class="col-3"><label class="form-label">Cost price</label>
            <input name="cost_price" type="number" step="0.01" min="0" class="form-control"></div>
          <div class="col-6"><label class="form-label">Quantity *</label>
            <input name="quantity" type="number" min="0" class="form-control" required></div>
          <div class="col-6"><label class="form-label">Reorder level</label>
            <input name="reorder_level" type="number" min="0" class="form-control" value="5"></div>
          <div class="col-12"><label class="form-label">Image (JPG/PNG/WebP, max 2 MB)</label>
            <input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
