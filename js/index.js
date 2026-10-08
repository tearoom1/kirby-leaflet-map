/* global panel */

import AddressSearch from './components/AddressSearch.vue'
import LocationField from './components/LocationField.vue'
import LocationFieldPreview from './components/LocationFieldPreview.vue'
import PathField from './components/PathField.vue'

panel.plugin('tearoom1/leaflet-map', {
  fields: {
    'leaflet-location': LocationField,
    'leaflet-path': PathField
  },
  components: {
    'k-leaflet-address-search': AddressSearch,
    'k-leaflet-location-field-preview': LocationFieldPreview
  }
})
