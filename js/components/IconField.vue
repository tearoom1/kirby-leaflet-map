<template>
  <k-field v-bind="$props" class="k-leaflet-icon-field">
    <div class="k-leaflet-icon-grid" role="radiogroup" :aria-label="label">
      <button
        type="button"
        role="radio"
        class="k-leaflet-icon-option k-leaflet-icon-none"
        :aria-checked="!value"
        :title="$t('tearoom1.leaflet-map.icon.none')"
        :disabled="disabled"
        @click="select(null)"
      />
      <button
        v-for="icon in icons"
        :key="icon.value"
        type="button"
        role="radio"
        class="k-leaflet-icon-option"
        :style="{ '--marker': color, '--symbol': symbolColor }"
        :aria-checked="value === icon.value"
        :aria-label="icon.label"
        :title="icon.label"
        :disabled="disabled"
        @click="select(icon.value)"
        v-html="icon.svg"
      />
    </div>
    <p class="k-leaflet-icon-label">{{ selectedLabel }}</p>
  </k-field>
</template>

<script>
import { contrast } from '../helpers/icon.js'

export default {
  inheritAttrs: false,

  props: {
    label: String,
    help: String,
    name: String,
    disabled: Boolean,
    required: Boolean,
    value: String,
    icons: {
      type: Array,
      default: () => []
    }
  },

  emits: ['input'],

  computed: {
    // symbols are shown on the marker color of the location
    color () {
      return this.entryColor() || '#3388ff'
    },

    symbolColor () {
      return contrast(this.color)
    },

    selectedLabel () {
      return this.icons.find(icon => icon.value === this.value)?.label ?? this.$t('tearoom1.leaflet-map.icon.none')
    }
  },

  methods: {
    select (value) {
      this.$emit('input', value)
    },

    entryColor () {
      let parent = this.$parent
      for (let i = 0; parent && i < 6; i++, parent = parent.$parent) {
        const values = parent.$props?.value
        if (values && typeof values === 'object' && !Array.isArray(values) && 'color' in values) {
          return values.color
        }
      }

      return null
    }
  }
}
</script>

<style>
.k-leaflet-icon-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(2.25rem, 1fr));
  gap: .375rem;
  padding: .5rem;
  background: var(--input-color-back, var(--color-white));
  border-radius: var(--rounded);
  box-shadow: var(--shadow);
}

.k-leaflet-icon-option {
  display: grid;
  place-items: center;
  aspect-ratio: 1;
  width: 100%;
  max-width: 2.25rem;
  border-radius: 50%;
  background: var(--marker);
  color: var(--symbol);
  border: 2px solid transparent;
  outline-offset: 2px;
  cursor: pointer;
  opacity: .55;
  transition: opacity .15s;
}

.k-leaflet-icon-option svg {
  width: 58%;
  height: 58%;
}

.k-leaflet-icon-option:hover,
.k-leaflet-icon-option:focus-visible {
  opacity: 1;
}

.k-leaflet-icon-option[aria-checked="true"] {
  opacity: 1;
  outline: 2px solid var(--color-focus);
}

.k-leaflet-icon-none {
  background: transparent;
  border: 2px dashed var(--color-border);
  opacity: 1;
}

.k-leaflet-icon-option:disabled {
  cursor: not-allowed;
}

.k-leaflet-icon-label {
  margin-top: .375rem;
  font-size: var(--text-xs);
  color: var(--color-text-dimmed);
}
</style>
