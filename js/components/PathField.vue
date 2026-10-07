<template>
  <k-field
    v-bind="$props"
    :input="_uid"
    class="k-leaflet-path-field"
  >
    <div class="k-leaflet-field">
      <k-leaflet-address-search v-if="search && !disabled" @select="jumpTo" />

      <div
        ref="map"
        class="k-leaflet-map"
        :class="{ 'is-disabled': disabled }"
      />

      <div class="k-leaflet-path-bar">
        <span class="k-leaflet-hint">
          {{ points.length }} {{ $t('tearoom1.leaflet-map.path.points') }} ·
          {{ $t('tearoom1.leaflet-map.path.help') }}
        </span>
        <k-button-group v-if="points.length && !disabled">
          <k-button
            icon="undo"
            size="sm"
            variant="filled"
            :text="$t('tearoom1.leaflet-map.path.undo')"
            @click="removePoint(points.length - 1)"
          />
          <k-button
            icon="trash"
            size="sm"
            variant="filled"
            :title="$t('tearoom1.leaflet-map.path.clear')"
            @click="clear"
          />
        </k-button-group>
      </div>
    </div>
  </k-field>
</template>

<script>
import L from 'leaflet'
import MapMixin, { normalize, round } from '../mixins/map.js'

const toPoints = (value) => (Array.isArray(value) ? value : [])
  .map(normalize)
  .filter(Boolean)

export default {
  mixins: [MapMixin],

  props: {
    value: [Array, String]
  },

  data () {
    return {
      points: toPoints(this.value),
      map: null,
      line: null,
      markers: []
    }
  },

  watch: {
    value (value) {
      // ignore the echo of our own input event
      if (JSON.stringify(toPoints(value)) !== JSON.stringify(this.points)) {
        this.points = toPoints(value)
        this.draw()
      }
    }
  },

  mounted () {
    // existing paths only have the former points structure: adopt it
    if (!this.points.length) {
      const legacy = toPoints(this.entryValues('points')?.points)
      if (legacy.length) {
        this.points = legacy
        this.$nextTick(() => this.$emit('input', this.points))
      }
    }

    this.map = this.createMap(this.$refs.map)
    // use the color of the path entry if there is one
    const color = this.entryValues('color')?.color || '#3388ff'
    this.line = L.polyline([], { color, weight: 4, opacity: 0.85 }).addTo(this.map)

    if (this.points.length > 1) {
      this.map.fitBounds(this.points.map(point => [point.lat, point.lng]), { padding: [30, 30], maxZoom: 16 })
    } else if (this.points.length === 1) {
      this.map.setView([this.points[0].lat, this.points[0].lng], 15)
    }

    if (!this.disabled) {
      this.map.on('click', (event) => this.addPoint(event.latlng))
    }

    this.draw()
  },

  methods: {
    jumpTo (result) {
      this.map.setView([result.lat, result.lng], 16)
    },

    addPoint (latlng) {
      this.points.push({ lat: round(latlng.lat), lng: round(latlng.lng) })
      this.update()
    },

    removePoint (index) {
      this.points.splice(index, 1)
      this.update()
    },

    clear () {
      this.points = []
      this.update()
    },

    update () {
      this.draw()
      this.$emit('input', this.points.length ? [...this.points] : null)
    },

    draw () {
      if (!this.map) {
        return
      }

      this.line.setLatLngs(this.points.map(point => [point.lat, point.lng]))
      this.markers.forEach(marker => marker.remove())

      this.markers = this.points.map((point, index) => {
        const marker = L.marker([point.lat, point.lng], {
          draggable: !this.disabled,
          title: this.$t('tearoom1.leaflet-map.path.remove'),
          icon: L.divIcon({
            className: 'k-leaflet-marker k-leaflet-path-point',
            html: String(index + 1),
            iconSize: [20, 20],
            iconAnchor: [10, 10]
          })
        }).addTo(this.map)

        if (!this.disabled) {
          marker.on('drag', () => {
            const latlngs = this.line.getLatLngs()
            latlngs[index] = marker.getLatLng()
            this.line.setLatLngs(latlngs)
          })
          marker.on('dragend', () => {
            const position = marker.getLatLng()
            this.points.splice(index, 1, { lat: round(position.lat), lng: round(position.lng) })
            this.update()
          })
          marker.on('click', () => this.removePoint(index))
        }

        return marker
      })
    }
  }
}
</script>

<style>
.k-leaflet-path-bar {
  display: flex;
  gap: var(--spacing-2);
  align-items: center;
  justify-content: space-between;
}

.k-leaflet-path-point {
  display: grid;
  place-items: center;
  font-size: 10px;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
}
</style>
