/* global Vue, $t, onTranslationReady */

function startVocabSearchApp () {
  const vocabSearch = Vue.createApp({
    data () {
      return {
        languages: [],
        selectedLanguage: null,
        searchTerm: null,
        searchCounter: null,
        renderedResultsList: [],
        languageStrings: null,
        showDropdown: false,
        showNotation: null
      }
    },
    computed: {
      searchPlaceholder () {
        return $t('Search...')
      },
      noResults () {
        return $t('No results')
      },
      selectSearchLanguageAriaMessage () {
        return $t('Select search language')
      },
      textInputWithDropdownButtonAriaMessage () {
        return $t('Text input with dropdown button')
      },
      searchAriaMessage () {
        return $t('Search')
      }
    },
    mounted () {
      this.languages = window.SKOSMOS.languageOrder
      this.selectedLanguage = this.parseSearchLang()
      this.searchCounter = 0 // used for matching the query and the response in case there are many responses
      this.languageStrings = window.SKOSMOS.language_strings
      this.renderedResultsList = []
      this.showNotation = window.SKOSMOS.showNotation
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
        const skosmosSearchUrlParams = new URLSearchParams({ query: this.formatSearchTerm(), unique: true })
        if (this.selectedLanguage !== 'all') skosmosSearchUrlParams.set('lang', this.selectedLanguage)
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
      notationMatches (searchTerm, notation) {
        if (notation && notation.toLowerCase().includes(searchTerm.toLowerCase())) {
          return true
        }
        return false
      },
      parseSearchLang () {
        // if content language can be found from uri params, use that and update it to SKOSMOS object and to search lang cookie
        const urlParams = new URLSearchParams(window.location.search)
        const paramLang = urlParams.get('clang')
        const anyLang = urlParams.get('anylang')
        if (anyLang) {
          this.changeLang('all')
          return 'all'
        }
        if (paramLang) {
          this.changeLang(paramLang)
          return paramLang
        }
        // otherwise pick content lang from SKOSMOS object (it should always exist)
        if (window.SKOSMOS.content_lang) {
          return window.SKOSMOS.content_lang
        }
        return null
      },
      renderMatchingPart (searchTerm, label, lang = null) {
        if (label) {
          let langSpec = ''
          if (lang && this.selectedLanguage === 'all') {
            langSpec = ' (' + lang + ')'
          }
          const searchTermLowerCase = searchTerm.toLowerCase()
          const labelLowerCase = label.toLowerCase()
          if (labelLowerCase.includes(searchTermLowerCase)) {
            const startIndex = labelLowerCase.indexOf(searchTermLowerCase)
            const endIndex = startIndex + searchTermLowerCase.length
            return {
              before: label.substring(0, startIndex),
              match: label.substring(startIndex, endIndex),
              after: label.substring(endIndex) + langSpec
            }
          }
          return label + langSpec
        }
        return null
      },
      translateType (type) {
        return $t(type)
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
            result.hit = this.renderMatchingPart(renderedSearchTerm, result.prefLabel, result.lang)
          } else if ('altLabel' in result) {
            result.hitType = 'alt'
            result.hit = this.renderMatchingPart(renderedSearchTerm, result.altLabel, result.lang)
            result.hitPref = this.renderMatchingPart(renderedSearchTerm, result.prefLabel)
          } else {
            if (this.notationMatches(renderedSearchTerm, result.notation)) {
              result.hitType = 'notation'
              result.hit = this.renderMatchingPart(renderedSearchTerm, result.notation, result.lang)
            } else if ('matchedPrefLabel' in result) {
              result.hitType = 'pref'
              result.hit = this.renderMatchingPart(renderedSearchTerm, result.matchedPrefLabel, result.lang)
            } else if ('prefLabel' in result) {
              result.hitType = 'pref'
              result.hit = this.renderMatchingPart(renderedSearchTerm, result.prefLabel, result.lang)
            }
          }
          if ('uri' in result) { // create relative Skosmos page URL from the search result URI
            result.pageUrl = window.SKOSMOS.vocab + '/' + window.SKOSMOS.lang + '/page?'
            const urlParams = new URLSearchParams({ uri: result.uri })
            if (this.selectedLanguage !== window.SKOSMOS.lang) { // add content language parameter
              urlParams.append('clang', this.selectedLanguage)
            }
            result.pageUrl += urlParams.toString()
          }
          // render search result renderedTypes
          if (result.type.length > 1) { // remove the type for SKOS concepts if the result has more than one type
            result.type.splice(result.type.indexOf('skos:Concept'), 1)
          }
          // use the translateType function to map translations for the type IRIs
          result.renderedType = result.type.map(this.translateType).join(', ')
          result.showNotation = this.showNotation
        })

        if (this.renderedResultsList.length === 0) { // show no results message
          this.renderedResultsList.push({
            prefLabel: this.noResults,
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

        const searchUrlParams = new URLSearchParams({ clang: window.SKOSMOS.content_lang, q: this.searchTerm })
        if (this.selectedLanguage === 'all') searchUrlParams.set('anylang', 'true')
        const searchUrl = window.SKOSMOS.vocab + '/' + window.SKOSMOS.lang + '/search?' + searchUrlParams.toString()
        window.location.href = searchUrl
      },
      changeLang (clang) {
        this.selectedLanguage = clang
        window.SKOSMOS.content_lang = clang
        this.resetSearchTermAndHideDropdown()
      },
      changeContentLangAndReload (clang) {
        this.changeLang(clang)
        const params = new URLSearchParams(window.location.search)
        if (clang === 'all') {
          params.set('anylang', 'true')
        } else {
          params.delete('anylang')
          params.set('clang', clang)
        }
        this.$forceUpdate()
        window.location.search = params.toString()
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
        this.showDropdown = true
        this.$forceUpdate()
      }
    },
    template: `
      <div class="d-flex my-auto ms-auto">
        <div class="d-flex justify-content-end input-group ms-auto" id="search-wrapper">

        <div class="dropdown" id="language-selector">
          <button class="btn btn-outline-secondary dropdown-toggle"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            :aria-label="selectSearchLanguageAriaMessage"
            v-if="languageStrings">
            {{ languageStrings[selectedLanguage] }}
            <i class="fa-solid fa-chevron-down"></i>
          </button>
          <ul class="dropdown-menu" id="language-list" role="menu">
            <li v-for="(value, key) in languageStrings" :key="key" role="none">
              <a
                class="dropdown-item"
                :value="key"
                @click="changeContentLangAndReload(key)"
                @keydown.enter="changeContentLangAndReload(key)"
                role="menuitem"
                tabindex=0 >
                {{ value }}
              </a>
            </li>
          </ul>
        </div>

          <span id="headerbar-search" class="dropdown">
            <input type="search"
              class="form-control"
              id="search-field"
              autocomplete="off"
              data-bs-toggle=""
              :aria-label="textInputWithDropdownButtonAriaMessage"
              :placeholder="searchPlaceholder"
              v-click-outside="hideAutoComplete"
              v-model="searchTerm"
              @input="autoComplete()"
              @keyup.enter="gotoSearchPage()"
              @click="showAutoComplete()">
            <ul id="search-autocomplete-results"
                class="dropdown-menu w-100"
                :class="{ 'show': showDropdown }"
                aria-labelledby="search-field">
              <li class="autocomplete-result container" v-for="result in renderedResultsList"
                :key="result.prefLabel" >
                <template v-if="result.pageUrl">
                  <a :href=result.pageUrl>
                    <div class="row pb-1">
                      <div class="col" v-if="result.hitType == 'hidden'">
                        <span class="result">
                          <template v-if="result.showNotation && result.notation">
                            {{ result.notation }}&nbsp;
                          </template>
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
                          <i>
                            <template v-if="result.showNotation && result.notation">
                              {{ result.notation }}&nbsp;
                            </template>
                            <template v-if="result.hit.hasOwnProperty('match')">
                              {{ result.hit.before }}<b>{{ result.hit.match }}</b>{{ result.hit.after }}
                            </template>
                            <template v-else>
                              {{ result.hit }}
                            </template>
                          </i>
                        </span>
                        <span> &rarr;&nbsp;<span class="result">
                          <template v-if="result.showNotation && result.notation">
                              {{ result.notation }}&nbsp;
                            </template>
                            <template v-if="result.hitPref.hasOwnProperty('match')">
                              {{ result.hitPref.before }}<b>{{ result.hitPref.match }}</b>{{ result.hitPref.after }}
                            </template>
                            <template v-else>
                              {{ result.hitPref }}
                            </template>
                          </span>
                        </span>
                      </div>
                      <div class="col" v-else-if="result.hitType == 'notation'">
                        <span class="result">
                          <template v-if="result.hit.hasOwnProperty('match')">
                            {{ result.hit.before }}<b>{{ result.hit.match }}</b>{{ result.hit.after }}
                          </template>
                          <template v-else>
                            {{ result.hit }}
                          </template>
                        </span>
                        <span>
                          {{ result.prefLabel }}
                        </span>
                      </div>
                      <div class="col" v-else-if="result.hitType == 'pref'">
                        <span class="result">
                          <template v-if="result.showNotation && result.notation">
                            {{ result.notation }}&nbsp;
                          </template>
                          <template v-if="result.hit.hasOwnProperty('match')">
                            {{ result.hit.before }}<b>{{ result.hit.match }}</b>{{ result.hit.after }}
                          </template>
                          <template v-else>
                            {{ result.hit }}
                          </template>
                        </span>
                      </div>
                      <div class="col-auto align-self-end pr-1" v-html="result.renderedType"></div>
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
          <button id="search-button" class="btn btn-outline-secondary" :aria-label="searchAriaMessage" @click="gotoSearchPage()">
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
}

onTranslationReady(startVocabSearchApp)
