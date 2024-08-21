async function loadLocaleMessages() {
  const locales = ['en', 'fi'];
  const messages = {};

  for (const locale of locales) {
    const response = await fetch(`http://localhost/skosmos/resource/translations/messages.${locale}.json`);
    const data = await response.json();
    console.log(data);
    messages[locale] = data;
  }

  return messages;
}

async function createI18nInstance() {
  const messages = await loadLocaleMessages();

  const i18n = VueI18n.createI18n({
    locale: window.SKOSMOS.lang || 'en', // oletuskieli
    fallbackLocale: 'en', // Fallback
    messages,
  });

  return i18n;
}

(async function() {
  const i18n = await createI18nInstance();

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
      }
    },
    mounted () {
      fetch('rest/v1/' + window.SKOSMOS.vocab + '/vocabularyStatistics?lang=' + window.SKOSMOS.lang)
          .then(data => {
            return data.json()
          })
          .then(data => {
            this.concepts = data.concepts
            this.subTypes = data.subTypes
            this.conceptGroups = data.conceptGroups
          })
    },
    template: `
      <h3 class="fw-bold py-3">{{ $t('Resource counts by type') }}</h3>
      <table class="table" id="resource-stats">
        <tbody>
        <tr><th class="versal">{{ $t('Type') }}</th><th class="versal">{{ $t('Count') }}</th></tr>
        <template v-if="hasCounts">
          <resource-counts :concepts="concepts" :subTypes="subTypes" :conceptGroups="conceptGroups"></resource-counts>
        </template>
        <template v-else >
          <i class="fa-solid fa-spinner fa-spin-pulse"></i>
        </template>
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

  resourceCountsApp.use(i18n);
  resourceCountsApp.mount('#resource-counts');
})();
