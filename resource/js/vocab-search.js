/* global Vue */
/* global SKOSMOS */

const vocabSearch = Vue.createApp({
  data () {
    return {
      languages: [],
      selectedLanguage: "",
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
      console.log(this.selectedLanguage)
      // Partial page load to change content language
    }
  },
  template: `
    <div class="d-flex mb-2 my-auto ms-auto">
    <form class="input-group" id="search-wrapper">
      <select v-model="selectedLanguage" @change="changeLang" class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown-item" aria-expanded="false">
        <option class="dropdown-item" v-for="lang in languages" v-bind:value="lang" >{{ lang }}</option>
        <option class="dropdown-item" value="all">All</option>
      </select>
      <input v-model="searchTerm" @input="autoComplete" @keyup.enter="gotoSearchPage" type="search" class="form-control" aria-label="Text input with dropdown button" placeholder="Search...">
      <button @click="gotoSearchPage" id="search-button" class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"/></button>
    </form>
    </div>
  `
})

vocabSearch.mount('#search-vocab')
