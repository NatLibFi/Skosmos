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
        const params = new URLSearchParams({
          lang: window.SKOSMOS.content_lang,
          limit: '200'
        })
        fetch(`rest/v1/${window.SKOSMOS.vocab}/new?${params}`)
          .then(data => {
            return data.json()
          })
          .then(data => {
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
          })
      },
      loadMoreChanges () {
        this.loadingMoreConcepts = true
        // Remove scrolling event listener while new changes are loaded
        this.$refs.tabChanges.$refs.list.removeEventListener('scroll', this.handleScrollEvent)
        const params = new URLSearchParams({
          lang: window.SKOSMOS.content_lang,
          limit: '200',
          offset: this.currentOffset
        })
        fetch(`rest/v1/${window.SKOSMOS.vocab}/new?${params}`)
          .then(data => {
            return data.json()
          })
          .then(data => {
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
        const width = document.getElementById('sidebar-tabs').getBoundingClientRect().width
        this.listStyle = {
          height: 'calc( 100% - ' + height + 'px )',
          width: width + 'px'
        }
      }
    },
    template: `
      <div
        v-click-tab-changes="handleClickChangesEvent"
        v-click-collapse-btn="setListStyle"
        v-resize-window="setListStyle"
        v-window-popstate="() => selectedConcept = ''"
      >
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
      document.querySelector('#changes').addEventListener('shown.bs.tab', el.clickTabEvent) // registering an event listener on bootstrap's tab shown event on the changes nav-item element
    },
    unmounted: el => {
      document.querySelector('#changes').removeEventListener('shown.bs.tab', el.clickTabEvent)
    }
  })

  /* Custom directive used to add an event listener on clicks on the sidebar-collapse-btn element on mobile */
  tabChangesApp.directive('click-collapse-btn', {
    beforeMount: (el, binding) => {
      el.clickTabEvent = event => {
        binding.value() // calling the method given as the attribute value (seListStyle)
      }
      document.querySelector('#sidebar-collapse-btn').addEventListener('click', el.clickTabEvent) // registering an event listener on clicks on the sidebar-collapse-btn element on mobile
    },
    unmounted: el => {
      document.querySelector('#sidebar-collapse-btn').removeEventListener('click', el.clickTabEvent)
    }
  })

  /* Custom directive used to add an event listener on resizing the window */
  tabChangesApp.directive('resize-window', {
    beforeMount: (el, binding) => {
      el.resizeWindowEvent = event => {
        binding.value() // calling the method given as the attribute value (setListStyle)
      }
      window.addEventListener('resize', el.resizeWindowEvent) // registering an event listener on resizing the window
    },
    unmounted: el => {
      window.removeEventListener('resize', el.resizeWindowEvent)
    }
  })

  /* Custom directive used to add an event listener on popstate events */
  tabChangesApp.directive('window-popstate', {
    beforeMount: (el, binding) => {
      el.windowPopstateEvent = event => {
        binding.value() // calling the method given as the attribute value
      }
      window.addEventListener('popstate', el.windowPopstateEvent) // registering an event listener on popstate events
    },
    unmounted: el => {
      window.removeEventListener('popstate', el.windowPopstateEvent)
    }
  })

  tabChangesApp.component('tab-changes', {
    props: ['changedConcepts', 'selectedConcept', 'loadingConcepts', 'loadingMoreConcepts', 'loadingMessage', 'toConceptPageAriaMessage', 'listStyle'],
    data () {
      return {
        conceptInFocus: 0,
        changedConceptsLength: 0
      }
    },
    inject: ['partialPageLoad', 'getConceptURL'],
    emits: ['selectConcept'],
    methods: {
      loadConcept (event, uri, i) {
        this.conceptInFocus = i
        partialPageLoad(event, getConceptURL(uri))
        this.$emit('selectConcept', uri)
      },
      getIndexedConcepts () {
        // Give each uri and replacedBy in changedConcepts a unique index for keyboard navigation
        let counter = 0

        const indexed = new Map(
          [...this.changedConcepts].map(([month, entries]) => [
            month,
            entries.map(entry => {
              const result = { ...entry, index: counter++ }
              if (entry.replacedBy) result.replacedByIndex = counter++
              return result
            })
          ])
        )
        this.changedConceptsLength = counter
        return indexed
      },
      handleKeydownEvent (e) {
        if (e.key === ' ') {
          // Click on link currently in focus
          e.preventDefault()
          this.$refs['concept' + this.conceptInFocus][0].click()
        } else if (e.key === 'ArrowDown') {
          // On last element move focus to first list item, otherwise next list item
          e.preventDefault()
          this.conceptInFocus = (this.conceptInFocus + 1) % this.changedConceptsLength
          this.$refs['concept' + this.conceptInFocus][0].focus()
        } else if (e.key === 'ArrowUp') {
          // On first element move focus to last list item, otherwise to previous list item
          e.preventDefault()
          if (this.conceptInFocus === 0) {
            this.conceptInFocus = this.changedConceptsLength - 1
          } else {
            this.conceptInFocus -= 1
          }
          this.$refs['concept' + this.conceptInFocus][0].focus()
        } else if (e.key === 'End') {
          // Move focus to last list item
          e.preventDefault()
          this.conceptInFocus = this.changedConceptsLength - 1
          this.$refs['concept' + this.conceptInFocus][0].focus()
        } else if (e.key === 'Home') {
          // Move focus to first list item
          e.preventDefault()
          this.conceptInFocus = 0
          this.$refs['concept' + this.conceptInFocus][0].focus()
        }
      }
    },
    template: `
      <div class="sidebar-list pt-3" tabindex="-1" :style="listStyle" ref="list">
        <template v-if="loadingConcepts">
          <div>
            {{ loadingMessage }} <i class="fa-solid fa-spinner fa-spin-pulse"></i>
          </div>
        </template>
        <template v-else>
          <ul class="list-group" v-if="changedConcepts.length !== 0">
            <template v-for="[month, concepts] in getIndexedConcepts()">
              <li class="list-group-item py-1 px-2">
                <h2 class="pb-1">{{ month }}</h2>
              </li>
              <li v-for="concept in concepts" class="list-group-item py-1 px-2">
                <template v-if="concept.deprecated">
                  <a :class="{ 'selected': selectedConcept === concept.uri }"
                    :href="getConceptURL(concept.uri)"
                    :tabindex="concept.index === conceptInFocus ? 0 : -1"
                    :ref="'concept' + concept.index"
                    @click="loadConcept($event, concept.uri, concept.index)"
                    @keydown="handleKeydownEvent($event)"
                  >
                    <s>{{ concept.prefLabel }}</s>
                    <span class="visually-hidden">{{ toConceptPageAriaMessage }}</span>
                  </a>
                  <template v-if="concept.replacedBy">
                    <i class="fa-solid fa-arrow-right"></i>
                    <a :class="{ 'selected': selectedConcept === concept.replacedBy }"
                      :href="getConceptURL(concept.replacedBy)"
                      :tabindex="concept.replacedByIndex === conceptInFocus ? 0 : -1"
                      :ref="'concept' + concept.replacedByIndex"
                      @click="loadConcept($event, concept.replacedBy, concept.replacedByIndex)"
                      @keydown="handleKeydownEvent($event)"
                    >
                      {{ concept.replacingLabel }}
                      <span class="visually-hidden">{{ toConceptPageAriaMessage }}</span>
                    </a>
                  </template>
                </template>
                <template v-else>
                  <a :class="{ 'selected': selectedConcept === concept.uri }"
                    :href="getConceptURL(concept.uri)"
                    :tabindex="concept.index === conceptInFocus ? 0 : -1"
                    :ref="'concept' + concept.index"
                    @click="loadConcept($event, concept.uri, concept.index)"
                    @keydown="handleKeydownEvent($event)"
                  >
                    {{ concept.prefLabel }}
                    <span class="visually-hidden">{{ toConceptPageAriaMessage }}</span>
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

  if (document.getElementById('tab-changes')) {
    tabChangesApp.mount('#tab-changes')
  }
}

onTranslationReady(startChangesApp)
