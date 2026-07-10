<?php
/**
 * /api/products
 * GET    /api/products?q=&category_id=&low_stock=1&page=
 * GET    /api/products/{id}
 * POST   /api/products            (multipart: fields + optional image)
 * PUT    /api/products/{id}       (multipart POST with _method=PUT)
 * DELETE /api/products/{id}
 */

const PRODUCT_IMG_DIR = __DIR__ . '/../../uploads/products/';
const PRODUCT_IMG_MAX = 2 * 1024 * 1024; // 2 MB

function handle(string $method, ?int $id, ?string $action): void
{
    switch ($method) {
        case 'GET':    $id === null ? product_list() : product_show($id);
        case 'POST':   product_save(null);
        case 'PUT':    product_save($id ?? json_error('ID required.', 400));
        case 'DELETE': product_delete($id ?? json_error('ID required.', 400));
        default:       json_error('Method not allowed.', 405);
    }
}

const PRODUCT_SELECT = '
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id';

function product_list(): void
{
    [$page, $perPage, $q, $offset] = list_params();
    $where  = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
        array_push($params, "%{$q}%", "%{$q}%");
    }
    if (!empty($_GET['category_id'])) {
        $where[]  = 'p.category_id = ?';
        $params[] = (int)$_GET['category_id'];
    }
    if (!empty($_GET['low_stock'])) {
        $where[] = 'p.quantity <= p.reorder_level';
    }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = db()->prepare("SELECT COUNT(*) c FROM products p {$w}");
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['c'];

    $stmt = db()->prepare(PRODUCT_SELECT . " {$w} ORDER BY p.id DESC LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    json_out(paginated($stmt->fetchAll(), $total, $page, $perPage));
}

function product_show(int $id, int $code = 200): void
{
    $stmt = db()->prepare(PRODUCT_SELECT . ' WHERE p.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: json_error('Not found.', 404);
    json_out($row, $code);
}

function product_save(?int $id): void
{
    $data = $_POST; // multipart form
    require_fields($data, ['name', 'sku', 'category_id', 'price', 'quantity']);

    if ($id !== null) {
        $existing = find_or_404('products', $id);
    }

    // Unique SKU check
    $stmt = db()->prepare('SELECT id FROM products WHERE sku = ? AND id != ?');
    $stmt->execute([trim($data['sku']), $id ?? 0]);
    if ($stmt->fetch()) {
        json_error('SKU already exists.', 409);
    }

    $image = $existing['image'] ?? null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $image = store_product_image($_FILES['image'], $image);
    }

    // On update, missing optional fields keep their current values.
    $fields = [
        'category_id'   => (int)$data['category_id'],
        'name'          => trim($data['name']),
        'sku'           => trim($data['sku']),
        'price'         => round((float)$data['price'], 2),
        'cost_price'    => round((float)($data['cost_price'] ?? $existing['cost_price'] ?? 0), 2),
        'quantity'      => max(0, (int)$data['quantity']),
        'reorder_level' => max(0, (int)($data['reorder_level'] ?? $existing['reorder_level'] ?? 5)),
        'image'         => $image,
    ];

    if ($id === null) {
        $cols = implode(',', array_keys($fields));
        $ph   = implode(',', array_fill(0, count($fields), '?'));
        db()->prepare("INSERT INTO products ({$cols}) VALUES ({$ph})")
            ->execute(array_values($fields));
        product_show((int)db()->lastInsertId(), 201);
    } else {
        $sets = implode(',', array_map(fn($k) => "{$k} = ?", array_keys($fields)));
        $vals = array_values($fields);
        $vals[] = $id;
        db()->prepare("UPDATE products SET {$sets} WHERE id = ?")->execute($vals);
        product_show($id);
    }
}

function store_product_image(array $file, ?string $replacing): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_error('Image upload failed.', 400);
    }
    if ($file['size'] > PRODUCT_IMG_MAX) {
        json_error('Image exceeds 2 MB limit.', 413);
    }
    $mime = mime_content_type($file['tmp_name']);
    $ext  = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => json_error('Only JPG, PNG or WebP images allowed.', 415),
    };
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], PRODUCT_IMG_DIR . $name)) {
        json_error('Could not store image.', 500);
    }
    if ($replacing && is_file(PRODUCT_IMG_DIR . $replacing)) {
        @unlink(PRODUCT_IMG_DIR . $replacing);
    }
    return $name;
}

function product_delete(int $id): void
{
    $row = find_or_404('products', $id);
    try {
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            json_error('Cannot delete: product has purchase/sale history.', 409);
        }
        throw $e;
    }
    if ($row['image'] && is_file(PRODUCT_IMG_DIR . $row['image'])) {
        @unlink(PRODUCT_IMG_DIR . $row['image']);
    }
    json_out(['deleted' => true]);
}
