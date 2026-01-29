/* global Vue, bootstrap, $t, onTranslationReady */

function startGlobalSearchApp () {
  const globalSearch = Vue.createApp({
    data () {
      return {
        languages: [],
        selectedLanguage: null,
        selectedVocabs: [],
        searchTerm: null,
        searchCounter: null,
        renderedResultsList: [],
        languageStrings: null,
        vocabStrings: null,
        showDropdown: false,
        showNotation: null
      }
    },
    computed: {
      vocabSelectorPlaceholder () {
        return $t('1. Choose vocabulary')
      },
      langSelectorPlaceholder () {
        return $t('2. Choose language')
      },
      searchPlaceholder () {
        return $t('3. Enter search term')
      },
      anyLanguage () {
        return $t('Any language')
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
      },
      getSelectedVocabs () {
        return this.selectedVocabs.map(key => ({ key, value: this.vocabStrings[key] }))
      },
      selectedVocabsString () {
        return this.getSelectedVocabs.map(voc => voc.value).join(', ')
      }
    },
    mounted () {
      this.languages = window.SKOSMOS.languageOrder
      this.selectedLanguage = this.getSearchLang()
      this.languageStrings = this.formatLanguages()
      this.vocabStrings = window.SKOSMOS.vocab_list
    },
    watch: {
      selectedLanguage (newLang) {
        if (!newLang) return
        const url = new URL(window.location.href)
        if (newLang === 'all') {
          url.searchParams.set('anylang', 'on')
        } else {
          url.searchParams.set('clang', newLang)
          url.searchParams.delete('anylang')
        }
        window.history.replaceState({}, '', url.toString())
      }
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
        let skosmosSearchUrl = window.SKOSMOS.baseHref + 'rest/v1/search?'
        const skosmosSearchApiParams = this.formatSearchApiParams()
        skosmosSearchUrl += skosmosSearchApiParams.toString()
        fetch(skosmosSearchUrl)
          .then(data => data.json())
          .then(data => {
            if (mySearchCounter === this.searchCounter) {
              this.renderedResultsList = data.results // update results (update cache if it is implemented)
              this.renderResults() // render after the fetch has finished
            }
          })
      },
      formatLanguages () {
        const languages = window.SKOSMOS.contentLanguages
        const anyLanguageEntry = { all: this.anyLanguage }
        return { ...languages, ...anyLanguageEntry }
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
        return (label) || typeUri
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
            result.pageUrl = window.SKOSMOS.baseHref + result.vocab + '/' + window.SKOSMOS.lang + '/page/?'
            const urlParams = new URLSearchParams({ uri: result.uri })
            if (this.selectedLanguage) {
              urlParams.set('clang', this.selectedLanguage)
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
        this.selectedLanguage = clang
        this.resetSearchTermAndHideDropdown()
      },
      resetSearchTermAndHideDropdown () {
        this.searchTerm = ''
        this.renderedResultsList = []
        this.hideAutoComplete()
      },
      toggleLanguageDropdown () {
        this.showDropdown = !this.showDropdown
      },
      dropdownKeyNav (event, dropdownEl, activeEl) {
        if (!dropdownEl) return
        const vocabSelector = document.querySelector('#vocab-selector')
        const btn = document.querySelector('#vocab-selector .dropdown-toggle')
        const menu = document.querySelector('#vocab-selector .dropdown-menu')
        const dropdown = bootstrap.Dropdown.getInstance(btn)
        switch (event.key) {
          case 'ArrowUp': {
            event.preventDefault()
            if (menu.classList.contains('show')) { dropdown.hide() }
            break
          }
          case 'ArrowDown': {
            event.preventDefault()
            dropdown.show()
            const list = document.querySelector('#vocab-list')
            const first = list.firstElementChild
            if (first) first.focus()
            break
          }
          case 'ArrowLeft': {
            const previousEl = vocabSelector.previousSibling
            if (previousEl) {
              const button = previousEl.querySelector('button')
              if (button) button.focus()
            }
            break
          }
          case 'ArrowRight': {
            const nextEl = vocabSelector.nextSibling
            if (nextEl) {
              const button = nextEl.querySelector('button')
              if (button) button.focus()
            }
            break
          }
          case 'Enter': {
            event.preventDefault()
            dropdown.toggle()
            break
          }
        }
      },
      listKeyNav (event, list, activeEl) {
        if (!list) return
        const items = list.querySelectorAll('li')
        if (!items.length) return
        switch (event.key) {
          case 'ArrowUp':
            event.preventDefault()
            this.moveSelection(list, -1)
            break
          case 'ArrowDown':
            event.preventDefault()
            this.moveSelection(list, 1)
            break
          case 'Enter':
            event.preventDefault()
            document.activeElement.querySelector('input').click()
            break
        }
      },
      moveSelection (targetList, delta) {
        if (!targetList) return
        const items = targetList.querySelectorAll('li')
        const current = document.activeElement

        if (!items.length) return

        switch (delta) {
          case 1:
            current.nextElementSibling.focus()
            break
          case -1:
            if (current === targetList.firstElementChild) {
              const btn = document.querySelector('#vocab-selector .dropdown-toggle')
              const dropdown = bootstrap.Dropdown.getInstance(btn)
              btn.focus()
              dropdown.hide()
              break
            }
            current.previousElementSibling.focus()
            break
        }
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
      <div id="search-wrapper" class="input-group ps-xl-2 flex-nowrap">
        <div class="dropdown" id="vocab-selector">
          <button class="btn btn-outline-secondary dropdown-toggle vocab-dropdown-btn"
            role="button"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            :aria-label="selectSearchLanguageAriaMessage"
            v-if="languageStrings"
            v-key-nav="dropdownKeyNav"
          >
            <span v-if="selectedVocabsString">{{ selectedVocabsString }}</span>
            <span v-else>{{ vocabSelectorPlaceholder }}</span>
            <i class="chevron fa-solid fa-chevron-down"></i>
          </button>
          <ul
            class="dropdown-menu"
            id="vocab-list"
            role="menu"
            ref="vocabList"
            v-key-nav="listKeyNav"
          >
            <li v-for="(value, key) in vocabStrings" :key="key" tabindex=0>
              <label class="vocab-select">
                <input
                  type="checkbox"
                  :value="key"
                  v-model="selectedVocabs"
                  tabindex=-1
                  @click.stop>
                  <span class="checkmark"></span>
                {{ value }}
              </label>
            </li>
          </ul>
        </div>

        <div class="dropdown" id="language-selector">
          <button class="btn btn-outline-secondary dropdown-toggle"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            :aria-label="selectSearchLanguageAriaMessage"
            @click="toggleLanguageDropdown"
            v-if="languageStrings">
              <span v-if="selectedLanguage && languageStrings[selectedLanguage]">
                {{ languageStrings[selectedLanguage] }}
              </span>
              <span v-else>{{ langSelectorPlaceholder }}</span>
            <i class="chevron fa-solid fa-chevron-down"></i>
          </button>
          <ul class="dropdown-menu" id="language-list" role="menu">
            <li v-for="(value, key) in languageStrings" :key="key" role="none" tabindex=0>
              <label class="dropdown-item">
                <input
                  type="radio"
                  class="d-none"
                  :value="key"
                  v-model="selectedLanguage">
                {{ value }}
              </label>
            </li>
          </ul>
        </div>
        <div class="input-group flex-nowrap" id="search-form">
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
          binding.value(event, el, document.activeElement)
        }
      }
      el.__keynavHandler__ = handler
      el.addEventListener('keyup', handler)
    },
    unmounted: el => {
      el.removeEventListener('keyup', el.__keynavHandler__)
      delete el.__keynavHandler__
    }
  })

  if (document.getElementById('global-search-wrapper')) {
    globalSearch.mount('#global-search-wrapper')
  }
}

onTranslationReady(startGlobalSearchApp)
