<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $supplierId   = (int)($_POST['supplier_id'] ?? 0);
    $purchaseDate = $_POST['purchase_date'] ?? date('Y-m-d');
    $notes        = trim($_POST['notes'] ?? '');

    // Line items from arrays
    $productIds = $_POST['product_id']  ?? [];
    $quantities = $_POST['quantity']    ?? [];
    $unitCosts  = $_POST['unit_cost']   ?? [];

    $lines = [];
    for ($i = 0; $i < count($productIds); $i++) {
        $pid = (int)$productIds[$i];
        $qty = (int)$quantities[$i];
        $cst = (float)$unitCosts[$i];
        if ($pid <= 0 || $qty <= 0) continue;
        if ($cst < 0) { $errors[] = "Line " . ($i+1) . ": cost cannot be negative."; continue; }
        $lines[] = ['product_id'=>$pid,'quantity'=>$qty,'unit_cost'=>$cst,'subtotal'=>$qty*$cst];
    }

    if ($supplierId <= 0)                       $errors[] = 'Please select a supplier.';
    if (!$purchaseDate)                         $errors[] = 'Please select a purchase date.';
    if (!$lines)                                $errors[] = 'Add at least one product line with quantity > 0.';

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $reference = generate_reference('PUR');
            $total = array_sum(array_column($lines, 'subtotal'));

            $stmt = $pdo->prepare(
                'INSERT INTO purchases (reference_no, supplier_id, user_id, purchase_date, total_amount, notes)
                 VALUES (:ref,:sid,:uid,:dt,:tot,:n)'
            );
            $stmt->execute([
                'ref'=>$reference,'sid'=>$supplierId,'uid'=>current_user()['id'],
                'dt'=>$purchaseDate,'tot'=>$total,'n'=>$notes ?: null,
            ]);
            $purchaseId = (int)$pdo->lastInsertId();

            $insItem = $pdo->prepare(
                'INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, subtotal)
                 VALUES (:pid,:prod,:q,:uc,:st)'
            );
            $updProduct = $pdo->prepare(
                'UPDATE products SET quantity = quantity + :q, cost_price = :cp WHERE product_id = :id'
            );
            $logMove = $pdo->prepare(
                'INSERT INTO stock_movements
                    (product_id, movement_type, quantity_change, resulting_qty, reference_id, user_id, note)
                 VALUES (:p, "purchase", :q, :rq, :ref, :u, :note)'
            );
            $getQty = $pdo->prepare('SELECT quantity FROM products WHERE product_id = :id FOR UPDATE');

            foreach ($lines as $ln) {
                $insItem->execute([
                    'pid'=>$purchaseId,'prod'=>$ln['product_id'],'q'=>$ln['quantity'],
                    'uc'=>$ln['unit_cost'],'st'=>$ln['subtotal'],
                ]);

                // Lock row, compute new qty for accurate audit snapshot
                $getQty->execute(['id'=>$ln['product_id']]);
                $currentQty = (int)$getQty->fetchColumn();
                $newQty     = $currentQty + $ln['quantity'];

                $updProduct->execute(['q'=>$ln['quantity'],'cp'=>$ln['unit_cost'],'id'=>$ln['product_id']]);

                $logMove->execute([
                    'p'=>$ln['product_id'],'q'=>$ln['quantity'],'rq'=>$newQty,
                    'ref'=>$purchaseId,'u'=>current_user()['id'],'note'=>"Purchase $reference",
                ]);
            }

            $pdo->commit();
            flash('success', "Purchase $reference recorded. Total: " . money($total));
            redirect('modules/purchases/view.php?id=' . $purchaseId);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    flash_old($_POST);
}

$suppliers = db()->query('SELECT supplier_id, name FROM suppliers WHERE is_active = 1 ORDER BY name')->fetchAll();
$products  = db()->query('SELECT product_id, sku, name, cost_price FROM products WHERE is_active = 1 ORDER BY name')->fetchAll();

$pageTitle = 'New Purchase';
include __DIR__ . '/../../includes/header.php';
?>

<form method="POST" novalidate id="purchaseForm">
    <?= csrf_field() ?>
    <div class="card p-4 mb-3">
        <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Supplier *</label>
                <select name="supplier_id" class="form-select" required>
                    <option value="">— Select supplier —</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int)$s['supplier_id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date *</label>
                <input type="date" name="purchase_date" class="form-control" required value="<?= old('purchase_date', date('Y-m-d')) ?>">
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
            <table class="table align-middle" id="lineTable">
                <thead>
                    <tr>
                        <th style="width: 45%">Product</th>
                        <th style="width: 15%">Quantity</th>
                        <th style="width: 15%">Unit Cost</th>
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
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Purchase</button>
            <a href="<?= url('modules/purchases/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
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
                ${products.map(p => `<option value="${p.product_id}" data-cost="${p.cost_price}">${p.sku} — ${p.name}</option>`).join('')}
            </select>
        </td>
        <td><input type="number" name="quantity[]" min="1" value="1" class="form-control form-control-sm qty"></td>
        <td><input type="number" name="unit_cost[]" step="0.01" min="0" value="0.00" class="form-control form-control-sm cost"></td>
        <td class="text-end sub">${fmt(0)}</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger del"><i class="bi bi-x"></i></button></td>
    `;
    document.getElementById('lineBody').appendChild(tr);

    tr.querySelector('.prod').addEventListener('change', (e) => {
        const opt = e.target.selectedOptions[0];
        if (opt && opt.dataset.cost) tr.querySelector('.cost').value = opt.dataset.cost;
        recalc();
    });
    tr.querySelector('.qty').addEventListener('input', recalc);
    tr.querySelector('.cost').addEventListener('input', recalc);
    tr.querySelector('.del').addEventListener('click', () => { tr.remove(); recalc(); });
}

function recalc() {
    let total = 0;
    document.querySelectorAll('#lineBody tr').forEach(tr => {
        const q = +tr.querySelector('.qty').value || 0;
        const c = +tr.querySelector('.cost').value || 0;
        const s = q * c;
        tr.querySelector('.sub').textContent = fmt(s);
        total += s;
    });
    document.getElementById('grandTotal').textContent = fmt(total);
}

document.getElementById('addLine').addEventListener('click', buildLine);
buildLine();  // start with 1 row
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
