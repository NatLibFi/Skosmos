/* global Vue */
/* global partialPageLoad, getConceptURL */

const tabGroupsApp = Vue.createApp({
  data () {
    return {
      groups: [],
      selectedGroup: '',
    }
  },
  provide () {
    return {
      
    }
  },
  mounted () {
    // Load groups if groups tab is active when the page is first opened (otherwise only load groups when the tab is clicked)
    if (document.querySelector('#groups > a').classList.contains('active')) {
      this.loadGroups()
    }
  },
  beforeUpdate () {

  },
  methods: {
    handleClickGroupsEvent () {
      // Only load groups if groups tab is available
      if (!document.querySelector('#groups > a').classList.contains('disabled')) {
        this.loadGroups()
      }
    },
    loadGroups () {
      fetch('rest/v1/' + window.SKOSMOS.vocab + '/groups/?lang=' + window.SKOSMOS.content_lang)
        .then(data => {
          return data.json()
        })
        .then(data => {
          const groups = data.groups
          const result = []
        
          // Map groups by uri with group properties for easy lookup
          const uriMap = new Map()
          for (const group of groups) {
            uriMap.set(group.uri, { ...group, childGroups: [], isOpen: false, isGroup: true })
          }

          // Iterate through groups and set child groups in uriMap
          for (const group of groups) {
            if (group.childGroups) {
              for (const childUri of group.childGroups) {
                const child = uriMap.get(childUri)
                if (child) {
                  uriMap.get(group.uri).childGroups.push(child)
                }
              }
            }
        
            // Add top level groups to result list
            if (!groups.some(other => other.childGroups?.includes(group.uri))) {
              result.push(uriMap.get(group.uri))
            }
          }
          
          return {result, uriMap}
        })
        .then(({result, uriMap}) => {

          // Check which page we are on
          if (window.SKOSMOS.uri) {
            // If we are on concept/group page, set open nodes and load group members
            this.selectedGroup = window.SKOSMOS.uri

            fetch('rest/v1/' + window.SKOSMOS.vocab + '/groupMembers/?lang=' + window.SKOSMOS.content_lang + '&uri=' + window.SKOSMOS.uri)
              .then(data => {
                return data.json()
              })
              .then(data => {
                // Filter out existing groups from members list and add the correct properties
                const members = data.members
                  .filter(m => !uriMap.has(m.uri))
                  .map(m => {
                    return {...m, childGroups: [], isOpen: false, isGroup: false }
                  })
                console.log(members)

                // Set isOpen to true for the selected group and its parents and add child members to selected group
                this.setIsOpenAndAddMembers(result, this.selectedGroup, members)

                this.groups = result
                console.log("groups", this.groups)
              })
          } else {
            // If we are on vocab home page, just set groups
            this.groups = result
            console.log("groups", this.groups)
          }
        })
    },
    setIsOpenAndAddMembers (tree, selectedGroup, members) {
      // Recursive function to find selected group and set its properties
      const findAndSet = node => {
        if (node.uri === selectedGroup) {
          // If selected group was found, set this group to open, add members to it and return true
          node.isOpen = true
          node.childGroups.push(...members)
          return true
        }

        for (const child of node.childGroups) {
          // Recursively call findAndSet for all children
          if (findAndSet(child)) {
            // If selected group was found in children, set this group to open and return true 
            node.isOpen = true
            return true
          }
        }

        // If selected group was not found, return false
        return false
      }
  
      for (const root of tree) {
        findAndSet(root)
      }
    }
  },
  template: `
    <div v-click-tab-groups="handleClickGroupsEvent">
    </div>
  `
})

/* Custom directive used to add an event listener on clicks on the groups nav-item element */
tabGroupsApp.directive('click-tab-groups', {
  beforeMount: (el, binding) => {
    el.clickTabEvent = event => {
      binding.value() // calling the method given as the attribute value (handleClickGroupsEvent)
    }
    document.querySelector('#groups').addEventListener('click', el.clickTabEvent) // registering an event listener on clicks on the groups nav-item element
  },
  unmounted: el => {
    document.querySelector('#groups').removeEventListener('click', el.clickTabEvent)
  }
})

tabGroupsApp.mount('#tab-groups')
