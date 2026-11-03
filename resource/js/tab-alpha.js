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
        listStyle: {},
        ariaLiveMessage: ''
      }
    },
    computed: {
      loadingMessage () {
        return $t('Loading more items')
      },
      toConceptPageAriaMessage () {
        return $t('Go to the concept page')
      },
      chooseLetterMessage () {
        return $t('Choose alphabetical listing letter')
      },
      conceptsLoadedMessage () {
        return $t('Concepts loaded for letter %letter%').replace('%letter%', this.selectedLetter)
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
        const params = new URLSearchParams({ lang: window.SKOSMOS.content_lang })
        fetch(`rest/v1/${window.SKOSMOS.vocab}/index/?${params}`)
          .then(data => {
            return data.json()
          })
          .then(data => {
            this.indexLetters = data.indexLetters
            this.selectedLetter = this.indexLetters[0]
            this.loadingLetters = false
            this.loadConcepts(this.indexLetters[0], true)
          })
      },
      loadConcepts (letter, first = false) {
        this.loadingConcepts = true
        this.currentOffset = 0
        this.selectedLetter = letter
        // Remove scrolling event listener while concepts are loaded
        this.$refs.tabAlpha.$refs.list.removeEventListener('scroll', this.handleScrollEvent)
        const params = new URLSearchParams({
          lang: window.SKOSMOS.content_lang,
          limit: '250'
        })
        const url = `rest/v1/${window.SKOSMOS.vocab}/index/${letter}?${params}`
        fetchWithAbort(url, 'alpha')
          .then(data => {
            return data.json()
          })
          .then(data => {
            this.indexConcepts = data.indexConcepts
            this.currentOffset = 250
            this.loadingConcepts = false
            // Add scrolling event listener back after concepts are loaded
            this.$refs.tabAlpha.$refs.list.addEventListener('scroll', this.handleScrollEvent)
            // Update aria live message after selecting a new letter (not on first load)
            if (!first) {
              this.ariaLiveMessage = this.conceptsLoadedMessage
            }
          })
          .catch(error => {
            if (error.name !== 'AbortError') {
              throw error
            }
          })
      },
      loadMoreConcepts () {
        this.loadingMoreConcepts = true
        // Remove scrolling event listener while new concepts are loaded
        this.$refs.tabAlpha.$refs.list.removeEventListener('scroll', this.handleScrollEvent)
        const params = new URLSearchParams({
          lang: window.SKOSMOS.content_lang,
          limit: '250',
          offset: this.currentOffset
        })
        const url = `rest/v1/${window.SKOSMOS.vocab}/index/${this.selectedLetter}?${params}`
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
            if (error.name !== 'AbortError') {
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
        const letters = this.$refs.tabAlpha.$refs.letters
        const sidebarTabs = document.getElementById('sidebar-tabs')

        const height = letters ? letters.clientHeight + sidebarTabs.clientHeight : sidebarTabs.clientHeight
        const width = sidebarTabs.getBoundingClientRect().width

        this.listStyle = {
          height: 'calc( 100% - ' + height + 'px )',
          width: width + 'px'
        }
      }
    },
    template: `
      <div
        v-click-tab-alphabetical="handleClickAlphabeticalEvent"
        v-toggle-sidebar="setListStyle"
        v-resize-window="setListStyle"
        v-window-popstate="() => selectedConcept = ''"
      >
        <tab-alpha
          :index-letters="indexLetters"
          :index-concepts="indexConcepts"
          :selected-concept="selectedConcept"
          :selected-letter="selectedLetter"
          :loading-letters="loadingLetters"
          :loading-concepts="loadingConcepts"
          :loading-more-concepts="loadingMoreConcepts"
          :loading-message="loadingMessage"
          :list-style="listStyle"
          :to-concept-page-aria-message="toConceptPageAriaMessage"
          :choose-letter-message="chooseLetterMessage"
          :aria-live-message="ariaLiveMessage"
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
      document.querySelector('#alphabetical').addEventListener('shown.bs.tab', el.clickTabEvent) // registering an event listener on bootstrap's tab shown event on the alphabetical nav-item element
    },
    unmounted: el => {
      document.querySelector('#alphabetical').removeEventListener('shown.bs.tab', el.clickTabEvent)
    }
  })

  /* Custom directive used to add an event listener on clicks on the sidebar-collapse-btn element on mobile */
  tabAlphaApp.directive('toggle-sidebar', {
    beforeMount: (el, binding) => {
      el.clickTabEvent = event => {
        binding.value() // calling the method given as the attribute value (seListStyle)
      }
      document.querySelector('#sidebar-collapse-btn').addEventListener('sidebar-toggled', el.clickTabEvent) // registering an event listener on toggling sidebar on mobile
    },
    unmounted: el => {
      document.querySelector('#sidebar-collapse-btn').addEventListener('sidebar-toggled', el.clickTabEvent)
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

  /* Custom directive used to add an event listener on popstate events */
  tabAlphaApp.directive('window-popstate', {
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

  tabAlphaApp.component('tab-alpha', {
    props: [
      'indexLetters',
      'indexConcepts',
      'selectedConcept',
      'selectedLetter',
      'loadingLetters',
      'loadingConcepts',
      'loadingMoreConcepts',
      'loadingMessage',
      'listStyle',
      'toConceptPageAriaMessage',
      'chooseLetterMessage',
      'ariaLiveMessage'
    ],
    data () {
      return {
        conceptInFocus: 0
      }
    },
    emits: ['loadConcepts', 'selectConcept'],
    inject: ['partialPageLoad', 'getConceptURL', 'showNotation'],
    methods: {
      loadConcepts (event, letter) {
        event.preventDefault()
        this.conceptInFocus = 0
        this.$emit('loadConcepts', letter)
      },
      loadConcept (event, uri, i) {
        this.conceptInFocus = i
        partialPageLoad(event, getConceptURL(uri))
        this.$emit('selectConcept', uri)
      },
      handleKeydownEvent (e) {
        if (e.key === ' ') {
          // Click on link currently in focus
          e.preventDefault()
          this.$refs['concept' + this.conceptInFocus][0].click()
        } else if (e.key === 'ArrowDown') {
          // On last element move focus to first list item, otherwise next list item
          e.preventDefault()
          this.conceptInFocus = (this.conceptInFocus + 1) % this.indexConcepts.length
          this.$refs['concept' + this.conceptInFocus][0].focus()
        } else if (e.key === 'ArrowUp') {
          // On first element move focus to last list item, otherwise to previous list item
          e.preventDefault()
          if (this.conceptInFocus === 0) {
            this.conceptInFocus = this.indexConcepts.length - 1
          } else {
            this.conceptInFocus -= 1
          }
          this.$refs['concept' + this.conceptInFocus][0].focus()
        } else if (e.key === 'End') {
          // Move focus to last list item
          e.preventDefault()
          this.conceptInFocus = this.indexConcepts.length - 1
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
      <template v-if="!loadingLetters">
        <fieldset class="letters" ref="letters">
          <div class="visually-hidden aria-live-message" aria-live="polite">
            {{ ariaLiveMessage }}
          </div>
          <legend class="visually-hidden">
            {{ chooseLetterMessage }}>
          </legend>
          <div class="form-check" v-for="letter in indexLetters">
            <input class="form-check-input visually-hidden" type="radio" name="letters"
              :checked="selectedLetter === letter"
              :id="'alpha-' + letter" 
              :value="letter"
              @change="loadConcepts($event, letter)"
              @keydown.enter="loadConcepts($event, letter)"
            >
            <label class="form-check-label"
              :for="'alpha-' + letter"
              :class="{ 'selected': selectedLetter === letter, 'wide': letter.includes('-') }"
            >
              {{ letter }}
            </label>
          </div>
        </fieldset>
      </template>
      
      <div class="sidebar-list" tabindex="-1" :style="listStyle" ref="list">
        <template v-if="loadingConcepts || loadingLetters">
          <div>
            {{ this.loadingMessage }} <i class="fa-solid fa-spinner fa-spin-pulse"></i>
          </div>
        </template>
        <template v-else>
          <ul class="list-group" v-if="indexConcepts.length !== 0">
            <li v-for="(concept, i) in indexConcepts" class="list-group-item py-1 px-2">
              <template v-if="concept.altLabel">
                <span class="fst-italic">{{ concept.altLabel }}</span>
                <i class="fa-solid fa-arrow-right"></i>
              </template>
              <a :class="{ 'selected': selectedConcept === concept.uri }"
                :href="getConceptURL(concept.uri)"
                :tabindex="i === conceptInFocus ? 0 : -1"
                :ref="'concept' + i"
                @click="loadConcept($event, concept.uri, i)"
                @keydown="handleKeydownEvent($event)"
              >
                {{ concept.prefLabel }}{{ showNotation && concept.qualifier ? ' (' + concept.qualifier + ')' : '' }}
                <span class="visually-hidden">{{ toConceptPageAriaMessage }}</span>
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

  if (document.getElementById('tab-alphabetical')) {
    tabAlphaApp.mount('#tab-alphabetical')
  }
}

onTranslationReady(startAlphaApp)
