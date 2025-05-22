var VANILLA_JS_PLUGIN = VANILLA_JS_PLUGIN || {}

VANILLA_JS_PLUGIN = {
  vocab: window.SKOSMOS.vocab,
  template: function () {
    return `<p id="vanilla-js-plugin-message">Current vocab: ${ this.vocab }</p>`
  },
  render: function() {
    // Check if the plugin already exists
    const existingPlugin = document.getElementById('vanilla-js-plugin')
    if (existingPlugin) {
      // Remove the existing plugin
      existingPlugin.remove()
    }

    // Create a new element for the plugin and add the template to it
    const newPlugin = document.createElement('div')
    newPlugin.id = 'vanilla-js-plugin'
    newPlugin.innerHTML = this.template()

    // Add the new plugin to the DOM
    document.getElementById('headerbar-bottom-slot').appendChild(newPlugin)
  },
  remove: function() {
    // Remove the plugin if it exists
    const existingPlugin = document.getElementById('vanilla-js-plugin')
    if (existingPlugin) {
      existingPlugin.remove()
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
  window.vanillaJSCallback = function(params) {
    if (params.pageType == 'vocab-home') {
      VANILLA_JS_PLUGIN.render()
    } else {
      VANILLA_JS_PLUGIN.remove()
    }
  }
})
