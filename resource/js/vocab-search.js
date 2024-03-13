/* global Vue */

const vocabSearch = Vue.createApp({
  data () {
    return {
      languages: [],
      selectedLanguage: null,
      searchTerm: null,
      searchCounter: null,
      renderedResultsList: [],
      languageStrings: null,
      msgs: null
    }
  },
  mounted () {
    this.languages = SKOSMOS.languageOrder
    this.selectedLanguage = SKOSMOS.content_lang
    this.searchCounter = 0
    this.languageStrings = SKOSMOS.language_strings[SKOSMOS.lang] ?? SKOSMOS.language_strings.en
    this.msgs = SKOSMOS.msgs[SKOSMOS.lang] ?? SKOSMOS.msgs.en
    this.renderedResultsList = []
    document.addEventListener('click', this.onClickOutside)
  },
  beforeUnmount () {
    document.removeEventListener('click', this.onClickOutside)
  },
  methods: {
    autoComplete () {
      const delayMs = 300

      // when new autocomplete is fired, empty the previous result
      this.hideDropdown()

      // cancel the timer for upcoming API call
      clearTimeout(this._timerId)

      // TODO: if the search term is in cache, use the cache

      // delay call, but don't execute if the search term is not at least two characters
      if (this.searchTerm.length > 1) {
        this._timerId = setTimeout(() => { this.search() }, delayMs)
      }
    },
    search () {
      const mySearchCounter = this.searchCounter + 1 // make sure we can identify this search later in case of several ongoing searches
      this.searchCounter = mySearchCounter

      let skosmosSearchUrl = 'rest/v1/' + SKOSMOS.vocab + '/search?'
      const skosmosSearchUrlParams = new URLSearchParams({ query: this.formatSearchTerm(), lang: SKOSMOS.lang })
      skosmosSearchUrl += skosmosSearchUrlParams.toString()

      fetch(skosmosSearchUrl)
        .then(data => data.json())
        .then(data => {
          if (mySearchCounter === this.searchCounter) {
            this.renderedResultsList = data.results // update results (update cache if it is implemented)
            this.renderResults() // render after the fetch has finished
          }
        })
    },
    formatSearchTerm () {
      if (this.searchTerm.includes('*')) { return this.searchTerm }
      return this.searchTerm + '*'
    },
    renderMatchingPart (searchTerm, label) {
      if (label) {
        const searchTermLowerCase = searchTerm.toLowerCase()
        const labelLowerCase = label.toLowerCase()
        if (labelLowerCase.includes(searchTermLowerCase)) {
          const startIndex = labelLowerCase.indexOf(searchTermLowerCase)
          const endIndex = startIndex + searchTermLowerCase.length
          return label.substring(0, startIndex) + `<b>${label.substring(startIndex, endIndex)}</b>` + label.substring(endIndex)
        }
        return label
      }
      return null
    },
    translateType (type) {
      return SKOSMOS.msgs[SKOSMOS.lang][type]
    },
    /*
     * renderResults is used when the search string has been indexed in the cache
     * it also shows the autocomplete results list
     * TODO: Showing labels in other languages, extra concept information and such goes here
     */
    renderResults () {
      // TODO: get the results list form cache if it is implemented
      const renderedSearchTerm = this.searchTerm // save the search term in case it changes while rendering
      this.renderedResultsList.forEach(result => {
        const hitPref = 'prefLabel' in result ? this.renderMatchingPart(renderedSearchTerm, result.prefLabel) : null
        const hitAlt = 'altLabel' in result ? this.renderMatchingPart(renderedSearchTerm, result.altLabel) : null
        const hitHidden = 'hiddenLabel' in result ? this.renderMatchingPart(renderedSearchTerm, result.hiddenLabel) : null
        if ('uri' in result) { // create relative Skosmos page URL from the search result URI
          result.pageUrl = SKOSMOS.vocab + '/' + SKOSMOS.lang + '/page?'
          const urlParams = new URLSearchParams({ uri: result.uri })
          result.pageUrl += urlParams.toString()
        }
        // render search result labels
        if (hitHidden) {
          result.rendered = '<span class="result">' + result.prefLabel + '</span>'
        } else if (hitAlt) {
          result.rendered = hitAlt + ' <span class="d-inline">&rarr;&nbsp;' + '<span class="result">' + hitPref + '</span></span>'
        } else if (hitPref) {
          result.rendered = '<span class="result">' + hitPref + '</span>'
        }
        // render search result renderedTypes
        if (result.type.length > 1) { // remove the type for SKOS concepts if the result has more than one type
          result.type.splice(result.type.indexOf('skos:Concept'), 1)
        }
        // use the translateType function to map translations for the type IRIs
        result.renderedType = result.type.map(this.translateType).join(', ')
      })

      if (this.renderedResultsList.length === 0) { // show no results message
        this.renderedResultsList.push({
          prefLabel: this.msgs['No results'],
          lang: SKOSMOS.lang
        })
      }
      const element = document.getElementById('search-autocomplete-results')
      element.classList.add('show')
    },
    hideDropdown () {
      const element = document.getElementById('search-autocomplete-results')
      element.classList.remove('show')
      this.renderedResultsList = []
    },
    gotoSearchPage () {
      if (!this.searchTerm) return

      const currentVocab = SKOSMOS.vocab + '/' + SKOSMOS.lang + '/'
      const vocabHref = window.location.href.substring(0, window.location.href.lastIndexOf(SKOSMOS.vocab)) + currentVocab
      let searchUrlParams = new URLSearchParams({ clang: SKOSMOS.content_lang })
      searchParams.set("q", this.searchTerm)
      if (this.selectedLanguage === 'all') searchParams.set("anylang", "on")
      const searchUrl = vocabHref + 'search?' + searchUrlParams.toString()
      window.location.href = searchUrl
    },
    changeLang () {
      SKOSMOS.content_lang = this.selectedLanguage
      // TODO: Implement (a normal) page load to change content according to the new content language
    },
    resetSearchTermAndHideDropdown () {
      this.searchTerm = ''
      this.hideDropdown()
    },
    onClickOutside (event) {
      const listener = document.getElementById('search-autocomplete-results')
      // Check if the clicked element is outside your element
      if (listener && !listener.contains(event.target)) {
        this.hideDropdown()
      }
    }
  },
  template: `
    <div class="d-flex my-auto ms-auto">
      <div class="d-flex justify-content-end input-group ms-auto" id="search-wrapper">
        <select class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown-item" aria-expanded="false"
          v-model="selectedLanguage"
          @change="changeLang()"
        >
          <option class="dropdown-item" v-for="(value, key) in languageStrings" :value="key">{{ value }}</option>
        </select>
        <span class="dropdown">
          <input type="search"
            class="form-control"
            id="search-field"
            aria-expanded="false"
             autocomplete="off"
            data-bs-toggle=""
            aria-label="Text input with dropdown button"
            placeholder="Search..."
            v-model="searchTerm"
            @input="autoComplete()"
            @keyup.enter="gotoSearchPage()"
            @click="">
          <ul id="search-autocomplete-results" class="dropdown-menu"
            aria-labelledby="search-field">
            <li class="autocomplete-result container" v-for="result in renderedResultsList"
              :key="result.prefLabel" >
              <template v-if="result.pageUrl">
                <a :href=result.pageUrl>
                  <div class="row pb-1">
                    <div class="col" v-html="result.rendered"></div>
                    <div class="col-auto align-self-end pe-1" v-html="result.renderedType"></div>
                  </div>
                </a>
              </template>
              <template v-else>
                {{ result.prefLabel }}
              </template>
            </li>
          </ul>
        </span>
        <button id="clear-button" class="btn btn-danger" type="clear" v-if="searchTerm" @click="resetSearchTermAndHideDropdown()">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <button id="search-button" class="btn btn-outline-secondary" @click="gotoSearchPage()">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </div>
    </div>
  `
})

vocabSearch.mount('#search-vocab')
