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
      const params = new URLSearchParams({ lang: window.SKOSMOS.lang })
      fetch(`rest/v1/${window.SKOSMOS.vocab}/labelStatistics?${params}`)
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
            <tr class="term-counts-header">
              <th>{{ conceptLanguageLabel }}</th>
              <th>{{ preferredTermsLabel }}</th>
              <th>{{ alternateTermsLabel }}</th>
              <th>{{ hiddenTermsLabel }}</th>
            </tr>
            <template v-if="languages.length">
              <tr v-for="l in languages" :key="l.literal">
                <td class="term-counts-lang">{{ l.literal }}</td>
                <td>
                  <span class="d-inline d-md-none" aria-hidden="true">{{ preferredTermsLabel }}</span>
                  {{ l.properties.find(a => a.property === 'skos:prefLabel').labels }}
                </td>
                <td>
                  <span class="d-inline d-md-none" aria-hidden="true">{{ alternateTermsLabel }}</span>
                  {{ l.properties.find(a => a.property === 'skos:altLabel').labels }}
                </td>
                <td>
                  <span class="d-inline d-md-none" aria-hidden="true">{{ hiddenTermsLabel }}</span>
                  {{ l.properties.find(a => a.property === 'skos:hiddenLabel').labels }}
                </td>
              </tr>
            </template>
            <template v-else>
              <tr><td colspan="4"><i class="fa-solid fa-spinner fa-spin-pulse"></i></td></tr>
            </template>
          </tbody>
        </table>
      `
  })

  if (document.getElementById('term-counts')) {
    termCountsApp.mount('#term-counts')
  }
}

onTranslationReady(startTermCountsApp)
