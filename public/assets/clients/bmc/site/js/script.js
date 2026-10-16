// BMC Barros — interações da vitrine (menu de departamentos, filtros do catálogo).

(function initDepartmentMenu() {
  const toggle = document.querySelector('[data-menu-toggle]');
  const nav = document.querySelector('[data-dept-nav]');
  if (!toggle || !nav) {
    return;
  }

  const setOpen = (open) => {
    nav.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Fechar departamentos' : 'Abrir departamentos');
  };

  toggle.addEventListener('click', () => setOpen(!nav.classList.contains('is-open')));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && nav.classList.contains('is-open')) {
      setOpen(false);
      toggle.focus();
    }
  });
  window.matchMedia('(min-width: 901px)').addEventListener('change', (mq) => {
    if (mq.matches) {
      setOpen(false);
    }
  });
})();

(function initCatalogFilters() {
  const drawerRoot = document.getElementById('catalog-filters-drawer');
  const openBtn = document.querySelector('[data-catalog-filters-open]');

  if (drawerRoot && openBtn) {
    let lastFocus = null;

    const closeDrawer = () => {
      drawerRoot.hidden = true;
      drawerRoot.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('catalog-filters-drawer-open');
      openBtn.setAttribute('aria-expanded', 'false');
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      }
    };

    const openDrawer = () => {
      lastFocus = document.activeElement;
      drawerRoot.hidden = false;
      drawerRoot.setAttribute('aria-hidden', 'false');
      document.body.classList.add('catalog-filters-drawer-open');
      openBtn.setAttribute('aria-expanded', 'true');
      drawerRoot.querySelector('.catalog-filters-drawer__close')?.focus();
    };

    openBtn.addEventListener('click', openDrawer);
    drawerRoot.querySelectorAll('[data-catalog-filters-close]').forEach((el) => el.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', (event) => {
      if (!drawerRoot.hidden && event.key === 'Escape') {
        closeDrawer();
      }
    });
  }

  document.querySelectorAll('[data-sidebar-tree-toggle]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      const group = button.closest('[data-sidebar-tree-group]');
      const subnav = group?.querySelector('.sidebar-subnav');
      if (!group || !subnav) {
        return;
      }
      const isOpen = group.classList.toggle('is-open');
      subnav.hidden = !isOpen;
      button.setAttribute('aria-expanded', String(isOpen));
    });
  });
})();
