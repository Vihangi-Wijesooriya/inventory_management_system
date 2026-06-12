<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $customerId = (int)($_POST['customer_id'] ?? 0) ?: null;
    $saleDate   = $_POST['sale_date'] ?? date('Y-m-d');
    $notes      = trim($_POST['notes'] ?? '');

    $productIds = $_POST['product_id']  ?? [];
    $quantities = $_POST['quantity']    ?? [];
    $unitPrices = $_POST['unit_price']  ?? [];

    $lines = [];
    for ($i = 0; $i < count($productIds); $i++) {
        $pid = (int)$productIds[$i];
        $qty = (int)$quantities[$i];
        $prc = (float)$unitPrices[$i];
        if ($pid <= 0 || $qty <= 0) continue;
        if ($prc < 0) { $errors[] = "Line " . ($i+1) . ": price cannot be negative."; continue; }
        $lines[] = ['product_id'=>$pid,'quantity'=>$qty,'unit_price'=>$prc,'subtotal'=>$qty*$prc];
    }

    if (!$saleDate) $errors[] = 'Please select a date.';
    if (!$lines)    $errors[] = 'Add at least one product line with quantity > 0.';

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            // Lock product rows and verify stock availability
            $checkQty = $pdo->prepare('SELECT name, quantity FROM products WHERE product_id = :id FOR UPDATE');
            foreach ($lines as &$ln) {
                $checkQty->execute(['id'=>$ln['product_id']]);
                $row = $checkQty->fetch();
                if (!$row) {
                    throw new RuntimeException("Product #{$ln['product_id']} not found.");
                }
                if ((int)$row['quantity'] < $ln['quantity']) {
                    throw new RuntimeException("Insufficient stock for {$row['name']}. Available: {$row['quantity']}, requested: {$ln['quantity']}.");
                }
                $ln['_current_qty'] = (int)$row['quantity'];
            }
            unset($ln);

            $reference = generate_reference('SAL');
            $total = array_sum(array_column($lines, 'subtotal'));

            $stmt = $pdo->prepare(
                'INSERT INTO sales (reference_no, customer_id, user_id, sale_date, total_amount, notes)
                 VALUES (:ref,:cid,:uid,:dt,:tot,:n)'
            );
            $stmt->execute([
                'ref'=>$reference,'cid'=>$customerId,'uid'=>current_user()['id'],
                'dt'=>$saleDate,'tot'=>$total,'n'=>$notes ?: null,
            ]);
            $saleId = (int)$pdo->lastInsertId();

            $insItem = $pdo->prepare(
                'INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal)
                 VALUES (:sid,:prod,:q,:up,:st)'
            );
            $updProduct = $pdo->prepare('UPDATE products SET quantity = quantity - :q WHERE product_id = :id');
            $logMove = $pdo->prepare(
                'INSERT INTO stock_movements
                    (product_id, movement_type, quantity_change, resulting_qty, reference_id, user_id, note)
                 VALUES (:p, "sale", :q, :rq, :ref, :u, :note)'
            );

            foreach ($lines as $ln) {
                $insItem->execute([
                    'sid'=>$saleId,'prod'=>$ln['product_id'],'q'=>$ln['quantity'],
                    'up'=>$ln['unit_price'],'st'=>$ln['subtotal'],
                ]);
                $updProduct->execute(['q'=>$ln['quantity'],'id'=>$ln['product_id']]);
                $newQty = $ln['_current_qty'] - $ln['quantity'];
                $logMove->execute([
                    'p'=>$ln['product_id'],'q'=>-$ln['quantity'],'rq'=>$newQty,
                    'ref'=>$saleId,'u'=>current_user()['id'],'note'=>"Sale $reference",
                ]);
            }

            $pdo->commit();
            flash('success', "Sale $reference recorded. Total: " . money($total));
            redirect('modules/sales/view.php?id=' . $saleId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
    flash_old($_POST);
}

