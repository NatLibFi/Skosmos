/* global Vue */
/* global SKOSMOS */

const vocabSearch = Vue.createApp({
  data () {
    return {
      languages: [],
      selectedLanguage: null,
      searchTerm: null,
      renderedResultsList: [],
      languageStrings: null,
      msgs: null
    }
  },
  mounted () {
    this.languages = SKOSMOS.languageOrder
    this.selectedLanguage = SKOSMOS.content_lang
    this.languageStrings = SKOSMOS.language_strings[SKOSMOS.lang] ?? SKOSMOS.language_strings.en
    this.msgs = SKOSMOS.msgs[SKOSMOS.lang] ?? SKOSMOS.msgs.en
    this.renderedResultsList = []
  },
  methods: {
    autoComplete () {
      const delayMs = 300

      // when new autocomplete is fired, empty the preivous result
      this.hideDropdown()

      // cancel pending API calls when method is called
      clearTimeout(this._timerId)

      // is the search term is in cache, use the cache

      // delay call, but don't execute if the search term is not at least two characters
      if (this.searchTerm.length > 1) {
        this._timerId = setTimeout(() => { this.search() }, delayMs)
      }
    },
    /*
     * search should fetch the response and save it to cache
     * search calls renderResults for displaying the response
     */
    search () {
      fetch('rest/v1/' + SKOSMOS.vocab + '/search?query=' + this.formatSearchTerm() + '&lang=' + SKOSMOS.lang)
        .then(data => data.json())
        .then(data => {
          this.renderedResultsList = data.results // update results (update cache if it is implemented)
          this.renderResults() // render after the fetch has finished
        })
    },
    formatSearchTerm () {
      if (this.searchTerm.includes('*')) { return this.searchTerm }
      const formatted = this.searchTerm + '*'
      return formatted
    },
    /*
     * renderResults is used when the search string has been indexed in the cache
     * it also shows the autocomplete results list
     * TODO: Showing labels in other languages, extra concept information and such goes here
     */
    renderResults () {
      // get the results list form cache if it is implemented

      this.renderedResultsList.forEach(result => {
        if ('uri' in result) { // change uris to Skosmos page urls
          result.uri = SKOSMOS.vocab + '/' +  SKOSMOS.lang + '/page?uri=' +  encodeURIComponent(result.uri)
        }
      })

      if (this.renderedResultsList.length === 0) { // show no results message
        this.renderedResultsList.push({
          prefLabel: this.msgs['No results'],
          lang: SKOSMOS.lang
        })
      }
      const element = document.getElementById('search-autocomplete-results')
      element.classList.add('show')
      console.log(this.renderedResultsList)
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
      let langParam = '&clang=' + SKOSMOS.content_lang
      if (this.selectedLanguage === 'all') langParam += '&anylang=on'
      const searchUrl = vocabHref + 'search?q=' + this.searchTerm + langParam
      window.location.href = searchUrl
    },
    changeLang () {
      SKOSMOS.content_lang = this.selectedLanguage
      // TODO: Impelement partial page load to change content according to the new content language
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
          <ul id="search-autocomplete-results" class="dropdown-menu w-100"
            aria-labelledby="search-field">
            <li v-for="result in renderedResultsList"
              :key="result.prefLabel"
              class="cursor-pointer hover:bg-gray-100 p-1" >
              <template v-if="result.uri">
                <a :href="result.uri">{{ result.prefLabel }}</a>
              </template>
              <template v-else>
                {{ result.prefLabel }}
              </template>
            </li>
          </ul>
        </span>
        <button id="clear-button" class="btn btn-danger" type="clear" v-if="searchTerm" @click="searchTerm = ''" @click="hideDropdown()">
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
