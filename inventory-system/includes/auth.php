<?php
/**
 * Authentication & authorization helpers.
 */

/** Attempt to log in a user by username + password. Returns true on success. */
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare(
        'SELECT user_id, username, password_hash, full_name, role, is_active
         FROM users WHERE username = :u LIMIT 1'
    );
    $stmt->execute(['u' => $username]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_active']) {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Re-hash if PHP recommends it (e.g. cost increased).
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = db()->prepare('UPDATE users SET password_hash = :h WHERE user_id = :id');
        $upd->execute(['h' => $newHash, 'id' => $user['user_id']]);
    }

    // Record last login
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE user_id = :id')
        ->execute(['id' => $user['user_id']]);

    // Fresh session ID after privilege change
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'        => (int)$user['user_id'],
        'username'  => $user['username'],
        'full_name' => $user['full_name'],
        'role'      => $user['role'],
    ];

    return true;
}

/** Log the current user out. */
function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** True if a user is logged in. */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

/** Current user array (or null). */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Current user's role. */
function current_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

/** Convenience role check. */
function is_admin(): bool
{
    return current_role() === 'admin';
}

/** Guard: require any logged-in user. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        redirect('modules/auth/login.php');
    }
}

/** Guard: require admin role. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Forbidden — admin access required.');
    }
}