$customers = db()->query('SELECT customer_id, name FROM customers WHERE is_active = 1 ORDER BY name')->fetchAll();
$products  = db()->query('SELECT product_id, sku, name, unit_price, quantity FROM products WHERE is_active = 1 ORDER BY name')->fetchAll();

$pageTitle = 'New Sale';
include __DIR__ . '/../../includes/header.php';
?>

<form method="POST" novalidate id="saleForm">
    <?= csrf_field() ?>
    <div class="card p-4 mb-3">
        <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-select">
                    <option value="">— Walk-in / no customer —</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['customer_id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date *</label>
                <input type="date" name="sale_date" class="form-control" required value="<?= old('sale_date', date('Y-m-d')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-control" value="<?= old('notes') ?>">
            </div>
        </div>
    </div>

    <div class="card p-4">
        <h6 class="mb-3">Line Items</h6>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 45%">Product</th>
                        <th style="width: 15%">Quantity</th>
                        <th style="width: 15%">Unit Price</th>
                        <th style="width: 20%" class="text-end">Subtotal</th>
                        <th style="width: 5%"></th>
                    </tr>
                </thead>
                <tbody id="lineBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Total</td>
                        <td class="text-end fw-bold" id="grandTotal"><?= money(0) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addLine">
            <i class="bi bi-plus-lg"></i> Add Line
        </button>

        <hr>
        <div class="d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Sale</button>
            <a href="<?= url('modules/sales/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>

<script>
const products = <?= json_encode($products) ?>;
const currency = <?= json_encode(CURRENCY_SYMBOL) ?>;
function fmt(n) { return currency + ' ' + Number(n || 0).toFixed(2); }

function buildLine() {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="product_id[]" class="form-select form-select-sm prod">
                <option value="">— Select product —</option>
                ${products.map(p => `<option value="${p.product_id}" data-price="${p.unit_price}" data-stock="${p.quantity}">
                    ${p.sku} — ${p.name} (stock: ${p.quantity})
                </option>`).join('')}
            </select>
        </td>
        <td><input type="number" name="quantity[]" min="1" value="1" class="form-control form-control-sm qty"></td>
        <td><input type="number" name="unit_price[]" step="0.01" min="0" value="0.00" class="form-control form-control-sm price"></td>
        <td class="text-end sub">${fmt(0)}</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger del"><i class="bi bi-x"></i></button></td>
    `;
    document.getElementById('lineBody').appendChild(tr);

    const prodSel = tr.querySelector('.prod');
    const qtyIn   = tr.querySelector('.qty');
    const priceIn = tr.querySelector('.price');

    prodSel.addEventListener('change', (e) => {
        const opt = e.target.selectedOptions[0];
        if (opt && opt.dataset.price) priceIn.value = opt.dataset.price;
        qtyIn.max = opt && opt.dataset.stock ? opt.dataset.stock : '';
        recalc();
    });
    qtyIn.addEventListener('input', () => {
        const opt = prodSel.selectedOptions[0];
        if (opt && opt.dataset.stock && +qtyIn.value > +opt.dataset.stock) {
            qtyIn.classList.add('is-invalid');
        } else {
            qtyIn.classList.remove('is-invalid');
        }
        recalc();
    });
    priceIn.addEventListener('input', recalc);
    tr.querySelector('.del').addEventListener('click', () => { tr.remove(); recalc(); });
}

function recalc() {
    let total = 0;
    document.querySelectorAll('#lineBody tr').forEach(tr => {
        const q = +tr.querySelector('.qty').value || 0;
        const c = +tr.querySelector('.price').value || 0;
        const s = q * c;
        tr.querySelector('.sub').textContent = fmt(s);
        total += s;
    });
    document.getElementById('grandTotal').textContent = fmt(total);
}

document.getElementById('addLine').addEventListener('click', buildLine);
buildLine();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
