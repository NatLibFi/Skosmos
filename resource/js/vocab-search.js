/* global Vue, bootstrap, $t, onTranslationReady */

function startVocabSearchApp () {
  const vocabSearch = Vue.createApp({
    data () {
      return {
        selectedLanguage: null,
        searchTerm: null,
        searchCounter: null,
        renderedResultsList: [],
        languageStrings: null,
        uriPrefixes: {},
        showAutoCompleteDropdown: false,
        focusedLangIndex: -1,
        showNotation: null
      }
    },
    computed: {
      searchPlaceholder () {
        return $t('Search in this vocabulary')
      },
      anyLanguages () {
        return $t('Any language')
      },
      noResults () {
        return $t('No results')
      },
      selectSearchLanguageAriaMessage () {
        return $t('Select search language')
      },
      searchFieldAriaMessage () {
        return $t('Search in this vocabulary')
      },
      searchButtonAriaMessage () {
        return $t('Search')
      },
      clearSearchAriaMessage () {
        return $t('Clear search field')
      }
    },
    mounted () {
      this.selectedLanguage = this.parseSearchLang()
      this.searchCounter = 0 // used for matching the query and the response in case there are many responses
      this.languageStrings = this.formatLanguages()
      this.renderedResultsList = []
      this.uriPrefixes = {}
      this.showNotation = window.SKOSMOS.showNotation

      this.langMenuKeydownHandler = (e) => {
        // Bypass Bootstrap event listener on window level
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
          if (e.target.closest('#language-selector') && e.target.className === 'dropdown-item') {
            e.stopImmediatePropagation()
            this.onLangMenuKeydown(e)
          }
        }
      }
      window.addEventListener('keydown', this.langMenuKeydownHandler, true)
    },
    beforeUnmount () {
      window.removeEventListener('keydown', this.langMenuKeydownHandler, true)
    },
    methods: {
      autoComplete () {
        const delayMs = 300

        // when new autocomplete is fired, empty the previous result
        this.renderedResultsList = []

        // cancel the timer for upcoming API call
        clearTimeout(this._timerId)
        this.hideAutoComplete()

        // delay call, but don't execute if the search term is not at least two characters
        if (this.searchTerm.length > 1) {
          this._timerId = setTimeout(() => { this.search() }, delayMs)
        }
      },
      search () {
        const mySearchCounter = this.searchCounter + 1 // make sure we can identify this search later in case of several ongoing searches
        this.searchCounter = mySearchCounter
        let skosmosSearchUrl = 'rest/v1/' + window.SKOSMOS.vocab + '/search?'
        const unique = this.selectedLanguage !== 'all'
        const skosmosSearchUrlParams = new URLSearchParams({ query: this.formatSearchTerm(), unique: unique })
        if (this.selectedLanguage !== 'all') skosmosSearchUrlParams.set('lang', this.selectedLanguage)
        skosmosSearchUrl += skosmosSearchUrlParams.toString()

        fetch(skosmosSearchUrl)
          .then(data => data.json())
          .then(data => {
            if (mySearchCounter === this.searchCounter) {
              this.renderedResultsList = data.results // update results (update cache if it is implemented)
              this.uriPrefixes = data['@context']
              this.renderResults() // render after the fetch has finished
            }
          })
      },
      formatLanguages () {
        const languages = window.SKOSMOS.contentLanguages
        const anyLanguagesEntry = { all: this.anyLanguages }
        return { ...languages, ...anyLanguagesEntry }
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
      renderType (typeUri) {
        const label = window.SKOSMOS.types[typeUri]
        if (label) return label

        const [prefix, local] = typeUri.split(':')
        const iriBase = this.uriPrefixes[prefix]

        if (iriBase) {
          const iri = iriBase + local
          return window.SKOSMOS.types[iri] || typeUri
        }

        return typeUri
      },
      /*
      * renderResults is used when the search string has been indexed in the cache
      * it also shows the autocomplete results list
      */
      renderResults () {
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
          // use the renderType function to map translations for the type IRIs
          result.renderedType = result.type.map(uri => this.renderType(uri)).join(', ')
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
        this.showAutoCompleteDropdown = false
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
        if (clang !== 'all') {
          window.SKOSMOS.content_lang = clang
        }
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

        this.$nextTick(() => {
          this.$refs.searchInputField.focus()
        })
      },
      /*
      * Show the existing autocomplete list if it was hidden by onClickOutside()
      */
      showAutoComplete () {
        this.showAutoCompleteDropdown = true
        this.$forceUpdate()
      },
      onLangMenuKeydown (event) {
        const items = this.$refs.langMenu.querySelectorAll('[role="menuitemradio"]')
        switch (event.key) {
          case 'ArrowDown': {
            event.preventDefault()
            if (this.focusedLangIndex === items.length) {
              this.focusedLangIndex = 0
              items[this.focusedLangIndex].focus()
            } else {
              this.focusedLangIndex = (this.focusedLangIndex + 1) % items.length
              items[this.focusedLangIndex].focus()
            }
            break
          }
          case 'ArrowUp': {
            event.preventDefault()
            if (this.focusedLangIndex === 0) {
              this.closeLangMenu()
            }
            this.focusedLangIndex =
              (this.focusedLangIndex - 1 + items.length) % items.length
            items[this.focusedLangIndex].focus()
            break
          }
          case 'Enter':
          case ' ': {
            event.preventDefault()
            if (event.target.classList.contains('dropdown-toggle')) {
              this.openLangMenu()
            } else {
              this.changeContentLangAndReload(Object.keys(this.languageStrings)[this.focusedLangIndex])
            }
            break
          }
          case 'Escape': {
            this.closeLangMenu()
            break
          }
        }
      },
      openLangMenu () {
        const btn = this.$refs.langButton
        const dropdown = bootstrap.Dropdown.getOrCreateInstance(btn)
        dropdown.show()

        this.$nextTick(() => {
          const items = this.$refs.langMenu.querySelectorAll('[role="menuitemradio"]')

          this.focusedLangIndex = Math.max(
            0,
            Object.keys(this.languageStrings).indexOf(this.selectedLanguage)
          )

          items[this.focusedLangIndex]?.focus()
        })
      },

      closeLangMenu () {
        const btn = this.$refs.langButton
        const dropdown = bootstrap.Dropdown.getOrCreateInstance(btn)
        dropdown.hide()

        this.focusedLangIndex = -1
        this.$nextTick(() => {
          this.$refs.langButton.focus()
        })
      }
    },
    template: `
      <div class="input-group ps-xl-2 flex-nowrap" id="search-wrapper">

      <div class="dropdown" id="language-selector">
          <button
            ref="langButton"
            class="btn btn-outline-secondary dropdown-toggle"
            data-bs-toggle="dropdown"
            @keydown="onLangMenuKeydown"
            aria-haspopup="true"
            :aria-label="selectSearchLanguageAriaMessage">
            <template v-if="languageStrings">{{ languageStrings[selectedLanguage] }}</template>
            <i class="chevron fa-solid fa-chevron-down"></i>
          </button>

          <ul
            ref="langMenu"
            id="language-list"
            class="dropdown-menu"
            role="menu">
            <li
              v-for="(value, key, index) in languageStrings"
              :key="key"
              role="menuitemradio"
              :aria-checked="selectedLanguage === key"
              :tabindex="focusedLangIndex === index ? 0 : -1"
              @click="changeContentLangAndReload(key)"
              @focus="focusedLangIndex = index"
              class="dropdown-item">
              {{ value }}
            </li>
          </ul>
        </div>

        <span id="headerbar-search" class="dropdown">
          <input type="search"
            ref="searchInputField"
            class="form-control"
            id="search-field"
            autocomplete="off"
            data-bs-toggle=""
            :aria-label="searchFieldAriaMessage"
            :placeholder="searchPlaceholder"
            v-click-outside="hideAutoComplete"
            v-model="searchTerm"
            @input="autoComplete()"
            @keyup.enter="gotoSearchPage()"
            @click="showAutoComplete()">
          <ul id="search-autocomplete-results"
              class="dropdown-menu w-100"
              :class="{ 'show': showAutoCompleteDropdown }"
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
        <button id="clear-button"
                class="btn btn-danger"
                type="clear"
                :aria-label="clearSearchAriaMessage"
                v-if="searchTerm"
                @click="resetSearchTermAndHideDropdown()">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <button id="search-button" class="btn btn-outline-secondary" :aria-label="searchButtonAriaMessage" @click="gotoSearchPage()">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
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

  if (document.getElementById('search-vocab')) {
    vocabSearch.mount('#search-vocab')
  }
}

onTranslationReady(startVocabSearchApp)
