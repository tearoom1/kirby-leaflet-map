/**
 * Leaflet Map - Interactive map
 * Uses Leaflet.js
 */

const initArillasMap = () => {
  const mapContainers = document.querySelectorAll('.leaflet-map');

  if (!mapContainers.length) return;

  // Load Leaflet CSS
  // const leafletCss = document.createElement('link');
  // leafletCss.rel = 'stylesheet';
  // leafletCss.href = '/assets/leaflet/leaflet.css';
  // document.head.appendChild(leafletCss);
  // import('/leaflet/dist/leaflet.css')

  // Load Leaflet JS
  mapContainers.forEach(container => {
    initSingleMap(container);
  });
}

const initSingleMap = (container) => {
  // Data
  const dataElement = container.querySelector('.leaflet-map__data');
  if (!dataElement) return;

  const mapData = JSON.parse(dataElement.textContent);
  const blockId = container.id;
  const mapElement = container.querySelector('.leaflet-map__map');
  const thumbnail = container.querySelector('.leaflet-map__thumbnail');
  const openBtn = container.querySelector('.leaflet-map__thumbnail');
  const closeBtn = container.querySelector('.leaflet-map__close-btn');
  const interactive = container.querySelector('.leaflet-map__interactive');

  var centerLocation = mapData.locations.find(location => location.center === 'true');
  if (!centerLocation) {
    // take first location as center
    centerLocation = mapData.locations[0];
  }

  // Arillas approximate center coordinates
  const defaultCenter = centerLocation ? [centerLocation.lat, centerLocation.lng] : [0, 0];
  let map = null;


  function setupMap() {
    // Create map with a slight delay to ensure container is visible
    setTimeout(() => {
      map = L.map(mapElement, {
        center: defaultCenter,
        zoom: 15,
        minZoom: 10,
        maxZoom: 19
      });

      // Add OpenStreetMap tiles
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      // Add markers
      addMarkers(map, mapData.locations);

      // Add paths
      addPaths(map, mapData.paths);

      // Force a resize to ensure map renders correctly
      setTimeout(() => map.invalidateSize(), 100);
    }, 50);
  }

// Event handlers
  const openMap = () => {
    if (!map) {
      // Initialize map on first open
      interactive.setAttribute('aria-hidden', 'false');

      setupMap();
    } else {
      interactive.setAttribute('aria-hidden', 'false');
      setTimeout(() => map.invalidateSize(), 100);
    }
  };

  const closeMap = () => {
    interactive.setAttribute('aria-hidden', 'true');
  };

  // Attach event listeners
  if (openBtn) {
    openBtn.addEventListener('click', openMap);
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', closeMap);
  }

  // Handle escape key to close map
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && interactive.getAttribute('aria-hidden') === 'false') {
      closeMap();
    }
  });

  if (!interactive) {
    setupMap();
  }
};


const addMarkers = (map, locations) => {
  if (!locations || !locations.length) return;

  locations.forEach(location => {
    if (!location.lat || !location.lng || !location.type || location.hide === 'true') return;

    var marker;

    let markerSizeVal = location.size ? parseInt(location.size) : 1;
    marker = L.circle([location.lat, location.lng], {
      className: `leaflet-map__marker leaflet-map__marker--style-${location.type}`,
      fillOpacity: 1,
      radius: 10 + markerSizeVal * 15
    });

    if (location.title || location.description) {
      const content = `
        <div class="leaflet-map__popup">
          ${location.title ? `<h4>${location.title}</h4>` : ''}
          ${location.description ? `<p>${location.description}</p>` : ''}
        </div>
      `;
      marker.bindPopup(content);
      if (location.tooltip === 'true') {
        marker.bindTooltip(`<h5>${location.title}</h5>`, {permanent: true, direction: 'top', offset: [0, -10]});
      }
    }

    marker.addTo(map);
  });
};

const addPaths = (map, paths) => {
  if (!paths || !paths.length) return;

  paths.forEach(path => {
    if (!path.points || !path.points.length) return;

    const points = path.points.map(point => [point.lat, point.lng]);

    if (points.length > 1) {
      const polyline = L.polyline(points, {
        color: path.color || '#3388ff',
        weight: 2,
        opacity: 0.7
      }).addTo(map);

      if (path.title) {

        const content = `
        <div class="leaflet-map__popup">
          ${path.title ? `<h4>${path.title}</h4>` : ''}
        </div>
      `;
        polyline.bindPopup(content);
        if (path.tooltip === 'true') {
          polyline.bindTooltip(`<h5>${path.title}</h5>`, {permanent: true, direction: 'top', offset: [0, -10]});
        }
      }
    }
  });
};

// Initialize maps when DOM is ready
document.addEventListener('DOMContentLoaded', initArillasMap);

