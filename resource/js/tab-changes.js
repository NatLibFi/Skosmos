/* global Vue, $t, onTranslationReady */
/* global partialPageLoad, getConceptURL */

function startChangesApp () {
  const tabChangesApp = Vue.createApp({
    data () {
      return {
        changedConcepts: new Map(),
        selectedConcept: '',
        loadingConcepts: false,
        loadingMoreConcepts: false,
        currentOffset: 0,
        listStyle: {}
      }
    },
    computed: {
      loadingMessage () {
        return $t('Loading more items')
      },
      toConceptPageAriaMessage () {
        return $t('Go to the concept page')
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
        this.currentOffset = 0
        // Remove scrolling event listener while changes are loaded
        this.$refs.tabChanges.$refs.list.removeEventListener('scroll', this.handleScrollEvent)
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
              // Capitalize month name
              key = key.charAt(0).toUpperCase() + key.slice(1)

              if (!changesByDate.get(key)) {
                changesByDate.set(key, [])
              }

              changesByDate.get(key).push(concept)
            }

            this.changedConcepts = changesByDate
            this.loadingConcepts = false
            this.currentOffset = 200
            // Add scrolling event listener back after changes are loaded
            this.$refs.tabChanges.$refs.list.addEventListener('scroll', this.handleScrollEvent)
            console.log('changes', this.changedConcepts)
          })
      },
      loadMoreChanges () {
        this.loadingMoreConcepts = true
        // Remove scrolling event listener while new changes are loaded
        this.$refs.tabChanges.$refs.list.removeEventListener('scroll', this.handleScrollEvent)
        fetch('rest/v1/' + window.SKOSMOS.vocab + '/new?lang=' + window.SKOSMOS.content_lang + '&limit=200&offset=' + this.currentOffset)
          .then(data => {
            return data.json()
          })
          .then(data => {
            console.log('data', data)

            // Group concepts by month and year
            for (const concept of data.changeList) {
              const date = new Date(concept.date)
              let key = date.toLocaleString(window.SKOSMOS.lang, { month: 'long', year: 'numeric' })
              // Capitalize month name
              key = key.charAt(0).toUpperCase() + key.slice(1)

              if (!this.changedConcepts.get(key)) {
                this.changedConcepts.set(key, [])
              }

              this.changedConcepts.get(key).push(concept)
            }

            this.currentOffset += 200
            this.loadingMoreConcepts = false
            // Add scrolling event listener back if more changes were loaded
            if (data.changeList.length > 0) {
              this.$refs.tabChanges.$refs.list.addEventListener('scroll', this.handleScrollEvent)
            }
            console.log('changes', this.changedConcepts)
          })
      },
      handleScrollEvent () {
        const listElement = this.$refs.tabChanges.$refs.list
        if (listElement.scrollTop + listElement.clientHeight >= listElement.scrollHeight - 1) {
          this.loadMoreChanges()
        }
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
          :loading-more-concepts="loadingMoreConcepts"
          :loading-message="loadingMessage"
          :to-concept-page-aria-message="toConceptPageAriaMessage"
          :list-style="listStyle"
          @select-concept="selectedConcept = $event"
          ref="tabChanges"
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
    props: ['changedConcepts', 'selectedConcept', 'loadingConcepts', 'loadingMoreConcepts', 'loadingMessage', 'toConceptPageAriaMessage', 'listStyle'],
    inject: ['partialPageLoad', 'getConceptURL'],
    emits: ['selectConcept'],
    methods: {
      loadConcept (event, uri) {
        partialPageLoad(event, getConceptURL(uri))
        this.$emit('selectConcept', uri)
      }
    },
    template: `
      <div class="sidebar-list pt-3" :style="listStyle" ref="list">
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
                    :aria-label="toConceptPageAriaMessage"
                  >
                    <s>{{ concept.prefLabel }}</s>
                  </a>
                  <i class="fa-solid fa-arrow-right"></i>
                  <a :class="{ 'selected': selectedConcept === concept.replacedBy }"
                    :href="getConceptURL(concept.replacedBy)"
                    @click="loadConcept($event, concept.replacedBy)"
                    :aria-label="toConceptPageAriaMessage"
                  >
                    {{ concept.replacingLabel }}
                  </a>
                </template>
                <template v-else>
                  <a :class="{ 'selected': selectedConcept === concept.uri }"
                    :href="getConceptURL(concept.uri)"
                    @click="loadConcept($event, concept.uri)"
                    :aria-label="toConceptPageAriaMessage"
                  >
                    {{ concept.prefLabel }}
                  </a>
                </template>
              </li>
            </template>
            <template v-if="loadingMoreConcepts">
              <li class="list-group-item py-1 px-2">
                {{ this.loadingMessage }} <i class="fa-solid fa-spinner fa-spin-pulse"></i>
              </li>
            </template>
          </ul>
        </template>
      </div>
    `
  })

  tabChangesApp.mount('#tab-changes')
}

onTranslationReady(startChangesApp)
