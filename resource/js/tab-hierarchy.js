const tabHierApp = Vue.createApp({
  data () {
    return {
      hierarchy: []
    }
  },
  mounted () {
    // load hierarchy if hierarchy tab is active when the page is first opened (otherwise only load hierachy when the tab is clicked)
    if (document.querySelector('#hierarchy > a').classList.contains('active')) {
      this.loadHierarchy()
    }
  },
  methods: {
    loadHierarchy () {
      // if we are on the concept page, load hierarchy for the concept, otherwise load top concepts
      if (SKOSMOS.uri) {
        this.loadConceptHierarchy()
      } else {
        this.loadTopConcepts()
      }
    },
    loadTopConcepts () {
      fetch('rest/v1/' + SKOSMOS.vocab + '/topConcepts/?lang=' + SKOSMOS.lang)
        .then(data => {
          return data.json()
        })
        .then(data => {
          console.log('top', data)
          for (c of data.topconcepts) {
            this.hierarchy.push({ uri: c.uri, label: c.label, hasChildren: c.hasChildren, children: [] })
          }
          console.log(this.hierarchy)
        })
    },
    loadConceptHierarchy () {
      fetch('https://api.finto.fi/rest/v1/' + SKOSMOS.vocab + '/hierarchy/?uri=' + SKOSMOS.uri + '&lang=' + SKOSMOS.lang)
        .then(data => {
          return data.json()
        })
        .then(data => {
          console.log('hier', data)

          const bt = data.broaderTransitive
          let parents = [] // queue of nodes in hierarchy tree with potential missing child nodes

          // add top concepts to hierarchy tree
          for (concept in bt) {
            if (bt[concept].top) {
              if (bt[concept].narrower) {
                // children of the current concept
                const children = bt[concept].narrower.map(c => {
                  return {uri: c.uri, label: c.label, hasChildren: c.hasChildren, children: [] }
                })
                // new concept node to be added to hierarchy tree
                const conceptNode = { uri: bt[concept].uri, label: bt[concept].prefLabel, hasChildren: true, children: children }
                // push new concept to hierarchy tree
                this.hierarchy.push(conceptNode)
                // push new concept to parent queue
                parents.push(conceptNode)
              } else {
                // push new concept node to hierarchy tree
                this.hierarchy.push({ uri: bt[concept].uri, label: bt[concept].prefLabel, hasChildren: bt[concept].hasChildren, children: [] })
              }
            }
          }

          // add other concepts to hierarhy tree
          while (parents.length !== 0) {
            let parent = parents.shift() // parent node with potential missing child nodes
            let concepts = []

            // find all concepts in broaderTransative which have current parent node as parent
            for (concept in bt) {
              if(bt[concept].broader && bt[concept].broader.includes(parent.uri)) {
                concepts.push(bt[concept])
              }
            }

            // for all found concepts, add their children to hierarchy
            for (concept of concepts) {
              if (concept.narrower) {
                // corresponding concept node in hierarchy tree
                const conceptNode = parent.children.find(c => c.uri === concept.uri)
                // children of current concept
                const children = concept.narrower.map(c => {
                  return {uri: c.uri, label: c.label, hasChildren: c.hasChildren, children: [] }
                })
                // set children of current concept as children of concept node
                conceptNode.children = children
                // push concept node to parent queue
                parents.push(conceptNode)
              }
            }
          }

          console.log(this.hierarchy)
        })
    },
    loadChildren (concept) {
      if (concept.children.length === 0 && concept.hasChildren) {
        fetch('rest/v1/' + SKOSMOS.vocab + '/children?uri=' + concept.uri + '&lang=' + SKOSMOS.lang)
          .then(data => {
            return data.json()
          })
          .then(data => {
            console.log(data)
            for (c of data.narrower) {
              concept.children.push({ uri: c.uri, label: c.prefLabel, hasChildren: c.hasChildren, children: [] })
            }
            console.log(this.hierarchy)
          })
      }
    }
  },
  template: `
    <div v-click-tab-hierarchy="loadHierarchy">
      <ul>
        <template v-for="c in hierarchy">
          <tab-hier :concept="c" @load-children="loadChildren($event)"></tab-hier>
        </template>
      </ul>
    </div>
  `
})

tabHierApp.directive('click-tab-hierarchy', {
  beforeMount: (el, binding) => {
    el.clickTabEvent = event => {
      binding.value() // calling the method given as the attribute value (loadHierarchy)
    }
    document.querySelector('#hierarchy').addEventListener('click', el.clickTabEvent) // registering an event listener on clicks on the hierarchy nav-item element
  },
  unmounted: el => {
    document.querySelector('#hierarchy').removeEventListener('click', el.clickTabEvent)
  }
})

tabHierApp.component('tab-hier', {
  props: ['concept'],
  emits: ['loadChildren'],
  methods: {
    loadChildren (event, concept) {
      event.preventDefault()
      this.$emit('loadChildren', concept)
    },
    loadChildrenRecursive(concept) {
      this.$emit('loadChildren', concept)
    }
  },
  template: `
    <li>
      <div @click="loadChildren($event, concept)">
        <a :style="{ fontWeight: concept.hasChildren ? 'bold' : 'normal' }">
          {{ concept.label }}
        </a>
      </div>
      <ul v-if="concept.children.length !== 0">
        <template v-for="c in concept.children">
          <tab-hier :concept="c" @load-children="loadChildrenRecursive($event)"></tab-hier>
        </template>
      </ul>
    </li>
  `
})

tabHierApp.mount('#tab-hierarchy')
