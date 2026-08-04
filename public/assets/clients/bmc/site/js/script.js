// Site simples da BMC Barros - Construção & Mais.
// Funções: menu móvel, rolagem suave, geolocalização e direcionamento para Google Maps.

const menuButton = document.querySelector('.menu-btn');
const nav = document.querySelector('.nav');

// Menu móvel acessível
if (menuButton && nav) {
  menuButton.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(isOpen));
    menuButton.textContent = isOpen ? '×' : '☰';
  });

  nav.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', () => {
      nav.classList.remove('open');
      menuButton.setAttribute('aria-expanded', 'false');
      menuButton.textContent = '☰';
    });
  });
}

// Rolagem suave para navegação interna
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', event => {
    const targetId = anchor.getAttribute('href');
    const target = targetId ? document.querySelector(targetId) : null;

    if (target) {
      event.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// Geolocalização e direcionamento automático para Google Maps
const mapsBtn = document.getElementById('maps-direction-btn');
const storeAddress = 'Av. Ver. José Antônio De Lima, 120 - Rincão - SP';

if (mapsBtn) {
  mapsBtn.addEventListener('click', (event) => {
    event.preventDefault();
    
    // Tenta obter a localização do usuário
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const userLat = position.coords.latitude;
          const userLng = position.coords.longitude;
          
          // Abre o Google Maps com rota da localização do usuário até a loja
          const mapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${encodeURIComponent(storeAddress)}&travelmode=driving`;
          window.open(mapsUrl, '_blank', 'noopener,noreferrer');
        },
        (error) => {
          // Se o usuário negar permissão, abre o mapa sem rota
          console.log('Geolocalização não permitida, abrindo mapa direto.');
          const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(storeAddress)}`;
          window.open(mapsUrl, '_blank', 'noopener,noreferrer');
        }
      );
    } else {
      // Se o navegador não suporta geolocalização, abre o mapa direto
      const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(storeAddress)}`;
      window.open(mapsUrl, '_blank', 'noopener,noreferrer');
    }
  });
}

// Catálogo: drawer de filtros (mobile) e árvore de categorias na sidebar
(function initCatalogFilters() {
  const drawerRoot = document.getElementById('catalog-filters-drawer');
  const openBtn = document.querySelector('[data-catalog-filters-open]');

  if (drawerRoot && openBtn) {
    let lastFocus = null;

    const closeDrawer = () => {
      drawerRoot.hidden = true;
      drawerRoot.setAttribute('aria-hidden', 'true');
      drawerRoot.classList.remove('catalog-filters-drawer--open');
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
      drawerRoot.classList.add('catalog-filters-drawer--open');
      document.body.classList.add('catalog-filters-drawer-open');
      openBtn.setAttribute('aria-expanded', 'true');
      drawerRoot.querySelector('.catalog-filters-drawer__close')?.focus();
    };

    openBtn.addEventListener('click', openDrawer);
    drawerRoot.querySelectorAll('[data-catalog-filters-close]').forEach((el) => {
      el.addEventListener('click', closeDrawer);
    });
    drawerRoot.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', closeDrawer);
    });
    document.addEventListener('keydown', (event) => {
      if (!drawerRoot.hidden && event.key === 'Escape') {
        closeDrawer();
      }
    });
  }

  initSidebarTreeToggles();
})();

function initSidebarTreeToggles() {
  document.querySelectorAll('[data-sidebar-tree-toggle]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const group = button.closest('[data-sidebar-tree-group]');
      const subnav = group?.querySelector('.sidebar-subnav');
      if (!group || !subnav) {
        return;
      }

      const isOpen = group.classList.toggle('is-open');
      subnav.hidden = !isOpen;
      button.setAttribute('aria-expanded', String(isOpen));
      button.setAttribute('aria-label', isOpen ? 'Recolher subcategorias' : 'Expandir subcategorias');
    });
  });
}
