<?php
/** GET /api/dashboard — all dashboard data in one call. */

function handle(string $method, ?int $id, ?string $action): void
{
    if ($method !== 'GET') {
        json_error('Method not allowed.', 405);
    }
    $pdo = db();

    $counts = $pdo->query('
        SELECT
            (SELECT COUNT(*) FROM products)   AS products,
            (SELECT COUNT(*) FROM categories) AS categories,
            (SELECT COUNT(*) FROM suppliers)  AS suppliers,
            (SELECT COUNT(*) FROM customers)  AS customers,
            (SELECT COALESCE(SUM(total),0) FROM sales     WHERE DATE(sale_date)     = CURDATE()) AS sales_today,
            (SELECT COALESCE(SUM(total),0) FROM sales     WHERE MONTH(sale_date)    = MONTH(CURDATE()) AND YEAR(sale_date)     = YEAR(CURDATE())) AS sales_month,
            (SELECT COALESCE(SUM(total),0) FROM purchases WHERE MONTH(purchase_date)= MONTH(CURDATE()) AND YEAR(purchase_date) = YEAR(CURDATE())) AS purchases_month,
            (SELECT COALESCE(SUM(quantity * price),0) FROM products) AS stock_value
    ')->fetch();

    $lowStock = $pdo->query('
        SELECT id, name, sku, quantity, reorder_level
        FROM products
        WHERE quantity <= reorder_level
        ORDER BY quantity ASC
        LIMIT 10')->fetchAll();

    // Sales totals for the last 7 days, zero-filled.
    $stmt = $pdo->query("
        SELECT DATE(sale_date) AS d, SUM(total) AS t
        FROM sales
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(sale_date)");
    $byDay = array_column($stmt->fetchAll(), 't', 'd');

    $chart = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $chart[] = ['date' => $d, 'total' => (float)($byDay[$d] ?? 0)];
    }

    $topProducts = $pdo->query('
        SELECT p.name, SUM(si.quantity) AS sold
        FROM sale_items si
        JOIN products p ON p.id = si.product_id
        JOIN sales s ON s.id = si.sale_id
        WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY si.product_id, p.name
        ORDER BY sold DESC
        LIMIT 5')->fetchAll();

    json_out([
        'counts'       => $counts,
        'low_stock'    => $lowStock,
        'sales_chart'  => $chart,
        'top_products' => $topProducts,
    ]);
}
