/* global Vue, bootstrap, $t, onTranslationReady, getConceptURL */

function startGlobalSearchApp () {
  const globalSearch = Vue.createApp({
    data () {
      return {
        languages: [],
        selectedLanguage: null,
        selectedVocabs: [],
        searchTerm: '',
        searchCounter: null,
        renderedResultsList: [],
        languageStrings: null,
        uriPrefixes: {},
        vocabStrings: null,
        showDropdown: false,
        showNotation: null
      }
    },
    computed: {
      vocabSelectorLabel () {
        return $t('Choose vocabulary')
      },
      langSelectorLabel () {
        return $t('Content language')
      },
      searchLabel () {
        return $t('Enter search term')
      },
      allVocabularies () {
        return $t('all vocabularies')
      },
      allLanguages () {
        return $t('all languages')
      },
      noResults () {
        return $t('No results')
      },
      selectSearchVocabAriaMessage () {
        return $t('Select search vocabularies')
      },
      selectSearchLanguageAriaMessage () {
        return $t('Select search language')
      },
      searchFieldAriaMessage () {
        return $t('Enter search term')
      },
      searchButtonAriaMessage () {
        return $t('Search')
      },
      clearSearchAriaMessage () {
        return $t('Clear search field')
      },
      getSelectedVocabs () {
        return this.selectedVocabs.map(key => ({ key, value: this.vocabStrings[key].short }))
      },
      selectedVocabsString () {
        return this.getSelectedVocabs.map(voc => voc.value).join(', ')
      }
    },
    mounted () {
      this.languages = window.SKOSMOS.languageOrder
      this.selectedLanguage = this.getSearchLang()
      this.languageStrings = this.formatLanguages()
      this.uriPrefixes = {}
      this.vocabStrings = window.SKOSMOS.vocab_list
    },
    watch: {
      selectedLanguage (newLang) {
        if (!newLang) return
        const url = new URL(window.location.href)
        if (newLang === 'all') {
          url.searchParams.set('anylang', 'on')
        } else if (newLang === window.SKOSMOS.lang) {
          url.searchParams.delete('clang')
          url.searchParams.delete('anylang')
          window.SKOSMOS.content_lang = newLang
        } else {
          url.searchParams.set('clang', newLang)
          url.searchParams.delete('anylang')
          window.SKOSMOS.content_lang = newLang
        }
        window.history.replaceState({}, '', url.toString())
      }
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
        let skosmosSearchUrl = window.SKOSMOS.baseHref + 'rest/v1/search?'
        const skosmosSearchApiParams = this.formatSearchApiParams()
        skosmosSearchUrl += skosmosSearchApiParams.toString()
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
      formatSearchUrlParams () {
        const params = new URLSearchParams({ q: this.searchTerm })
        if (this.selectedLanguage === 'all') {
          params.set('anylang', 'on')
        } else {
          if (this.selectedLanguage) {
            params.set('clang', this.selectedLanguage)
          }
        }
        params.set('vocabs', this.formatVocabParam())

        return params
      },
      formatSearchApiParams () {
        const apiSearchTerm = this.searchTerm.includes('*') ? this.searchTerm : `${this.searchTerm}*`
        const params = new URLSearchParams({ query: apiSearchTerm, unique: true })
        const searchLang = this.getSearchLang() || window.SKOSMOS.lang
        params.set('lang', searchLang)
        params.set('vocab', this.formatVocabParam())

        return params
      },
      formatVocabParam () {
        const vocabs = this.getSelectedVocabs
        return vocabs.map(voc => voc.key).join(' ')
      },
      notationMatches (searchTerm, notation) {
        return notation?.toLowerCase()?.includes(searchTerm.toLowerCase()) === true
      },
      getSearchLang () {
        const urlParams = new URLSearchParams(window.location.search)
        const paramLang = urlParams.get('clang')
        const anyLang = urlParams.get('anylang')
        if (anyLang) {
          return 'all'
        }
        if (paramLang) {
          return paramLang
        }
        // otherwise pick content lang from SKOSMOS object
        if (window.SKOSMOS.content_lang) {
          return window.SKOSMOS.content_lang
        }
        // fall back to UI lang from SKOSMOS object
        if (window.SKOSMOS.lang) {
          return window.SKOSMOS.lang
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
          return {
            plaintext: label + langSpec
          }
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
            result.pageUrl = getConceptURL(result.uri, result.vocab)
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
        this.showDropdown = false
        this.$forceUpdate()
      },
      gotoSearchPage () {
        if (!this.searchTerm) return

        const searchUrlParams = this.formatSearchUrlParams()
        const searchUrl = window.SKOSMOS.baseHref + window.SKOSMOS.lang + '/search?' + searchUrlParams.toString()

        window.location.href = searchUrl
      },
      changeLang (clang) {
        this.resetSearchTermAndHideDropdown()
      },
      resetSearchTermAndHideDropdown () {
        // cancel any pending autocomplete request
        clearTimeout(this._timerId)
        this.searchCounter += 1
        this.searchTerm = ''
        this.renderedResultsList = []
        this.hideAutoComplete()

        this.$nextTick(() => {
          this.$refs.globalSearchInputField.focus()
        })
      },
      onLangMenuKeydown (e) {
        const items = Array.from(e.currentTarget.querySelectorAll('input'))
        console.log('Lang menu key')
        // prevent Bootstrap native radio button arrow left /arrow right behavior
        if (!items.length) return
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
          e.stopPropagation()
          return
        }
        const currentIndex = items.indexOf(document.activeElement)

        const focusAt = (newIndex) => {
          const i = (newIndex + items.length) % items.length
          items[i].focus()
        }

        switch (e.key) {
          case 'ArrowDown':
            e.preventDefault()
            e.stopPropagation()
            focusAt(currentIndex < 0 ? 0 : currentIndex + 1)
            break
          case 'ArrowUp':
            e.preventDefault()
            if (currentIndex === 0) {
              const dropdownWrapper = e.currentTarget.closest('.dropdown')
              const btn = dropdownWrapper.querySelector('.dropdown-toggle')
              const dropdownBtn = bootstrap.Dropdown.getOrCreateInstance(btn)
              dropdownBtn.hide()
              btn.focus()
              return
            }
            focusAt(currentIndex - 1)
            break
          case 'Enter': {
            e.preventDefault()
            if (currentIndex < 0) break
            items[currentIndex].parentElement.click()
            const btn = e.currentTarget.closest('.dropdown').querySelector('.dropdown-toggle')
            btn.focus()
            break
          }
          case 'Home':
            e.preventDefault()
            focusAt(0)
            break
          case 'End':
            e.preventDefault()
            focusAt(items.length - 1)
            break
          case 'Escape': {
            e.preventDefault()
            if (currentIndex < 0) return
            items[currentIndex].click()
            const btn = e.currentTarget.closest('.dropdown').querySelector('.dropdown-toggle')
            bootstrap.Dropdown.getOrCreateInstance(btn).hide()
            btn.focus()
            break
          }
        }
      },
      onVocabMenuKeydown (e) {
        const items = Array.from(e.currentTarget.querySelectorAll('input'))
        if (!items.length) return
        let currentIndex = items.indexOf(document.activeElement)

        if (currentIndex === -1) {
          currentIndex = 0
          items[currentIndex].focus()
        }

        const focusAt = (newIndex) => {
          const i = (newIndex + items.length) % items.length
          items[i].focus()
        }

        switch (e.key) {
          case 'ArrowDown':
            e.preventDefault()
            e.stopPropagation()
            focusAt(currentIndex < 0 ? 0 : currentIndex + 1)
            break
          case 'ArrowUp':
            e.preventDefault()
            if (currentIndex === 0) {
              const dropdownWrapper = e.currentTarget.closest('.dropdown')
              const btn = dropdownWrapper.querySelector('.dropdown-toggle')
              const dropdownBtn = bootstrap.Dropdown.getOrCreateInstance(btn)
              dropdownBtn.hide()
              btn.focus()
              return
            }
            focusAt(currentIndex - 1)
            break
          case 'Enter':
            e.preventDefault()
            items[currentIndex].click()
            break

          case 'Home':
            e.preventDefault()
            focusAt(0)
            break
          case 'End':
            e.preventDefault()
            focusAt(items.length - 1)
            break
          case 'Escape': {
            e.preventDefault()
            const btn = e.currentTarget.closest('.dropdown').querySelector('.dropdown-toggle')
            const dropdownBtn = bootstrap.Dropdown.getInstance(btn)
            dropdownBtn.toggle()
            btn.focus()
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
        }

        switch (e.key) {
          case 'ArrowDown':
            e.preventDefault()
            e.stopPropagation()
            focusAt(currentIndex < 0 ? 0 : currentIndex + 1)
            break

          case 'ArrowUp':
            e.preventDefault()
            e.stopPropagation()
            if (currentIndex <= 0) {
              // move focus back to the search input
              this.$refs.globalSearchInputField.focus()
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
            this.$refs.globalSearchInputField.focus()
            break

          case 'Enter':
            // let the browser follow the <a href> naturally
            break
        }
      },
      dropdownKeyNav (event, dropdownBtn) {
        const dropDownList = dropdownBtn.parentNode
        const dropdown = bootstrap.Dropdown.getInstance(dropdownBtn)

        switch (event.key) {
          case 'ArrowUp': {
            if (dropDownList.classList.contains('show')) { dropdown.hide() }
            break
          }

          case 'ArrowLeft': {
            const previousEl = dropDownList.parentNode.previousSibling
            if (previousEl) {
              const button = previousEl.querySelector('button')
              if (button) button.focus()
            }
            break
          }
          case 'ArrowRight': {
            const nextEl = dropDownList.parentNode.nextSibling
            if (nextEl) {
              const button = nextEl.querySelector('button')
              if (button) button.focus()
            }
            break
          }
          case 'Enter': {
            dropdownBtn.click()
            break
          }
        }
      },
      /*
      * Show the existing autocomplete list if it was hidden by onClickOutside()
      */
      showAutoComplete () {
        this.showDropdown = true
        this.$forceUpdate()
      },
      focusFirstResult () {
        const firstLink = this.$el?.querySelector('#search-autocomplete-results a')
        if (firstLink) firstLink.focus()
      }
    },
    template: `
      <div id="search-wrapper" class="input-group ps-xl-2 flex-nowrap">
        <div class="search-field-group">
          <span id="vocab-selector-label" class="search-field-label">{{ vocabSelectorLabel }}</span>
          <div class="dropdown" id="vocab-selector">
            <button
              type="button"
              class="btn btn-outline-secondary dropdown-toggle vocab-dropdown-btn"
              data-bs-toggle="dropdown"
              data-bs-auto-close="outside"
              aria-expanded="false"
              aria-controls="vocab-list"
              aria-labelledby="vocab-selector-label vocab-selector-current"
              v-if="languageStrings"
              v-key-nav="dropdownKeyNav"
            >
              <span id="vocab-selector-current" v-if="selectedVocabsString">{{ selectedVocabsString }}</span>
              <span id="vocab-selector-current" v-else>{{ allVocabularies }}</span>
              <i class="chevron fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
            <ul
              class="dropdown-menu"
              @keydown="onVocabMenuKeydown"
              id="vocab-list"
              aria-labelledby="vocab-selector-label">
              <li v-for="(value, key) in vocabStrings" :key="key" tabindex=-1>
                <label class="dropdown-item vocab-select">
                  <input
                    type="checkbox"
                    :value="key"
                    v-model="selectedVocabs"
                    tabindex=-1
                    @click.stop>
                    <span class="checkmark" aria-hidden="true"></span>
                  {{ value.short }}
                </label>
              </li>
            </ul>
          </div>
        </div>

        <div class="search-field-group">
          <span id="content-language-label" class="search-field-label">{{ langSelectorLabel }}</span>
          <div class="dropdown" id="language-selector">
            <button
              type="button"
              class="btn btn-outline-secondary dropdown-toggle"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              aria-controls="language-list"
              aria-labelledby="content-language-label content-language-current"
              v-key-nav="dropdownKeyNav"
              v-if="languageStrings">
                <span id="content-language-current" v-if="selectedLanguage && languageStrings[selectedLanguage]">
                  {{ languageStrings[selectedLanguage] }}
                </span>
                <span id="content-language-current" v-else>{{ allLanguages }}</span>
              <i class="chevron fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
            <ul
              class="dropdown-menu"
              @keydown="onLangMenuKeydown"
              id="language-list"
              role="radiogroup"
              aria-labelledby="content-language-label">
              <li v-for="(value, key) in languageStrings" :key="key" tabindex=-1>
                <label class="dropdown-item">
                  <input
                    type="radio"
                    name="content-language"
                    :value="key"
                    tabindex=-1
                    @change="changeLang(key)"
                    @keydown.left.prevent
                    @keydown.right.prevent
                    @click.stop
                    v-model="selectedLanguage">
                  {{ value }}
                </label>
              </li>
            </ul>
          </div>
        </div>

        <div class="search-field-group">
          <label for="search-field" class="search-field-label">{{ searchLabel }}</label>
          <div class="input-group flex-nowrap" id="search-form">
            <span id="headerbar-search" class="dropdown">
              <input type="search"
                ref="globalSearchInputField"
                class="form-control"
                id="search-field"
                autocomplete="off"
                data-bs-toggle=""
                v-click-outside="hideAutoComplete"
                v-model="searchTerm"
                @input="autoComplete($event)"
                @keydown.down="focusFirstResult()"
                @keyup.enter="gotoSearchPage()"
                @click="showAutoComplete()">
              <ul id="search-autocomplete-results"
                  class="dropdown-menu w-100"
                  :class="{ 'show': showDropdown }"
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
                            <template v-if="result.hit.match">
                              {{ result.hit.before }}<b>{{ result.hit.match }}</b>{{ result.hit.after }}
                            </template>
                            <template v-else>
                              {{ result.hit.plaintext }}
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
                                {{ result.hit.plaintext }}
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
                                {{ result.hitPref.plaintext }}
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
                              {{ result.hit.plaintext }}
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
                              {{ result.hit.plaintext }}
                            </template>
                          </span>
                        </div>
                        <div class="col-auto align-self-end pr-1" v-html="result.renderedType"></div>
                        <div class="result-vocab-title">{{ vocabStrings[result.vocab].title }}</div>
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
                    :aria-label="clearSearchAriaMessage"
                    type="clear"
                    v-if="searchTerm"
                    @click="resetSearchTermAndHideDropdown()">
              <i class="fa-solid fa-xmark"></i>
            </button>
            <button id="search-button" class="btn btn-outline-secondary" :aria-label="searchButtonAriaMessage" @click="gotoSearchPage()">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
          </div>
        </div>
      </div>
    `
  })

  globalSearch.directive('click-outside', {
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

  globalSearch.directive('key-nav', {
    beforeMount: (el, binding) => {
      const handler = event => {
        const { key } = event
        // Keep default Bootstrap behavior on these keys:
        if (key === 'Tab' || key === 'Escape' || key === ' ') return

        const handledKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Enter']
        if (!handledKeys.includes(key)) return

        if (!el.contains(document.activeElement)) return

        event.preventDefault()

        if (typeof binding.value === 'function') {
          binding.value(event, el)
        }
      }
      el.__keynavHandler__ = handler
      el.addEventListener('keydown', handler)
    },
    unmounted: el => {
      el.removeEventListener('keydown', el.__keynavHandler__)
      delete el.__keynavHandler__
    }
  })

  if (document.getElementById('global-search-wrapper')) {
    globalSearch.mount('#global-search-wrapper')
  }
}

onTranslationReady(startGlobalSearchApp)
