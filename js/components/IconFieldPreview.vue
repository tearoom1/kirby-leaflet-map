<template>
  <div class="k-leaflet-icon-field-preview">
    <span
      v-if="icon"
      class="k-leaflet-icon-preview-symbol"
      :style="{ '--marker': color, '--symbol': symbolColor }"
      :title="icon.label"
      v-html="icon.svg"
    />
  </div>
</template>

<script>
import { contrast } from '../helpers/icon.js'

// Structure table cell for the leaflet-icon field
export default {
  props: {
    value: String,
    row: Object,
    column: Object,
    field: Object
  },

  computed: {
    icon () {
      return (this.field?.icons ?? this.column?.icons ?? []).find(icon => icon.value === this.value)
    },

    color () {
      return this.row?.color || '#3388ff'
    },

    symbolColor () {
      return contrast(this.color)
    }
  }
}
</script>

<style>
.k-leaflet-icon-field-preview {
  padding: 0 var(--table-cell-padding, .75rem);
}

.k-leaflet-icon-preview-symbol {
  display: grid;
  place-items: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  background: var(--marker);
  color: var(--symbol);
}

.k-leaflet-icon-preview-symbol svg {
  width: 60%;
  height: 60%;
}
</style>
