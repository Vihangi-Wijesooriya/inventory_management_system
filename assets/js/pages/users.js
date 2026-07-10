const roleBadge = (r) =>
  `<span class="badge ${r === 'admin' ? 'text-bg-primary' : 'text-bg-secondary'} text-capitalize">${r}</span>`;

// Password required on create, optional on edit.
const form = document.getElementById('crudForm');
const pwInput = form.elements.password;
const pwHint = document.getElementById('pwHint');
document.getElementById('addBtn').addEventListener('click', () => {
  pwInput.required = true;
  pwHint.classList.add('d-none');
}, { capture: true });
document.querySelector('#dataTable tbody').addEventListener('click', (e) => {
  if (e.target.closest('[data-edit]')) {
    pwInput.required = false;
    pwHint.classList.remove('d-none');
  }
}, { capture: true });

initCrud({
  resource: 'users',
  labelSingular: 'User',
  columns: [
    { field: 'id' },
    { field: 'name' },
    { field: 'username' },
    { field: 'role', render: (r) => roleBadge(r.role) },
    { field: 'created_at', render: (r) => escapeHtml((r.created_at || '').slice(0, 10)) },
  ],
});
