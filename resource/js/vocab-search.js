/* global Vue, bootstrap, $t, onTranslationReady, getConceptURL */

function startVocabSearchApp () {
  const vocabSearch = Vue.createApp({
    data () {
      return {
        selectedLanguage: null,
        searchTerm: '',
        searchCounter: 0, // used for matching the query and the response in case there are many responses
        renderedResultsList: [],
        languageStrings: null,
        uriPrefixes: {},
        showAutoCompleteDropdown: false,
        focusedLangIndex: -1,
        showNotation: null
      }
    },
    computed: {
      allLanguages () {
        return $t('all languages')
      },
      noResults () {
        return $t('No results')
      },
      contentLanguageMessage () {
        return $t('Content language')
      },
      searchLabel () {
        return $t('Enter search term')
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
      this.searchTerm = window.SKOSMOS.search_query || ''
      this.languageStrings = this.formatLanguages()
      this.renderedResultsList = []
      this.uriPrefixes = {}
      this.showNotation = window.SKOSMOS.showNotation

      this.langMenuKeydownHandler = (e) => {
        // Bypass Bootstrap event listener on window level
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ' || e.key === 'Escape') {
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
      autoComplete (event) {
        const delayMs = 300

        /* Reading search term from input element instead of relying on v-model
           because mobile browsers don't always update the value correctly */
        this.searchTerm = event.target.value
        this.searchCounter += 1

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
        const allLanguagesEntry = { all: this.allLanguages }
        return { ...languages, ...allLanguagesEntry }
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
            result.pageUrl = getConceptURL(result.uri)
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
        // cancel any pending autocomplete request
        clearTimeout(this._timerId)
        this.searchCounter += 1
        this.searchTerm = ''
        this.renderedResultsList = []
        this.hideAutoComplete()
        // prevent the input focus handler from immediately re-showing the list
        this._skipShowOnFocus = true

        this.$nextTick(() => {
          this.$refs.searchInputField.focus()
        })
      },
      onSearchFieldFocus () {
        if (this._skipShowOnFocus) {
          this._skipShowOnFocus = false
          return
        }
        this.showAutoComplete()
      },
      /*
      * Show the existing autocomplete list if it was hidden by onClickOutside()
      */
      showAutoComplete () {
        this.showAutoCompleteDropdown = true
      },
      focusFirstResult () {
        const firstLink = this.$el?.querySelector('#search-autocomplete-results a')
        if (firstLink) firstLink.focus()
      },
      /*
      * Focus the top-level focusable element before (-1) or after (1) the
      * search field, skipping the autocomplete result links.
      * Note: must stay in sync with the native tab order of the wrapper,
      * since the Tab key on the search input itself relies on that order.
      */
      focusSearchFieldSibling (direction) {
        const input = this.$refs.searchInputField
        const resultsList = this.$el?.querySelector('#search-autocomplete-results')
        const focusables = Array.from(this.$el.querySelectorAll('input, button, a[href]'))
          // offsetParent excludes items in the hidden language dropdown menu
          .filter(el => !el.disabled && el.offsetParent !== null && !(resultsList && resultsList.contains(el)))
        const index = focusables.indexOf(input)
        const target = focusables[index + direction]
        if (target) target.focus()
      },
      onSearchFieldKeydown (event) {
        switch (event.key) {
          case 'ArrowDown':
            event.preventDefault()
            // re-open the list if it was hidden, then move focus to the first result
            this.showAutoComplete()
            this.$nextTick(() => this.focusFirstResult())
            break
          case 'ArrowUp':
            this.hideAutoComplete()
            break
          case 'Escape':
            event.preventDefault()
            this.hideAutoComplete()
            break
          case 'Tab':
            // close the list but let the focus move to the next element
            this.hideAutoComplete()
            break
        }
      },
      onLangMenuKeydown (event) {
        const items = this.$refs.langMenu.querySelectorAll('[role="radio"]')
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
              break
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
      onResultsKeydown (e) {
        const items = Array.from(e.currentTarget.querySelectorAll('a'))
        if (!items.length) return

        const currentIndex = items.indexOf(document.activeElement)

        const focusAt = (newIndex) => {
          const i = (newIndex + items.length) % items.length
          items[i].focus()
          // focus() alone does not scroll a partially visible item into full
          // view, so scroll the list container as needed
          const list = e.currentTarget
          const itemRect = items[i].getBoundingClientRect()
          const listRect = list.getBoundingClientRect()
          if (itemRect.bottom > listRect.bottom) {
            list.scrollTop += itemRect.bottom - listRect.bottom
          } else if (itemRect.top < listRect.top) {
            list.scrollTop += itemRect.top - listRect.top
          }
        }

        switch (e.key) {
          case 'ArrowDown':
            e.preventDefault()
            focusAt(currentIndex < 0 ? 0 : currentIndex + 1)
            break

          case 'ArrowUp':
            e.preventDefault()
            if (currentIndex <= 0) {
              // move focus back to the search input
              this.$refs.searchInputField.focus()
            } else {
              focusAt(currentIndex - 1)
            }
            break

          case 'Home':
            e.preventDefault()
            focusAt(0)
            break

          case 'End':
            e.preventDefault()
            focusAt(items.length - 1)
            break

          case 'Escape':
            e.preventDefault()
            this.hideAutoComplete()
            // Invariant: whenever the input is focused programmatically, set
            // _skipShowOnFocus first so onSearchFieldFocus does not re-show the list
            this._skipShowOnFocus = true
            this.$refs.searchInputField.focus()
            break

          case 'Tab': {
            // leave the list entirely: jump to the previous/next top-level
            // focusable element around the search field (skipping the result
            // links and, for Shift-Tab, the search field itself)
            e.preventDefault()
            this.hideAutoComplete()
            this.focusSearchFieldSibling(e.shiftKey ? -1 : 1)
            break
          }

          case 'Enter':
            // activate the focused result explicitly (also works when the
            // event is synthesized by assistive technology or tests)
            if (currentIndex < 0) break
            e.preventDefault()
            items[currentIndex].click()
            break
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

        <div class="search-field-group">
          <span id="content-language-label" class="search-field-label">{{ contentLanguageMessage }}</span>
          <div class="dropdown" id="language-selector">
            <button
              type="button"
              ref="langButton"
              class="btn btn-outline-secondary dropdown-toggle"
              data-bs-toggle="dropdown"
              @keydown="onLangMenuKeydown"
              aria-expanded="false"
              aria-controls="language-list"
              aria-labelledby="content-language-label content-language-current">
              <span id="content-language-current" v-if="selectedLanguage && languageStrings[selectedLanguage]">
                {{ languageStrings[selectedLanguage] }}
              </span>
              <i class="chevron fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>

            <ul
              ref="langMenu"
              id="language-list"
              class="dropdown-menu"
              role="radiogroup"
              aria-labelledby="content-language-label">
              <li
                v-for="(value, key, index) in languageStrings"
                :key="key"
                role="radio"
                :aria-checked="selectedLanguage === key"
                :tabindex="focusedLangIndex === index ? 0 : -1"
                @click="changeContentLangAndReload(key)"
                @focus="focusedLangIndex = index"
                class="dropdown-item">
                {{ value }}
              </li>
            </ul>
          </div>
        </div>

        <div class="search-field-group">
          <label for="search-field" class="search-field-label">{{ searchLabel }}</label>
          <div id="headerbar-search" class="dropdown">
            <input type="search"
              ref="searchInputField"
              class="form-control"
              id="search-field"
              autocomplete="off"
              data-bs-toggle=""
              v-click-outside="hideAutoComplete"
              v-model="searchTerm"
              @input="autoComplete($event)"
              @keydown="onSearchFieldKeydown"
              @keyup.enter="gotoSearchPage()"
              @focus="onSearchFieldFocus()">
            <ul id="search-autocomplete-results"
                class="w-100"
                :class="{ 'show': showAutoCompleteDropdown }"
                aria-labelledby="search-field"
                @keydown="onResultsKeydown">
              <li class="autocomplete-result container" v-for="result in renderedResultsList"
                :key="result.prefLabel" >
                <template v-if="result.pageUrl">
                  <a :href=result.pageUrl>
                    <div class="row py-1">
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
          </div>
        </div>



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
