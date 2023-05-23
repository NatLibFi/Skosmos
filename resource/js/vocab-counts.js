/* global Vue */
/* global SKOSMOS */

const resourceCountsApp = Vue.createApp({
  data () {
    return {
      concepts: {},
      subTypes: {},
      conceptGroups: {}
    }
  },
  mounted () {
    fetch('rest/v1/' + SKOSMOS.vocab + '/vocabularyStatistics?lang=' + SKOSMOS.lang)
      .then(data => {
        return data.json()
      })
      .then(data => {
        console.log('resource count', data)
        this.concepts = data.concepts
        this.subTypes = data.subTypes
        this.conceptGroups = data.conceptGroups
      })
  },
  template: `
    <h3 class="fw-bold py-3">Resource counts by type</h3>
    <table class="table" id="resource-stats">
      <tbody>
        <tr><th class="versal">Type</th><th class="versal">Count</th></tr>
        <resource-counts :concepts="concepts" :subTypes="subTypes" :conceptGroups="conceptGroups"></resource-counts>
      </tbody>
    </table>
  `
})

resourceCountsApp.component('resource-counts', {
  props: ['concepts', 'subTypes', 'conceptGroups'],
  template: `
    <tr>
      <td>{{ concepts.label }}</td>
      <td>{{ concepts.count }}</td>
    </tr>
    <tr v-for="st in subTypes">
      <td>* {{ st.label }}</td>
      <td>{{ st.count }}</td>
    </tr>
    <tr>
      <td>* Käytöstä poistettu käsite</td>
      <td>{{ concepts.deprecatedCount }}</td>
    </tr>
    <tr v-if="conceptGroups">
      <td>{{ conceptGroups.label }}</td>
      <td>{{ conceptGroups.count }}</td>
    </tr>
  `
})

resourceCountsApp.mount('#resource-counts')
