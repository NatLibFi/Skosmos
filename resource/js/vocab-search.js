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
      this.autoCompeteResults = [
        {
          uri: 'http://www.yso.fi/onto/yso/p19378',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p19378',
          prefLabel: 'kissa',
          lang: 'fi',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p864',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p864',
          prefLabel: 'kissaeläimet',
          lang: 'fi',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p18191',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p18191',
          prefLabel: 'kissamaki',
          lang: 'fi',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p17951',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p17951',
          prefLabel: 'kissanhuuto-oireyhtymä',
          lang: 'fi',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p29087',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p29087',
          prefLabel: 'kissankasvattajat',
          lang: 'fi',
          hiddenLabel: 'kissankasvattaja',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p29087',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p29087',
          prefLabel: 'kissankasvattajat',
          lang: 'fi',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p21153',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p21153',
          prefLabel: 'kissankello',
          lang: 'fi',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p29557',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p29557',
          prefLabel: 'kissankäpälälude',
          lang: 'fi',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p26343',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p26343',
          prefLabel: 'kissanäyttelyt',
          lang: 'fi',
          hiddenLabel: 'kissanäyttely',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p26343',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p26343',
          prefLabel: 'kissanäyttelyt',
          lang: 'fi',
          vocab: 'yso'
        },
        {
          uri: 'http://www.yso.fi/onto/yso/p19378',
          type: [
            'skos:Concept',
            'http://www.yso.fi/onto/yso-meta/Concept'
          ],
          localname: 'p19378',
          prefLabel: 'kissa',
          lang: 'fi',
          altLabel: 'kissat',
          vocab: 'yso'
        }
      ]
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
