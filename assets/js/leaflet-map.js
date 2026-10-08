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
  const paths = mapData.paths || [];
  // an explicit center wins, otherwise the map shows all locations and paths
  const centerLocation = locations.find(location => location.center);
  const points = [
    ...locations.map(location => [location.lat, location.lng]),
    ...paths.flatMap(path => path.points)
  ];

  const zoom = mapData.zoom || {};
  const tiles = mapData.tiles || {};
  let map = null;

  const setupMap = () => {
    // Create map with a slight delay to ensure container is visible
    setTimeout(() => {
      map = L.map(mapElement, {
        minZoom: zoom.min || 10,
        maxZoom: zoom.max || 19,
        // half steps let the automatic view fit the locations more closely
        zoomSnap: 0.5
      });

      if (centerLocation || points.length < 2) {
        const center = centerLocation ? [centerLocation.lat, centerLocation.lng] : (points[0] || [0, 0]);
        map.setView(center, zoom.default || 15);
      } else {
        // allow zooming out below the minimum if that's needed to show all locations
        // extra room at the top for the tooltips above the markers
        const padding = { paddingTopLeft: [30, 50], paddingBottomRight: [30, 20] };
        const fitZoom = map.getBoundsZoom(points, false, L.point(60, 70));
        if (fitZoom < map.getMinZoom()) {
          map.setMinZoom(fitZoom);
        }
        // never zoom in further than the default zoom when fitting
        map.fitBounds(points, { ...padding, maxZoom: zoom.default || 15 });
      }

      // the selected map style, the others only if visitors may switch
      const tileLayers = (mapData.layers && mapData.layers.length ? mapData.layers : [tiles]).map(layer =>
        L.tileLayer(layer.url || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: layer.attribution || '',
          // zoom in further than the style offers by scaling its tiles
          maxNativeZoom: layer.maxZoom || 19,
          maxZoom: zoom.max || 19,
          // tile servers like OSM require a referrer, even if the site restricts it
          referrerPolicy: 'strict-origin-when-cross-origin'
        })
      );
      tileLayers[0].addTo(map);

      if (tileLayers.length > 1) {
        const choices = {};
        mapData.layers.forEach((layer, index) => { choices[layer.label] = tileLayers[index]; });
        L.control.layers(choices, null, { position: 'topright' }).addTo(map);
      }

      const labels = mapData.labels || 'individual';
      addMarkers(map, locations, labels, mapData.icons || {});
      addPaths(map, paths, labels);

      // permanent tooltips overlap when zoomed out far, hide them there
      const tooltipZoom = map.getZoom() - 1.5;
      const toggleTooltips = () => mapElement.classList.toggle('leaflet-map--far', map.getZoom() < tooltipZoom);
      map.on('zoomend', toggleTooltips);

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

  // no heading elements: they would pick up the heading styles of the website
  if (title) {
    const heading = document.createElement('strong');
    heading.className = 'leaflet-map__popup-title';
    heading.textContent = title;
    wrapper.appendChild(heading);
  }

  if (descriptionHtml) {
    const text = document.createElement('div');
    text.className = 'leaflet-map__popup-text';
    text.innerHTML = descriptionHtml;
    wrapper.appendChild(text);
  }

  return wrapper;
};

const tooltipContent = (title) => {
  const label = document.createElement('span');
  label.className = 'leaflet-map__label';
  label.textContent = title;
  return label;
};

// label above the marker: always visible, on hover only or none (map setting)
const bindLabel = (layer, title, individual, mode) => {
  if (!title || mode === 'none') return;

  const permanent = mode === 'always' || (mode === 'individual' && individual);

  layer.bindTooltip(tooltipContent(title), {
    permanent,
    direction: 'top',
    offset: [0, -10],
    className: permanent ? 'leaflet-map__tooltip is-permanent' : 'leaflet-map__tooltip'
  });
};

/**
 * Round marker with the symbol, sized like the plain markers would be.
 * The symbol markup comes from the plugin or the site configuration.
 */
const iconMarker = (location, svg) => {
  const diameter = 22 + (location.size || 1) * 4;
  const element = document.createElement('span');
  element.className = 'leaflet-map__symbol';
  element.style.setProperty('--marker', location.color || '#3388ff');
  element.style.setProperty('--symbol', location.iconColor || '#ffffff');
  element.innerHTML = svg;

  const marker = L.marker([location.lat, location.lng], {
    icon: L.divIcon({
      className: 'leaflet-map__symbol-marker',
      html: element,
      iconSize: [diameter, diameter],
      iconAnchor: [diameter / 2, diameter / 2],
      popupAnchor: [0, -diameter / 2],
      tooltipAnchor: [0, -diameter / 2 + 6]
    }),
    title: location.title || '',
    keyboard: true
  });

  return marker;
};

const addMarkers = (map, locations, labels, icons) => {
  locations.forEach(location => {
    const marker = location.icon && icons[location.icon]
      ? iconMarker(location, icons[location.icon])
      // radius in pixels, so markers keep their size at every zoom level
      : L.circleMarker([location.lat, location.lng], {
        className: 'leaflet-map__marker',
        fillOpacity: 1,
        fillColor: location.color || '#3388ff',
        color: '#ffffff',
        weight: 2,
        radius: 5 + (location.size || 1) * 2
      });

    if (location.title || location.description) {
      marker.bindPopup(popupContent(location.title, location.description));
    }

    bindLabel(marker, location.title, location.tooltip, labels);

    marker.addTo(map);
  });
};

const addPaths = (map, paths, labels) => {
  paths.forEach(path => {
    const polyline = L.polyline(path.points, {
      color: path.color || '#3388ff',
      weight: 4,
      opacity: 0.85,
      lineCap: 'round',
      lineJoin: 'round'
    }).addTo(map);

    if (path.title) {
      polyline.bindPopup(popupContent(path.title));
    }

    bindLabel(polyline, path.title, path.tooltip, labels);
  });
};

// Initialize maps when DOM is ready
document.addEventListener('DOMContentLoaded', initLeafletMaps);
