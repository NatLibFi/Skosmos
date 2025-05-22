const makeCallbacks = () => {
  const newPage = window.SKOSMOS.pageType
  const newUri = window.SKOSMOS.uri
  const newPrefs = window.SKOSMOS.prefLabels
  const ldJsonScript = document.querySelector('script[type="application/ld+json"]')
  const embeddedJsonLd = ldJsonScript ? JSON.parse(ldJsonScript.innerHTML) : {}

  const params = { uri: newUri, prefLabels: newPrefs, pageType: newPage, jsonLd: embeddedJsonLd }

  if (window.SKOSMOS.pluginCallbacks) {

    for (const i in window.SKOSMOS.pluginCallbacks) {
      const fname = window.SKOSMOS.pluginCallbacks[i]
      const callback = window[fname]
      if (typeof callback === 'function') {
        callback(params)
      }
    }
  }
}

// Make callbacks on page load
document.addEventListener('DOMContentLoaded', () => {
  makeCallbacks()
})

// Make callbacks on partial page load
document.addEventListener('loadConceptPage', () => {
  makeCallbacks()
})
