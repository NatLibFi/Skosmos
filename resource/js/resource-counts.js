/* global Vue, $t, onTranslationReady */

function startResourceCountsApp () {
  const resourceCountsApp = Vue.createApp({
    data () {
      return {
        concepts: {},
        subTypes: {},
        conceptGroups: {}
      }
    },
    computed: {
      hasCounts () {
        return Object.keys(this.concepts).length > 0
      },
      resourceCountsTitle () {
        return $t('Resource counts by type')
      },
      typeLabel () {
        return $t('Type')
      },
      countLabel () {
        return $t('Count')
      },
      deprecatedConceptLabel () {
        return $t('Deprecated concept')
      }
    },
    mounted () {
      const params = new URLSearchParams({ lang: window.SKOSMOS.lang })
      fetch(`rest/v1/${window.SKOSMOS.vocab}/vocabularyStatistics?${params}`)
        .then(response => response.json())
        .then(data => {
          this.concepts = data.concepts
          this.subTypes = data.subTypes
          this.conceptGroups = data.conceptGroups
        })
    },
    template: `
        <h3 class="fw-bold py-3">{{ resourceCountsTitle }}</h3>
        <table class="table" id="resource-stats">
          <tbody>
            <tr>
              <th>{{ typeLabel }}</th>
              <th>{{ countLabel }}</th>
            </tr>
            <template v-if="hasCounts">
              <tr>
                <td>{{ concepts.label }}</td>
                <td>{{ concepts.count }}</td>
              </tr>
              <tr v-for="st in subTypes" :key="st.label">
                <td class="ps-md-4 ps-3 d-flex"><span class="me-1">&#x2022;</span>{{ st.label }}</td>
                <td>{{ st.count }}</td>
              </tr>
              <tr>
                <td class="ps-md-4 ps-3 d-flex"><span class="me-1">&#x2022;</span>{{ deprecatedConceptLabel }}</td>
                <td>{{ concepts.deprecatedCount }}</td>
              </tr>
              <tr v-if="conceptGroups">
                <td>{{ conceptGroups.label }}</td>
                <td>{{ conceptGroups.count }}</td>
              </tr>
            </template>
            <template v-else>
              <tr><td colspan="2"><i class="fa-solid fa-spinner fa-spin-pulse"></i></td></tr>
            </template>
          </tbody>
        </table>
      `
  })

  resourceCountsApp.component('resource-counts', {
    props: ['concepts', 'subTypes', 'conceptGroups', 'deprecatedConceptLabel'],
    template: `
        
      `
  })

  if (document.getElementById('resource-counts')) {
    resourceCountsApp.mount('#resource-counts')
  }
}

onTranslationReady(startResourceCountsApp)
