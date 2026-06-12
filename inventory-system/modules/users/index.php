<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where = []; $params = [];
if ($search !== '') {
    $where[] = '(username LIKE :s OR full_name LIKE :s OR email LIKE :s)';
    $params['s'] = '%' . $search . '%';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM users $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT user_id, username, full_name, email, role, is_active, last_login_at, created_at
        FROM users $whereSql
        ORDER BY username
        LIMIT $perPage OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Users';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search..." value="<?= e($search) ?>">
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
    <a href="<?= url('modules/users/create.php') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New User
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th class="text-center">Role</th>
                    <th class="text-center">Status</th>
                    <th>Last Login</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$users): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><code><?= e($u['username']) ?></code>
                            <?php if ($u['user_id'] == current_user()['id']): ?>
                                <span class="badge bg-info ms-1">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($u['full_name']) ?></td>
                        <td class="text-muted"><?= e($u['email'] ?: '—') ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $u['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                <?= e(ucfirst($u['role'])) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?= $u['is_active']
                                ? '<span class="badge bg-success">Active</span>'
                                : '<span class="badge bg-secondary">Inactive</span>' ?>
                        </td>
                        <td class="small text-muted">
                            <?= $u['last_login_at'] ? fmt_datetime($u['last_login_at']) : 'Never' ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('modules/users/edit.php?id=' . (int)$u['user_id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($u['user_id'] != current_user()['id']): ?>
                                <form method="POST" action="<?= url('modules/users/delete.php') ?>" class="d-inline"
                                      onsubmit="return confirm('Delete this user?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$u['user_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
