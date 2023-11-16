/* global Vue */
/* global SKOSMOS */

const vocabSearch = Vue.createApp({
  data () {
    return {
      languages: []
    }
  },
  mounted () { this.languages = SKOSMOS.languageOrder },
  template: `
    <div>
      <input v-model="searchTerm" @input="performSearch" placeholder="Search...">
      <select v-model="selectedLanguage" @change="performSearch">
        <option v-for="lang in languages">{{ lang }}</option>
        <option value="all">All</option>
      </select>
    </div>
  `
})

vocabSearch.mount('#search-vocab')
