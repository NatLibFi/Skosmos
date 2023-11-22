/* global Vue */
/* global SKOSMOS */
/* global partialPageLoad, getConceptURL */

const tabHierApp = Vue.createApp({
  data () {
    return {
      hierarchy: [],
      loading: false,
      selectedConcept: ''
    }
  },
  provide () {
    return {
      partialPageLoad,
      getConceptURL
    }
  },
  mounted () {
    // load hierarchy if hierarchy tab is active when the page is first opened (otherwise only load hierachy when the tab is clicked)
    if (document.querySelector('#hierarchy > a').classList.contains('active')) {
      this.loadHierarchy()
    }
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
      if (SKOSMOS.uri) {
        this.loadConceptHierarchy()
      } else {
        this.loadTopConcepts()
      }
    },
    loadTopConcepts () {
      this.loading = true
      fetch('rest/v1/' + SKOSMOS.vocab + '/topConcepts/?lang=' + SKOSMOS.lang)
        .then(data => {
          return data.json()
        })
        .then(data => {
          console.log('top', data)

          this.hierarchy = []

          for (const c of data.topconcepts) {
            this.hierarchy.push({ uri: c.uri, label: c.label, hasChildren: c.hasChildren, children: [], isOpen: false })
          }

          this.loading = false
          console.log(this.hierarchy)
        })
    },
    loadConceptHierarchy () {
      this.loading = true
      fetch('rest/v1/' + SKOSMOS.vocab + '/hierarchy/?uri=' + SKOSMOS.uri + '&lang=' + SKOSMOS.lang)
        .then(data => {
          return data.json()
        })
        .then(data => {
          console.log('hier', data)

          this.hierarchy = []

          const bt = data.broaderTransitive
          const parents = [] // queue of nodes in hierarchy tree with potential missing child nodes

          // add top concepts to hierarchy tree
          for (const concept in bt) {
            if (bt[concept].top) {
              if (bt[concept].narrower) {
                // children of the current concept
                const children = bt[concept].narrower.map(c => {
                  return { uri: c.uri, label: c.label, hasChildren: c.hasChildren, children: [], isOpen: false }
                })
                // new concept node to be added to hierarchy tree
                const conceptNode = { uri: bt[concept].uri, label: bt[concept].prefLabel, hasChildren: true, children, isOpen: true }
                // push new concept to hierarchy tree
                this.hierarchy.push(conceptNode)
                // push new concept to parent queue
                parents.push(conceptNode)
              } else {
                // push new concept node to hierarchy tree
                this.hierarchy.push({ uri: bt[concept].uri, label: bt[concept].prefLabel, hasChildren: bt[concept].hasChildren, children: [], isOpen: false })
              }
            }
          }

          // add other concepts to hierarhy tree
          while (parents.length !== 0) {
            const parent = parents.shift() // parent node with potential missing child nodes
            const concepts = []

            // find all concepts in broaderTransative which have current parent node as parent
            for (const concept in bt) {
              if (bt[concept].broader && bt[concept].broader.includes(parent.uri)) {
                concepts.push(bt[concept])
              }
            }

            // for all found concepts, add their children to hierarchy
            for (const concept of concepts) {
              if (concept.narrower) {
                // corresponding concept node in hierarchy tree
                const conceptNode = parent.children.find(c => c.uri === concept.uri)
                // children of current concept
                const children = concept.narrower.map(c => {
                  return { uri: c.uri, label: c.label, hasChildren: c.hasChildren, children: [], isOpen: false }
                })
                // set children of current concept as children of concept node
                conceptNode.children = children
                conceptNode.isOpen = children.length !== 0
                // push concept node to parent queue
                parents.push(conceptNode)
              }
            }
          }

          this.loading = false
          this.selectedConcept = SKOSMOS.uri
          console.log(this.hierarchy)
        })
    },
    loadChildren (concept) {
      // load children only if concept has children but they have not been loaded yet
      if (concept.children.length === 0 && concept.hasChildren) {
        fetch('rest/v1/' + SKOSMOS.vocab + '/children?uri=' + concept.uri + '&lang=' + SKOSMOS.lang)
          .then(data => {
            return data.json()
          })
          .then(data => {
            console.log(data)
            for (const c of data.narrower) {
              concept.children.push({ uri: c.uri, label: c.prefLabel, hasChildren: c.hasChildren, children: [], isOpen: false })
            }
            console.log(this.hierarchy)
          })
      }
    }
  },
  template: `
    <div v-click-tab-hierarchy="handleClickHierarchyEvent">
      <ul class="list-group sidebar-list p-0" v-if="!loading">
        <template v-for="(c, i) in hierarchy">
          <tab-hier
            :concept="c"
            :selectedConcept="selectedConcept"
            :isTopConcept="true"
            :isLast="i == hierarchy.length - 1 && !c.isOpen"
            @load-children="loadChildren($event)"
            @select-concept="selectedConcept = $event"
          ></tab-hier>
        </template>
      </ul>
      <template v-else>Loading...</template><!-- Add a spinner or equivalent -->
    </div>
  `
})

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

tabHierApp.component('tab-hier', {
  props: ['concept', 'selectedConcept', 'isTopConcept', 'isLast'],
  emits: ['loadChildren', 'selectConcept'],
  inject: ['partialPageLoad', 'getConceptURL'],
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
      <button type="button" class="hierarchy-button btn btn-primary"
        :class="{ 'open': concept.isOpen }"
        v-if="concept.hasChildren"
        @click="handleClickOpenEvent(concept)"
      >
        <i>{{ concept.isOpen ? '&#x25E2;' : '&#x25FF;' }}</i>
      </button>
      <span :class="{ 'last': isLast }">
        <a :class="{ 'selected': selectedConcept === concept.uri }"
          :href="getConceptURL(concept.uri)"
          @click="handleClickConceptEvent($event, concept)"
        >{{ concept.label }}</a>
      </span>
      <ul class="list-group px-3" v-if="concept.children.length !== 0 && concept.isOpen">
        <template v-for="(c, i) in concept.children">
          <tab-hier
            :concept="c"
            :selectedConcept="selectedConcept"
            :isTopConcept="false"
            :isLast="i == concept.children.length - 1 && !c.isOpen"
            @load-children="loadChildrenRecursive($event)"
            @select-concept="selectConceptRecursive($event)"
          ></tab-hier>
        </template>
      </ul>
    </li>
  `
})

tabHierApp.mount('#tab-hierarchy')
