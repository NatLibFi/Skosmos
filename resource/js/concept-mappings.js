/* global Vue */
/* global fetchWithAbort */

const conceptMappingsApp = Vue.createApp({
  data () {
    return {
      mappings: {},
      loading: false
    }
  },
  provide: {
    content_lang: window.SKOSMOS.content_lang
  },
  computed: {
    hasMappings () {
      return Object.keys(this.mappings).length > 0
    },
    customLabels () {
      return window.SKOSMOS.customLabels
    }
  },
  methods: {
    loadMappings () {
      this.mappings = {} // clear mappings before starting to load new ones
      this.loading = true
      const params = new URLSearchParams({
        uri: window.SKOSMOS.uri,
        external: 'true',
        lang: window.SKOSMOS.lang,
        clang: window.SKOSMOS.content_lang
      })
      const url = `rest/v1/${window.SKOSMOS.vocab}/mappings?${params.toString()}`

      fetchWithAbort(url, 'concept')
        .then(data => {
          return data.json()
        })
        .then(data => {
          this.mappings = this.group_by(data.mappings, 'typeLabel')
          this.loading = false
        })
        .catch(error => {
          if (error.name === 'AbortError') {
            console.log('Fetching of mappings aborted')
          } else {
            throw error
          }
          this.loading = false
        })
    },
    // from https://stackoverflow.com/a/71505541
    group_by (arr, prop) {
      return arr.reduce(function (ret, x) {
        if (!ret[x[prop]]) { ret[x[prop]] = [] }
        ret[x[prop]].push(x)
        return ret
      }, {})
    }
  },
  mounted () {
    // Only load mappings when on the concept page
    // window.SKOSMOS variable should maybe have a separate property for current page
    if (window.SKOSMOS.uri) {
      this.loadMappings()
    }
  },
  template: `
    <div v-load-concept-page="loadMappings">
      <template v-if="loading">
        <div class="main-content-section py-4">
          <i class="fa-solid fa-spinner fa-spin-pulse"></i>
        </div>
      </template>
      <template v-else-if="hasMappings">
        <div class="main-content-section py-4">
          <concept-mappings :mappings="mappings" :customLabels="customLabels"></concept-mappings>
        </div>
      </template>
    </div>
  `
})

/* Custom directive used to add an event listener for loading a concept page */
conceptMappingsApp.directive('load-concept-page', {
  mounted: (el, binding) => {
    el.loadConceptPageEvent = event => {
      binding.value() // calling the method given as the attribute value (loadMappings)
    }
    document.addEventListener('loadConceptPage', el.loadConceptPageEvent) // registering an event listener for loading a concept page
  },
  unmounted: el => {
    document.removeEventListener('loadConceptPage', el.loadConceptPageEvent)
  }
})

conceptMappingsApp.component('concept-mappings', {
  props: ['mappings', 'customLabels'],
  inject: ['content_lang'],
  template: `
    <div class="row property prop-mapping" v-for="(mapping, label) in mappings">
      <template v-if="customLabels">
        <div class="col-lg-4 ps-0 property-label" :title="customLabels[mapping[0].type[0]][1] || mapping[0].description">
          <h2>{{ customLabels[mapping[0].type[0]][0] }}</h2>
        </div>
      </template>
      <template v-else>
        <div class="col-lg-4 ps-0 property-label" :title="mapping[0].description"><h2>{{ label }}</h2></div>
      </template>
      <div class="col-lg-8 gx-0 gx-lg-4">
        <div class="row mb-2" v-for="m in mapping">
          <div class="col-5 prop-mapping-label">
            <a :href="m.hrefLink">{{ m.prefLabel }}</a><span v-if="m.lang && m.lang !== this.content_lang"> ({{ m.lang }})</span>
          </div>
          <div class="col-7 prop-mapping-vocab">{{ m.vocabName }}</div>
        </div>
      </div>
    </div>
  `
})

conceptMappingsApp.mount('#concept-mappings')
