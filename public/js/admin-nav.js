(function () {
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
