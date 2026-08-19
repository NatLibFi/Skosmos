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

function registerCopyToClipboardEvent () {
  const mainElement = document.getElementById('main-container')
  mainElement.addEventListener('click', function (event) {
    const target = event.target.closest('.copy-clipboard')
    if (target) {
      event.preventDefault()
      const targetId = target.dataset.targetId
      if (targetId) {
        copyToClipboard(targetId)
      }
    }
  })
}

// register the copyToClipboard function as event an handler for the copy buttons
registerCopyToClipboardEvent()
