var VANILLA_JS_PLUGIN = VANILLA_JS_PLUGIN || {}

VANILLA_JS_PLUGIN = {
  message: "Vanilla JS plugin",
  template: function () {
    return `<p id="vanilla-js-plugin-message">${ this.message }</p>`
  },
  render: function() {
    const newDiv = document.createElement("div")
    newDiv.id = "vanilla-js-plugin"
    newDiv.innerHTML = this.template()

    document.getElementById("headerbar-bottom-slot").appendChild(newDiv)
  }
}

document.addEventListener('DOMContentLoaded', function() {
  window.vanillaJSCallback = function(params) {
    if (params.page == 'vocab-home') {
      VANILLA_JS_PLUGIN.render()
    }
  }
})
