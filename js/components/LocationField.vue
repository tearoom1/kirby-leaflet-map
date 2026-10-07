<template>
  <k-field
    v-bind="$props"
    :input="_uid"
    class="k-leaflet-location-field"
  >
    <div class="k-leaflet-location">
      <form
        v-if="search && !disabled"
        class="k-leaflet-location-search"
        @submit.prevent="find"
      >
        <k-input
          type="text"
          icon="search"
          :value="query"
          :placeholder="$t('tearoom1.leaflet-map.location.search')"
          @input="query = $event"
        />
        <ul v-if="results.length" class="k-leaflet-location-results">
          <li v-for="(result, index) in results" :key="index">
            <button type="button" @click="choose(result)">{{ result.label }}</button>
          </li>
        </ul>
        <p v-else-if="searched" class="k-leaflet-location-hint">
          {{ $t('tearoom1.leaflet-map.location.noResults') }}
        </p>
      </form>

      <div
        ref="map"
        class="k-leaflet-location-map"
        :class="{ 'is-disabled': disabled }"
      />

      <div class="k-leaflet-location-bar">
        <k-input
          type="number"
          :value="location ? location.lat : null"
          :step="0.000001"
          :min="-90"
          :max="90"
          :disabled="disabled"
          placeholder="Latitude"
          @input="setCoordinate('lat', $event)"
        />
        <k-input
          type="number"
          :value="location ? location.lng : null"
          :step="0.000001"
          :min="-180"
          :max="180"
          :disabled="disabled"
          placeholder="Longitude"
          @input="setCoordinate('lng', $event)"
        />
        <k-button
          v-if="location && !disabled"
          icon="trash"
          size="sm"
          variant="filled"
          :title="$t('tearoom1.leaflet-map.location.clear')"
          @click="clear"
        />
      </div>

      <p v-if="!location && !disabled" class="k-leaflet-location-hint">
        {{ $t('tearoom1.leaflet-map.location.empty') }}
      </p>
    </div>
  </k-field>
</template>

<script>
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const round = (number) => Math.round(number * 1e6) / 1e6

