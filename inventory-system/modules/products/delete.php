<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/_helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/products/index.php');
}
csrf_verify();
$id = (int)($_POST['id'] ?? 0);

try {
    $stmt = db()->prepare('SELECT image_path FROM products WHERE product_id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    $del = db()->prepare('DELETE FROM products WHERE product_id = :id');
    $del->execute(['id' => $id]);

    if ($row) delete_product_image($row['image_path']);
    flash('success', 'Product deleted.');
} catch (PDOException $e) {
    $msg = $e->getCode() === '23000'
        ? 'Cannot delete: product is referenced in purchase or sale records.'
        : 'Could not delete: ' . $e->getMessage();
    flash('danger', $msg);
}
redirect('modules/products/index.php');
