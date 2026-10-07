/* global panel */

import LocationField from './components/LocationField.vue'
import LocationFieldPreview from './components/LocationFieldPreview.vue'

panel.plugin('tearoom1/kirby-leaflet-map', {
  fields: {
    'leaflet-location': LocationField
  },
  components: {
    'k-leaflet-location-field-preview': LocationFieldPreview
  }
})
