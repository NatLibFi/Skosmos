/* global Vue */
/* global SKOSMOS */

const conceptMappingsApp = Vue.createApp({
  data () {
    return {
      mappings: []
    }
  },
  provide: {
    content_lang: SKOSMOS.content_lang
  },
  methods: {
    loadMappings () {
      fetch('https://api.finto.fi/rest/v1/' + SKOSMOS.vocab + '/mappings?uri=' + SKOSMOS.uri + '&external=true&clang=' + SKOSMOS.lang + '&lang=' + SKOSMOS.content_lang)
        .then(data => {
          return data.json()
        })
        .then(data => {
          console.log(data.mappings)
          this.mappings = this.group_by(data.mappings, 'typeLabel')
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
    // SKOSMOS variable should maybe have a separate property for current page
    if (SKOSMOS.uri) {
      this.loadMappings()
    }
  },
  template: `
    <div v-load-concept-page="loadMappings">
      <concept-mappings :mappings="mappings" v-if="mappings.length !== 0"></concept-mappings>
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
  props: ['mappings'],
  inject: ['content_lang'],
  template: `
    <table>
      <tr v-for="mapping in Object.entries(mappings)">
        <td :title="mapping[1][0].description">{{ mapping[0]}}</td>
        <td>
          <template v-for="m in mapping[1]">
            <a :href="m.hrefLink">{{ m.prefLabel }}</a>
            <span v-if="m.lang !== this.content_lang"> ({{ m.lang }})</span><br>
          </template>
        </td>
        <td>
          <template v-for="m in mapping[1]">{{ m.vocabName }}<br></template>
        </td>
      </tr>
    </table>
  `
})

conceptMappingsApp.mount('#concept-mappings')
