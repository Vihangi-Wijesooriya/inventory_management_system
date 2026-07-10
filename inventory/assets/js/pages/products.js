const tbody      = document.querySelector('#dataTable tbody');
const pagination = document.getElementById('pagination');
const modalEl    = document.getElementById('crudModal');
const modal      = new bootstrap.Modal(modalEl);
const form       = document.getElementById('crudForm');
const saveBtn    = document.getElementById('saveBtn');
const catFilter  = document.getElementById('categoryFilter');
const catSelect  = document.getElementById('categorySelect');
const lowStock   = document.getElementById('lowStockFilter');
const COLS = 7;

let state = { page: 1, q: '', category_id: '', low_stock: false };
let editingId = null;

async function loadCategories() {
  const res = await apiGet('/categories?per_page=100');
  const options = res.data
    .map((c) => `<option value="${c.id}">${escapeHtml(c.name)}</option>`)
    .join('');
  catFilter.insertAdjacentHTML('beforeend', options);
  catSelect.innerHTML = '<option value="">Select...</option>' + options;
}

function stockBadge(p) {
  const low = Number(p.quantity) <= Number(p.reorder_level);
  return `<span class="badge ${low ? 'badge-stock-low' : 'badge-stock-ok'}">${p.quantity}</span>`;
}

function thumb(p) {
  return p.image
    ? `<img src="uploads/products/${escapeHtml(p.image)}" class="product-thumb" alt="">`
    : '<div class="product-thumb d-grid place-items-center"><i class="bi bi-image text-secondary m-auto"></i></div>';
}

async function load() {
  tbody.innerHTML = spinnerRow(COLS);
  const params = new URLSearchParams({ page: state.page, q: state.q });
  if (state.category_id) params.set('category_id', state.category_id);
  if (state.low_stock) params.set('low_stock', '1');

  try {
    const res = await apiGet('/products?' + params);
    if (!res.data.length) {
      tbody.innerHTML = emptyRow(COLS);
    } else {
      tbody.innerHTML = res.data.map((p) => `
        <tr>
          <td>${thumb(p)}</td>
          <td>${escapeHtml(p.name)}</td>
          <td><code>${escapeHtml(p.sku)}</code></td>
          <td>${escapeHtml(p.category_name ?? '—')}</td>
          <td class="text-end">${money(p.price)}</td>
          <td class="text-center">${stockBadge(p)}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" data-edit="${p.id}"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-outline-danger" data-del="${p.id}"><i class="bi bi-trash"></i></button>
          </td>
        </tr>`).join('');
    }
    renderPagination(pagination, res.meta, (p) => { state.page = p; load(); });
    bindRows(res.data);
  } catch (e) {
    tbody.innerHTML = emptyRow(COLS, e.message);
  }
}

function bindRows(rows) {
  tbody.querySelectorAll('[data-edit]').forEach((btn) =>
    btn.addEventListener('click', () => openModal(rows.find((r) => r.id == btn.dataset.edit))));
  tbody.querySelectorAll('[data-del]').forEach((btn) =>
    btn.addEventListener('click', async () => {
      if (!(await confirmDialog('Delete this product?'))) return;
      try {
        await apiDelete(`/products/${btn.dataset.del}`);
        toast('Product deleted.');
        load();
      } catch (e) {
        toast(e.message, 'danger');
      }
    }));
}

function openModal(row = null) {
  editingId = row ? row.id : null;
  modalEl.querySelector('.modal-title').textContent = row ? 'Edit Product' : 'Add Product';
  form.reset();
  if (row) {
    [...form.elements].forEach((el) => {
      if (el.name && el.type !== 'file' && row[el.name] != null) el.value = row[el.name];
    });
  }
  modal.show();
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(form);
  if (!fd.get('image')?.size) fd.delete('image'); // don't send empty file part
  saveBtn.disabled = true;
  try {
    if (editingId) {
      fd.set('_method', 'PUT');
      await apiPost(`/products/${editingId}`, fd);
      toast('Product updated.');
    } else {
      await apiPost('/products', fd);
      toast('Product added.');
    }
    modal.hide();
    load();
  } catch (err) {
    toast(err.message, 'danger');
  } finally {
    saveBtn.disabled = false;
  }
});

document.getElementById('addBtn').addEventListener('click', () => openModal());
onSearch(document.getElementById('searchInput'), (q) => { state = { ...state, page: 1, q }; load(); });
catFilter.addEventListener('change', () => { state = { ...state, page: 1, category_id: catFilter.value }; load(); });
lowStock.addEventListener('change', () => { state = { ...state, page: 1, low_stock: lowStock.checked }; load(); });

loadCategories().then(load).catch((e) => toast(e.message, 'danger'));
