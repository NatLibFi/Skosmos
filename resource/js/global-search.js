/* global Vue, $t, onTranslationReady */

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
      allLanguages () {
        return $t('All languages')
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
      this.selectedLanguage = this.updateSearchLang()
      this.languageStrings = this.formatLangStrings()
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
      formatLangStrings () {
        const langStrings = window.SKOSMOS.langStrings
        const allLanguegesEntry = { all: this.allLanguages }
        const formattedList = { ...allLanguegesEntry, ...langStrings }
        return formattedList

      },
      formatSearchUrlParams () {
        const params = new URLSearchParams({ q: this.searchTerm })
        if (this.selectedLanguage === 'all') {
          params.set('anylang', 'on')
        } else {
          params.set('clang', this.selectedLanguage)
        }
        params.set('vocabs', this.formatVocabParam())

        return params
      },
      formatSearchApiParams () {
        const apiSearchTerm = this.searchTerm.includes('*') ? this.searchTerm : `${this.searchTerm}*`
        const params = new URLSearchParams({ query: apiSearchTerm, unique: true })
        params.set('lang', this.getSearchLang())
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
        // otherwise pick content lang from SKOSMOS object (it should always exist)
        if (window.SKOSMOS.content_lang) {
          return window.SKOSMOS.content_lang
        }
        return null
      },
      updateSearchLang () {
        const lang = this.getSearchLang()
        if (lang) { this.changeLang(lang) }
        return lang
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
      translateType (type) {
        return $t(type)
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
            result.pageUrl = result.uri
            const urlParams = this.formatSearchUrlParams()
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
      /*
      * Show the existing autocomplete list if it was hidden by onClickOutside()
      */
      showAutoComplete () {
        this.showDropdown = true
        this.$forceUpdate()
      }
    },
    template: `
      <div id="search-wrapper">
        <div class="dropdown" id="vocab-selector">
          <button class="btn btn-outline-secondary dropdown-toggle vocab-dropdown-btn"
            role="button"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            :aria-label="selectSearchLanguageAriaMessage"
            v-if="languageStrings">
            <span v-if="selectedVocabsString">{{ selectedVocabsString }}</span>
            <span v-else>{{ vocabSelectorPlaceholder }}</span>
            <i class="chevron fa-solid fa-chevron-down"></i>
          </button>
          <ul class="dropdown-menu" id="vocab-list" role="menu">
            <li v-for="(value, key) in vocabStrings" :key="key">
              <label class="vocab-select">
                <input
                  type="checkbox"
                  :value="key"
                  v-model="selectedVocabs"
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
        <div class="input-group" id="search-form">
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

  if (document.getElementById('global-search-wrapper')) {
    globalSearch.mount('#global-search-wrapper')
  }
}

onTranslationReady(startGlobalSearchApp)
