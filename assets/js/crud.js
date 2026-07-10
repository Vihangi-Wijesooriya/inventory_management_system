/**
 * Generic CRUD page wiring for simple resources.
 * Page must contain: #searchInput, #addBtn, #dataTable tbody, #pagination,
 * #crudModal with a form #crudForm whose inputs are named after API fields.
 *
 * Usage: initCrud({ resource: 'categories', columns: [...], labelSingular: 'Category' })
 * Column: { field, label?, render? }
 */
function initCrud(config) {
  const tbody      = document.querySelector('#dataTable tbody');
  const pagination = document.getElementById('pagination');
  const modalEl    = document.getElementById('crudModal');
  const modal      = new bootstrap.Modal(modalEl);
  const form       = document.getElementById('crudForm');
  const title      = modalEl.querySelector('.modal-title');
  const saveBtn    = modalEl.querySelector('#saveBtn');
  const colCount   = config.columns.length + 1;

  let state = { page: 1, q: '' };
  let editingId = null;

  async function load() {
    tbody.innerHTML = spinnerRow(colCount);
    try {
      const res = await apiGet(`/${config.resource}?page=${state.page}&q=${encodeURIComponent(state.q)}`);
      if (!res.data.length) {
        tbody.innerHTML = emptyRow(colCount);
      } else {
        tbody.innerHTML = res.data.map((row) => `
          <tr>
            ${config.columns.map((c) =>
              `<td>${c.render ? c.render(row) : escapeHtml(row[c.field] ?? '—')}</td>`).join('')}
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary" data-edit="${row.id}"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" data-del="${row.id}"><i class="bi bi-trash"></i></button>
            </td>
          </tr>`).join('');
      }
      renderPagination(pagination, res.meta, (p) => { state.page = p; load(); });
      bindRowActions(res.data);
    } catch (e) {
      tbody.innerHTML = emptyRow(colCount, e.message);
    }
  }

  function bindRowActions(rows) {
    tbody.querySelectorAll('[data-edit]').forEach((btn) =>
      btn.addEventListener('click', () => {
        const row = rows.find((r) => r.id == btn.dataset.edit);
        openModal(row);
      }));
    tbody.querySelectorAll('[data-del]').forEach((btn) =>
      btn.addEventListener('click', async () => {
        if (!(await confirmDialog(`Delete this ${config.labelSingular.toLowerCase()}?`))) return;
        try {
          await apiDelete(`/${config.resource}/${btn.dataset.del}`);
          toast(`${config.labelSingular} deleted.`);
          load();
        } catch (e) {
          toast(e.message, 'danger');
        }
      }));
  }

  function openModal(row = null) {
    editingId = row ? row.id : null;
    title.textContent = (row ? 'Edit ' : 'Add ') + config.labelSingular;
    form.reset();
    if (row) {
      [...form.elements].forEach((el) => {
        if (el.name && row[el.name] != null) el.value = row[el.name];
      });
    }
    modal.show();
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(form).entries());
    saveBtn.disabled = true;
    try {
      if (editingId) {
        await apiPut(`/${config.resource}/${editingId}`, body);
        toast(`${config.labelSingular} updated.`);
      } else {
        await apiPost(`/${config.resource}`, body);
        toast(`${config.labelSingular} added.`);
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
  onSearch(document.getElementById('searchInput'), (q) => { state = { page: 1, q }; load(); });

  load();
}
