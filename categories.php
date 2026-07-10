<?php
require __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Categories';
$pageScripts = ['crud.js', 'pages/categories.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
      <input id="searchInput" class="form-control" style="max-width:280px" placeholder="Search Categories...">
      <button id="addBtn" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Category</button>
    </div>
    <div class="table-responsive">
      <table class="table table-hover" id="dataTable">
        <thead><tr><th>ID</th><th>Name</th><th>Description</th><th class="text-end">Actions</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end" id="pagination"></div>
  </div>
</div>

<div class="modal fade" id="crudModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="crudForm">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Name *</label><input name="name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
