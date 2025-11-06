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
        listStyle: {}
      }
    },
    computed: {
      openAriaMessage () {
        return $t('Open')
      },
      goToTheConceptPageAriaMessage () {
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
      loadTopConcepts () {
        this.loadingHierarchy = true
        if (window.SKOSMOS.showConceptSchemesInHierarchy) {
          // if concept schemes are shown in hierarchy, fetch them from API and set them as top concepts in hierarchy
          fetch('rest/v1/' + window.SKOSMOS.vocab + '/?lang=' + window.SKOSMOS.content_lang)
            .then(data => {
              return data.json()
            })
            .then(data => {
              console.log('data', data)

              this.hierarchy = []

              for (const c of data.conceptschemes.sort((a, b) => this.compareConcepts(a, b))) {
                this.hierarchy.push({ uri: c.uri, label: c.title || c.label, hasChildren: true, children: [], isOpen: false, notation: undefined, isScheme: true })
              }

              this.loadingHierarchy = false
              console.log('hier', this.hierarchy)
            })
        } else {
          // otherwise, fetch top concepts
          fetch('rest/v1/' + window.SKOSMOS.vocab + '/topConcepts/?lang=' + window.SKOSMOS.content_lang)
            .then(data => {
              return data.json()
            })
            .then(data => {
              console.log('data', data)

              this.hierarchy = []

              for (const c of data.topconcepts.sort((a, b) => this.compareConcepts(a, b))) {
                this.hierarchy.push(this.createConceptNode(c))
              }

              this.loadingHierarchy = false
              console.log('hier', this.hierarchy)
            })
        }
      },
      async loadConceptHierarchy () {
        this.loadingHierarchy = true
        this.hierarchy = []

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

        this.loadingHierarchy = false
        this.selectedConcept = window.SKOSMOS.uri
        console.log('hier', this.hierarchy)
      },
      loadChildren (concept) {
        // load children only if concept has children but they have not been loaded yet
        if (concept.children.length === 0 && concept.hasChildren) {
          this.loadingChildren.push(concept)
          if (window.SKOSMOS.showConceptSchemesInHierarchy && concept.isScheme) {
            // if the concept is a concept scheme, fetch topconcepts as children
            fetch('rest/v1/' + window.SKOSMOS.vocab + '/topConcepts?scheme=' + concept.uri + '&lang=' + window.SKOSMOS.content_lang)
              .then(data => {
                return data.json()
              })
              .then(data => {
                console.log('data', data)
                for (const c of data.topconcepts.sort((a, b) => this.compareConcepts(a, b))) {
                  concept.children.push(this.createConceptNode(c))
                }
                this.loadingChildren = this.loadingChildren.filter(x => x !== concept)
                console.log('hier', this.hierarchy)
              })
          } else {
            // otherwise, fetch children on concept
            fetch('rest/v1/' + window.SKOSMOS.vocab + '/children?uri=' + concept.uri + '&lang=' + window.SKOSMOS.content_lang)
              .then(data => {
                return data.json()
              })
              .then(data => {
                console.log('data', data)
                for (const c of data.narrower.sort((a, b) => this.compareConcepts(a, b))) {
                  concept.children.push(this.createConceptNode(c))
                }
                this.loadingChildren = this.loadingChildren.filter(x => x !== concept)
                console.log('hier', this.hierarchy)
              })
          }
        }
      },
      async loadConceptSchemes () {
        const res = await fetch('rest/v1/' + window.SKOSMOS.vocab + '/?lang=' + window.SKOSMOS.content_lang)
        const data = await res.json()

        for (const s of data.conceptschemes.sort((a, b) => this.compareConcepts(a, b))) {
          const schemeNode = { uri: s.uri, label: s.title || s.label, hasChildren: true, children: [], isOpen: s.uri === window.SKOSMOS.uri, notation: undefined, isScheme: true }
          this.hierarchy.push(schemeNode)
        }
      },
      async loadTopConceptsForConceptScheme () {
        const res = await fetch('rest/v1/' + window.SKOSMOS.vocab + '/topConcepts?scheme=' + window.SKOSMOS.uri + '&lang=' + window.SKOSMOS.content_lang)
        const data = await res.json()

        // find selected scheme in hierarchy
        const scheme = this.hierarchy.find(s => s.uri === window.SKOSMOS.uri)
        // add top concepts to hierarchy as the scheme's children
        scheme.children = data.topconcepts
          .sort((a, b) => this.compareConcepts(a, b))
          .map(c => this.createConceptNode(c))
      },
      async loadHierarchyForConcept () {
        const res = await fetch('rest/v1/' + window.SKOSMOS.vocab + '/hierarchy/?uri=' + window.SKOSMOS.uri + '&lang=' + window.SKOSMOS.content_lang)
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
        // children of the current concept
        if (concept.narrower) {
          const children = concept.narrower
            .sort((a, b) => this.compareConcepts(a, b))
            .map(c => this.createConceptNode(c))
          // new concept node to be added to hierarchy tree
          const conceptNode = this.createConceptNode(concept, true, children)

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
        const width = document.getElementById('sidebar-tabs').clientWidth - 1
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

        // Set language and options
        const lang = window.SKOSMOS.content_lang || window.SKOSMOS.lang
        const options = {
          numeric: window.SKOSMOS.sortByNotation === 'natural', // Set numeric to true if sort should be natural
          sensitivity: 'variant' // Strings that differ in base letters, diacritic marks, or case compare as unequal
        }

        const result = strA.localeCompare(strB, lang, options)
        if (result !== 0) {
          return result
        } else {
          // fall back to non-numeric sort to ensure a consistent order
          return strA.localeCompare(strB, lang, { sensitivity: 'variant' })
        }
      }
    },
    template: `
      <div v-click-tab-hierarchy="handleClickHierarchyEvent" v-resize-window="setListStyle">
        <div id="hierarchy-list" class="sidebar-list p-0" :style="listStyle">
          <ul class="list-group" v-if="!loadingHierarchy">
            <tab-hier-wrapper
              :hierarchy="hierarchy"
              :selectedConcept="selectedConcept"
              :loadingChildren="loadingChildren"
              :openAriaMessage="openAriaMessage"
              :goToTheConceptPageAriaMessage="goToTheConceptPageAriaMessage"
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
      document.querySelector('#hierarchy').addEventListener('click', el.clickTabEvent) // registering an event listener on clicks on the hierarchy nav-item element
    },
    unmounted: el => {
      document.querySelector('#hierarchy').removeEventListener('click', el.clickTabEvent)
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

  tabHierApp.component('tab-hier-wrapper', {
    props: ['hierarchy', 'selectedConcept', 'loadingChildren', 'openAriaMessage', 'goToTheConceptPageAriaMessage'],
    emits: ['loadChildren', 'selectConcept'],
    mounted () {
      // scroll automatically to selected concept after the whole hierarchy tree has been mounted
      if (this.selectedConcept) {
        const selected = document.querySelectorAll('#hierarchy-list .list-group-item .selected')[0]
        const list = document.querySelector('#hierarchy-list')

        // distances to the top of the page
        const selectedTop = selected.getBoundingClientRect().top
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
          :openAriaMessage="openAriaMessage"
          :goToTheConceptPageAriaMessage="goToTheConceptPageAriaMessage"
          @load-children="loadChildren($event)"
          @select-concept="selectConcept($event)"
        ></tab-hier>
      </template>
    `
  })

  tabHierApp.component('tab-hier', {
    props: ['concept', 'selectedConcept', 'isTopConcept', 'isLast', 'loadingChildren', 'openAriaMessage', 'goToTheConceptPageAriaMessage'],
    emits: ['loadChildren', 'selectConcept'],
    inject: ['partialPageLoad', 'getConceptURL', 'showNotation'],
    methods: {
      handleClickOpenEvent (concept) {
        concept.isOpen = !concept.isOpen
        this.$emit('loadChildren', concept)
      },
      handleClickConceptEvent (event, concept) {
        concept.isOpen = true
        this.$emit('loadChildren', concept)
        this.$emit('selectConcept', concept.uri)
        this.partialPageLoad(event, this.getConceptURL(concept.uri))
      },
      loadChildrenRecursive (concept) {
        this.$emit('loadChildren', concept)
      },
      selectConceptRecursive (concept) {
        this.$emit('selectConcept', concept)
      }
    },
    template: `
      <li class="list-group-item p-0" :class="{ 'top-concept': isTopConcept }">
        <button type="button" class="hierarchy-button btn btn-primary" :aria-label="openAriaMessage" 
          :class="{ 'open': concept.isOpen }"
          v-if="concept.hasChildren"
          @click="handleClickOpenEvent(concept)"
        >
          <template v-if="loadingChildren.includes(concept)">
            <i class="fa-solid fa-spinner fa-spin-pulse"></i>
          </template>
          <template v-else>
            <img v-if="concept.isOpen" alt="" src="resource/pics/black-lower-right-triangle.png">
            <img v-else alt="" src="resource/pics/lower-right-triangle.png">
          </template>
        </button>
        <span class="concept-label" :class="{ 'last': isLast }">
          <a :class="{ 'selected': selectedConcept === concept.uri }"
            :href="getConceptURL(concept.uri)"
            @click="handleClickConceptEvent($event, concept)"
            :aria-label="goToTheConceptPageAriaMessage"
          >
            <span v-if="showNotation && concept.notation" class="concept-notation">{{ concept.notation }} </span>
            {{ concept.label }}
          </a>
        </span>
        <ul class="list-group ps-3" v-if="concept.children.length !== 0 && concept.isOpen">
          <template v-for="(c, i) in concept.children">
            <tab-hier
              :concept="c"
              :selectedConcept="selectedConcept"
              :openAriaMessage="openAriaMessage"
              :goToTheConceptPageAriaMessage="goToTheConceptPageAriaMessage"
              :isTopConcept="false"
              :isLast="i == concept.children.length - 1"
              :loadingChildren="loadingChildren"
              @load-children="loadChildrenRecursive($event)"
              @select-concept="selectConceptRecursive($event)"
            ></tab-hier>
          </template>
        </ul>
      </li>
    `
  })

  tabHierApp.mount('#tab-hierarchy')
}

onTranslationReady(startHierarchyApp)
