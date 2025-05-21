var VUE_PLUGIN = VUE_PLUGIN || {}

VUE_PLUGIN = {
  vuePluginApp: Vue.createApp({
    data () {
      return {
        message: 'Vue plugin'
      }
    },
    template: `<p id="vue-plugin-message">{{ message }}</p>`
  }),
  render: function() {
    const newDiv = document.createElement("div")
    newDiv.id = "vue-plugin"

    document.getElementById("headerbar-bottom-slot").appendChild(newDiv)

    this.vuePluginApp.mount("#vue-plugin")
  }
}

document.addEventListener('DOMContentLoaded', function() {
  window.vueCallback = function(params) {
    if (params.page == 'landing') {
      VUE_PLUGIN.render()
    }
  }
})
