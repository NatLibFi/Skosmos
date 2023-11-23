/* global Vue */
/* global SKOSMOS */

const vocabSearch = Vue.createApp({
  data () {
    return {
      languages: [],
      selectedLanguage: null,
      searchTerm: null,
      autoCompeteResults: []
    }
  },
  mounted () {
    this.languages = SKOSMOS.languageOrder
    this.selectedLanguage = SKOSMOS.content_lang
  },
  methods: {
    autoComplete () {
      if (!this.searchTerm) return
      console.log('Auto complete: ' + this.searchTerm)
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
      console.log(SKOSMOS.content_lang)
      // Partial page load to change content according to the new content language:
    }
  },
  template: `
    <div class="d-flex mb-2 my-auto ms-auto">
      <div class="input-group" id="search-wrapper">
        <select class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown-item" aria-expanded="false"
          v-model="selectedLanguage"
          @change="changeLang()"
        >
          <option class="dropdown-item" v-for="lang in languages" :value="lang">{{ lang }}</option>
          <option class="dropdown-item" value="all">All</option>
        </select>
        <input type="search" class="form-control" aria-label="Text input with dropdown button" placeholder="Search..."
          v-model="searchTerm"
          @input="autoComplete()"
          @keyup.enter="gotoSearchPage()"
        >
        <button id="clear-button" class="btn btn-danger" type="clear" v-if="searchTerm" @click="searchTerm = ''">
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
