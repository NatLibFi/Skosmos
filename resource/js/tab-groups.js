/* global Vue, $t, onTranslationReady */
/* global partialPageLoad, getConceptURL */

function startGroupsApp () {
  const tabGroupsApp = Vue.createApp({
    data () {
      return {
        groups: [],
        selectedGroup: '',
        loadingGroups: true,
        loadingChildren: [],
        listStyle: {},
        visibleConceptCount: 0
      }
    },
    provide () {
      return {
        partialPageLoad,
        getConceptURL,
        showNotation: window.SKOSMOS.showNotation
      }
    },
    computed: {
      toConceptPageAriaMessage () {
        return $t('Go to the concept page')
      }
    },
    mounted () {
      // Load groups if groups tab is active when the page is first opened (otherwise only load groups when the tab is clicked)
      if (document.querySelector('#groups > a').classList.contains('active')) {
        this.loadGroups()
      }

      this.setListStyle()
    },
    beforeUpdate () {
      this.setListStyle()
    },
    methods: {
      handleClickGroupsEvent () {
        // Only load groups if groups tab is available
        if (!document.querySelector('#groups > a').classList.contains('disabled')) {
          this.loadGroups()
        }
      },
      loadGroups () {
        this.loadingGroups = true
        const params = new URLSearchParams({ lang: window.SKOSMOS.content_lang })
        fetch(`rest/v1/${window.SKOSMOS.vocab}/groups/?${params}`)
          .then(data => {
            return data.json()
          })
          .then(data => {
            this.groups = []

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

            return { result, uriMap }
          })
          .then(({ result, uriMap }) => {
            // Check that we are on a group page
            if (uriMap.has(window.SKOSMOS.uri)) {
              this.selectedGroup = window.SKOSMOS.uri

              // Only load members if selected group has members
              if (uriMap.get(this.selectedGroup).hasMembers) {
                const params = new URLSearchParams({
                  lang: window.SKOSMOS.content_lang,
                  uri: this.selectedGroup
                })
                fetch(`rest/v1/${window.SKOSMOS.vocab}/groupMembers/?${params}`)
                  .then(data => {
                    return data.json()
                  })
                  .then(data => {
                    // Filter out existing groups from members list and add the correct properties
                    const members = data.members
                      .filter(m => !uriMap.has(m.uri))
                      .map(m => {
                        return { ...m, childGroups: [], isOpen: false, isGroup: false }
                      })

                    // Set isOpen to true for the selected group and its parents and add child members to selected group
                    this.setIsOpenAndAddMembers(result, this.selectedGroup, members)

                    this.groups = result
                    this.addIndicesToGroups()
                    this.loadingGroups = false
                  })
              } else {
                // If selected group has no members, set isOpen for the group and its parents
                this.setIsOpenAndAddMembers(result, this.selectedGroup, [])

                this.groups = result
                this.addIndicesToGroups()
                this.loadingGroups = false
              }
            } else {
              // If we are on vocab home page, simply set groups to result
              this.groups = result
              this.addIndicesToGroups()
              this.loadingGroups = false
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
      },
      setListStyle () {
        const height = document.getElementById('sidebar-tabs').clientHeight
        const width = document.getElementById('sidebar-tabs').getBoundingClientRect().width
        this.listStyle = {
          height: 'calc( 100% - ' + height + 'px )',
          width: width + 'px'
        }
      },
      loadChildren (group) {
        // Load children only if group has children and they have not been loaded yet
        if (group.childGroups.length === 0 && group.hasMembers) {
          this.loadingChildren.push(group)
          const params = new URLSearchParams({
            lang: window.SKOSMOS.content_lang,
            uri: group.uri
          })
          fetch(`rest/v1/${window.SKOSMOS.vocab}/groupMembers/?${params}`)
            .then(data => {
              return data.json()
            })
            .then(data => {
              for (const m of data.members) {
                group.childGroups.push({ ...m, childGroups: [], isOpen: false, isGroup: false })
              }
              this.addIndicesToGroups()
              this.loadingChildren = this.loadingChildren.filter(x => x !== group)
            })
        } else if (group.childGroups.length > 0) {
          // If the group already has children loaded, update indices in groups
          this.addIndicesToGroups()
        }
      },
      addIndicesToGroups () {
        // Adds a unique index to each concept that is visible in DOM after groups is updated
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

            if (node.childGroups.length > 0) {
              traverse(node.childGroups, node.isOpen && parentIsOpen)
            }
          }
        }

        traverse(this.groups, true)

        this.visibleConceptCount = counter
      }
    },
    template: `
      <div
        v-click-tab-groups="handleClickGroupsEvent"
        v-click-collapse-btn="setListStyle" 
        v-resize-window="setListStyle"
        v-window-popstate="() => selectedGroup = ''"
      >
        <div id="groups-list" class="sidebar-list p-0" tabindex="-1" :style="listStyle">
          <ul v-if="!loadingGroups" aria-labelledby="groups" role="tree" class="list-group">
            <tab-groups-wrapper
              :groups="groups"
              :selectedGroup="selectedGroup"
              :loadingChildren="loadingChildren"
              :toConceptPageAriaMessage="toConceptPageAriaMessage"
              :visibleConceptCount="visibleConceptCount"
              @load-children="loadChildren($event)"
              @select-group="selectedGroup = $event"
            ></tab-groups-wrapper>
          </ul>
          <i v-else class="fa-solid fa-spinner fa-spin-pulse"></i>
        </div>
      </div>
    `
  })

  /* Custom directive used to add an event listener on clicks on the groups nav-item element */
  tabGroupsApp.directive('click-tab-groups', {
    beforeMount: (el, binding) => {
      el.clickTabEvent = event => {
        binding.value() // calling the method given as the attribute value (handleClickGroupsEvent)
      }
      document.querySelector('#groups').addEventListener('shown.bs.tab', el.clickTabEvent) // registering an event listener on bootstrap's tab shown event on the groups nav-item element
    },
    unmounted: el => {
      document.querySelector('#groups').removeEventListener('shown.bs.tab', el.clickTabEvent)
    }
  })

  /* Custom directive used to add an event listener on clicks on the sidebar-collapse-btn element on mobile */
  tabGroupsApp.directive('click-collapse-btn', {
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
  tabGroupsApp.directive('resize-window', {
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
  tabGroupsApp.directive('window-popstate', {
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

  tabGroupsApp.component('tab-groups-wrapper', {
    props: ['groups', 'selectedGroup', 'loadingChildren', 'toConceptPageAriaMessage', 'visibleConceptCount'],
    emits: ['loadChildren', 'selectGroup'],
    data () {
      return {
        conceptInFocus: 0
      }
    },
    methods: {
      loadChildren (group) {
        this.$emit('loadChildren', group)
      },
      selectGroup (group) {
        this.$emit('selectGroup', group)
      },
      handleKeydownEvent (e) {
        if (e.key === ' ' || e.key === 'Enter') {
          // Click on link currently in focus
          e.preventDefault()
          document.querySelector('#groups-concept' + this.conceptInFocus + ' a').click()
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
        document.getElementById('groups-concept' + this.conceptInFocus).focus()
      }
    },
    template: `
      <template v-for="(g, i) in groups" >
        <tab-groups
          :group="g"
          :selectedGroup="selectedGroup"
          :isTopGroup="true"
          :isLast="i == groups.length - 1"
          :loadingChildren="loadingChildren"
          :toConceptPageAriaMessage="toConceptPageAriaMessage"
          :conceptInFocus="conceptInFocus"
          @load-children="loadChildren($event)"
          @select-group="selectGroup($event)"
          @link-keydown="handleKeydownEvent($event)"
          @move-focus="moveFocus($event)"
        ></tab-groups>
      </template>
    `
  })

  tabGroupsApp.component('tab-groups', {
    props: ['group', 'selectedGroup', 'isTopGroup', 'isLast', 'loadingChildren', 'toConceptPageAriaMessage', 'conceptInFocus'],
    emits: ['loadChildren', 'selectGroup', 'linkKeydown', 'closeParent', 'moveFocus'],
    inject: ['partialPageLoad', 'getConceptURL', 'showNotation'],
    methods: {
      handleClickOpenEvent (group) {
        group.isOpen = !group.isOpen
        this.$emit('loadChildren', group)
        this.$emit('moveFocus', group.index)
      },
      handleClickGroupEvent (event, group) {
        group.isOpen = true
        this.$emit('loadChildren', group)
        this.$emit('selectGroup', group.uri)
        this.$emit('moveFocus', group.index)
        this.partialPageLoad(event, this.getConceptURL(group.uri))
      },
      handleKeydownEvent (e, g) {
        // Prevent event from bubbling up to ancestors
        if (e.currentTarget !== e.target) {
          return
        }

        if (e.key === 'ArrowRight') {
          if (!g.isOpen && g.hasMembers) {
            // If right arrow key is pressed on a closed group, open it
            g.isOpen = true
            this.$emit('loadChildren', g)
          } else if (g.childGroups.length > 0) {
            // If right arrow is pressed on a open group, move focus to first child
            this.$emit('moveFocus', g.index + 1)
          }
        } else if (e.key === 'ArrowLeft' && g.isOpen) {
          // If left arrow key is pressed on an open group, close it
          g.isOpen = false
          this.$emit('loadChildren', g)
        } else if (e.key === 'ArrowLeft') {
          // If left arrow key is pressed on a closed group, close its parent
          this.$emit('closeParent')
        } else {
          // Otherwise, deal with other key press types in tab-groups-wrapper
          this.$emit('linkKeydown', e)
        }
      },
      handleCloseParentEvent () {
        // Close this group when left arrow key is pressed on a closed child
        this.group.isOpen = false
        this.$emit('loadChildren', this.group)
        this.$emit('moveFocus', this.group.index)
      },
      loadChildrenRecursive (group) {
        this.$emit('loadChildren', group)
      },
      selectGroupRecursive (group) {
        this.$emit('selectGroup', group)
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
        :class="{ 'top-concept': isTopGroup }"
        :tabindex="group.index === conceptInFocus ? 0 : -1"
        :id="'groups-concept' + group.index"
        :aria-expanded="group.hasMembers ? group.isOpen : null"
        :aria-selected="group.uri === selectedGroup || null"
        @keydown="handleKeydownEvent($event, group)"
      >
        <button type="button" class="hierarchy-button btn btn-primary" aria-hidden="true" tabindex="-1"
          :class="{ 'open': group.isOpen }"
          v-if="group.hasMembers"
          @click="handleClickOpenEvent(group)"
        >
          <template v-if="loadingChildren.includes(group)">
            <i class="fa-solid fa-spinner fa-spin-pulse"></i>
          </template>
          <template v-else>
            <img v-if="group.isOpen" alt="" src="resource/pics/black-lower-right-triangle.svg">
            <img v-else alt="" src="resource/pics/lower-right-triangle.svg">
          </template>
        </button>
        <span class="concept-label" :class="{ 'last': isLast }">
          <a tabindex="-1"
            :class="{ 'selected': selectedGroup === group.uri, 'group': group.isGroup }"
            :href="getConceptURL(group.uri)"
            @click="handleClickGroupEvent($event, group)"
          >
            <span v-if="showNotation && group.notation" class="concept-notation">{{ group.notation }} </span>
            {{ group.prefLabel }}
            <span class="visually-hidden">{{ toConceptPageAriaMessage }}</span>
          </a>
        </span>
        <ul v-if="group.childGroups.length !== 0 && group.isOpen" class="list-group ps-3" role="group">
          <template v-for="(g, i) in group.childGroups">
            <tab-groups
              :group="g"
              :selectedGroup="selectedGroup"
              :isTopGroup="false"
              :isLast="i == group.childGroups.length - 1"
              :loadingChildren="loadingChildren"
              :toConceptPageAriaMessage="toConceptPageAriaMessage"
              :conceptInFocus="conceptInFocus"
              @load-children="loadChildrenRecursive($event)"
              @select-group="selectGroupRecursive($event)"
              @link-keydown="linkKeydownRecursive($event)"
              @close-parent="handleCloseParentEvent()"
              @move-focus="moveFocusRecursive($event)"
            ></tab-groups>
          </template>
        </ul>
      </li>
    `
  })

  if (document.getElementById('tab-groups')) {
    tabGroupsApp.mount('#tab-groups')
  }
}

onTranslationReady(startGroupsApp)
