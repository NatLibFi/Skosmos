// function for copying the content from a specific element (by id) to the clipboard
async function copyToClipboard (id) {
  const copyElem = document.getElementById(id)
  const sel = window.getSelection()
  const range = document.createRange()
  range.selectNodeContents(copyElem)
  sel.removeAllRanges()
  sel.addRange(range)

  try {
    await navigator.clipboard.writeText(copyElem.innerText)
  } catch (err) {
    console.log('Failed to copy text to clipboard: ', err)
  }
}

// register the copyToClipboard function as event an handler for the copy buttons
const copyPrefElem = document.getElementById('copy-preflabel')
if (copyPrefElem) {
  copyPrefElem.addEventListener('click', () => {
    (async () => {
      await copyToClipboard('concept-preflabel')
    })()
  })
}

const copyUriElem = document.getElementById('copy-uri')
if (copyUriElem) {
  copyUriElem.addEventListener('click', () => {
    (async () => {
      await copyToClipboard('concept-preflabel')
    })()
  })
}
