/**
 * Shared frontend layer: fetch wrapper, toasts, confirm dialog, helpers.
 */

const API = '/api';

class ApiError extends Error {
  constructor(message, status, payload) {
    super(message);
    this.status = status;
    this.payload = payload;
  }
}

async function apiRequest(method, path, body = null) {
  const options = {
    method,
    headers: { 'X-Requested-With': 'fetch' },
    credentials: 'same-origin',
  };

  if (body instanceof FormData) {
    options.body = body; // browser sets multipart boundary
  } else if (body !== null) {
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(body);
  }

  let res;
  try {
    res = await fetch(API + path, options);
  } catch {
    throw new ApiError('Network error — check your connection.', 0, null);
  }

  if (res.status === 401 && !path.startsWith('/auth/login')) {
    window.location.href = 'index.php';
    throw new ApiError('Session expired.', 401, null);
  }

  const data = await res.json().catch(() => null);
  if (!res.ok) {
    throw new ApiError(data?.error || `Request failed (${res.status}).`, res.status, data);
  }
  return data;
}

const apiGet    = (path)       => apiRequest('GET', path);
const apiPost   = (path, body) => apiRequest('POST', path, body);
const apiPut    = (path, body) => apiRequest('PUT', path, body);
const apiDelete = (path)       => apiRequest('DELETE', path);

/* ---------- UI helpers ---------- */

function toast(message, type = 'success') {
  const el = document.createElement('div');
  el.className = `toast align-items-center text-bg-${type} border-0`;
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${escapeHtml(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  document.getElementById('toasts').appendChild(el);
  const t = new bootstrap.Toast(el, { delay: 3500 });
  el.addEventListener('hidden.bs.toast', () => el.remove());
  t.show();
}

function confirmDialog(message) {
  return new Promise((resolve) => {
    let modal = document.getElementById('confirmModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'confirmModal';
      modal.className = 'modal fade';
      modal.innerHTML = `
        <div class="modal-dialog modal-sm modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-body" id="confirmMsg"></div>
            <div class="modal-footer py-2">
              <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button class="btn btn-sm btn-danger" id="confirmOk">Delete</button>
            </div>
          </div>
        </div>`;
      document.body.appendChild(modal);
    }
    modal.querySelector('#confirmMsg').textContent = message;
    const bs = bootstrap.Modal.getOrCreateInstance(modal);
    const okBtn = modal.querySelector('#confirmOk');

    const onOk = () => { cleanup(); bs.hide(); resolve(true); };
    const onHide = () => { cleanup(); resolve(false); };
    const cleanup = () => {
      okBtn.removeEventListener('click', onOk);
      modal.removeEventListener('hidden.bs.modal', onHide);
    };
    okBtn.addEventListener('click', onOk);
    modal.addEventListener('hidden.bs.modal', onHide);
    bs.show();
  });
}

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

const money = (n) => 'Rs. ' + Number(n ?? 0).toLocaleString('en-LK', { minimumFractionDigits: 2 });

function spinnerRow(colspan) {
  return `<tr><td colspan="${colspan}" class="text-center py-5">
    <div class="spinner-border text-primary spinner-border-sm"></div></td></tr>`;
}

function emptyRow(colspan, message = 'No records found.') {
  return `<tr><td colspan="${colspan}" class="text-center text-secondary py-5">${escapeHtml(message)}</td></tr>`;
}

function renderPagination(container, meta, onPage) {
  if (!meta || meta.total_pages <= 1) { container.innerHTML = ''; return; }
  let html = '<ul class="pagination pagination-sm mb-0">';
  for (let p = 1; p <= meta.total_pages; p++) {
    html += `<li class="page-item ${p === meta.page ? 'active' : ''}">
      <button class="page-link" data-page="${p}">${p}</button></li>`;
  }
  html += '</ul>';
  container.innerHTML = html;
  container.querySelectorAll('[data-page]').forEach((btn) =>
    btn.addEventListener('click', () => onPage(Number(btn.dataset.page))));
}

/** Debounced search input wiring. */
function onSearch(input, callback, delay = 350) {
  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => callback(input.value.trim()), delay);
  });
}

/* Logout button (present on all authed pages) */
document.getElementById('logoutBtn')?.addEventListener('click', async () => {
  try { await apiPost('/auth/logout'); } catch { /* session may already be gone */ }
  window.location.href = 'index.php';
});
