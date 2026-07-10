<?php
/** POST /api/auth/login | POST /api/auth/logout | GET /api/auth/me */

function handle(string $method, ?int $id, ?string $action): void
{
    if ($action === 'login' && $method === 'POST') {
        $data = read_json();
        require_fields($data, ['username', 'password']);

        $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([trim($data['username'])]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            json_error('Invalid username or password.', 401);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int)$user['id'],
            'name'     => $user['name'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];
        json_out(['user' => $_SESSION['user']]);
    }

    if ($action === 'logout' && $method === 'POST') {
        $_SESSION = [];
        session_destroy();
        json_out(['logged_out' => true]);
    }

    if ($action === 'me' && $method === 'GET') {
        json_out(['user' => current_user()]);
    }

    json_error('Unknown auth route.', 404);
}
