<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('modules/users/index.php');
csrf_verify();
$id = (int)($_POST['id'] ?? 0);

if ($id === (int)current_user()['id']) {
    flash('danger', 'You cannot delete your own account.');
    redirect('modules/users/index.php');
}

try {
    $stmt = db()->prepare('DELETE FROM users WHERE user_id = :id');
    $stmt->execute(['id' => $id]);
    flash('success', 'User deleted.');
} catch (PDOException $e) {
    $msg = $e->getCode() === '23000'
        ? 'Cannot delete: this user has recorded purchases or sales. Deactivate them instead.'
        : 'Could not delete: ' . $e->getMessage();
    flash('danger', $msg);
}
redirect('modules/users/index.php');
