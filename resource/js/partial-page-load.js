/* global $t, onTranslationReady */

const fetchWithAbort = (function () {
  const controllers = {}

  return function (url, category, options = {}) {
    // Abort the previous request in the same category if it exists
    if (controllers[category]) {
      controllers[category].abort()
    }

    // Create a new AbortController instance for this request
    controllers[category] = new AbortController()

    // Add the AbortController signal to the fetch options
    options.signal = controllers[category].signal

    // Perform the fetch request
    return fetch(url, options)
      .then(response => {
        // Remove the abort controller after the request is done
        delete controllers[category]
        return response
      })
  }
})()

const updateTopbarNav = (conceptHTML) => {
  // update topbar navigation with language links
  const conceptTopbarNav = conceptHTML.querySelector('#topbar-nav')
  const topbarNav = document.querySelector('#topbar-nav')
  topbarNav.innerHTML = conceptTopbarNav.innerHTML
}

const updateMainContent = (conceptHTML) => {
  // concept card
  const conceptMainContent = conceptHTML.querySelectorAll('#main-content > :not(#concept-mappings)') // all elements from concept card except concept mappings

  // emptying vocab info
  const mainContent = document.querySelector('#main-content')
  const toBeRemoved = document.querySelectorAll('#main-content > :not(#concept-mappings)') // all elements from vocab info except concept mappings
  for (const elem of toBeRemoved) {
    mainContent.removeChild(elem)
  }

  // inserting concept card into vocab info
  const fragment = document.createDocumentFragment()
  for (const elem of conceptMainContent) {
    fragment.appendChild(elem)
  }
  mainContent.prepend(fragment)
}

const updateTitle = (conceptHTML) => {
  document.title = conceptHTML.querySelector('title').innerHTML
}

const updateJsonLD = (conceptHTML) => {
  const JsonLD = document.querySelector('#json-ld-data')
  const newJsonLD = conceptHTML.querySelector('#json-ld-data')
  if (JsonLD) {
    JsonLD.innerHTML = '{}'
    if (newJsonLD) {
      JsonLD.innerHTML = newJsonLD.innerHTML
    }
  } else if (newJsonLD) {
    // insert after the first JS script as it is in the template
    const elemBefore = document.querySelector('script')
    if (elemBefore) {
      elemBefore.parentNode.insertBefore(newJsonLD, elemBefore.nextSibling)
    }
  }
}

const updateSKOSMOS = (conceptHTML) => {
  // new window.SKOSMOS object from concept page
  const skosmosScript = conceptHTML.querySelector('#skosmos-global-vars').innerHTML
  const skosmosObject = skosmosScript.slice(skosmosScript.indexOf('{'))
  const newSKOSMOS = JSON.parse(skosmosObject)

  // replacing all values in the old window.SKOSMOS object with new ones
  for (const i in newSKOSMOS) {
    window.SKOSMOS[i] = newSKOSMOS[i]
  }
}

const moveFocus = () => {
  // Move focus to prefLabel after page has been loaded
  document.getElementById('concept-preflabel').focus()
}

const partialPageLoad = (event, pageUri) => {
  event.type !== 'popstate' && event.preventDefault()

  // fetching html content of the concept page
  fetchWithAbort(pageUri, 'concept')
    .then(data => {
      return data.text()
    })
    .then(data => {
      // updating url and history when clicking on concept links
      if (event.type !== 'popstate' && window.history.pushState) { window.history.pushState({ url: pageUri }, '', pageUri) }

      // concept page HTML
      const conceptHTML = document.createElement('div')
      conceptHTML.innerHTML = data.trim()

      updateTopbarNav(conceptHTML)
      updateMainContent(conceptHTML)
      updateTitle(conceptHTML)
      updateJsonLD(conceptHTML)
      updateSKOSMOS(conceptHTML)
      moveFocus()

      // removing/adding disabled class from hierarchy tab
      if (document.querySelector('#hierarchy > a')) {
        if (window.SKOSMOS.isGroup) { // Add disabled class if opening a group page
          document.querySelector('#hierarchy').classList.add('disabled')
          document.querySelector('#hierarchy > a').classList.add('disabled')
          onTranslationReady(() => {
            // Prevent a race condition
            if (document.querySelector('#hierarchy').classList.contains('disabled')) {
              if (window.SKOSMOS.showTopConcepts) {
                document.querySelector('#hierarchy').dataset.title = $t('hierarchy-disabled-group-page-help')
              } else {
                document.querySelector('#hierarchy').dataset.title = $t('hierarchy-disabled-help')
              }
            }
          })
        } else { // Otherwise remove disabled class and tooltip text
          document.querySelector('#hierarchy').classList.remove('disabled')
          document.querySelector('#hierarchy > a').classList.remove('disabled')
          delete document.querySelector('#hierarchy').dataset.title
        }
      }

      // custom event to signal that a new concept page was loaded
      const e = new Event('loadConceptPage')
      document.dispatchEvent(e)
    })
    .catch(error => {
      if (error.name !== 'AbortError') {
        throw error
      }
    })
}

// Event listener for handling browser's back and forward navigation
window.addEventListener('popstate', (e) => {
  // Do a partial page load when moving to a concept page, otherwise load new page fully
  if (window.location.href.includes(`${window.SKOSMOS.vocab}/${window.SKOSMOS.lang}/page/`)) {
    if (e.state?.url) {
      partialPageLoad(e, e.state.url)
    } else {
      partialPageLoad(e, window.location.href)
    }
  } else {
    window.location.reload()
  }
})
