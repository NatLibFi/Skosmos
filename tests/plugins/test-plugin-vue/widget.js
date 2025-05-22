var VUE_PLUGIN = VUE_PLUGIN || {}

VUE_PLUGIN = {
  vueApp: null,
  createVueApp: function() {
    return Vue.createApp({
      data() {
        return {
          message: 'Vue plugin'
        }
      },
      template: `<p id="vue-plugin-message">{{ message }}</p>`
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
    if (this.vueApp) {
      this.vueApp.unmount()
      this.vueApp = null
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
  window.vueCallback = function(params) {
    if (params.page == 'vocab-home') {
      VUE_PLUGIN.render()
    } else {
      VUE_PLUGIN.remove()
    }
  }
})
