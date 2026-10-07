<template>
  <k-field
    v-bind="$props"
    :input="_uid"
    class="k-leaflet-location-field"
  >
    <div class="k-leaflet-field">
      <k-leaflet-address-search v-if="search && !disabled" @select="choose" />

      <div
        ref="map"
        class="k-leaflet-map"
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

      <p v-if="!location && !disabled" class="k-leaflet-hint">
        {{ $t('tearoom1.leaflet-map.location.empty') }}
      </p>
    </div>
  </k-field>
</template>

<script>
import L from 'leaflet'
import MapMixin, { normalize, round } from '../mixins/map.js'

export default {
  mixins: [MapMixin],

  props: {
    value: [Object, String]
  },

  data () {
    return {
      location: normalize(this.value),
      map: null,
      marker: null
    }
  },

  watch: {
    value (value) {
      this.location = normalize(value)
      this.updateMarker()
    }
  },

  mounted () {
    // existing entries only have the former lat/lng fields: adopt them,
    // so they are stored in the new format with the next save
    if (!this.location) {
      const legacy = normalize(this.entryValues('lat'))
      if (legacy) {
        this.location = legacy
        this.$nextTick(() => this.$emit('input', legacy))
      }
    }

    this.map = this.createMap(this.$refs.map)

    if (this.location) {
      this.map.setView([this.location.lat, this.location.lng], 13)
    }

    if (!this.disabled) {
      this.map.on('click', (event) => this.set(event.latlng.lat, event.latlng.lng))
    }

    this.updateMarker()
  },

  methods: {
    choose (result) {
      this.set(result.lat, result.lng)
      this.map.setView([result.lat, result.lng], 16)
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
          className: 'k-leaflet-marker',
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
.k-leaflet-field {
  display: grid;
  gap: var(--spacing-2);
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
</style>
