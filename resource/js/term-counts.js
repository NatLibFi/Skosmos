/* global Vue, $t, onTranslationReady */

function startTermCountsApp () {
  const termCountsApp = Vue.createApp({
    data () {
      return {
        languages: []
      }
    },
    computed: {
      termCountsTitle () {
        return $t('Term counts by language')
      },
      conceptLanguageLabel () {
        return $t('Concept language')
      },
      preferredTermsLabel () {
        return $t('Preferred terms')
      },
      alternateTermsLabel () {
        return $t('Alternate terms')
      },
      hiddenTermsLabel () {
        return $t('Hidden terms')
      }
    },
    mounted () {
      fetch('rest/v1/' + window.SKOSMOS.vocab + '/labelStatistics?lang=' + window.SKOSMOS.lang)
        .then(data => {
          return data.json()
        })
        .then(data => {
          this.languages = data.languages
        })
    },
    template: `
        <h3 class="fw-bold py-3">{{ termCountsTitle }}</h3>
        <table class="table" id="term-stats">
          <tbody>
            <tr>
              <th class="main-table-label fw-bold">{{ conceptLanguageLabel }}</th>
              <th class="main-table-label fw-bold">{{ preferredTermsLabel }}</th>
              <th class="main-table-label fw-bold">{{ alternateTermsLabel }}</th>
              <th class="main-table-label fw-bold">{{ hiddenTermsLabel }}</th>
            </tr>
            <template v-if="languages.length">
              <term-counts :languages="languages"></term-counts>
            </template>
            <template v-else>
              <i class="fa-solid fa-spinner fa-spin-pulse"></i>
            </template>
          </tbody>
        </table>
      `
  })

  termCountsApp.component('term-counts', {
    props: ['languages'],
    template: `
        <tr v-for="l in languages" :key="l.literal">
          <td>{{ l.literal }}</td>
          <td>{{ l.properties.find(a => a.property === 'skos:prefLabel').labels }}</td>
          <td>{{ l.properties.find(a => a.property === 'skos:altLabel').labels }}</td>
          <td>{{ l.properties.find(a => a.property === 'skos:hiddenLabel').labels }}</td>
        </tr>
      `
  })

  termCountsApp.mount('#term-counts')
}

onTranslationReady(startTermCountsApp)
