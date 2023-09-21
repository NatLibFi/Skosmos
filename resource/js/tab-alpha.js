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
    // load alphabetical index if alphabetical tab is active when the page is first opened (otherwise only load the index when the tab is clicked)
    // this should probably be done differently
    if (document.querySelector('#alphabetical > a').classList.contains('active')) {
      this.loadLetters()
    }
  },
  methods: {
    loadLetters () {
      // only load index the first time the page is opened or the alphabetical tab is clicked
      if (this.indexLetters.length === 0) {
        fetch('rest/v1/' + SKOSMOS.vocab + '/index/?lang=' + SKOSMOS.lang)
          .then(data => {
            return data.json()
          })
          .then(data => {
            this.indexLetters = data.indexLetters
            this.loadConcepts(this.indexLetters[0])
          })
      }
    },
    loadConcepts (letter) {
      this.selectedLetter = letter
      fetch('rest/v1/' + SKOSMOS.vocab + '/index/' + this.selectedLetter + '?lang=' + SKOSMOS.lang + '&limit=50')
        .then(data => {
          return data.json()
        })
        .then(data => {
          this.indexConcepts = data.indexConcepts
        })
    }
  },
  template: `
    <div v-click-tab-alphabetical="loadLetters">
      <tab-alpha :index-letters="indexLetters" :index-concepts="indexConcepts" v-if="indexLetters" @load-concepts="loadConcepts($event)"></tab-alpha>
    </div>
  `
})

/* Custom directive used to add an event listener on clicks on an element outside of this component */
tabAlphaApp.directive('click-tab-alphabetical', {
  beforeMount: (el, binding) => {
    el.clickTabEvent = event => {
      binding.value() // calling the method given as the attribute value (loadLetters)
    }
    document.querySelector('#alphabetical').addEventListener('click', el.clickTabEvent) // registering an event listener on clicks on the alphabetical nav-item element
  },
  unmounted: el => {
    document.querySelector('#alphabetical').removeEventListener('click', el.clickTabEvent)
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

      if (uri.indexOf(SKOSMOS.uriSpace) !== -1) {
        page = uri.substr(SKOSMOS.uriSpace.length)

        if (/[^a-zA-Z0-9-_.~]/.test(page) || page.indexOf('/') > -1) {
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
    updateMainContent (conceptHTML) {
      // concept card
      const conceptMainContent = conceptHTML.querySelectorAll('#main-content > :not(#concept-mappings)') // all elements from concept card except concept mappings

      // emptying vocab info
      const mainContent = document.querySelector('#main-content')
      const toBeRemoved = document.querySelectorAll('#main-content > :not(#concept-mappings)') // all elements from vocab info except concept mappings
      for (let i = 0; i < toBeRemoved.length; i++) {
        mainContent.removeChild(toBeRemoved[i])
      }

      // inserting concept card into vocab info
      for (let i = 0; i < conceptMainContent.length; i++) {
        mainContent.prepend(conceptMainContent[i])
      }
    },
    updateTitle (conceptHTML) {
      document.title = conceptHTML.querySelector('title').innerHTML
    },
    updateJsonLD (conceptHTML) {
      const JsonLD = document.querySelector('script[type="application/ld+json"]')
      const newJsonLD = conceptHTML.querySelector('script[type="application/ld+json"]')
      if (JsonLD) {
        JsonLD.innerHTML = '{}'
        if (newJsonLD) {
          JsonLD.innerHTML = newJsonLD.innerHTML
        }
      } else if (newJsonLD) {
        // insert after the first JS script as it is in the template
        const elemBefore = document.querySelector('script')
        if (elemBefore) {
          elemBefore.parentNode.insertBefore(newJsonLD, elemBefore.nextSibling)
        }
      }
    },
    updateSKOSMOS (conceptHTML) {
      // new SKOSMOS object from concept page
      const skosmosScript = conceptHTML.querySelector('#skosmos-global-vars').innerHTML
      const skosmosObject = skosmosScript.slice(skosmosScript.indexOf('{'))
      const newSKOSMOS = JSON.parse(skosmosObject)

      // replacing all values in the old SKOSMOS object with new ones
      for (const i in newSKOSMOS) {
        SKOSMOS[i] = newSKOSMOS[i]
      }
    },
    partialPageLoad (event, pageUri) {
      event.preventDefault()

      // fetching html content of the concept page
      fetch(pageUri)
        .then(data => {
          return data.text()
        })
        .then(data => {
          // updating url and history
          if (window.history.pushState) { window.history.pushState({ url: pageUri }, '', pageUri) }

          // concept page HTML
          const conceptHTML = document.createElement('div')
          conceptHTML.innerHTML = data.trim()

          this.updateMainContent(conceptHTML)
          this.updateTitle(conceptHTML)
          this.updateJsonLD(conceptHTML)
          this.updateSKOSMOS(conceptHTML)

          // custom event to signal that a new concept page was loaded
          const event = new Event('loadConceptPage')
          document.dispatchEvent(event)
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
        <a :href="getHref(concept.uri)" @click="partialPageLoad($event, getHref(concept.uri))">{{ concept.prefLabel }}</a>
      </li>
    </ul>
  `
})

tabAlphaApp.mount('#tab-alphabetical')
