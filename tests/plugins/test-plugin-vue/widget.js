var VUE_PLUGIN = VUE_PLUGIN || {}

VUE_PLUGIN = {
  vueApp: null,
  createVueApp: function() {
    return Vue.createApp({
      data() {
        return {
          uri: window.SKOSMOS.uri
        }
      },
      template: `<p id="vue-plugin-message">Current concept: {{ uri }}</p>`
    })
  },
  render: function() {
    const mountPoint = document.getElementById('vue-plugin')
    if (mountPoint) {
      // Unmount the Vue app if it exists
      if (this.vueApp) {
        this.vueApp.unmount()
      }
      // Remove the old mount point element
      mountPoint.remove()
    }

    // Create a new element for the Vue app
    const newMountPoint = document.createElement('div')
    newMountPoint.id = 'vue-plugin'
    document.getElementById('headerbar-bottom-slot').appendChild(newMountPoint)

    // Create a new Vue app instance and mount it to the new mount point
    this.vueApp = this.createVueApp()
    this.vueApp.mount('#vue-plugin')
  },
  remove: function() {
    // Unmount and remove old Vue app if it exists
    if (this.vueApp) {
      this.vueApp.unmount()
      this.vueApp = null
    }
    // Remove old mount point element if it exists
    const mountPoint = document.getElementById('vue-plugin')
    if (mountPoint) {
      mountPoint.remove()
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
  window.vueCallback = function(params) {
    if (params.pageType == 'concept') {
      VUE_PLUGIN.render()
    } else {
      VUE_PLUGIN.remove()
    }
  }
})
