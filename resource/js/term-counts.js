/* global Vue */

const termCountsApp = Vue.createApp({
  data () {
    return {
      languages: []
    }
  },
  mounted () {
    fetch('rest/v1/' + window.SKOSMOS.vocab + '/labelStatistics?lang=' + window.SKOSMOS.lang)
      .then(data => {
        console.log('a')
        return data.json()
      })
      .then(data => {
        console.log('b')
        this.languages = data.languages
      })
  },
  template: `
    <h3 class="fw-bold py-3">Term counts by language</h3>
    
      <table class="table" id="term-stats">
        <tbody>
          <tr>
            <th class="main-table-label fw-bold">Concept language</th>
            <th class="main-table-label fw-bold">Preferred terms</th>
            <th class="main-table-label fw-bold">Alternate terms</th>
            <th class="main-table-label fw-bold">Hidden terms</th>
          </tr>
          <term-counts :languages="languages"></term-counts>
        </tbody>
      </table>
    
  `
})

termCountsApp.component('term-counts', {
  props: ['languages'],
  template: `
    <template v-if="languages.length">
      <tr v-for="l in languages">
        <td>{{ l.literal }}</td>
        <td>{{ l.properties.find(a => a.property === 'skos:prefLabel').labels }}</td>
        <td>{{ l.properties.find(a => a.property === 'skos:altLabel').labels }}</td>
        <td>{{ l.properties.find(a => a.property === 'skos:hiddenLabel').labels }}</td>
      </tr>
    </template>
    <template v-else >
      <i class="fa-solid fa-spinner fa-spin-pulse"></i>
    </template>
  `
})

termCountsApp.mount('#term-counts')
