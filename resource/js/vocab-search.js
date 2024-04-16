/* global Vue */

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
    this.languages = window.SKOSMOS.languageOrder
    this.selectedLanguage = window.SKOSMOS.content_lang
    this.languageStrings = window.SKOSMOS.language_strings[window.SKOSMOS.lang]
  },
  methods: {
    autoComplete () {

    },
    gotoSearchPage () {
      if (!this.searchTerm) return

      const currentVocab = window.SKOSMOS.vocab + '/' + window.SKOSMOS.lang + '/'
      const vocabHref = window.location.href.substring(0, window.location.href.lastIndexOf(window.SKOSMOS.vocab)) + currentVocab
      let langParam = '&clang=' + window.SKOSMOS.content_lang
      if (this.selectedLanguage === 'all') langParam += '&anylang=on'
      const searchUrl = vocabHref + 'search?q=' + this.searchTerm + langParam
      window.location.href = searchUrl
    },
    changeLang () {
      window.SKOSMOS.content_lang = this.selectedLanguage
      // TODO: Impelemnt partial page load to change content according to the new content language
    }
  },
  template: `
    <div class="d-flex mb-2 my-auto ms-auto">
      <div class="input-group" id="search-wrapper">
        <select class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown-item" aria-expanded="false"
          v-model="selectedLanguage"
          @change="changeLang()"
          aria-label="Select search language">
          <option class="dropdown-item" v-for="(value, key) in languageStrings" :value="key">{{ value }}</option>
        </select>
        <input type="search" class="form-control" aria-label="Text input with dropdown button" placeholder="Search..."
          v-model="searchTerm"
          @input="autoComplete()"
          @keyup.enter="gotoSearchPage()"
        >
        <button id="clear-button" class="btn btn-danger" type="clear" v-if="searchTerm" @click="searchTerm = ''">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <button id="search-button" class="btn btn-outline-secondary" aria-label="Search" @click="gotoSearchPage()">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </div>
    </div>
  `
})

vocabSearch.mount('#search-vocab')
