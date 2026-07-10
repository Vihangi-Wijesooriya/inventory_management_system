const typeSel  = document.getElementById('reportType');
const fromInp  = document.getElementById('fromDate');
const toInp    = document.getElementById('toDate');
const area     = document.getElementById('reportArea');
const dateBox  = document.getElementById('dateFilters');

const today = new Date().toISOString().slice(0, 10);
fromInp.value = today.slice(0, 8) + '01';
toInp.value = today;

typeSel.addEventListener('change', () => {
  dateBox.style.display = typeSel.value === 'inventory' ? 'none' : 'flex';
});

function queryString(format = '') {
  const params = new URLSearchParams();
  if (typeSel.value !== 'inventory') {
    params.set('from', fromInp.value);
    params.set('to', toInp.value);
  }
  if (format) params.set('format', format);
  return params.toString();
}

async function run() {
  area.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
  try {
    const r = await apiGet(`/reports/${typeSel.value}?` + queryString());
    if (!r.rows.length) {
      area.innerHTML = '<div class="text-secondary text-center py-5">No data for the selected range.</div>';
      return;
    }
    const headers = Object.keys(r.rows[0]);
    const moneyCols = new Set(['total', 'price', 'stock_value']);
    const summary = Object.entries(r.summary)
      .map(([k, v]) => `<span class="me-4"><strong>${escapeHtml(k.replace('_', ' '))}:</strong> ${
        k === 'count' ? v : money(v)}</span>`)
      .join('');

    area.innerHTML = `
      <h2 class="h6">${escapeHtml(r.title)}${r.from ? ` <small class="text-secondary">(${r.from} → ${r.to})</small>` : ''}</h2>
      <div class="mb-3">${summary}</div>
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead><tr>${headers.map((h) => `<th>${escapeHtml(h.replace('_', ' '))}</th>`).join('')}</tr></thead>
          <tbody>
            ${r.rows.map((row) => `<tr>${headers.map((h) =>
              `<td>${moneyCols.has(h) ? money(row[h]) : escapeHtml(row[h] ?? '—')}</td>`).join('')}</tr>`).join('')}
          </tbody>
        </table>
      </div>`;
  } catch (e) {
    area.innerHTML = `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;
  }
}

document.getElementById('runBtn').addEventListener('click', run);
document.getElementById('printBtn').addEventListener('click', () => window.print());
document.getElementById('csvBtn').addEventListener('click', () => {
  window.location.href = `/api/reports/${typeSel.value}?` + queryString('csv');
});
