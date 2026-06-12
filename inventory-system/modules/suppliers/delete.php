<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('modules/suppliers/index.php');
csrf_verify();
$id = (int)($_POST['id'] ?? 0);

try {
    $stmt = db()->prepare('DELETE FROM suppliers WHERE supplier_id = :id');
    $stmt->execute(['id' => $id]);
    flash('success', 'Supplier deleted.');
} catch (PDOException $e) {
    flash('danger', $e->getCode() === '23000'
        ? 'Cannot delete: supplier has purchase records.'
        : 'Could not delete: ' . $e->getMessage());
}
redirect('modules/suppliers/index.php');
