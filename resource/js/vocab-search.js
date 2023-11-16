/* global Vue */
/* global SKOSMOS */

const vocabSearch = Vue.createApp({
  data () {
    return {
      languages: [],
      selectedLanguage: {}
    }
  },
  mounted () {
      this.languages = SKOSMOS.languageOrder
      this.selectedLanguage = SKOSMOS.content_lang
  },
  template: `
    <div>
      <input v-model="searchTerm" @input="performSearch" placeholder="Search...">
      <select v-model="selectedLanguage" @change="performSearch">
        <option v-for="lang in languages" v-bind:value="lang" >{{ lang }}</option>
        <option value="all">All</option>
      </select>
    </div>
  `
})

vocabSearch.mount('#search-vocab')
