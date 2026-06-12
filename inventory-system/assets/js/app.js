// Inventory Management System — frontend JS

document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle (mobile)
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    // Highlight active sidebar link based on URL
    const links = document.querySelectorAll('.sidebar-nav a');
    const path = window.location.pathname;
    links.forEach(a => {
        const href = a.getAttribute('href');
        if (href && path.includes(href.split('/').slice(-2).join('/'))) {
            a.classList.add('active');
        }
    });

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});
