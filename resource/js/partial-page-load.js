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
  for (const elem of conceptMainContent) {
    mainContent.prepend(elem)
  }
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

/* eslint-disable no-unused-vars */
const partialPageLoad = (event, pageUri) => {
  event.preventDefault()

  // fetching html content of the concept page
  fetchWithAbort(pageUri, 'concept')
    .then(data => {
      return data.text()
    })
    .then(data => {
      // updating url and history
      if (window.history.pushState) { window.history.pushState({ url: pageUri }, '', pageUri) }

      // removing disabled class from hierarchy tab
      if (document.querySelector('#hierarchy > a')) { document.querySelector('#hierarchy > a').classList.remove('disabled') }

      // concept page HTML
      const conceptHTML = document.createElement('div')
      conceptHTML.innerHTML = data.trim()

      updateMainContent(conceptHTML)
      updateTitle(conceptHTML)
      updateJsonLD(conceptHTML)
      updateSKOSMOS(conceptHTML)

      // custom event to signal that a new concept page was loaded
      const event = new Event('loadConceptPage')
      document.dispatchEvent(event)
    })
    .catch(error => {
      if (error.name === 'AbortError') {
        console.log('Fetch aborted for ' + pageUri)
      } else {
        throw error
      }
    })
}
/* eslint-disable no-unused-vars */
