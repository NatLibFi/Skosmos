/* global Vue */
/* global partialPageLoad, getConceptURL */

const tabHierApp = Vue.createApp({
  data () {
    return {
      hierarchy: [],
      loading: true,
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
      if (window.SKOSMOS.uri) {
        this.loadConceptHierarchy()
      } else {
        this.loadTopConcepts()
      }
    },
    loadTopConcepts () {
      this.loading = true
      fetch('rest/v1/' + window.SKOSMOS.vocab + '/topConcepts/?lang=' + window.SKOSMOS.content_lang)
        .then(data => {
          return data.json()
        })
        .then(data => {
          console.log('data', data)

          this.hierarchy = []

          for (const c of data.topconcepts.sort((a, b) => a.label.localeCompare(b.label, window.SKOSMOS.content_lang))) {
            this.hierarchy.push({ uri: c.uri, label: c.label, hasChildren: c.hasChildren, children: [], isOpen: false })
          }

          this.loading = false
          console.log('hier', this.hierarchy)
        })
    },
    loadConceptHierarchy () {
      this.loading = true
      fetch('rest/v1/' + window.SKOSMOS.vocab + '/hierarchy/?uri=' + window.SKOSMOS.uri + '&lang=' + window.SKOSMOS.content_lang)
        .then(data => {
          return data.json()
        })
        .then(data => {
          console.log('data', data)

          this.hierarchy = []

          // transform broaderTransitive to an array and sort it
          const bt = Object.values(data.broaderTransitive).sort((a, b) => a.prefLabel.localeCompare(b.prefLabel, window.SKOSMOS.content_lang))
          const parents = [] // queue of nodes in hierarchy tree with potential missing child nodes

          // add top concepts to hierarchy tree
          for (const concept of bt) {
            if (concept.top) {
              if (concept.narrower) {
                // children of the current concept
                const children = concept.narrower
                  .sort((a, b) => a.label.localeCompare(b.label, window.SKOSMOS.content_lang))
                  .map(c => {
                    return { uri: c.uri, label: c.label, hasChildren: c.hasChildren, children: [], isOpen: false }
                  })
                // new concept node to be added to hierarchy tree
                const conceptNode = { uri: concept.uri, label: concept.prefLabel, hasChildren: true, children, isOpen: true }
                // push new concept to hierarchy tree
                this.hierarchy.push(conceptNode)
                // push new concept to parent queue
                parents.push(conceptNode)
              } else {
                // push new concept node to hierarchy tree
                this.hierarchy.push({ uri: concept.uri, label: concept.prefLabel, hasChildren: concept.hasChildren, children: [], isOpen: false })
              }
            }
          }

          // add other concepts to hierarhy tree
          while (parents.length !== 0) {
            const parent = parents.shift() // parent node with potential missing child nodes
            const concepts = []

            // find all concepts in broaderTransative which have current parent node as parent
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
                  .sort((a, b) => a.label.localeCompare(b.label, window.SKOSMOS.content_lang))
                  .map(c => {
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
          this.selectedConcept = window.SKOSMOS.uri
          console.log('hier', this.hierarchy)
        })
    },
    loadChildren (concept) {
      // load children only if concept has children but they have not been loaded yet
      if (concept.children.length === 0 && concept.hasChildren) {
        fetch('rest/v1/' + window.SKOSMOS.vocab + '/children?uri=' + concept.uri + '&lang=' + window.SKOSMOS.content_lang)
          .then(data => {
            return data.json()
          })
          .then(data => {
            console.log('data', data)
            for (const c of data.narrower.sort((a, b) => a.prefLabel.localeCompare(b.prefLabel, window.SKOSMOS.content_lang))) {
              concept.children.push({ uri: c.uri, label: c.prefLabel, hasChildren: c.hasChildren, children: [], isOpen: false })
            }
            console.log('hier', this.hierarchy)
          })
      }
    },
    getListStyle () {
      const height = document.getElementById('sidebar-tabs').clientHeight
      const width = document.getElementById('sidebar-tabs').clientWidth
      return {
        height: 'calc( 100% - ' + height + 'px)',
        width: width + 'px'
      }
    }
  },
  template: `
    <div v-click-tab-hierarchy="handleClickHierarchyEvent">
      <div id="hierarchy-list" class="sidebar-list p-0" :style="getListStyle()">
        <ul class="list-group" v-if="!loading">
          <tab-hier-wrapper
            :hierarchy="hierarchy"
            :selectedConcept="selectedConcept"
            @load-children="loadChildren($event)"
            @select-concept="selectedConcept = $event"
          ></tab-hier-wrapper>
        </ul>
        <template v-else>Loading...</template><!-- Add a spinner or equivalent -->
      </div>
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

tabHierApp.component('tab-hier-wrapper', {
  props: ['hierarchy', 'selectedConcept'],
  emits: ['loadChildren', 'selectConcept'],
  mounted () {
    // scroll automatically to selected concept after the whole hierarchy tree has been mounted
    if (this.selectedConcept) {
      const selected = document.querySelectorAll('.list-group-item .selected')[0]
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
        :isLast="i == hierarchy.length - 1 && !c.isOpen"
        @load-children="loadChildren($event)"
        @select-concept="selectConcept($event)"
      ></tab-hier>
    </template>
  `
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
          aria-label="Go to the concept page">{{ concept.label }}</a>
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
