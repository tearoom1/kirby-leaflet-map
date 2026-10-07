/**
 * Leaflet Map - Interactive map
 * Uses Leaflet.js
 */

const initLeafletMaps = () => {
  document.querySelectorAll('.leaflet-map').forEach(container => {
    initSingleMap(container);
  });
};

const initSingleMap = (container) => {
  const dataElement = container.querySelector('.leaflet-map__data');
  if (!dataElement) return;

  const mapData = JSON.parse(dataElement.textContent);
  const mapElement = container.querySelector('.leaflet-map__map');
  const openBtn = container.querySelector('.leaflet-map__thumbnail');
  const closeBtn = container.querySelector('.leaflet-map__close-btn');
  const loadBtn = container.querySelector('.leaflet-map__load-btn');
  const interactive = container.querySelector('.leaflet-map__interactive');

  const locations = mapData.locations || [];
  const centerLocation = locations.find(location => location.center) || locations[0];
  const defaultCenter = centerLocation ? [centerLocation.lat, centerLocation.lng] : [0, 0];

  const zoom = mapData.zoom || {};
  const tiles = mapData.tiles || {};
  let map = null;

  const setupMap = () => {
    // Create map with a slight delay to ensure container is visible
    setTimeout(() => {
      map = L.map(mapElement, {
        center: defaultCenter,
        zoom: zoom.default || 15,
        minZoom: zoom.min || 10,
        maxZoom: zoom.max || 19
      });

      L.tileLayer(tiles.url || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: tiles.attribution || '',
        maxZoom: tiles.maxZoom || 19,
        // tile servers like OSM require a referrer, even if the site restricts it
        referrerPolicy: 'strict-origin-when-cross-origin'
      }).addTo(map);

      addMarkers(map, locations);
      addPaths(map, mapData.paths || []);

      // Force a resize to ensure map renders correctly
      setTimeout(() => map.invalidateSize(), 100);
    }, 50);
  };

  const openMap = () => {
    interactive.setAttribute('aria-hidden', 'false');

    if (!map) {
      // Initialize map on first open
      setupMap();
    } else {
      setTimeout(() => map.invalidateSize(), 100);
    }

    // focus once the overlay is visible, so keyboard users can close it
    if (closeBtn) setTimeout(() => closeBtn.focus(), 50);
  };

  const closeMap = () => {
    interactive.setAttribute('aria-hidden', 'true');
  };

  // Popup mode: the map opens in an overlay
  if (interactive) {
    if (openBtn) openBtn.addEventListener('click', openMap);
    if (closeBtn) closeBtn.addEventListener('click', closeMap);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && interactive.getAttribute('aria-hidden') === 'false') {
        closeMap();
      }
    });
    return;
  }

  // Embedded mode, optionally only after the visitor agreed to load the tiles
  if (loadBtn) {
    loadBtn.addEventListener('click', () => {
      loadBtn.closest('.leaflet-map__consent').remove();
      setupMap();
    });
    return;
  }

  setupMap();
};

/**
 * Build popup and tooltip content with DOM nodes, so titles are never
 * interpreted as HTML. The description is HTML rendered by Kirby.
 */
const popupContent = (title, descriptionHtml) => {
  const wrapper = document.createElement('div');
  wrapper.className = 'leaflet-map__popup';

  if (title) {
    const heading = document.createElement('h4');
    heading.textContent = title;
    wrapper.appendChild(heading);
  }

  if (descriptionHtml) {
    const text = document.createElement('p');
    text.innerHTML = descriptionHtml;
    wrapper.appendChild(text);
  }

  return wrapper;
};

const tooltipContent = (title) => {
  const heading = document.createElement('h5');
  heading.textContent = title;
  return heading;
};

const addMarkers = (map, locations) => {
  locations.forEach(location => {
    const marker = L.circle([location.lat, location.lng], {
      className: 'leaflet-map__marker',
      fillOpacity: 1,
      fillColor: location.color || '#3388ff',
      color: '#ffffff',
      weight: 1,
      radius: 5 + (location.size || 1) * 10
    });

    if (location.title || location.description) {
      marker.bindPopup(popupContent(location.title, location.description));

      if (location.tooltip && location.title) {
        marker.bindTooltip(tooltipContent(location.title), {permanent: true, direction: 'top', offset: [0, -10]});
      }
    }

    marker.addTo(map);
  });
};

const addPaths = (map, paths) => {
  paths.forEach(path => {
    const polyline = L.polyline(path.points, {
      color: path.color || '#3388ff',
      weight: 2,
      opacity: 0.7
    }).addTo(map);

    if (path.title) {
      polyline.bindPopup(popupContent(path.title));

      if (path.tooltip) {
        polyline.bindTooltip(tooltipContent(path.title), {permanent: true, direction: 'top', offset: [0, -10]});
      }
    }
  });
};

// Initialize maps when DOM is ready
document.addEventListener('DOMContentLoaded', initLeafletMaps);
