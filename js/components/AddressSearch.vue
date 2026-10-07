<template>
  <form class="k-leaflet-search" @submit.prevent="find">
    <k-input
      type="text"
      icon="search"
      :value="query"
      :placeholder="$t('tearoom1.leaflet-map.location.search')"
      @input="query = $event"
    />
    <ul v-if="results.length" class="k-leaflet-search-results">
      <li v-for="(result, index) in results" :key="index">
        <button type="button" @click="choose(result)">{{ result.label }}</button>
      </li>
    </ul>
    <p v-else-if="searched" class="k-leaflet-hint">
      {{ $t('tearoom1.leaflet-map.location.noResults') }}
    </p>
  </form>
</template>

<script>
export default {
  emits: ['select'],

  data () {
    return {
      query: '',
      results: [],
      searched: false
    }
  },

  methods: {
    async find () {
      // search on submit only: the geocoding service forbids search-as-you-type
      if (this.query.trim().length < 3) {
        return
      }

      this.results = await this.$api.get('leaflet-map/geocode', { q: this.query })
      this.searched = true
    },

    choose (result) {
      this.$emit('select', result)
      this.results = []
      this.searched = false
      this.query = ''
    }
  }
}
</script>

<style>
.k-leaflet-search .k-input {
  background: var(--input-color-back, var(--color-white));
  border-radius: var(--rounded);
}

.k-leaflet-search-results {
  margin-top: var(--spacing-1);
  border-radius: var(--rounded);
  background: var(--input-color-back, var(--color-white));
  box-shadow: var(--shadow);
  overflow: hidden;
}

.k-leaflet-search-results button {
  display: block;
  width: 100%;
  padding: var(--spacing-2) var(--spacing-3);
  text-align: start;
  font-size: var(--text-sm);
}

.k-leaflet-search-results li + li {
  border-top: 1px solid var(--color-border);
}

.k-leaflet-search-results button:hover,
.k-leaflet-search-results button:focus-visible {
  background: var(--color-gray-200, rgba(0, 0, 0, .05));
}

.k-leaflet-hint {
  font-size: var(--text-xs);
  color: var(--color-text-dimmed);
}

.k-leaflet-map {
  height: 16rem;
  border-radius: var(--rounded);
  box-shadow: var(--shadow);
  z-index: 0;
  cursor: crosshair;
}

.k-leaflet-map.is-disabled {
  cursor: default;
  opacity: .8;
}

.k-leaflet-marker {
  border: 3px solid #fff;
  border-radius: 50%;
  background: var(--color-blue-600, #3388ff);
  box-shadow: 0 1px 4px rgba(0, 0, 0, .4);
}
</style>
