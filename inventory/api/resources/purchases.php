<?php
/**
 * /api/purchases
 * GET  /api/purchases?from=&to=&q=&page=
 * GET  /api/purchases/{id}        (includes items)
 * POST /api/purchases             { supplier_id, items: [{product_id, quantity, unit_cost}] }
 * DELETE /api/purchases/{id}      (reverts stock)
 */

function handle(string $method, ?int $id, ?string $action): void
{
    switch ($method) {
        case 'GET':    $id === null ? purchase_list() : purchase_show($id);
        case 'POST':   purchase_create();
        case 'DELETE': purchase_delete($id ?? json_error('ID required.', 400));
        default:       json_error('Method not allowed.', 405);
    }
}

function purchase_list(): void
{
    [$page, $perPage, $q, $offset] = list_params();
    $where  = [];
    $params = [];

    if ($q !== '') {
        $where[]  = '(pu.invoice_no LIKE ? OR su.name LIKE ?)';
        array_push($params, "%{$q}%", "%{$q}%");
    }
    if (!empty($_GET['from'])) { $where[] = 'DATE(pu.purchase_date) >= ?'; $params[] = $_GET['from']; }
    if (!empty($_GET['to']))   { $where[] = 'DATE(pu.purchase_date) <= ?'; $params[] = $_GET['to']; }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = db()->prepare("SELECT COUNT(*) c FROM purchases pu LEFT JOIN suppliers su ON su.id = pu.supplier_id {$w}");
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['c'];

    $stmt = db()->prepare("
        SELECT pu.*, su.name AS supplier_name, u.name AS user_name,
               (SELECT COUNT(*) FROM purchase_items pi WHERE pi.purchase_id = pu.id) AS item_count
        FROM purchases pu
        LEFT JOIN suppliers su ON su.id = pu.supplier_id
        LEFT JOIN users u ON u.id = pu.user_id
        {$w} ORDER BY pu.id DESC LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    json_out(paginated($stmt->fetchAll(), $total, $page, $perPage));
}

function purchase_show(int $id): void
{
    $stmt = db()->prepare('
        SELECT pu.*, su.name AS supplier_name, u.name AS user_name
        FROM purchases pu
        LEFT JOIN suppliers su ON su.id = pu.supplier_id
        LEFT JOIN users u ON u.id = pu.user_id
        WHERE pu.id = ?');
    $stmt->execute([$id]);
    $purchase = $stmt->fetch() ?: json_error('Not found.', 404);

    $stmt = db()->prepare('
        SELECT pi.*, p.name AS product_name, p.sku
        FROM purchase_items pi
        JOIN products p ON p.id = pi.product_id
        WHERE pi.purchase_id = ?');
    $stmt->execute([$id]);
    $purchase['items'] = $stmt->fetchAll();
    json_out($purchase);
}

function purchase_create(): void
{
    $data = read_json();
    require_fields($data, ['supplier_id']);
    if (empty($data['items']) || !is_array($data['items'])) {
        json_error('At least one item is required.', 422);
    }
    find_or_404('suppliers', (int)$data['supplier_id']);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $total = 0;
        $lines = [];

        foreach ($data['items'] as $item) {
            $pid  = (int)($item['product_id'] ?? 0);
            $qty  = (int)($item['quantity'] ?? 0);
            $cost = round((float)($item['unit_cost'] ?? 0), 2);
            if ($pid <= 0 || $qty <= 0 || $cost < 0) {
                json_error('Each item needs product_id, positive quantity and unit_cost.', 422);
            }
            $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ? FOR UPDATE');
            $stmt->execute([$pid]);
            if (!$stmt->fetch()) {
                json_error("Product #{$pid} not found.", 422);
            }
            $subtotal = round($cost * $qty, 2);
            $total   += $subtotal;
            $lines[]  = [$pid, $qty, $cost, $subtotal];
        }

        $invoice = 'PUR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $pdo->prepare('
            INSERT INTO purchases (supplier_id, user_id, invoice_no, total, purchase_date)
            VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([(int)$data['supplier_id'], current_user()['id'], $invoice, $total]);
        $purchaseId = (int)$pdo->lastInsertId();

        $itemStmt  = $pdo->prepare('
            INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, subtotal)
            VALUES (?, ?, ?, ?, ?)');
        $stockStmt = $pdo->prepare('
            UPDATE products SET quantity = quantity + ?, cost_price = ? WHERE id = ?');

        foreach ($lines as [$pid, $qty, $cost, $subtotal]) {
            $itemStmt->execute([$purchaseId, $pid, $qty, $cost, $subtotal]);
            $stockStmt->execute([$qty, $cost, $pid]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    purchase_show($purchaseId);
}

function purchase_delete(int $id): void
{
    find_or_404('purchases', $id);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT product_id, quantity FROM purchase_items WHERE purchase_id = ?');
        $stmt->execute([$id]);
        $revert = $pdo->prepare('UPDATE products SET quantity = GREATEST(quantity - ?, 0) WHERE id = ?');
        foreach ($stmt->fetchAll() as $item) {
            $revert->execute([$item['quantity'], $item['product_id']]);
        }
        $pdo->prepare('DELETE FROM purchase_items WHERE purchase_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM purchases WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    json_out(['deleted' => true, 'stock_reverted' => true]);
}
