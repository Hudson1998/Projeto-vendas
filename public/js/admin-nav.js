(function () {
  function ajustarAlturaSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    if (!sidebar) return;
    const zoom = parseFloat(getComputedStyle(document.documentElement).zoom) || 1;
    sidebar.style.height = (window.innerHeight / zoom) + 'px';
  }

  ajustarAlturaSidebar();
  window.addEventListener('resize', ajustarAlturaSidebar);
  document.addEventListener('ajaxpage:loaded', ajustarAlturaSidebar);

  function closeAdminSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('admin-sidebar-overlay');
    if (sidebar) sidebar.classList.remove('is-open');
    if (overlay) overlay.classList.remove('is-open');
  }

  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('#admin-sidebar-toggle');
    if (toggle) {
      const sidebar = document.getElementById('admin-sidebar');
      const overlay = document.getElementById('admin-sidebar-overlay');
      if (sidebar) sidebar.classList.toggle('is-open');
      if (overlay) overlay.classList.toggle('is-open');
      return;
    }

    if (e.target.closest('#admin-sidebar-overlay')) {
      closeAdminSidebar();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAdminSidebar();
  });

  document.addEventListener('ajaxpage:loaded', closeAdminSidebar);
})();
