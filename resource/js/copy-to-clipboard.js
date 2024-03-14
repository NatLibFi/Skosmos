// function for copying the content from a specific element (by id) to the clipboard
function copyToClipboard (id) {
  const copyElem = document.getElementById(id)
  const sel = window.getSelection()
  const range = document.createRange()
  range.selectNodeContents(copyElem)
  sel.removeAllRanges()
  sel.addRange(range)

  navigator.clipboard.writeText(copyElem.innerText).catch((err) =>
    console.error('Failed to copy text to clipboard: ', err))
}

function registerCopyToClipboardEvents () {
  const copyPrefElem = document.getElementById('copy-preflabel')
  if (copyPrefElem) {
    copyPrefElem.addEventListener('click', () => copyToClipboard('concept-preflabel'))
  }

  const copyNotationElem = document.getElementById('copy-notation')
  if (copyNotationElem) {
    copyNotationElem.addEventListener('click', () => copyToClipboard('concept-notation'))
  }

  const copyUriElem = document.getElementById('copy-uri')
  if (copyUriElem) {
    copyUriElem.addEventListener('click', () => copyToClipboard('concept-uri'))
  }
}

// register the copyToClipboard function as event an handler for the copy buttons
registerCopyToClipboardEvents()

// re-register the event handlers after partial page loads
document.addEventListener('loadConceptPage', registerCopyToClipboardEvents)
