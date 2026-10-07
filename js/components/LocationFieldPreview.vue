<template>
  <p class="k-leaflet-location-field-preview">{{ text }}</p>
</template>

<script>
// Structure table cell for the leaflet-location field
export default {
  props: {
    value: [Object, String],
    row: Object,
    column: Object,
    field: Object
  },

  computed: {
    text () {
      // fall back to the former lat/lng fields of existing entries
      const location = this.value && typeof this.value === 'object'
        ? this.value
        : { lat: this.row?.lat, lng: this.row?.lng }

      if (location.lat === undefined || location.lat === '' || location.lat === null) {
        return '–'
      }

      return Number(location.lat).toFixed(5) + ', ' + Number(location.lng).toFixed(5)
    }
  }
}
</script>

<style>
.k-leaflet-location-field-preview {
  padding: 0 var(--table-cell-padding, .75rem);
  font-family: var(--font-mono);
  font-size: var(--text-xs);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
