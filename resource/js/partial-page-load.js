/* global SKOSMOS */

const updateMainContent = (conceptHTML) => {
  // concept card
  const conceptMainContent = conceptHTML.querySelectorAll('#main-content > :not(#concept-mappings)') // all elements from concept card except concept mappings

  // emptying vocab info
  const mainContent = document.querySelector('#main-content')
  const toBeRemoved = document.querySelectorAll('#main-content > :not(#concept-mappings)') // all elements from vocab info except concept mappings
  for (let i = 0; i < toBeRemoved.length; i++) {
    mainContent.removeChild(toBeRemoved[i])
  }

  // inserting concept card into vocab info
  for (let i = 0; i < conceptMainContent.length; i++) {
    mainContent.prepend(conceptMainContent[i])
  }
}

const updateTitle = (conceptHTML) => {
  document.title = conceptHTML.querySelector('title').innerHTML
}

const updateJsonLD = (conceptHTML) => {
  const JsonLD = document.querySelector('script[type="application/ld+json"]')
  const newJsonLD = conceptHTML.querySelector('script[type="application/ld+json"]')
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
  // new SKOSMOS object from concept page
  const skosmosScript = conceptHTML.querySelector('#skosmos-global-vars').innerHTML
  const skosmosObject = skosmosScript.slice(skosmosScript.indexOf('{'))
  const newSKOSMOS = JSON.parse(skosmosObject)

  // replacing all values in the old SKOSMOS object with new ones
  for (const i in newSKOSMOS) {
    SKOSMOS[i] = newSKOSMOS[i]
  }
}

/* eslint-disable no-unused-vars */
const partialPageLoad = (event, pageUri) => {
  event.preventDefault()

  // fetching html content of the concept page
  fetch(pageUri)
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
}
/* eslint-disable no-unused-vars */
