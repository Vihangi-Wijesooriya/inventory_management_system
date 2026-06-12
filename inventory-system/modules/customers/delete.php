<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('modules/customers/index.php');
csrf_verify();
$id = (int)($_POST['id'] ?? 0);

try {
    $stmt = db()->prepare('DELETE FROM customers WHERE customer_id = :id');
    $stmt->execute(['id' => $id]);
    flash('success', 'Customer deleted.');
} catch (PDOException $e) {
    flash('danger', 'Could not delete: ' . $e->getMessage());
}
redirect('modules/customers/index.php');
