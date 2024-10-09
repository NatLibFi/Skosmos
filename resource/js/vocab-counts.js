function startResourceCountsApp() {
// Tehdään wrappäys seuraavista syistä:
// - kutsutaan vasta, kun $t-funktio on valmis (jotta saadaan käännökset mukaan),
// usein riippuvuuksien kanssa pelataan näin
// ja näin ollen ei tule tilannetta, jossa $t:tä kutsuttaisiin ennen kuin se on valmis
// - Vue-appin ajastus on helpompaa, kun se on wrappayksen sisäll
// - Nyt vain startResourceCountsApp ja waitForTranslationService ovat globaaleja,
// jolloin tarkemmat määritykset rajoittuvat selkeästi omiin funktioihinsa
// - debuggauksen lisääminen on helpompaa

    const resourceCountsApp = Vue.createApp({
      data() {
        return {
          concepts: {},
          subTypes: {},
          conceptGroups: {}
        };
      },
      computed: {
        hasCounts() {
          return Object.keys(this.concepts).length > 0;
        },
        resourceCountsTitle() {
          return $t('Resource counts by type');
        },
        typeLabel() {
          return $t('Type');
        },
        countLabel() {
          return $t('Count');
        }
      },
      mounted() {
        fetch('rest/v1/' + window.SKOSMOS.vocab + '/vocabularyStatistics?lang=' + window.SKOSMOS.lang)
          .then(response => response.json())
          .then(data => {
            this.concepts = data.concepts;
            this.subTypes = data.subTypes;
            this.conceptGroups = data.conceptGroups;
          });
      },
      template: `
        <h3 class="fw-bold py-3">{{ resourceCountsTitle }}</h3>
        <table class="table" id="resource-stats">
          <tbody>
            <tr><th class="versal">{{ typeLabel }}</th><th class="versal">{{ countLabel }}</th></tr>
            <template v-if="hasCounts">
              <resource-counts :concepts="concepts" :subTypes="subTypes" :conceptGroups="conceptGroups"></resource-counts>
            </template>
            <template v-else>
              <i class="fa-solid fa-spinner fa-spin-pulse"></i>
            </template>
          </tbody>
        </table>
      `
    });
  
    resourceCountsApp.component('resource-counts', {
      props: ['concepts', 'subTypes', 'conceptGroups'],
      template: `
        <tr>
          <td>{{ concepts.label }}</td>
          <td>{{ concepts.count }}</td>
        </tr>
        <tr v-for="st in subTypes" :key="st.label">
          <td>* {{ st.label }}</td>
          <td>{{ st.count }}</td>
        </tr>
        <tr>
          <td>* Deprecated concept</td>
          <td>{{ concepts.deprecatedCount }}</td>
        </tr>
        <tr v-if="conceptGroups">
          <td>{{ conceptGroups.label }}</td>
          <td>{{ conceptGroups.count }}</td>
        </tr>
      `
    });

    resourceCountsApp.mount('#resource-counts');
  }
  
  // Tarkistetaan, että $t on valmis, joten myös käännökset ovat valmiita
  function waitForTranslationService() {
    if (typeof $t !== 'undefined') {
      startResourceCountsApp(); // $t on valmis, joten Vue-appia voidaan kutsua
    } else {
      setTimeout(waitForTranslationService, 50);
    }
  }
  
  // Odotellaan $t:tä
  waitForTranslationService();
  

  //   Toimiva