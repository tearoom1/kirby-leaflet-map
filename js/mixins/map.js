import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

export const round = (number) => Math.round(number * 1e6) / 1e6

export const normalize = (value) => {
  if (!value || typeof value !== 'object') {
    return null
  }

  // points of the former structure fields were stored as { location: { lat, lng } } or { lat, lng }
  const point = value.location && typeof value.location === 'object' ? value.location : value
  const lat = parseFloat(point.lat)
  const lng = parseFloat(point.lng)

  return Number.isFinite(lat) && Number.isFinite(lng) ? { lat, lng } : null
}

/**
 * Shared setup of the panel picker maps.
 */
export default {
  inheritAttrs: false,

  props: {
    label: String,
    help: String,
    name: String,
    disabled: Boolean,
    required: Boolean,
    center: Array,
    zoom: Number,
    search: Boolean,
    tiles: Object
  },

  beforeDestroy () {
    this.resizeObserver?.disconnect()
    this.map?.remove()
  },

  methods: {
    createMap (element) {
      const map = L.map(element, {
        center: this.center,
        zoom: this.zoom
      })

      L.tileLayer(this.tiles.url, {
        attribution: this.tiles.attribution,
        maxZoom: this.tiles.maxZoom,
        // the panel sends no referrer, which tile servers like OSM reject
        referrerPolicy: 'strict-origin-when-cross-origin'
      }).addTo(map)

      // the field is often rendered inside a drawer that is still animating
      this.resizeObserver = new ResizeObserver(() => map.invalidateSize())
      this.resizeObserver.observe(element)

      return map
    },

    /**
     * Values of the surrounding structure entry, used to adopt
     * the former lat/lng and points fields of existing content.
     */
    entryValues (key) {
      let parent = this.$parent
      for (let i = 0; parent && i < 6; i++, parent = parent.$parent) {
        const values = parent.$props?.value
        if (values && typeof values === 'object' && !Array.isArray(values) && key in values) {
          return values
        }
      }

      return null
    }
  }
}
