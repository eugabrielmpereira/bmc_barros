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
