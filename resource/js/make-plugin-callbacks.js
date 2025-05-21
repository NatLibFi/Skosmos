const makeCallbacks = () => {
  const newPage = window.SKOSMOS.page || 'page'
  const newUri = window.SKOSMOS.uri
  const newPrefs = window.SKOSMOS.prefLabels
  const ldJsonScript = document.querySelector('script[type="application/ld+json"]')
  const embeddedJsonLd = ldJsonScript ? JSON.parse(ldJsonScript.innerHTML) : {}

  const params = { uri: newUri, prefLabels: newPrefs, page: newPage, jsonLd: embeddedJsonLd }

  if (window.pluginCallbacks) {
    for (const i in window.pluginCallbacks) {
      const fname = window.pluginCallbacks[i]
      const callback = window[fname]
      if (typeof callback === 'function') {
        callback(params)
      }
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  makeCallbacks()
})

document.addEventListener('loadConceptPage', () => {
  makeCallbacks()
})
