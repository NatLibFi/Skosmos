/* global Vue */
/* global SKOSMOS */

const tabAlphaApp = Vue.createApp({
  data () {
    return {
      indexLetters: [],
      indexConcepts: [],
      selectedLetter: ''
    }
  },
  mounted () {
  },
  methods: {
    loadLetters () {
      if (this.indexLetters.length === 0) {
        fetch('rest/v1/' + SKOSMOS.vocab + '/index/?lang=' + SKOSMOS.lang)
          .then(data => {
            return data.json()
          })
          .then(data => {
            console.log(data)
            this.indexLetters = data.indexLetters
            this.loadConcepts(this.indexLetters[0])
          })
      }
    },
    loadConcepts (letter) {
      this.selectedLetter = letter
      fetch('https://api.finto.fi/rest/v1/' + SKOSMOS.vocab + '/index/' + this.selectedLetter + '?lang=' + SKOSMOS.lang + '&limit=50')
        .then(data => {
          return data.json()
        })
        .then(data => {
          console.log(data)
          this.indexConcepts = data.indexConcepts
        })
    }
  },
  template: `
    <div v-click-tab-alpha="loadLetters">
      <tab-alpha :index-letters="indexLetters" :index-concepts="indexConcepts" v-if="indexLetters" @load-concepts="loadConcepts($event)"></tab-alpha>
    </div>
  `
})

/* Custom directive used to add an event listened on clicks on an element outside of this component */
tabAlphaApp.directive('click-tab-alpha', {
  beforeMount: (el, binding) => {
    el.clickTabEvent = event => {
      binding.value() // calling the method given as the attribute value
    }
    document.querySelector('#alpha').addEventListener('click', el.clickTabEvent) // registering an event listener on clicks on the alpha nav-item element
  },
  unmounted: el => {
    document.querySelector('#alpha').removeEventListener('click', el.clickTabEvent)
  }
})

tabAlphaApp.component('tab-alpha', {
  props: ['indexLetters', 'indexConcepts'],
  emits: ['loadConcepts'],
  methods: {
    loadConcepts (event, letter) {
      event.preventDefault()
      this.$emit('loadConcepts', letter)
    },
    getHref (uri) {
      const clangParam = (SKOSMOS.content_lang !== SKOSMOS.lang) ? 'clang=' + SKOSMOS.content_lang : ''
      let clangSeparator = '?'
      let page = ''

      if (uri.indexOf(SKOSMOS.uri_space) !== -1) {
        page = uri.substr(SKOSMOS.uri_space.length)

        if (/[^a-zA-Z0-9-_\.~]/.test(page) || page.indexOf('/') > -1) {
          // contains special characters or contains an additional '/' - fall back to full URI
          page = '?uri=' + encodeURIComponent(uri)
          clangSeparator = '&'
        }
      } else {
        // not within URI space - fall back to full URI
        page = '?uri=' + encodeURIComponent(uri)
        clangSeparator = '&'
      }

      return SKOSMOS.vocab + '/' + SKOSMOS.lang + '/page/' + page + (clangParam !== '' ? clangSeparator + clangParam : '')
    },
    partialPageLoad (event, pageUri, conceptUri) {
      event.preventDefault()

      fetch(pageUri)
        .then(data => {
          return data.text()
        })
        .then(data => {
          console.log(data)

          // concept card HTML
          const conceptHTML = document.createElement('div')
          conceptHTML.innerHTML = data.trim()

          // inserting concept card into vocab info
          const mainContent = document.querySelector('#main-content')
          mainContent.innerHTML = ''
          mainContent.appendChild(conceptHTML.querySelector('#main-content'))

          // what other properties should be changed?
          SKOSMOS.uri = conceptUri
          conceptMappingsApp.mount('#concept-mappings') // Vue apps can't be mounted multiple times so opening another concept page breaks mappings component
        })
    }
  },
  template: `
    <ul class="pagination">
      <li v-for="letter in indexLetters" class="page-item">
        <a class="page-link" href="#" @click="loadConcepts($event, letter)" style="background-color: darkblue;">{{ letter }}</a>
      </li>
    </ul>

    <ul class="list-group" id="alpha-list">
      <li v-for="concept in indexConcepts" class="list-group-item py-1">
        <template v-if="concept.altLabel">{{ concept.altLabel }} -> </template>
        <a :href="getHref(concept.uri)" @click="partialPageLoad($event, getHref(concept.uri), concept.uri)">{{ concept.prefLabel }}</a>
      </li>
    </ul>
  `
})

tabAlphaApp.mount('#tab-alpha')
