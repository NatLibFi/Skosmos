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
        listStyle: {}
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
      openAriaMessage () {
        return $t('Open')
      },
      goToTheConceptPageAriaMessage () {
        return $t('Go to the concept page')
      }
    },
    mounted () {
      // Load groups if groups tab is active when the page is first opened (otherwise only load groups when the tab is clicked)
      if (document.querySelector('#groups > a').classList.contains('active')) {
        this.loadGroups()
      }
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
        fetch('rest/v1/' + window.SKOSMOS.vocab + '/groups/?lang=' + window.SKOSMOS.content_lang)
          .then(data => {
            return data.json()
          })
          .then(data => {
            console.log('groups data', data)

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
                fetch('rest/v1/' + window.SKOSMOS.vocab + '/groupMembers/?lang=' + window.SKOSMOS.content_lang + '&uri=' + this.selectedGroup)
                  .then(data => {
                    return data.json()
                  })
                  .then(data => {
                    console.log('members data', data)
                    // Filter out existing groups from members list and add the correct properties
                    const members = data.members
                      .filter(m => !uriMap.has(m.uri))
                      .map(m => {
                        return { ...m, childGroups: [], isOpen: false, isGroup: false }
                      })

                    // Set isOpen to true for the selected group and its parents and add child members to selected group
                    this.setIsOpenAndAddMembers(result, this.selectedGroup, members)

                    this.groups = result
                    this.loadingGroups = false
                    console.log('groups', this.groups)
                  })
              } else {
                // If selected group has no members, set isOpen for the group and its parents
                this.setIsOpenAndAddMembers(result, this.selectedGroup, [])

                this.groups = result
                this.loadingGroups = false
                console.log('groups', this.groups)
              }
            } else {
              // If we are on vocab home page, simply set groups to result
              this.groups = result
              this.loadingGroups = false
              console.log('groups', this.groups)
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
        const width = document.getElementById('sidebar-tabs').clientWidth - 1
        this.listStyle = {
          height: 'calc( 100% - ' + height + 'px )',
          width: width + 'px'
        }
      },
      loadChildren (group) {
        // Load children only if group has children and they have not been loaded yet
        if (group.childGroups.length === 0 && group.hasMembers) {
          this.loadingChildren.push(group)
          fetch('rest/v1/' + window.SKOSMOS.vocab + '/groupMembers/?lang=' + window.SKOSMOS.content_lang + '&uri=' + group.uri)
            .then(data => {
              return data.json()
            })
            .then(data => {
              console.log('data', data)
              for (const m of data.members) {
                group.childGroups.push({ ...m, childGroups: [], isOpen: false, isGroup: false })
              }
              this.loadingChildren = this.loadingChildren.filter(x => x !== group)
              console.log('groups', this.groups)
            })
        }
      }
    },
    template: `
      <div v-click-tab-groups="handleClickGroupsEvent" v-resize-window="setListStyle">
        <div id="groups-list" class="sidebar-list p-0" :style="listStyle">
          <ul v-if="!loadingGroups" class="list-group">
            <tab-groups-wrapper
              :groups="groups"
              :selectedGroup="selectedGroup"
              :loadingChildren="loadingChildren"
              :openAriaMessage="openAriaMessage"
              :goToTheConceptPageAriaMessage="goToTheConceptPageAriaMessage"
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
      document.querySelector('#groups').addEventListener('click', el.clickTabEvent) // registering an event listener on clicks on the groups nav-item element
    },
    unmounted: el => {
      document.querySelector('#groups').removeEventListener('click', el.clickTabEvent)
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

  tabGroupsApp.component('tab-groups-wrapper', {
    props: ['groups', 'selectedGroup', 'loadingChildren', 'openAriaMessage', 'goToTheConceptPageAriaMessage'],
    emits: ['loadChildren', 'selectGroup'],
    mounted () {
    },
    methods: {
      loadChildren (group) {
        this.$emit('loadChildren', group)
      },
      selectGroup (group) {
        this.$emit('selectGroup', group)
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
          :openAriaMessage="openAriaMessage"
          :goToTheConceptPageAriaMessage="goToTheConceptPageAriaMessage"
          @load-children="loadChildren($event)"
          @select-group="selectGroup($event)"
        ></tab-groups>
      </template>
    `
  })

  tabGroupsApp.component('tab-groups', {
    props: ['group', 'selectedGroup', 'isTopGroup', 'isLast', 'loadingChildren', 'openAriaMessage', 'goToTheConceptPageAriaMessage'],
    emits: ['loadChildren', 'selectGroup'],
    inject: ['partialPageLoad', 'getConceptURL', 'showNotation'],
    methods: {
      handleClickOpenEvent (group) {
        group.isOpen = !group.isOpen
        this.$emit('loadChildren', group)
      },
      handleClickGroupEvent (event, group) {
        group.isOpen = true
        this.$emit('loadChildren', group)
        this.$emit('selectGroup', group.uri)
        this.partialPageLoad(event, this.getConceptURL(group.uri))
      },
      loadChildrenRecursive (group) {
        this.$emit('loadChildren', group)
      },
      selectGroupRecursive (group) {
        this.$emit('selectGroup', group)
      }
    },
    template: `
      <li class="list-group-item p-0" :class="{ 'top-concept': isTopGroup }">
        <button type="button" class="hierarchy-button btn btn-primary"
          :aria-label="openAriaMessage"
          :class="{ 'open': group.isOpen }"
          v-if="group.hasMembers"
          @click="handleClickOpenEvent(group)"
        >
          <template v-if="loadingChildren.includes(group)">
            <i class="fa-solid fa-spinner fa-spin-pulse"></i>
          </template>
          <template v-else>
            <img v-if="group.isOpen" alt="" src="resource/pics/black-lower-right-triangle.png">
            <img v-else alt="" src="resource/pics/lower-right-triangle.png">
          </template>
        </button>
        <span class="concept-label" :class="{ 'last': isLast }">
          <i v-if="group.isGroup" class="property-hover fa-solid fa-layer-group"></i>
          <a :class="{ 'selected': selectedGroup === group.uri }"
            :href="getConceptURL(group.uri)"
            @click="handleClickGroupEvent($event, group)"
            :aria-label="goToTheConceptPageAriaMessage"
          >
            <span v-if="showNotation && group.notation" class="concept-notation">{{ group.notation }} </span>
            {{ group.prefLabel }}
          </a>
        </span>
        <ul class="list-group ps-3" v-if="group.childGroups.length !== 0 && group.isOpen">
          <template v-for="(g, i) in group.childGroups">
            <tab-groups
              :group="g"
              :selectedGroup="selectedGroup"
              :isTopGroup="false"
              :isLast="i == group.childGroups.length - 1"
              :loadingChildren="loadingChildren"
              :openAriaMessage="openAriaMessage"
              :goToTheConceptPageAriaMessage="goToTheConceptPageAriaMessage"
              @load-children="loadChildrenRecursive($event)"
              @select-group="selectGroupRecursive($event)"
            ></tab-groups>
          </template>
        </ul>
      </li>
    `
  })

  tabGroupsApp.mount('#tab-groups')
}

onTranslationReady(startGroupsApp)
