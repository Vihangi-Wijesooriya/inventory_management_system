<?php
/**
 * /api/users — admin only (enforced in router).
 * Self-lockout protection: an admin cannot delete themselves, demote
 * themselves, or remove the last remaining admin.
 */

function handle(string $method, ?int $id, ?string $action): void
{
    switch ($method) {
        case 'GET':    $id === null ? user_list() : json_out(find_or_404('users', $id, 'id, name, username, role, created_at'));
        case 'POST':   user_save(null);
        case 'PUT':    user_save($id ?? json_error('ID required.', 400));
        case 'DELETE': user_delete($id ?? json_error('ID required.', 400));
        default:       json_error('Method not allowed.', 405);
    }
}

function user_list(): void
{
    [$page, $perPage, $q, $offset] = list_params();
    $where  = '';
    $params = [];
    if ($q !== '') {
        $where  = 'WHERE name LIKE ? OR username LIKE ?';
        $params = ["%{$q}%", "%{$q}%"];
    }
    $stmt = db()->prepare("SELECT COUNT(*) c FROM users {$where}");
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['c'];

    $stmt = db()->prepare("
        SELECT id, name, username, role, created_at
        FROM users {$where}
        ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    json_out(paginated($stmt->fetchAll(), $total, $page, $perPage));
}

function user_save(?int $id): void
{
    $data = read_json();
    $isCreate = $id === null;

    require_fields($data, $isCreate ? ['name', 'username', 'password', 'role'] : ['name', 'username', 'role']);

    if (!in_array($data['role'], ['admin', 'staff'], true)) {
        json_error("Role must be 'admin' or 'staff'.", 422);
    }
    if (!$isCreate) {
        find_or_404('users', $id);
        if ($id === current_user()['id'] && $data['role'] !== 'admin') {
            json_error('You cannot remove your own admin role.', 409);
        }
    }
    if (!empty($data['password']) && strlen($data['password']) < 6) {
        json_error('Password must be at least 6 characters.', 422);
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
    $stmt->execute([trim($data['username']), $id ?? 0]);
    if ($stmt->fetch()) {
        json_error('Username already taken.', 409);
    }

    if ($isCreate) {
        db()->prepare('
            INSERT INTO users (name, username, password_hash, role)
            VALUES (?, ?, ?, ?)')
            ->execute([
                trim($data['name']),
                trim($data['username']),
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['role'],
            ]);
        $id = (int)db()->lastInsertId();
    } else {
        $sql    = 'UPDATE users SET name = ?, username = ?, role = ?';
        $params = [trim($data['name']), trim($data['username']), $data['role']];
        if (!empty($data['password'])) {
            $sql     .= ', password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $sql     .= ' WHERE id = ?';
        $params[] = $id;
        db()->prepare($sql)->execute($params);
    }
    json_out(find_or_404('users', $id, 'id, name, username, role, created_at'), $isCreate ? 201 : 200);
}

function user_delete(int $id): void
{
    find_or_404('users', $id);

    if ($id === current_user()['id']) {
        json_error('You cannot delete your own account.', 409);
    }

    $target = find_or_404('users', $id, 'id, role');
    if ($target['role'] === 'admin') {
        $admins = (int)db()->query("SELECT COUNT(*) c FROM users WHERE role = 'admin'")->fetch()['c'];
        if ($admins <= 1) {
            json_error('Cannot delete the last admin account.', 409);
        }
    }

    try {
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            json_error('Cannot delete: user has recorded sales/purchases.', 409);
        }
        throw $e;
    }
    json_out(['deleted' => true]);
}
