<?php
require __DIR__ . '/includes/auth_check.php';
if (!$isAdmin) {
    header('Location: dashboard.php');
    exit;
}
$pageTitle = 'Users';
$pageScripts = ['crud.js', 'pages/users.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
      <input id="searchInput" class="form-control" style="max-width:280px" placeholder="Search users...">
      <button id="addBtn" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add User</button>
    </div>
    <div class="table-responsive">
      <table class="table table-hover" id="dataTable">
        <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
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
        <div class="mb-3"><label class="form-label">Name *</label>
          <input name="name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Username *</label>
          <input name="username" class="form-control" required autocomplete="off"></div>
        <div class="mb-3"><label class="form-label">Password <span class="text-secondary" id="pwHint">(leave blank to keep current)</span></label>
          <input name="password" type="password" class="form-control" minlength="6" autocomplete="new-password"></div>
        <div class="mb-3"><label class="form-label">Role *</label>
          <select name="role" class="form-select" required>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </select></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
