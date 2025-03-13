/* global Vue, $t, onTranslationReady */
/* global partialPageLoad, getConceptURL, fetchWithAbort */

function startAlphaApp () {
  const tabAlphaApp = Vue.createApp({
    data () {
      return {
        indexLetters: [],
        indexConcepts: [],
        selectedConcept: '',
        selectedLetter: '',
        loadingLetters: false,
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
        getConceptURL,
        showNotation: window.SKOSMOS.showNotation
      }
    },
    mounted () {
      // load alphabetical index if alphabetical tab is active when the page is first opened (otherwise only load the index when the tab is clicked)
      if (document.querySelector('#alphabetical > a').classList.contains('active')) {
        this.loadLetters()
      }
    },
    beforeUpdate () {
      this.setListStyle()
    },
    methods: {
      handleClickAlphabeticalEvent () {
        // only load index the first time the page is opened or if selected concept has changed
        if (this.indexLetters.length === 0 || this.selectedConcept !== window.SKOSMOS.uri) {
          this.selectedConcept = ''
          this.indexLetters = []
          this.indexConcepts = []
          this.loadLetters()
        }
      },
      loadLetters () {
        this.loadingLetters = true
        // Remove scrolling event listener while letters are loaded
        this.$refs.tabAlpha.$refs.list.removeEventListener('scroll', this.handleScrollEvent)
        fetch('rest/v1/' + window.SKOSMOS.vocab + '/index/?lang=' + window.SKOSMOS.content_lang)
          .then(data => {
            return data.json()
          })
          .then(data => {
            this.indexLetters = data.indexLetters
            this.selectedLetter = this.indexLetters[0]
            this.loadingLetters = false
            this.loadConcepts(this.indexLetters[0])
          })
      },
      loadConcepts (letter) {
        this.loadingConcepts = true
        this.currentOffset = 0
        // Remove scrolling event listener while concepts are loaded
        this.$refs.tabAlpha.$refs.list.removeEventListener('scroll', this.handleScrollEvent)
        const url = 'rest/v1/' + window.SKOSMOS.vocab + '/index/' + letter + '?lang=' + window.SKOSMOS.content_lang + '&limit=250'
        fetchWithAbort(url, 'alpha')
          .then(data => {
            return data.json()
          })
          .then(data => {
            this.indexConcepts = data.indexConcepts
            this.selectedLetter = letter
            this.currentOffset = 250
            this.loadingConcepts = false
            // Add scrolling event listener back after concepts are loaded
            this.$refs.tabAlpha.$refs.list.addEventListener('scroll', this.handleScrollEvent)
          })
          .catch(error => {
            if (error.name === 'AbortError') {
              console.log('Fetch aborted for letter ' + letter)
            } else {
              throw error
            }
          })
      },
      loadMoreConcepts () {
        this.loadingMoreConcepts = true
        // Remove scrolling event listener while new concepts are loaded
        this.$refs.tabAlpha.$refs.list.removeEventListener('scroll', this.handleScrollEvent)
        const url = 'rest/v1/' + window.SKOSMOS.vocab + '/index/' + this.selectedLetter + '?lang=' + window.SKOSMOS.content_lang + '&limit=250&offset=' + this.currentOffset
        fetchWithAbort(url, 'alpha')
          .then(data => {
            return data.json()
          })
          .then(data => {
            this.indexConcepts.push(...data.indexConcepts)
            this.currentOffset += 250
            this.loadingMoreConcepts = false
            // Add scrolling event listener back if more concepts were loaded
            if (data.indexConcepts.length > 0) {
              this.$refs.tabAlpha.$refs.list.addEventListener('scroll', this.handleScrollEvent)
            }
          })
          .catch(error => {
            if (error.name === 'AbortError') {
              console.log('Fetch aborted for letter ' + this.selectedLetter + ' and offset ' + this.currentOffset)
            } else {
              throw error
            }
          })
      },
      handleScrollEvent () {
        const listElement = this.$refs.tabAlpha.$refs.list
        if (listElement.scrollTop + listElement.clientHeight >= listElement.scrollHeight - 1) {
          this.loadMoreConcepts()
        }
      },
      setListStyle () {
        const pagination = this.$refs.tabAlpha.$refs.pagination
        const sidebarTabs = document.getElementById('sidebar-tabs')

        // get height and width of pagination and sidebar tabs elements if they exist
        const height = pagination && pagination.clientHeight + sidebarTabs.clientHeight
        const width = pagination && pagination.clientWidth - 1

        this.listStyle = {
          height: 'calc(100% - ' + height + 'px )',
          width: width + 'px'
        }
      }
    },
    template: `
      <div v-click-tab-alphabetical="handleClickAlphabeticalEvent" v-resize-window="setListStyle">
        <tab-alpha
          :index-letters="indexLetters"
          :index-concepts="indexConcepts"
          :selected-concept="selectedConcept"
          :loading-letters="loadingLetters"
          :loading-concepts="loadingConcepts"
          :loading-more-concepts="loadingMoreConcepts"
          :loading-message="loadingMessage"
          :list-style="listStyle"
          :to-concept-page-aria-message="toConceptPageAriaMessage"
          @load-concepts="loadConcepts($event)"
          @select-concept="selectedConcept = $event"
          ref="tabAlpha"
        ></tab-alpha>
      </div>
    `
  })

  /* Custom directive used to add an event listener on clicks on the alphabetical nav-item element */
  tabAlphaApp.directive('click-tab-alphabetical', {
    beforeMount: (el, binding) => {
      el.clickTabEvent = event => {
        binding.value() // calling the method given as the attribute value (loadLetters)
      }
      document.querySelector('#alphabetical').addEventListener('click', el.clickTabEvent) // registering an event listener on clicks on the alphabetical nav-item element
    },
    unmounted: el => {
      document.querySelector('#alphabetical').removeEventListener('click', el.clickTabEvent)
    }
  })

  /* Custom directive used to add an event listener on resizing the window */
  tabAlphaApp.directive('resize-window', {
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

  tabAlphaApp.component('tab-alpha', {
    props: ['indexLetters', 'indexConcepts', 'selectedConcept', 'loadingLetters', 'loadingConcepts', 'loadingMoreConcepts', 'loadingMessage', 'listStyle', 'toConceptPageAriaMessage'],
    emits: ['loadConcepts', 'selectConcept'],
    inject: ['partialPageLoad', 'getConceptURL', 'showNotation'],
    methods: {
      loadConcepts (event, letter) {
        event.preventDefault()
        this.$emit('loadConcepts', letter)
      },
      loadConcept (event, uri) {
        partialPageLoad(event, getConceptURL(uri))
        this.$emit('selectConcept', uri)
      }
    },
    template: `
      <template v-if="loadingLetters">
        <div class="loading-message">
          {{ this.loadingMessage }} <i class="fa-solid fa-spinner fa-spin-pulse"></i>
        </div>
      </template>
      <template v-else>
        <ul class="pagination" v-if="indexLetters.length !== 0" ref="pagination">
          <li v-for="letter in indexLetters" class="page-item">
            <a class="page-link" href="#" @click="loadConcepts($event, letter)">{{ letter }}</a>
          </li>
        </ul>
      </template>
      
      <div class="sidebar-list" :style="listStyle" ref="list">
        <template v-if="loadingConcepts">
          <div>
            {{ this.loadingMessage }} <i class="fa-solid fa-spinner fa-spin-pulse"></i>
          </div>
        </template>
        <template v-else>
          <ul class="list-group" v-if="indexConcepts.length !== 0">
            <li v-for="concept in indexConcepts" class="list-group-item py-1 px-2">
              <template v-if="concept.altLabel">
                <span class="fst-italic">{{ concept.altLabel }}</span>
                <i class="fa-solid fa-arrow-right"></i>
              </template>
              <a :class="{ 'selected': selectedConcept === concept.uri }"
                :href="getConceptURL(concept.uri)" @click="loadConcept($event, concept.uri)"
                :aria-label="toConceptPageAriaMessage"
              >
                {{ concept.prefLabel }}{{ showNotation && concept.qualifier ? ' (' + concept.qualifier + ')' : '' }}
              </a>
            </li>
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

  tabAlphaApp.mount('#tab-alphabetical')
}

onTranslationReady(startAlphaApp)
