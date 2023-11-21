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
      console.log(this.selectedLanguage)
      // Partial page load to change content language
    }
  },
  template: `
    <div>
      <select v-model="selectedLanguage" @change="changeLang">
        <option v-for="lang in languages" v-bind:value="lang" >{{ lang }}</option>
        <option value="all">All</option>
      </select>
      <input v-model="searchTerm" @input="autoComplete" @keyup.enter="gotoSearchPage" placeholder="Search...">
      <button @click="gotoSearchPage">Suurennuslasi</button>
    </div>
  `
})

vocabSearch.mount('#search-vocab')
