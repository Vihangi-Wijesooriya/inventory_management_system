/**
 * Shared wiring for the Sales and Purchases pages (identical structure).
 * initTxn({ resource, partyResource, partyField, dateField, hasCost, labels })
 */
function initTxn(cfg) {
  const tbody      = document.querySelector('#dataTable tbody');
  const pagination = document.getElementById('pagination');
  const modal      = new bootstrap.Modal(document.getElementById('saleModal'));
  const detailModal= new bootstrap.Modal(document.getElementById('detailModal'));
  const partySel   = document.getElementById('saleCustomer');
  const productSel = document.getElementById('saleProduct');
  const qtyInput   = document.getElementById('saleQty');
  const costInput  = document.getElementById('unitCost'); // purchases only
  const itemsBody  = document.querySelector('#itemsTable tbody');
  const totalCell  = document.getElementById('saleTotal');
  const saveBtn    = document.getElementById('saveSaleBtn');
  const COLS = 7;

  let state = { page: 1, q: '', from: '', to: '' };
  let products = [];
  let items = [];

  async function loadLookups() {
    const [party, prods] = await Promise.all([
      apiGet(`/${cfg.partyResource}?per_page=100`),
      apiGet('/products?per_page=100'),
    ]);
    partySel.insertAdjacentHTML('beforeend',
      party.data.map((p) => `<option value="${p.id}">${escapeHtml(p.name)}</option>`).join(''));
    products = prods.data;
    productSel.innerHTML = products
      .map((p) => `<option value="${p.id}">${escapeHtml(p.name)} (${escapeHtml(p.sku)}) — stock: ${p.quantity}</option>`)
      .join('');
  }

  async function load() {
    tbody.innerHTML = spinnerRow(COLS);
    const params = new URLSearchParams({ page: state.page, q: state.q });
    if (state.from) params.set('from', state.from);
    if (state.to) params.set('to', state.to);
    try {
      const res = await apiGet(`/${cfg.resource}?` + params);
      if (!res.data.length) {
        tbody.innerHTML = emptyRow(COLS);
      } else {
        tbody.innerHTML = res.data.map((r) => `
          <tr>
            <td><code>${escapeHtml(r.invoice_no)}</code></td>
            <td>${escapeHtml((r[cfg.dateField] || '').slice(0, 16))}</td>
            <td>${escapeHtml(r[cfg.partyName] ?? cfg.labels.noParty)}</td>
            <td>${escapeHtml(r.user_name ?? '—')}</td>
            <td class="text-center">${r.item_count}</td>
            <td class="text-end">${money(r.total)}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-view="${r.id}"><i class="bi bi-eye"></i></button>
              <button class="btn btn-sm btn-outline-danger" data-del="${r.id}"><i class="bi bi-trash"></i></button>
            </td>
          </tr>`).join('');
      }
      renderPagination(pagination, res.meta, (p) => { state.page = p; load(); });
      bindRows();
    } catch (e) {
      tbody.innerHTML = emptyRow(COLS, e.message);
    }
  }

  function bindRows() {
    tbody.querySelectorAll('[data-view]').forEach((btn) =>
      btn.addEventListener('click', () => showDetail(btn.dataset.view)));
    tbody.querySelectorAll('[data-del]').forEach((btn) =>
      btn.addEventListener('click', async () => {
        if (!(await confirmDialog(cfg.labels.deleteConfirm))) return;
        try {
          await apiDelete(`/${cfg.resource}/${btn.dataset.del}`);
          toast(cfg.labels.deleted);
          load();
        } catch (e) {
          toast(e.message, 'danger');
        }
      }));
  }

  async function showDetail(id) {
    document.getElementById('detailBody').innerHTML =
      '<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></div>';
    detailModal.show();
    try {
      const d = await apiGet(`/${cfg.resource}/${id}`);
      const unitKey = cfg.hasCost ? 'unit_cost' : 'unit_price';
      document.getElementById('detailBody').innerHTML = `
        <div class="row mb-3 small">
          <div class="col"><strong>Invoice:</strong> ${escapeHtml(d.invoice_no)}</div>
          <div class="col"><strong>Date:</strong> ${escapeHtml((d[cfg.dateField] || '').slice(0, 16))}</div>
          <div class="col"><strong>${cfg.labels.partyLabel}:</strong> ${escapeHtml(d[cfg.partyName] ?? cfg.labels.noParty)}</div>
        </div>
        <table class="table table-sm">
          <thead><tr><th>Product</th><th>SKU</th><th class="text-center">Qty</th><th class="text-end">Unit</th><th class="text-end">Subtotal</th></tr></thead>
          <tbody>
            ${d.items.map((i) => `
              <tr>
                <td>${escapeHtml(i.product_name)}</td>
                <td><code>${escapeHtml(i.sku)}</code></td>
                <td class="text-center">${i.quantity}</td>
                <td class="text-end">${money(i[unitKey])}</td>
                <td class="text-end">${money(i.subtotal)}</td>
              </tr>`).join('')}
          </tbody>
          <tfoot><tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end">${money(d.total)}</td></tr></tfoot>
        </table>`;
    } catch (e) {
      document.getElementById('detailBody').innerHTML =
        `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;
    }
  }

  /* ----- new transaction modal ----- */

  function renderItems() {
    const unitLabel = cfg.hasCost ? 'unit_cost' : 'unit_price';
    itemsBody.innerHTML = items.map((it, idx) => `
      <tr>
        <td>${escapeHtml(it.name)}</td>
        <td class="text-center">${it.quantity}</td>
        <td class="text-end">${money(it[unitLabel])}</td>
        <td class="text-end">${money(it.subtotal)}</td>
        <td class="text-end"><button class="btn btn-sm btn-link text-danger p-0" data-rm="${idx}"><i class="bi bi-x-lg"></i></button></td>
      </tr>`).join('');
    totalCell.textContent = money(items.reduce((s, i) => s + i.subtotal, 0));
    itemsBody.querySelectorAll('[data-rm]').forEach((b) =>
      b.addEventListener('click', () => { items.splice(Number(b.dataset.rm), 1); renderItems(); }));
  }

  document.getElementById('addItemBtn').addEventListener('click', () => {
    const pid = Number(productSel.value);
    const qty = Number(qtyInput.value);
    const product = products.find((p) => p.id === pid);
    if (!product || qty < 1) return;

    let unit;
    if (cfg.hasCost) {
      unit = Number(costInput.value);
      if (!(unit >= 0)) { toast('Enter a unit cost.', 'warning'); return; }
    } else {
      unit = Number(product.price);
      const already = items.filter((i) => i.product_id === pid).reduce((s, i) => s + i.quantity, 0);
      if (already + qty > product.quantity) {
        toast(`Only ${product.quantity} in stock for ${product.name}.`, 'warning');
        return;
      }
    }
    const item = {
      product_id: pid,
      name: product.name,
      quantity: qty,
      subtotal: Math.round(unit * qty * 100) / 100,
    };
    item[cfg.hasCost ? 'unit_cost' : 'unit_price'] = unit;
    items.push(item);
    renderItems();
  });

  saveBtn.addEventListener('click', async () => {
    if (!items.length) { toast('Add at least one item.', 'warning'); return; }
    const body = { items: items.map(({ product_id, quantity, unit_cost }) =>
      cfg.hasCost ? { product_id, quantity, unit_cost } : { product_id, quantity }) };

    if (cfg.hasCost) {
      if (!partySel.value) { toast('Select a supplier.', 'warning'); return; }
      body[cfg.partyField] = Number(partySel.value);
    } else if (partySel.value) {
      body[cfg.partyField] = Number(partySel.value);
    }

    saveBtn.disabled = true;
    try {
      await apiPost(`/${cfg.resource}`, body);
      toast(cfg.labels.saved);
      modal.hide();
      items = [];
      renderItems();
      await loadLookups(); // refresh stock numbers in the product dropdown
      load();
    } catch (e) {
      toast(e.message, 'danger');
    } finally {
      saveBtn.disabled = false;
    }
  });

  document.getElementById('addBtn').addEventListener('click', () => {
    items = [];
    renderItems();
    partySel.value = '';
    modal.show();
  });

  onSearch(document.getElementById('searchInput'), (q) => { state = { ...state, page: 1, q }; load(); });
  ['fromDate', 'toDate'].forEach((id, i) =>
    document.getElementById(id).addEventListener('change', (e) => {
      state = { ...state, page: 1, [i === 0 ? 'from' : 'to']: e.target.value };
      load();
    }));

  loadLookups().then(load).catch((e) => toast(e.message, 'danger'));
}