export default {
  inheritAttrs: false,

  props: {
    label: String,
    help: String,
    name: String,
    disabled: Boolean,
    required: Boolean,
    value: [Object, String],
    center: Array,
    zoom: Number,
    search: Boolean,
    tiles: Object
  },

  data () {
    return {
      location: this.normalize(this.value),
      map: null,
      marker: null,
      query: '',
      results: [],
      searched: false
    }
  },

  watch: {
    value (value) {
      this.location = this.normalize(value)
      this.updateMarker()
    }
  },

  mounted () {
    // existing entries only have the former lat/lng fields
    if (!this.location) {
      this.location = this.legacyLocation()
    }

    this.map = L.map(this.$refs.map, {
      center: this.location ? [this.location.lat, this.location.lng] : this.center,
      zoom: this.location ? 13 : this.zoom,
      attributionControl: true
    })

    L.tileLayer(this.tiles.url, {
      attribution: this.tiles.attribution,
      maxZoom: this.tiles.maxZoom,
      // the panel sends no referrer, which tile servers like OSM reject
      referrerPolicy: 'strict-origin-when-cross-origin'
    }).addTo(this.map)

    if (!this.disabled) {
      this.map.on('click', (event) => this.set(event.latlng.lat, event.latlng.lng))
    }

    this.updateMarker()

    // the field is often rendered inside a drawer that is still animating
    this.resizeObserver = new ResizeObserver(() => this.map && this.map.invalidateSize())
    this.resizeObserver.observe(this.$refs.map)
  },

  beforeDestroy () {
    this.resizeObserver?.disconnect()
    this.map?.remove()
  },

  methods: {
    normalize (value) {
      if (!value || typeof value !== 'object') {
        return null
      }

      const lat = parseFloat(value.lat)
      const lng = parseFloat(value.lng)

      return Number.isFinite(lat) && Number.isFinite(lng) ? { lat, lng } : null
    },

    legacyLocation () {
      // walk up to the structure entry form and read its lat/lng values
      let parent = this.$parent
      for (let i = 0; parent && i < 6; i++, parent = parent.$parent) {
        const values = parent.$props?.value
        if (values && typeof values === 'object' && 'lat' in values) {
          return this.normalize(values)
        }
      }

      return null
    },

    set (lat, lng) {
      this.location = { lat: round(lat), lng: round(lng) }
      this.updateMarker()
      this.$emit('input', this.location)
    },

    setCoordinate (key, value) {
      const number = parseFloat(value)
      if (!Number.isFinite(number)) {
        return
      }

      const location = this.location || { lat: 0, lng: 0 }
      location[key] = number
      this.set(location.lat, location.lng)
      this.map.panTo([this.location.lat, this.location.lng])
    },

    async find () {
      // search on submit only: the geocoding service forbids search-as-you-type
      if (this.query.trim().length < 3) {
        return
      }

      this.results = await this.$api.get('leaflet-map/geocode', { q: this.query })
      this.searched = true
    },

    choose (result) {
      this.set(result.lat, result.lng)
      this.map.setView([result.lat, result.lng], 16)
      this.results = []
      this.searched = false
      this.query = ''
    },

    clear () {
      this.location = null
      this.updateMarker()
      this.$emit('input', null)
    },

    updateMarker () {
      if (!this.map) {
        return
      }

      if (!this.location) {
        this.marker?.remove()
        this.marker = null
        return
      }

      const latlng = [this.location.lat, this.location.lng]

      if (this.marker) {
        this.marker.setLatLng(latlng)
        return
      }

      this.marker = L.marker(latlng, {
        draggable: !this.disabled,
        icon: L.divIcon({
          className: 'k-leaflet-location-marker',
          iconSize: [18, 18],
          iconAnchor: [9, 9]
        })
      }).addTo(this.map)

      this.marker.on('dragend', () => {
        const position = this.marker.getLatLng()
        this.set(position.lat, position.lng)
      })
    }
  }
}
</script>

<style>
.k-leaflet-location {
  display: grid;
  gap: var(--spacing-2);
}

.k-leaflet-location-search {
  position: relative;
}

.k-leaflet-location-search .k-input {
  background: var(--input-color-back, var(--color-white));
  border-radius: var(--rounded);
}

.k-leaflet-location-results {
  margin-top: var(--spacing-1);
  border-radius: var(--rounded);
  background: var(--input-color-back, var(--color-white));
  box-shadow: var(--shadow);
  overflow: hidden;
}

.k-leaflet-location-results button {
  display: block;
  width: 100%;
  padding: var(--spacing-2) var(--spacing-3);
  text-align: start;
  font-size: var(--text-sm);
}

.k-leaflet-location-results li + li {
  border-top: 1px solid var(--color-border);
}

.k-leaflet-location-results button:hover,
.k-leaflet-location-results button:focus-visible {
  background: var(--color-gray-200, rgba(0, 0, 0, .05));
}

.k-leaflet-location-map {
  height: 16rem;
  border-radius: var(--rounded);
  box-shadow: var(--shadow);
  z-index: 0;
  cursor: crosshair;
}

.k-leaflet-location-map.is-disabled {
  cursor: default;
  opacity: .8;
}

.k-leaflet-location-bar {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: var(--spacing-2);
  align-items: center;
}

.k-leaflet-location-bar .k-input {
  background: var(--input-color-back, var(--color-white));
  border-radius: var(--rounded);
}

.k-leaflet-location-hint {
  font-size: var(--text-xs);
  color: var(--color-text-dimmed);
}

.k-leaflet-location-marker {
  border: 3px solid #fff;
  border-radius: 50%;
  background: var(--color-blue-600, #3388ff);
  box-shadow: 0 1px 4px rgba(0, 0, 0, .4);
}
</style>
