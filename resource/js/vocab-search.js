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
      msgs: null,
      showDropdown: false
    }
  },
  mounted () {
    this.languages = window.SKOSMOS.languageOrder
    this.selectedLanguage = window.SKOSMOS.content_lang
    this.searchCounter = 0
    this.languageStrings = window.SKOSMOS.language_strings[window.SKOSMOS.lang] ?? window.SKOSMOS.language_strings.en
    this.msgs = window.SKOSMOS.msgs[window.SKOSMOS.lang] ?? window.SKOSMOS.msgs.en
    this.renderedResultsList = []
  },
  methods: {
    autoComplete () {
      const delayMs = 300

      // when new autocomplete is fired, empty the previous result
      this.renderedResultsList = []

      // cancel the timer for upcoming API call
      clearTimeout(this._timerId)
      this.hideAutoComplete()

      // TODO: if the search term is in cache, use the cache

      // delay call, but don't execute if the search term is not at least two characters
      if (this.searchTerm.length > 1) {
        this._timerId = setTimeout(() => { this.search() }, delayMs)
      }
    },
    search () {
      const mySearchCounter = this.searchCounter + 1 // make sure we can identify this search later in case of several ongoing searches
      this.searchCounter = mySearchCounter

      let skosmosSearchUrl = 'rest/v1/' + window.SKOSMOS.vocab + '/search?'
      const skosmosSearchUrlParams = new URLSearchParams({ query: this.formatSearchTerm(), lang: window.SKOSMOS.lang })
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
          return {
            before: label.substring(0, startIndex),
            match: label.substring(startIndex, endIndex),
            after: label.substring(endIndex)
          }
        }
        return label
      }
      return null
    },
    translateType (type) {
      return window.SKOSMOS.msgs[window.SKOSMOS.lang][type]
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
        if ('hiddenLabel' in result) {
          result.hitType = 'hidden'
          result.hit = this.renderMatchingPart(renderedSearchTerm, result.prefLabel)
        } else if ('altLabel' in result) {
          result.hitType = 'alt'
          result.hit = this.renderMatchingPart(renderedSearchTerm, result.altLabel)
          result.hitPref = this.renderMatchingPart(renderedSearchTerm, result.prefLabel)
        } else if ('prefLabel' in result) {
          result.hitType = 'pref'
          result.hit = this.renderMatchingPart(renderedSearchTerm, result.prefLabel)
        }
        if ('uri' in result) { // create relative Skosmos page URL from the search result URI
          result.pageUrl = window.SKOSMOS.vocab + '/' + window.SKOSMOS.lang + '/page?'
          const urlParams = new URLSearchParams({ uri: result.uri })
          result.pageUrl += urlParams.toString()
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
          lang: window.SKOSMOS.lang
        })
      }
      this.showAutoComplete()
    },
    hideAutoComplete () {
      this.showDropdown = false
      this.$forceUpdate()
    },
    gotoSearchPage () {
      if (!this.searchTerm) return

      const currentVocab = window.SKOSMOS.vocab + '/' + window.SKOSMOS.lang + '/'
      const vocabHref = window.location.href.substring(0, window.location.href.lastIndexOf(window.SKOSMOS.vocab)) + currentVocab
      const searchUrlParams = new URLSearchParams({ clang: window.SKOSMOS.content_lang, q: this.searchTerm })
      if (this.selectedLanguage === 'all') searchUrlParams.set('anylang', 'on')
      const searchUrl = vocabHref + 'search?' + searchUrlParams.toString()
      window.location.href = searchUrl
    },
    changeLang () {
      window.SKOSMOS.content_lang = this.selectedLanguage
      // TODO: Implement (a normal) page load to change content according to the new content language
    },
    resetSearchTermAndHideDropdown () {
      this.searchTerm = ''
      this.renderedResultsList = []
      this.hideAutoComplete()
    },
    /*
     * Show the existing autocomplete list if it was hidden by onClickOutside()
     */
    showAutoComplete () {
      console.log('Show autocomplete')
      this.showDropdown = true
      this.$forceUpdate()
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
        <span id="headerbar-search" class="dropdown">
          <input type="search"
            class="form-control"
            id="search-field"
            aria-expanded="false"
            autocomplete="off"
            data-bs-toggle=""
            aria-label="Text input with dropdown button"
            placeholder="Search..."
            v-click-outside="hideAutoComplete"
            v-model="searchTerm"
            @input="autoComplete()"
            @keyup.enter="gotoSearchPage()"
            @click="showAutoComplete()">
          <ul id="search-autocomplete-results" class="dropdown-menu" :class="{ 'show': showDropdown }"
            aria-labelledby="search-field">
            <li class="autocomplete-result container" v-for="result in renderedResultsList"
              :key="result.prefLabel" >
              <template v-if="result.pageUrl">
                <a :href=result.pageUrl>
                  <div class="row pb-1">
                    <div class="col" v-if="result.hitType == 'hidden'">
                      <span class="result">
                        <template v-if="result.hit.hasOwnProperty('match')">
                          {{ result.hit.before }}<b>{{ result.hit.match }}</b>{{ result.hit.after }}
                        </template>
                        <template v-else>
                          {{ result.hit }}
                        </template>
                      </span>
                    </div>
                    <div class="col" v-else-if="result.hitType == 'alt'">
                      <span>
                        <template v-if="result.hit.hasOwnProperty('match')">
                          {{ result.hit.before }}<b>{{ result.hit.match }}</b>{{ result.hit.after }}
                        </template>
                        <template v-else>
                          {{ result.hit }}
                        </template>
                      </span>
                      <span> &rarr;&nbsp;<span class="result">
                          <template v-if="result.hitPref.hasOwnProperty('match')">
                            {{ result.hitPref.before }}<b>{{ result.hitPref.match }}</b>{{ result.hitPref.after }}
                          </template>
                          <template v-else>
                            {{ result.hitPref }}
                          </template>
                        </span>
                      </span>
                    </div>
                    <div class="col" v-else-if="result.hitType == 'pref'">
                      <span class="result">
                        <template v-if="result.hit.hasOwnProperty('match')">
                          {{ result.hit.before }}<b>{{ result.hit.match }}</b>{{ result.hit.after }}
                        </template>
                        <template v-else>
                          {{ result.hit }}
                        </template>
                      </span>
                    </div>
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

vocabSearch.directive('click-outside', {
  beforeMount: (el, binding) => {
    el.clickOutsideEvent = event => {
      // Ensure the click was outside the element
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event) // Call the method provided in the directive's value
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted: el => {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
})

vocabSearch.mount('#search-vocab')
