/* global Vue, $t */
/* global partialPageLoad, getConceptURL */

function startChangesApp () {
  const tabChangesApp = Vue.createApp({
    data () {
      return {
        changedConcepts: new Map(),
        selectedConcept: '',
        loadingConcepts: false,
        listStyle: {}
      }
    },
    computed: {
      loadingMessage () {
        return $t('Loading more items')
      }
    },
    provide () {
      return {
        partialPageLoad,
        getConceptURL
      }
    },
    mounted () {
      // load changes if changes tab is active when the page is first opened (otherwise only load the changes when the tab is clicked)
      if (document.querySelector('#changes > a').classList.contains('active')) {
        this.loadChanges()
      }
    },
    beforeUpdate () {
      this.setListStyle()
    },
    methods: {
      handleClickChangesEvent () {
        // only load changes the first time the page is opened or if selected concept has changed
        if (this.changedConcepts.length === 0 || this.selectedConcept !== window.SKOSMOS.uri) {
          this.changedConcepts = new Map()
          this.selectedConcept = ''
          this.loadChanges()
        }
      },
      loadChanges () {
        this.loadingConcepts = true
        fetch('rest/v1/' + window.SKOSMOS.vocab + '/new?lang=' + window.SKOSMOS.content_lang + '&limit=200')
          .then(data => {
            return data.json()
          })
          .then(data => {
            console.log('data', data)

            // Group concepts by month and year
            // Using a map instead of an object because maps maintain original insertion order
            const changesByDate = new Map()
            for (const concept of data.changeList) {
              const date = new Date(concept.date)
              let key = date.toLocaleString(window.SKOSMOS.lang, { month: 'long', year: 'numeric' })

              // Reverse Northern Sami word order (year month -> month year)
              if (window.SKOSMOS.lang === 'se') {
                key = key.split(' ').reverse().join(' ')
              }
              // Capitalize month name
              key = key.charAt(0).toUpperCase() + key.slice(1)

              if (!changesByDate.get(key)) {
                changesByDate.set(key, [])
              }

              changesByDate.get(key).push(concept)
            }

            this.changedConcepts = changesByDate
            this.loadingConcepts = false
            console.log('changes', this.changedConcepts)
          })
      },
      setListStyle () {
        const height = document.getElementById('sidebar-tabs').clientHeight
        const width = document.getElementById('sidebar-tabs').clientWidth - 1
        this.listStyle = {
          height: 'calc( 100% - ' + height + 'px )',
          width: width + 'px'
        }
      }
    },
    template: `
      <div v-click-tab-changes="handleClickChangesEvent">
        <tab-changes
          :changed-concepts="changedConcepts"
          :selected-concept="selectedConcept"
          :loading-concepts="loadingConcepts"
          :loading-message="loadingMessage"
          :list-style="listStyle"
          @select-concept="selectedConcept = $event"
        ></tab-changes>
      </div>
    `
  })

  /* Custom directive used to add an event listener on clicks on the changes nav-item element */
  tabChangesApp.directive('click-tab-changes', {
    beforeMount: (el, binding) => {
      el.clickTabEvent = event => {
        binding.value() // calling the method given as the attribute value (loadChanges)
      }
      document.querySelector('#changes').addEventListener('click', el.clickTabEvent) // registering an event listener on clicks on the changes nav-item element
    },
    unmounted: el => {
      document.querySelector('#changes').removeEventListener('click', el.clickTabEvent)
    }
  })

  tabChangesApp.component('tab-changes', {
    props: ['changedConcepts', 'selectedConcept', 'loadingConcepts', 'loadingMessage', 'listStyle'],
    inject: ['partialPageLoad', 'getConceptURL'],
    emits: ['selectConcept'],
    methods: {
      loadConcept (event, uri) {
        partialPageLoad(event, getConceptURL(uri))
        this.$emit('selectConcept', uri)
      }
    },
    template: `
      <div class="sidebar-list pt-3" :style="listStyle">
        <template v-if="loadingConcepts">
          <div>
            {{ loadingMessage }} <i class="fa-solid fa-spinner fa-spin-pulse"></i>
          </div>
        </template>
        <template v-else>
          <ul class="list-group" v-if="changedConcepts.length !== 0">
            <template v-for="[month, concepts] in changedConcepts">
              <li class="list-group-item py-1 px-2">
                <h2 class="pb-1">{{ month }}</h2>
              </li>
              <li v-for="concept in concepts" class="list-group-item py-1 px-2">
                <template v-if="concept.replacedBy">
                  <a :class="{ 'selected': selectedConcept === concept.uri }"
                    :href="getConceptURL(concept.uri)"
                    @click="loadConcept($event, concept.uri)"
                    aria-label="go to the concept page"
                  >
                    <s>{{ concept.prefLabel }}</s>
                  </a>
                  <i class="fa-solid fa-arrow-right"></i>
                  <a :class="{ 'selected': selectedConcept === concept.replacedBy }"
                    :href="getConceptURL(concept.replacedBy)"
                    @click="loadConcept($event, concept.replacedBy)"
                    aria-label="go to the concept page"
                  >
                    {{ concept.replacingLabel }}
                  </a>
                </template>
                <template v-else>
                  <a :class="{ 'selected': selectedConcept === concept.uri }"
                    :href="getConceptURL(concept.uri)"
                    @click="loadConcept($event, concept.uri)"
                    aria-label="go to the concept page"
                  >
                    {{ concept.prefLabel }}
                  </a>
                </template>
              </li>
            </template>
          </ul>
        </template>
      </div>
    `
  })

  tabChangesApp.mount('#tab-changes')
}

const waitForTranslationServiceTabChanges = () => {
  if (typeof $t !== 'undefined') {
    startChangesApp()
  } else {
    setTimeout(waitForTranslationServiceTabChanges, 50)
  }
}

waitForTranslationServiceTabChanges()
