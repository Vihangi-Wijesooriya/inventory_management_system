(async () => {
  let d;
  try {
    d = await apiGet('/dashboard');
  } catch (e) {
    toast(e.message, 'danger');
    return;
  }

  document.querySelectorAll('[data-stat]').forEach((el) => {
    const v = d.counts[el.dataset.stat];
    el.textContent = el.dataset.money === '1' ? money(v) : Number(v).toLocaleString();
  });

  const list = document.getElementById('lowStockList');
  document.getElementById('lowCount').textContent = d.low_stock.length;
  list.innerHTML = d.low_stock.length
    ? d.low_stock.map((p) => `
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <div>
            <div>${escapeHtml(p.name)}</div>
            <small class="text-secondary">${escapeHtml(p.sku)}</small>
          </div>
          <span class="badge badge-stock-low">${p.quantity} / ${p.reorder_level}</span>
        </li>`).join('')
    : '<li class="list-group-item text-secondary px-0">All stock levels are healthy.</li>';

  new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
      labels: d.sales_chart.map((r) => r.date.slice(5)),
      datasets: [{
        label: 'Sales (Rs.)',
        data: d.sales_chart.map((r) => r.total),
        borderColor: '#0d6efd',
        backgroundColor: 'rgba(13,110,253,.12)',
        fill: true,
        tension: .3,
      }],
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
  });

  new Chart(document.getElementById('topChart'), {
    type: 'bar',
    data: {
      labels: d.top_products.map((p) => p.name),
      datasets: [{
        label: 'Units sold',
        data: d.top_products.map((p) => p.sold),
        backgroundColor: '#198754',
        borderRadius: 6,
      }],
    },
    options: {
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  });
})();
