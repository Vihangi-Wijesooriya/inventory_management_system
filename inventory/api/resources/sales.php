<?php
/**
 * /api/sales
 * GET  /api/sales?from=&to=&q=&page=
 * GET  /api/sales/{id}            (includes items)
 * POST /api/sales                 { customer_id?, items: [{product_id, quantity}] }
 * DELETE /api/sales/{id}          (restores stock)
 * Prices are taken from the products table server-side, never from the client.
 */

function handle(string $method, ?int $id, ?string $action): void
{
    switch ($method) {
        case 'GET':    $id === null ? sale_list() : sale_show($id);
        case 'POST':   sale_create();
        case 'DELETE': sale_delete($id ?? json_error('ID required.', 400));
        default:       json_error('Method not allowed.', 405);
    }
}

function sale_list(): void
{
    [$page, $perPage, $q, $offset] = list_params();
    $where  = [];
    $params = [];

    if ($q !== '') {
        $where[]  = '(s.invoice_no LIKE ? OR cu.name LIKE ?)';
        array_push($params, "%{$q}%", "%{$q}%");
    }
    if (!empty($_GET['from'])) { $where[] = 'DATE(s.sale_date) >= ?'; $params[] = $_GET['from']; }
    if (!empty($_GET['to']))   { $where[] = 'DATE(s.sale_date) <= ?'; $params[] = $_GET['to']; }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = db()->prepare("SELECT COUNT(*) c FROM sales s LEFT JOIN customers cu ON cu.id = s.customer_id {$w}");
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['c'];

    $stmt = db()->prepare("
        SELECT s.*, cu.name AS customer_name, u.name AS user_name,
               (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS item_count
        FROM sales s
        LEFT JOIN customers cu ON cu.id = s.customer_id
        LEFT JOIN users u ON u.id = s.user_id
        {$w} ORDER BY s.id DESC LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    json_out(paginated($stmt->fetchAll(), $total, $page, $perPage));
}

function sale_show(int $id): void
{
    $stmt = db()->prepare('
        SELECT s.*, cu.name AS customer_name, u.name AS user_name
        FROM sales s
        LEFT JOIN customers cu ON cu.id = s.customer_id
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.id = ?');
    $stmt->execute([$id]);
    $sale = $stmt->fetch() ?: json_error('Not found.', 404);

    $stmt = db()->prepare('
        SELECT si.*, p.name AS product_name, p.sku
        FROM sale_items si
        JOIN products p ON p.id = si.product_id
        WHERE si.sale_id = ?');
    $stmt->execute([$id]);
    $sale['items'] = $stmt->fetchAll();
    json_out($sale);
}

function sale_create(): void
{
    $data = read_json();
    if (empty($data['items']) || !is_array($data['items'])) {
        json_error('At least one item is required.', 422);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $total = 0;
        $lines = [];

        foreach ($data['items'] as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                json_error('Each item needs a product_id and a positive quantity.', 422);
            }
            // Lock the row so concurrent sales can't oversell.
            $stmt = $pdo->prepare('SELECT id, name, price, quantity FROM products WHERE id = ? FOR UPDATE');
            $stmt->execute([$pid]);
            $product = $stmt->fetch();
            if (!$product) {
                json_error("Product #{$pid} not found.", 422);
            }
            if ($product['quantity'] < $qty) {
                json_error("Insufficient stock for '{$product['name']}' (available: {$product['quantity']}).", 409);
            }
            $subtotal = round($product['price'] * $qty, 2);
            $total   += $subtotal;
            $lines[]  = [$pid, $qty, $product['price'], $subtotal];
        }

        $invoice = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $pdo->prepare('
            INSERT INTO sales (customer_id, user_id, invoice_no, total, sale_date)
            VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([
            !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
            current_user()['id'],
            $invoice,
            $total,
        ]);
        $saleId = (int)$pdo->lastInsertId();

        $itemStmt  = $pdo->prepare('
            INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?)');
        $stockStmt = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');

        foreach ($lines as [$pid, $qty, $price, $subtotal]) {
            $itemStmt->execute([$saleId, $pid, $qty, $price, $subtotal]);
            $stockStmt->execute([$qty, $pid]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    sale_show($saleId);
}

function sale_delete(int $id): void
{
    find_or_404('sales', $id);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT product_id, quantity FROM sale_items WHERE sale_id = ?');
        $stmt->execute([$id]);
        $restore = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
        foreach ($stmt->fetchAll() as $item) {
            $restore->execute([$item['quantity'], $item['product_id']]);
        }
        $pdo->prepare('DELETE FROM sale_items WHERE sale_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM sales WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    json_out(['deleted' => true, 'stock_restored' => true]);
}
