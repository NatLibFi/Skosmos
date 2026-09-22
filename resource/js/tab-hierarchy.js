/* global Vue, $t, onTranslationReady */
/* global partialPageLoad, getConceptURL */

function startHierarchyApp () {
  const tabHierApp = Vue.createApp({
    data () {
      return {
        hierarchy: [],
        loadingHierarchy: true,
        loadingChildren: [],
        selectedConcept: '',
        listStyle: {},
        visibleConceptCount: 0
      }
    },
    computed: {
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
      // load hierarchy if hierarchy tab is active when the page is first opened (otherwise only load hierarchy when the tab is clicked)
      if (document.querySelector('#hierarchy > a').classList.contains('active')) {
        this.loadHierarchy()
      }

      this.setListStyle()
    },
    beforeUpdate () {
      this.setListStyle()
    },
    methods: {
      handleClickHierarchyEvent () {
        // only load hierarchy if hierarchy tab is available
        if (!document.querySelector('#hierarchy > a').classList.contains('disabled')) {
          this.loadHierarchy()
        }
      },
      loadHierarchy () {
        // if we are on a concept page, load hierarchy for the concept, otherwise load top concepts
        if (window.SKOSMOS.uri) {
          this.loadConceptHierarchy()
        } else {
          this.loadTopConcepts()
        }
      },
      async loadTopConcepts () {
        this.loadingHierarchy = true
        if (window.SKOSMOS.showConceptSchemesInHierarchy) {
          // if concept schemes are shown in hierarchy, fetch them from API and set them as top concepts in hierarchy
          const params = new URLSearchParams({ lang: window.SKOSMOS.content_lang })
          try {
            const res = await fetch(`rest/v1/${window.SKOSMOS.vocab}/?${params}`)
            if (!res.ok) {
              this.loadingHierarchy = false
              return
            }
            const data = await res.json()
            this.hierarchy = []

            for (const c of data.conceptschemes.sort((a, b) => this.compareConcepts(a, b))) {
              this.hierarchy.push({ uri: c.uri, label: c.title || c.label, hasChildren: true, children: [], isOpen: false, notation: undefined, isScheme: true })
            }
          } catch (e) {
            console.error(e)
          } finally {
            this.addIndicesToHierarchy()
            this.loadingHierarchy = false
          }
        } else {
          // otherwise, fetch top concepts
          const params = new URLSearchParams({ lang: window.SKOSMOS.content_lang })
          try {
            const res = await fetch(`rest/v1/${window.SKOSMOS.vocab}/topConcepts/?${params}`)
            if (!res.ok) {
              this.loadingHierarchy = false
              return
            }
            const data = await res.json()
            this.hierarchy = []

            for (const c of data.topconcepts.sort((a, b) => this.compareConcepts(a, b))) {
              this.hierarchy.push(this.createConceptNode(c))
            }
          } catch (e) {
            console.error(e)
          } finally {
            this.addIndicesToHierarchy()
            this.loadingHierarchy = false
          }
        }
      },
      async loadConceptHierarchy () {
        this.loadingHierarchy = true
        this.hierarchy = []

        try {
          // if concept schemes are shown in hierarchy, add them to the root of the hierarchy tree
          if (window.SKOSMOS.showConceptSchemesInHierarchy) {
            await this.loadConceptSchemes()
          }

          if (this.hierarchy.some(s => s.uri === window.SKOSMOS.uri)) {
            // if we are on a page for a concept scheme, fetch its top concepts
            await this.loadTopConceptsForConceptScheme()
          } else {
            // otherwise, fetch hierarchy tree for selected concept
            await this.loadHierarchyForConcept()
          }
        } catch (e) {
          console.error(e)
        } finally {
          this.loadingHierarchy = false
          this.addIndicesToHierarchy()
          this.selectedConcept = window.SKOSMOS.uri
        }
      },
      loadChildren (concept) {
        // load children only if concept has children but they have not been loaded yet
        if (concept.children.length === 0 && concept.hasChildren) {
          this.loadingChildren.push(concept)
          if (window.SKOSMOS.showConceptSchemesInHierarchy && concept.isScheme) {
            // if the concept is a concept scheme, fetch topconcepts as children
            const params = new URLSearchParams({
              scheme: concept.uri,
              lang: window.SKOSMOS.content_lang
            })
            fetch(`rest/v1/${window.SKOSMOS.vocab}/topConcepts?${params}`)
              .then(data => {
                return data.json()
              })
              .then(data => {
                for (const c of data.topconcepts.sort((a, b) => this.compareConcepts(a, b))) {
                  concept.children.push(this.createConceptNode(c))
                }
                this.addIndicesToHierarchy()
                this.loadingChildren = this.loadingChildren.filter(x => x !== concept)
              })
          } else {
            // otherwise, fetch children of concept
            const params = new URLSearchParams({
              uri: concept.uri,
              lang: window.SKOSMOS.content_lang
            })
            fetch(`rest/v1/${window.SKOSMOS.vocab}/children?${params}`)
              .then(data => {
                return data.json()
              })
              .then(data => {
                for (const c of data.narrower.sort((a, b) => this.compareConcepts(a, b))) {
                  concept.children.push(this.createConceptNode(c))
                }
                this.addIndicesToHierarchy()
                this.loadingChildren = this.loadingChildren.filter(x => x !== concept)
              })
          }
        } else if (concept.children.length > 0) {
          // If the concept already has children loaded, update indices in hierarchy
          this.addIndicesToHierarchy()
        }
      },
      async loadConceptSchemes () {
        const params = new URLSearchParams({ lang: window.SKOSMOS.content_lang })
        const res = await fetch(`rest/v1/${window.SKOSMOS.vocab}/?${params}`)
        if (!res.ok) {
          return
        }
        const data = await res.json()

        for (const s of data.conceptschemes.sort((a, b) => this.compareConcepts(a, b))) {
          const schemeNode = { uri: s.uri, label: s.title || s.label, hasChildren: true, children: [], isOpen: s.uri === window.SKOSMOS.uri, notation: undefined, isScheme: true }
          this.hierarchy.push(schemeNode)
        }
      },
      async loadTopConceptsForConceptScheme () {
        const params = new URLSearchParams({
          scheme: window.SKOSMOS.uri,
          lang: window.SKOSMOS.content_lang
        })
        const res = await fetch(`rest/v1/${window.SKOSMOS.vocab}/topConcepts?${params}`)
        if (!res.ok) {
          return
        }
        const data = await res.json()

        // find selected scheme in hierarchy
        const scheme = this.hierarchy.find(s => s.uri === window.SKOSMOS.uri)
        // add top concepts to hierarchy as the scheme's children
        scheme.children = data.topconcepts
          .sort((a, b) => this.compareConcepts(a, b))
          .map(c => this.createConceptNode(c))
      },
      async loadHierarchyForConcept () {
        const params = new URLSearchParams({
          uri: window.SKOSMOS.uri,
          lang: window.SKOSMOS.content_lang
        })
        const res = await fetch(`rest/v1/${window.SKOSMOS.vocab}/hierarchy/?${params}`)
        if (!res.ok) {
          return
        }
        const data = await res.json()

        // transform broaderTransitive to an array and sort it
        const bt = Object.values(data.broaderTransitive).sort((a, b) => this.compareConcepts(a, b))
        const parents = [] // queue of nodes in hierarchy tree with potential missing child nodes

        // add top concepts to hierarchy tree
        for (const concept of bt) {
          if (concept.top || !concept.broader) {
            this.addTopConceptsToHierarchy(concept, parents)
          }
        }

        // add other concepts to hierarchy tree
        this.addChildConceptsToHierarchy(bt, parents)

        // if concept schemes are in shown hierarchy, open the concept scheme that contains selected concept
        if (window.SKOSMOS.showConceptSchemesInHierarchy) {
          this.openContainingScheme()
        }
      },
      addTopConceptsToHierarchy (concept, parents) {
        if (concept.narrower) {
          // children of the current concept
          const children = concept.narrower
            .sort((a, b) => this.compareConcepts(a, b))
            .map(c => this.createConceptNode(c))

          // new concept node to be added to hierarchy tree
          const conceptNode = this.createConceptNode({ ...concept, hasChildren: true }, true, children)

          if (window.SKOSMOS.showConceptSchemesInHierarchy) {
            // if concept schemes are shown in hierarchy, push new concept to the children of the correct concept scheme
            const scheme = this.hierarchy.find(s => s.uri === concept.top)
            scheme.children.push(conceptNode)

            if (concept.uri === window.SKOSMOS.uri) {
              scheme.isOpen = true
            }
          } else {
            // otherwise push new concept to the root of the hierarchy tree
            this.hierarchy.push(conceptNode)
          }
          // push new concept to parent queue
          parents.push(conceptNode)
        } else {
          const conceptNode = this.createConceptNode(concept)
          if (window.SKOSMOS.showConceptSchemesInHierarchy) {
            // if concept schemes are shown in hierarchy, push new concept to the children of the correct concept scheme
            const scheme = this.hierarchy.find(x => x.uri === concept.top)
            scheme.children.push(conceptNode)

            if (concept.uri === window.SKOSMOS.uri) {
              scheme.isOpen = true
            }
          } else {
            // otherwise push new concept to the root of the hierarchy tree
            this.hierarchy.push(conceptNode)
          }
        }
      },
      addChildConceptsToHierarchy (bt, parents) {
        while (parents.length !== 0) {
          const parent = parents.shift() // parent node with potential missing child nodes
          const concepts = []

          // find all concepts in broaderTransitive which have current parent node as parent
          for (const concept of bt) {
            if (concept.broader && concept.broader.includes(parent.uri)) {
              concepts.push(concept)
            }
          }

          // for all found concepts, add their children to hierarchy
          for (const concept of concepts) {
            if (concept.narrower) {
              // corresponding concept node in hierarchy tree
              const conceptNode = parent.children.find(c => c.uri === concept.uri)
              // children of current concept
              const children = concept.narrower
                .sort((a, b) => this.compareConcepts(a, b))
                .map(c => this.createConceptNode(c))
              // set children of current concept as children of concept node
              conceptNode.children = children
              conceptNode.isOpen = children.length !== 0
              // push concept node to parent queue
              parents.push(conceptNode)
            }
          }
        }
      },
      addIndicesToHierarchy () {
        // Adds a unique index to each concept that is visible in DOM after hierarchy is updated
        let counter = 0

        const traverse = (nodes, parentIsOpen) => {
          for (const node of nodes) {
            // Assign index only if parent is open
            if (parentIsOpen) {
              node.index = counter
              counter++
            } else {
              delete node.index
            }

            if (node.children.length > 0) {
              traverse(node.children, node.isOpen && parentIsOpen)
            }
          }
        }

        traverse(this.hierarchy, true)

        this.visibleConceptCount = counter
      },
      openContainingScheme () {
        const containsConcept = (node) => {
          if (node.uri === window.SKOSMOS.uri) return true
          if (!node.children) return false

          for (const child of node.children) {
            if (containsConcept(child)) return true
          }
          return false
        }

        for (const scheme of this.hierarchy) {
          if (containsConcept(scheme)) {
            scheme.isOpen = true
          }
        }
      },
      createConceptNode (concept, isOpen = false, children = []) {
        return {
          uri: concept.uri,
          label: concept.label || concept.prefLabel,
          hasChildren: concept.hasChildren,
          children,
          isOpen,
          notation: concept.notation,
          isScheme: false
        }
      },
      setListStyle () {
        const height = document.getElementById('sidebar-tabs').clientHeight
        const width = document.getElementById('sidebar-tabs').getBoundingClientRect().width
        this.listStyle = {
          height: 'calc( 100% - ' + height + 'px )',
          width: width + 'px'
        }
      },
      compareConcepts (a, b) {
        let strA, strB

        if (window.SKOSMOS.sortByNotation) {
          if (a.notation && b.notation) {
            // Set strings as notation if both have notation codes
            strA = a.notation
            strB = b.notation
          } else if (a.notation && !b.notation) {
            // Sort a before b if b has no notation
            return -1
          } else if (!a.notation && b.notation) {
            // Sort b before a if a has no notation
            return 1
          }
        }

        // Set strings to label/prefLabel if sorting should not be based on notation or if neither concept has notations
        strA = strA || a.label || a.prefLabel || a.title || ''
        strB = strB || b.label || b.prefLabel || b.title || ''

        const result = this.$collator.compare(strA, strB)
        if (result !== 0) {
          return result
        } else {
          // fall back to non-numeric sort to ensure a consistent order
          return this.$fallbackCollator.compare(strA, strB)
        }
      }
    },
    template: `
      <div
        v-click-tab-hierarchy="handleClickHierarchyEvent"
        v-click-collapse-btn="setListStyle"
        v-resize-window="setListStyle"
        v-window-popstate="() => selectedConcept = ''"
      >
        <div id="hierarchy-list" class="sidebar-list p-0" tabindex="-1" :style="listStyle">
          <ul class="list-group" aria-labelledby="hierarchy" role="tree" v-if="!loadingHierarchy">
            <tab-hier-wrapper
              :hierarchy="hierarchy"
              :selectedConcept="selectedConcept"
              :loadingChildren="loadingChildren"
              :toConceptPageAriaMessage="toConceptPageAriaMessage"
              :visibleConceptCount="visibleConceptCount"
              @load-children="loadChildren($event)"
              @select-concept="selectedConcept = $event"
            ></tab-hier-wrapper>
          </ul>
          <template v-else>
            <i class="fa-solid fa-spinner fa-spin-pulse"></i>
          </template>
        </div>
      </div>
    `
  })

  /* Custom directive used to add an event listener on clicks on the hierarchy nav-item element */
  tabHierApp.directive('click-tab-hierarchy', {
    beforeMount: (el, binding) => {
      el.clickTabEvent = event => {
        binding.value() // calling the method given as the attribute value (handleClickHierarchyEvent)
      }
      document.querySelector('#hierarchy').addEventListener('shown.bs.tab', el.clickTabEvent) // registering an event listener on bootstrap's tab shown event on the hierarchy nav-item element
    },
    unmounted: el => {
      document.querySelector('#hierarchy').removeEventListener('shown.bs.tab', el.clickTabEvent)
    }
  })

  /* Custom directive used to add an event listener on clicks on the sidebar-collapse-btn element on mobile */
  tabHierApp.directive('click-collapse-btn', {
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
  tabHierApp.directive('resize-window', {
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
  tabHierApp.directive('window-popstate', {
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

  tabHierApp.component('tab-hier-wrapper', {
    props: ['hierarchy', 'selectedConcept', 'loadingChildren', 'toConceptPageAriaMessage', 'visibleConceptCount'],
    emits: ['loadChildren', 'selectConcept'],
    data () {
      return {
        conceptInFocus: 0
      }
    },
    mounted () {
      // scroll automatically to selected concept after the whole hierarchy tree has been mounted
      if (this.selectedConcept) {
        const selected = document.querySelectorAll('#hierarchy-list .list-group-item .selected')[0]
        const list = document.querySelector('#hierarchy-list')

        // distances to the top of the page
        const selectedTop = selected?.getBoundingClientRect().top
        const listTop = list.getBoundingClientRect().top

        // height of the visible portion of the list element
        const listHeight = list.getBoundingClientRect().bottom < window.innerHeight
          ? list.getBoundingClientRect().height
          : window.innerHeight - listTop

        list.scrollBy({
          top: selectedTop - listTop - listHeight / 2, // scroll top of selected element to the middle of list element
          behavior: 'smooth'
        })
      }
    },
    methods: {
      loadChildren (concept) {
        this.$emit('loadChildren', concept)
      },
      selectConcept (concept) {
        this.$emit('selectConcept', concept)
      },
      handleKeydownEvent (e) {
        if (e.key === ' ' || e.key === 'Enter') {
          // Click on link currently in focus
          e.preventDefault()
          document.querySelector('#hierarchy-concept' + this.conceptInFocus + ' a').click()
        } else if (e.key === 'ArrowDown') {
          // On last element move focus to first list item, otherwise next list item
          e.preventDefault()
          this.moveFocus((this.conceptInFocus + 1) % this.visibleConceptCount)
        } else if (e.key === 'ArrowUp') {
          // On first element move focus to last list item, otherwise to previous list item
          e.preventDefault()
          this.moveFocus(this.conceptInFocus === 0 ? this.visibleConceptCount - 1 : this.conceptInFocus - 1)
        } else if (e.key === 'Home') {
          // Move focus to first list item
          e.preventDefault()
          this.moveFocus(0)
        } else if (e.key === 'End') {
          // Move focus to last list item
          e.preventDefault()
          this.moveFocus(this.visibleConceptCount - 1)
        }
      },
      moveFocus (i) {
        this.conceptInFocus = i
        document.getElementById('hierarchy-concept' + this.conceptInFocus).focus()
      }
    },
    template: `
      <template v-for="(c, i) in hierarchy" >
        <tab-hier
          :concept="c"
          :selectedConcept="selectedConcept"
          :isTopConcept="true"
          :isLast="i == hierarchy.length - 1"
          :loadingChildren="loadingChildren"
          :toConceptPageAriaMessage="toConceptPageAriaMessage"
          :conceptInFocus="conceptInFocus"
          @load-children="loadChildren($event)"
          @select-concept="selectConcept($event)"
          @link-keydown="handleKeydownEvent($event)"
          @move-focus="moveFocus($event)"
        ></tab-hier>
      </template>
    `
  })

  tabHierApp.component('tab-hier', {
    props: [
      'concept',
      'selectedConcept',
      'isTopConcept',
      'isLast',
      'loadingChildren',
      'toConceptPageAriaMessage',
      'conceptInFocus'
    ],
    emits: ['loadChildren', 'selectConcept', 'linkKeydown', 'closeParent', 'moveFocus'],
    inject: ['partialPageLoad', 'getConceptURL', 'showNotation'],
    methods: {
      handleClickOpenEvent (concept) {
        concept.isOpen = !concept.isOpen
        this.$emit('loadChildren', concept)
        this.$emit('moveFocus', concept.index)
      },
      handleClickConceptEvent (event, concept) {
        concept.isOpen = true
        this.$emit('loadChildren', concept)
        this.$emit('selectConcept', concept.uri)
        this.$emit('moveFocus', concept.index)
        this.partialPageLoad(event, this.getConceptURL(concept.uri))
      },
      handleKeydownEvent (e, c) {
        // Prevent event from bubbling up to ancestors
        if (e.currentTarget !== e.target) {
          return
        }

        if (e.key === 'ArrowRight') {
          if (!c.isOpen && c.hasChildren) {
            // If right arrow key is pressed on a closed concept, open it
            c.isOpen = true
            this.$emit('loadChildren', c)
          } else if (c.children.length > 0) {
            // If right arrow is pressed on a open concept, move focus to first child
            this.$emit('moveFocus', c.index + 1)
          }
        } else if (e.key === 'ArrowLeft' && c.isOpen) {
          // If left arrow key is pressed on an open concept, close it
          c.isOpen = false
          this.$emit('loadChildren', c)
        } else if (e.key === 'ArrowLeft') {
          // If left arrow key is pressed on a closed concept, close its parent
          this.$emit('closeParent')
        } else {
          // Otherwise, deal with other key press types in tab-hier-wrapper
          this.$emit('linkKeydown', e)
        }
      },
      handleCloseParentEvent () {
        // Close this concept when left arrow key is pressed on a closed child
        this.concept.isOpen = false
        this.$emit('loadChildren', this.concept)
        this.$emit('moveFocus', this.concept.index)
      },
      loadChildrenRecursive (concept) {
        this.$emit('loadChildren', concept)
      },
      selectConceptRecursive (concept) {
        this.$emit('selectConcept', concept)
      },
      linkKeydownRecursive (event) {
        this.$emit('linkKeydown', event)
      },
      moveFocusRecursive (index) {
        this.$emit('moveFocus', index)
      }
    },
    template: `
      <li class="list-group-item p-0" role="treeitem"
        :class="{ 'top-concept': isTopConcept }"
        :tabindex="concept.index === conceptInFocus ? 0 : -1"
        :id="'hierarchy-concept' + concept.index"
        :aria-expanded="concept.hasChildren ? concept.isOpen : null"
        :aria-selected="concept.uri === selectedConcept || null"
        @keydown="handleKeydownEvent($event, concept)"
      >
        <button type="button" class="hierarchy-button btn btn-primary" aria-hidden="true" tabindex="-1"
          :class="{ 'open': concept.isOpen }"
          v-if="concept.hasChildren"
          @click="handleClickOpenEvent(concept)"
        >
          <template v-if="loadingChildren.includes(concept)">
            <i class="fa-solid fa-spinner fa-spin-pulse"></i>
          </template>
          <template v-else>
            <img v-if="concept.isOpen" alt="" src="resource/pics/black-lower-right-triangle.svg">
            <img v-else alt="" src="resource/pics/lower-right-triangle.svg">
          </template>
        </button>
        <span class="concept-label" :class="{ 'last': isLast }">
          <a tabindex="-1"
            :class="{ 'selected': selectedConcept === concept.uri }" 
            :href="getConceptURL(concept.uri)"
            @click="handleClickConceptEvent($event, concept)"
          >
            <span v-if="showNotation && concept.notation" class="concept-notation">{{ concept.notation }} </span>
            {{ concept.label }}
            <span class="visually-hidden">{{ toConceptPageAriaMessage }}</span>
          </a>
        </span>
        <ul v-if="concept.children.length !== 0 && concept.isOpen" class="list-group ps-3" role="group">
          <template v-for="(c, i) in concept.children">
            <tab-hier
              :concept="c"
              :selectedConcept="selectedConcept"
              :toConceptPageAriaMessage="toConceptPageAriaMessage"
              :isTopConcept="false"
              :isLast="i == concept.children.length - 1"
              :loadingChildren="loadingChildren"
              :conceptInFocus="conceptInFocus"
              @load-children="loadChildrenRecursive($event)"
              @select-concept="selectConceptRecursive($event)"
              @link-keydown="linkKeydownRecursive($event)"
              @close-parent="handleCloseParentEvent()"
              @move-focus="moveFocusRecursive($event)"
            ></tab-hier>
          </template>
        </ul>
      </li>
    `
  })

  // initialize the collators needed by the app
  tabHierApp.config.globalProperties.$collator = new Intl.Collator(
    window.SKOSMOS.content_lang || window.SKOSMOS.lang,
    {
      sensitivity: 'variant',
      numeric: window.SKOSMOS.sortByNotation === 'natural'
    }
  )
  tabHierApp.config.globalProperties.$fallbackCollator = new Intl.Collator(
    window.SKOSMOS.content_lang || window.SKOSMOS.lang,
    {
      sensitivity: 'variant',
      numeric: false
    }
  )

  tabHierApp.mount('#tab-hierarchy')
}

async function initializeHierarchyApp () {
  try {
    await window.getIntlCollatorReady()
  } catch (e) {
    console.error('Intl.Collator polyfill failed to load, continuing with native collator:', e)
  }

  startHierarchyApp()
}

onTranslationReady(function () {
  if (document.getElementById('tab-hierarchy')) {
    if (typeof window.getIntlCollatorReady === 'function') {
      initializeHierarchyApp()
    } else {
      // wait for the polyfill promise to be initialized
      document.addEventListener('intlCollatorPromiseReady', initializeHierarchyApp)
    }
  }
})
