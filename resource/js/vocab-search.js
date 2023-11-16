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
    autoComplete() {
      if (!this.searchTerm) return;
      console.log("Auto complete: " + this.searchTerm)
    },
    gotoSearchPage() {
        if (!this.searchTerm) return;

        var currentVocab = SKOSMOS.vocab + "/" + SKOSMOS.lang + "/"
        var vocabHref = window.location.href.substring(0, window.location.href.lastIndexOf(SKOSMOS.vocab)) + currentVocab
        var langParam = "&clang=" + SKOSMOS.content_lang
        if (this.selectedLanguage == 'all') langParam += "&anylang=on"
        var searchUrl = vocabHref + "search?q=" + this.searchTerm + langParam

        location.href = searchUrl;
    },
    changeLang() {
        console.log(this.selectedLanguage);
        // Partial page load to change content language
        return;
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
