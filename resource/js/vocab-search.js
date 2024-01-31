/* global Vue */
/* global SKOSMOS */

const vocabSearch = Vue.createApp({
  data () {
    return {
      languages: [],
      selectedLanguage: null,
      searchTerm: null,
      autoCompeteResults: [],
      languageStrings: null
    }
  },
  mounted () {
    this.languages = SKOSMOS.languageOrder
    this.selectedLanguage = SKOSMOS.content_lang
    this.languageStrings = SKOSMOS.language_strings[SKOSMOS.lang]
    this.autoCompeteResults = []
  },
  methods: {
    autoComplete () {
    /* Take the input string
     *   - Once user has stopped typing aftes X ms, submit the search
     *   - Append an asterix after the search term
     *   - Display a waiting spinner? : NO
     *   - Process the search results into autoCompeteResults
     *   - If the user writes more text in addition to previously given input (startswith), don't perform search
     *     - But wait for X ms and filter the existing result list
     *   - When the result list is calculated, display the dropdown
     *   - If there are no results, display a dropdown with no results -message
     *   - Hide the dropdown list, if the user
     *     - Clears the text box from the clear-button
     *     - Deletes the contentents of the input field
     *     - Clicks on somewhere outside of the search result dropdown
     *
     *   - Input element should be rectangular
     */
      const delayMs = 500
      // cancel pending API calls
      clearTimeout(this._timerId)
      // delay new call 500ms
      this._timerId = setTimeout(() => { this.search() }, delayMs)
    },
    search () {
      fetch('rest/v1/' + SKOSMOS.vocab + '/search?query=' + this.searchTerm + '*' + '&lang=' + SKOSMOS.lang)
      .then( data => { return data.json() } )
      .then( data => this.autoCompeteResults = data.results )

      this.renderResults()
    },
    renderResults () {
      const element = document.getElementById('search-autocomplete-results')
      element.classList.add('show')
    },
    hideDropdown () {
      const element = document.getElementById('search-autocomplete-results')
      element.classList.remove('show')
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
            <li v-for="result in autoCompeteResults"
              :key="result.prefLabel"
              class="cursor-pointer hover:bg-gray-100 p-1" >
              {{ result.prefLabel }}
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
